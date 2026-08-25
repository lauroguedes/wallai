<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWallpaper;
use App\Services\ApplicationHealth;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

#[Signature('wallai:doctor
    {--deployment : Enforce the production self-hosting requirements}
    {--runtime : Check long-running service heartbeats}')]
#[Description('Check WallAI configuration, persistence, database, and queue readiness')]
class WallaiDoctor extends Command
{
    public function handle(ApplicationHealth $health): int
    {
        $checks = $health->checks();

        foreach ($this->configurationChecks() as $name => $check) {
            $checks[$name] = $check;
        }

        $this->components->info(sprintf(
            'WallAI %s · PHP %s · Laravel %s',
            config('app.version'),
            PHP_VERSION,
            app()->version(),
        ));

        $this->table(
            ['Check', 'Status', 'Details'],
            collect($checks)->map(
                fn (array $check, string $name): array => [
                    $name,
                    $check['healthy'] ? 'PASS' : 'FAIL',
                    $check['message'],
                ],
            )->values()->all(),
        );

        if (collect($checks)->contains(fn (array $check): bool => ! $check['healthy'])) {
            $this->components->error('WallAI is not ready. Resolve the failed checks and run the command again.');

            return self::FAILURE;
        }

        $this->components->info('WallAI is ready.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{healthy: bool, message: string}>
     */
    private function configurationChecks(): array
    {
        $checks = [
            'app_key' => $this->result(
                $this->appKeyIsValid(),
                'Application encryption key is valid.',
                'APP_KEY is missing or invalid.',
            ),
            'app_url' => $this->result(
                filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false,
                'Application URL is valid.',
                'APP_URL must be an absolute URL.',
            ),
            'queue_timeouts' => $this->result(
                $this->queueTimeoutsAreSafe(),
                'Queue timeout ordering is safe.',
                'Expected wallpaper job timeout < Horizon timeout < Redis retry_after.',
            ),
        ];

        if ($this->option('deployment')) {
            $checks['environment'] = $this->result(
                app()->isProduction(),
                'Application environment is production.',
                'APP_ENV must be production.',
            );
            $checks['debug'] = $this->result(
                config('app.debug') === false,
                'Debug mode is disabled.',
                'APP_DEBUG must be false.',
            );
            $checks['queue'] = $this->result(
                config('queue.default') === 'redis',
                'Redis is the active queue connection.',
                'QUEUE_CONNECTION must be redis for Horizon.',
            );

            $appUrlUsesHttps = parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https';
            $checks['secure_cookies'] = $this->result(
                ! $appUrlUsesHttps || config('session.secure') === true,
                'Session cookie security matches the application URL.',
                'SESSION_SECURE_COOKIE must be true when APP_URL uses HTTPS.',
            );

            if (filled(config('app.self_hosted_domain'))) {
                $publicDomain = (string) config('app.self_hosted_domain');
                $checks['https_domain'] = $this->result(
                    $appUrlUsesHttps
                        && Str::lower((string) parse_url((string) config('app.url'), PHP_URL_HOST)) === Str::lower($publicDomain),
                    'Bundled HTTPS domain matches the application URL.',
                    'APP_URL must use https:// and match WALLAI_DOMAIN.',
                );
                $checks['trusted_hosts'] = $this->result(
                    collect(config('app.trusted_hosts'))->contains(
                        fn (string $trustedHost): bool => Str::is($trustedHost, $publicDomain),
                    ),
                    'Trusted hosts are restricted.',
                    'TRUSTED_HOSTS must include WALLAI_DOMAIN.',
                );
            }
        }

        if ($this->option('runtime')) {
            $heartbeat = (int) Cache::get('wallai:scheduler-heartbeat', 0);
            $checks['scheduler'] = $this->result(
                $heartbeat >= now()->subMinutes(2)->timestamp,
                'Scheduler heartbeat is current.',
                'Scheduler heartbeat is missing or stale.',
            );
        }

        return $checks;
    }

    private function queueTimeoutsAreSafe(): bool
    {
        $jobTimeout = GenerateWallpaper::TIMEOUT;
        $horizonTimeout = (int) config('horizon.defaults.supervisor-wallpapers.timeout');
        $retryAfter = (int) config('queue.connections.redis.retry_after');

        return $jobTimeout < $horizonTimeout && $horizonTimeout < $retryAfter;
    }

    private function appKeyIsValid(): bool
    {
        try {
            $encryptedValue = Crypt::encryptString('wallai-health-check');

            return Crypt::decryptString($encryptedValue) === 'wallai-health-check';
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{healthy: bool, message: string}
     */
    private function result(bool $healthy, string $success, string $failure): array
    {
        return [
            'healthy' => $healthy,
            'message' => $healthy ? $success : $failure,
        ];
    }
}
