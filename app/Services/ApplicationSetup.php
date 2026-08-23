<?php

namespace App\Services;

use App\Enums\ApplicationMode;
use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationSetup
{
    private const int SETTING_ID = 1;

    public function settings(): ?ApplicationSetting
    {
        return ApplicationSetting::query()->find(self::SETTING_ID);
    }

    public function isInstalled(): bool
    {
        return $this->settings() !== null;
    }

    public function mode(): ?ApplicationMode
    {
        return $this->settings()?->mode;
    }

    public function authenticationEnabled(): bool
    {
        return $this->mode() === ApplicationMode::Authenticated;
    }

    public function installWithoutAuthentication(): void
    {
        $this->storeMode(ApplicationMode::Session);
    }

    public function installWithAuthentication(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password): User {
            $this->ensureNotInstalled();

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $password,
                'is_admin' => true,
            ]);

            $this->createSettings(ApplicationMode::Authenticated);

            return $user;
        });
    }

    private function storeMode(ApplicationMode $mode): void
    {
        DB::transaction(function () use ($mode): void {
            $this->ensureNotInstalled();
            $this->createSettings($mode);
        });
    }

    private function createSettings(ApplicationMode $mode): void
    {
        ApplicationSetting::query()->create([
            'id' => self::SETTING_ID,
            'mode' => $mode,
            'installed_at' => now(),
        ]);
    }

    private function ensureNotInstalled(): void
    {
        if ($this->isInstalled()) {
            throw ValidationException::withMessages([
                'installation' => 'WallAI has already been installed.',
            ]);
        }
    }
}
