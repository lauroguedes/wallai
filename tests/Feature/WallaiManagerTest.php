<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->temporaryDirectory = sys_get_temp_dir().'/wallai-manager-'.Str::lower((string) Str::ulid());
    $this->fakeBinaryDirectory = $this->temporaryDirectory.'/bin';
    $this->environmentFile = $this->temporaryDirectory.'/.env';
    $this->secretsDirectory = $this->temporaryDirectory.'/.secrets';
    $this->dockerLog = $this->temporaryDirectory.'/docker.log';
    $this->dockerStdinLog = $this->temporaryDirectory.'/docker-stdin.log';
    $this->isolatedProjectDirectory = $this->temporaryDirectory.'/project';

    File::ensureDirectoryExists($this->fakeBinaryDirectory);
    File::ensureDirectoryExists($this->isolatedProjectDirectory.'/bin');
    File::copy(base_path('bin/wallai'), $this->isolatedProjectDirectory.'/bin/wallai');
    File::copy(base_path('.env.docker.example'), $this->isolatedProjectDirectory.'/.env.docker.example');
    chmod($this->isolatedProjectDirectory.'/bin/wallai', 0755);
    File::put($this->fakeBinaryDirectory.'/docker', <<<'SHELL'
#!/bin/sh
if [ -n "${WALLAI_DOCKER_LOG:-}" ]; then
    printf '%s\n' "$*" >> "$WALLAI_DOCKER_LOG"
fi

if [ "${1:-}" = "compose" ] && [ "${2:-}" = "version" ] && [ "${3:-}" = "--short" ]; then
    echo '2.15.1'
fi

case "$*" in
    *'tar -xzf - -C /'*) cat > "${WALLAI_DOCKER_STDIN_LOG:-/dev/null}" ;;
esac

exit 0
SHELL);
    chmod($this->fakeBinaryDirectory.'/docker', 0755);
});

afterEach(function () {
    File::deleteDirectory($this->temporaryDirectory);
});

it('creates deployment configuration and secrets only during installation', function () {
    $install = new Process(
        [base_path('bin/wallai'), 'install'],
        base_path(),
        [
            'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
            'WALLAI_ENV_FILE' => $this->environmentFile,
            'WALLAI_SECRETS_DIR' => $this->secretsDirectory,
        ],
    );
    $install->run();

    expect($install->isSuccessful())->toBeTrue()
        ->and(File::exists($this->environmentFile))->toBeTrue()
        ->and(File::get($this->secretsDirectory.'/app_key'))->toStartWith('base64:')
        ->and(File::size($this->secretsDirectory.'/redis_password'))->toBeGreaterThan(32);

    File::delete($this->secretsDirectory.'/app_key');

    $start = new Process(
        [base_path('bin/wallai'), 'up'],
        base_path(),
        [
            'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
            'WALLAI_ENV_FILE' => $this->environmentFile,
            'WALLAI_SECRETS_DIR' => $this->secretsDirectory,
        ],
    );
    $start->run();

    expect($start->isSuccessful())->toBeFalse()
        ->and($start->getErrorOutput())->toContain('Required secret is missing')
        ->and(File::exists($this->secretsDirectory.'/app_key'))->toBeFalse();
});

it('refuses to start before WallAI has been configured', function () {
    $start = new Process(
        [base_path('bin/wallai'), 'up'],
        base_path(),
        [
            'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
            'WALLAI_ENV_FILE' => $this->environmentFile,
            'WALLAI_SECRETS_DIR' => $this->secretsDirectory,
        ],
    );
    $start->run();

    expect($start->isSuccessful())->toBeFalse()
        ->and($start->getErrorOutput())->toContain('WallAI is not configured');
});

it('passes the configured project name explicitly to compose', function () {
    $install = new Process(
        [base_path('bin/wallai'), 'install'],
        base_path(),
        [
            'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
            'WALLAI_DOCKER_LOG' => $this->dockerLog,
            'WALLAI_ENV_FILE' => $this->environmentFile,
            'WALLAI_SECRETS_DIR' => $this->secretsDirectory,
            'WALLAI_PROJECT_NAME' => 'wallai-test',
        ],
    );
    $install->run();

    expect($install->isSuccessful())->toBeTrue()
        ->and(File::get($this->dockerLog))->toContain('--project-name wallai-test');
});

it('configures and remembers a local source build', function () {
    File::put($this->isolatedProjectDirectory.'/.env', "WALLAI_PROJECT_NAME=native\n");

    $processEnvironment = [
        'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
        'WALLAI_DOCKER_LOG' => $this->dockerLog,
    ];

    $install = new Process(
        [$this->isolatedProjectDirectory.'/bin/wallai', 'install', '--local'],
        $this->isolatedProjectDirectory,
        $processEnvironment,
    );
    $install->run();

    $environmentFile = $this->isolatedProjectDirectory.'/.env.docker';
    $environment = File::get($environmentFile);

    expect($install->isSuccessful())->toBeTrue()
        ->and($install->getOutput())->toContain('http://wallai.localhost:8080')
        ->and($environment)->toContain('APP_URL=http://wallai.localhost:8080')
        ->toContain('APP_ENV=local')
        ->toContain('APP_DEBUG=true')
        ->toContain('WALLAI_BUILD_LOCAL=true')
        ->toContain('WALLAI_IMAGE=wallai')
        ->toContain('WALLAI_VERSION=local')
        ->toContain('WALLAI_PROJECT_NAME=wallai-local')
        ->toContain('WALLAI_DOMAIN=')
        ->toContain('SESSION_SECURE_COOKIE=false')
        ->toContain('TRUSTED_HOSTS=wallai.localhost,localhost,127.0.0.1')
        ->and(File::get($this->dockerLog))->toContain('compose.build.yaml');

    File::put($this->dockerLog, '');

    $doctor = new Process(
        [$this->isolatedProjectDirectory.'/bin/wallai', 'doctor'],
        $this->isolatedProjectDirectory,
        $processEnvironment,
    );
    $doctor->run();

    expect($doctor->isSuccessful())->toBeTrue()
        ->and(File::get($this->dockerLog))->toContain('--env-file '.$environmentFile)
        ->toContain('compose.build.yaml')
        ->toContain('wallai:doctor --runtime')
        ->not->toContain('wallai:doctor --deployment');
});

it('configures a production domain from install options', function () {
    $environmentFile = $this->isolatedProjectDirectory.'/production.env';
    $install = new Process(
        [
            $this->isolatedProjectDirectory.'/bin/wallai',
            'install',
            '--domain',
            'wallai.example.com',
            '--version',
            '2.1.0',
            '--port',
            '9090',
            '--project-name',
            'wallai-prod',
            '--env-file',
            $environmentFile,
        ],
        $this->isolatedProjectDirectory,
        [
            'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
            'WALLAI_DOCKER_LOG' => $this->dockerLog,
        ],
    );
    $install->run();

    $environment = File::get($environmentFile);

    expect($install->isSuccessful())->toBeTrue()
        ->and($install->getOutput())->toContain('https://wallai.example.com')
        ->and($environment)->toContain('APP_URL=https://wallai.example.com')
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('WALLAI_DOMAIN=wallai.example.com')
        ->toContain('SESSION_SECURE_COOKIE=true')
        ->toContain('TRUSTED_HOSTS=wallai.example.com')
        ->toContain('WALLAI_VERSION=2.1.0')
        ->toContain('WALLAI_PORT=9090')
        ->toContain('WALLAI_PROJECT_NAME=wallai-prod')
        ->and(File::get($this->dockerLog))->not->toContain('compose.build.yaml');

    File::put($this->dockerLog, '');

    $doctor = new Process(
        [$this->isolatedProjectDirectory.'/bin/wallai', 'doctor'],
        $this->isolatedProjectDirectory,
        [
            'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
            'WALLAI_DOCKER_LOG' => $this->dockerLog,
            'WALLAI_ENV_FILE' => $environmentFile,
        ],
    );
    $doctor->run();

    expect($doctor->isSuccessful())->toBeTrue()
        ->and(File::get($this->dockerLog))->toContain('wallai:doctor --deployment --runtime');
});

it('rejects conflicting local and production install modes', function () {
    $install = new Process(
        [
            $this->isolatedProjectDirectory.'/bin/wallai',
            'install',
            '--local',
            '--domain',
            'wallai.example.com',
        ],
        $this->isolatedProjectDirectory,
        ['PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH')],
    );
    $install->run();

    expect($install->isSuccessful())->toBeFalse()
        ->and($install->getErrorOutput())->toContain('--local cannot be combined with --domain');
});

it('keeps the direct port on loopback when public HTTPS is enabled', function () {
    $install = new Process(
        [
            $this->isolatedProjectDirectory.'/bin/wallai',
            'install',
            '--domain',
            'wallai.example.com',
            '--bind-address',
            '0.0.0.0',
        ],
        $this->isolatedProjectDirectory,
        ['PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH')],
    );
    $install->run();

    expect($install->isSuccessful())->toBeFalse()
        ->and($install->getErrorOutput())->toContain('--bind-address must remain loopback');
});

it('stops application services before recreating them during an update', function () {
    $processEnvironment = [
        'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
        'TMPDIR' => $this->temporaryDirectory.'/tmp',
        'WALLAI_BACKUP_DIR' => $this->temporaryDirectory.'/backups',
        'WALLAI_DOCKER_LOG' => $this->dockerLog,
        'WALLAI_ENV_FILE' => $this->environmentFile,
        'WALLAI_SECRETS_DIR' => $this->secretsDirectory,
    ];

    File::ensureDirectoryExists($processEnvironment['TMPDIR']);

    $install = new Process([base_path('bin/wallai'), 'install'], base_path(), $processEnvironment);
    $install->run();
    File::put($this->dockerLog, '');

    $update = new Process([base_path('bin/wallai'), 'update'], base_path(), $processEnvironment);
    $update->run();

    $dockerCalls = File::get($this->dockerLog);

    expect($update->isSuccessful())->toBeTrue()
        ->and(substr_count($dockerCalls, 'stop web horizon scheduler'))->toBe(2)
        ->and($dockerCalls)->toContain('up -d --force-recreate --wait')
        ->and(File::directories($processEnvironment['TMPDIR']))->toBeEmpty();
});

it('streams backup data into the non-root restore container', function () {
    $backupContents = $this->temporaryDirectory.'/backup-contents';
    $backupFile = $this->temporaryDirectory.'/wallai-backup.tar.gz';

    File::ensureDirectoryExists($backupContents.'/secrets');
    File::put($backupContents.'/data.tar.gz', 'streamed backup data');
    File::put($backupContents.'/environment', 'APP_ENV=production');
    File::put($backupContents.'/secrets/app_key', 'base64:test-key');
    File::put($backupContents.'/secrets/redis_password', 'test-password');

    (new Process(['tar', '-czf', $backupFile, '-C', $backupContents, '.']))->mustRun();

    $restore = new Process(
        [base_path('bin/wallai'), 'restore', $backupFile, '--force'],
        base_path(),
        [
            'PATH' => $this->fakeBinaryDirectory.':'.getenv('PATH'),
            'TMPDIR' => $this->temporaryDirectory.'/tmp',
            'WALLAI_DOCKER_LOG' => $this->dockerLog,
            'WALLAI_DOCKER_STDIN_LOG' => $this->dockerStdinLog,
            'WALLAI_ENV_FILE' => $this->environmentFile,
            'WALLAI_SECRETS_DIR' => $this->secretsDirectory,
        ],
    );
    File::ensureDirectoryExists($this->temporaryDirectory.'/tmp');
    $restore->run();

    expect($restore->isSuccessful())->toBeTrue()
        ->and(File::get($this->dockerStdinLog))->toBe('streamed backup data')
        ->and(File::get($this->dockerLog))->toContain('tar -xzf - -C /')
        ->toContain('--user 33:33')
        ->not->toContain('--volume');
});

it('does not bootstrap Laravel for maintenance commands', function () {
    $maintenanceCommand = new Process(
        ['sh', base_path('docker/entrypoint.sh'), 'printf', 'maintenance-command'],
        base_path(),
        [
            'APP_KEY' => 'base64:test-key',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $this->temporaryDirectory.'/database.sqlite',
            'WALLAI_OPTIMIZE' => 'true',
        ],
    );
    $maintenanceCommand->run();

    expect($maintenanceCommand->isSuccessful())->toBeTrue()
        ->and($maintenanceCommand->getOutput())->toBe('maintenance-command');
});
