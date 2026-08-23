<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('wallai:reset {--force : Skip the destructive confirmation prompt}')]
#[Description('Reset WallAI to its uninstalled factory state')]
class ResetWallai extends Command
{
    /** @var list<string> */
    private const array QUEUES = [
        'default',
        'notifications',
        'wallpapers-mobile',
        'wallpapers-desktop',
    ];

    /** @var list<string> */
    private const array DATABASE_TABLES = [
        'agent_conversation_messages',
        'agent_conversations',
        'wallpapers',
        'ai_provider_settings',
        'password_reset_tokens',
        'sessions',
        'users',
        'application_settings',
        'jobs',
        'job_batches',
        'failed_jobs',
        'cache',
        'cache_locks',
    ];

    /**
     * Execute the console command.
     */
    public function handle(QueueManager $queues, Filesystem $files): int
    {
        $this->newLine();
        $this->components->error('DANGER: This permanently resets WallAI to its factory state.');
        $this->components->warn('All users, invitations, sessions, generated images, provider settings, API keys, conversations, and queued jobs will be deleted.');
        $this->line('The database schema and application files will be preserved. The next web request will open the first-installation screen.');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Permanently delete all WallAI application data?', false)) {
            $this->components->info('Factory reset cancelled. No data was deleted.');

            return self::FAILURE;
        }

        $horizonPaused = false;

        try {
            $horizonPaused = $this->pauseHorizonWhenUsed();
            $this->clearQueuedJobs($queues);
            $this->clearDatabaseData();
            $this->clearStoredState($files);
        } catch (Throwable $exception) {
            $this->components->error("Factory reset failed: {$exception->getMessage()}");

            return self::FAILURE;
        } finally {
            if ($horizonPaused) {
                try {
                    Artisan::call('horizon:continue');
                } catch (Throwable) {
                    $this->components->warn('Horizon could not be resumed automatically. Run php artisan horizon:continue after checking Redis.');
                }
            }
        }

        $this->components->info('WallAI was reset successfully. Open the application to start a new installation.');

        return self::SUCCESS;
    }

    private function pauseHorizonWhenUsed(): bool
    {
        if (config('queue.default') !== 'redis') {
            return false;
        }

        Artisan::call('horizon:pause');

        return true;
    }

    private function clearQueuedJobs(QueueManager $queues): void
    {
        $connectionName = (string) config('queue.default');
        $connection = $queues->connection($connectionName);

        if (method_exists($connection, 'clear')) {
            foreach (self::QUEUES as $queue) {
                $connection->clear($queue);
            }
        }

        if ($connectionName === 'redis') {
            Artisan::call('horizon:forget', ['--all' => true]);
            Artisan::call('horizon:clear-metrics');
        }
    }

    private function clearDatabaseData(): void
    {
        $tables = collect(self::DATABASE_TABLES)
            ->push((string) config('session.table', 'sessions'))
            ->push((string) config('auth.passwords.users.table', 'password_reset_tokens'))
            ->unique()
            ->filter(fn (string $table): bool => Schema::hasTable($table));

        DB::transaction(function () use ($tables): void {
            foreach ($tables as $table) {
                DB::table($table)->delete();
            }
        });
    }

    private function clearStoredState(Filesystem $files): void
    {
        Cache::flush();
        if (! Storage::disk('public')->deleteDirectory('wallpapers')) {
            throw new \RuntimeException('Generated wallpaper files could not be deleted.');
        }

        if (config('session.driver') !== 'file') {
            return;
        }

        $sessionFiles = collect($files->files((string) config('session.files')))
            ->reject(fn (\SplFileInfo $file): bool => $file->getFilename() === '.gitignore')
            ->map(fn (\SplFileInfo $file): string => $file->getPathname())
            ->all();

        $files->delete($sessionFiles);
    }
}
