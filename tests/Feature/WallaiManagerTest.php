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

    File::ensureDirectoryExists($this->fakeBinaryDirectory);
    File::put($this->fakeBinaryDirectory.'/docker', <<<'SHELL'
#!/bin/sh
if [ -n "${WALLAI_DOCKER_LOG:-}" ]; then
    printf '%s\n' "$*" >> "$WALLAI_DOCKER_LOG"
fi

if [ "${1:-}" = "compose" ] && [ "${2:-}" = "version" ] && [ "${3:-}" = "--short" ]; then
    echo '2.15.1'
fi

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
