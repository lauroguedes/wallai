<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ApplicationHealth
{
    public function __construct(private Migrator $migrator) {}

    /**
     * @return array<string, array{healthy: bool, message: string}>
     */
    public function checks(): array
    {
        $checks = [
            'database' => fn (): string => $this->checkDatabase(),
            'migrations' => fn (): string => $this->checkMigrations(),
            'storage' => fn (): string => $this->checkStorage(),
        ];

        if ($this->usesRedis()) {
            $checks['redis'] = fn (): string => $this->checkRedis();
        }

        return collect($checks)
            ->map(fn (Closure $check): array => $this->runCheck($check))
            ->all();
    }

    private function checkDatabase(): string
    {
        DB::connection()->getPdo();

        return 'Database connection is available.';
    }

    private function checkMigrations(): string
    {
        if (! $this->migrator->repositoryExists()) {
            throw new \RuntimeException('The migration repository does not exist.');
        }

        $migrationFiles = $this->migrator->getMigrationFiles(database_path('migrations'));
        $pendingMigrations = array_diff(
            array_keys($migrationFiles),
            $this->migrator->getRepository()->getRan(),
        );

        if ($pendingMigrations !== []) {
            throw new \RuntimeException('Database migrations are pending.');
        }

        return 'Database migrations are current.';
    }

    private function checkStorage(): string
    {
        File::ensureDirectoryExists(storage_path('app/public/wallpapers'));

        $writableDirectories = [
            storage_path('app/public/wallpapers'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($writableDirectories as $directory) {
            if (! is_dir($directory) || ! is_writable($directory)) {
                throw new \RuntimeException("Required directory is not writable: {$directory}");
            }
        }

        return 'Application storage is writable.';
    }

    private function checkRedis(): string
    {
        $response = Redis::connection()->command('ping');

        if (! in_array($response, [true, 'PONG', '+PONG'], true)) {
            throw new \RuntimeException('Redis did not return a valid PONG response.');
        }

        return 'Redis connection is available.';
    }

    private function usesRedis(): bool
    {
        return config('queue.default') === 'redis'
            || config('cache.default') === 'redis'
            || config('session.driver') === 'redis';
    }

    /**
     * @return array{healthy: bool, message: string}
     */
    private function runCheck(Closure $check): array
    {
        try {
            return [
                'healthy' => true,
                'message' => $check(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'healthy' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
