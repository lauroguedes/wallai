<?php

namespace App\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WorkspaceContext
{
    public function __construct(private ApplicationSetup $setup) {}

    public function key(): string
    {
        if (! $this->setup->authenticationEnabled()) {
            return session()->getId();
        }

        $userId = Auth::id();

        if ($userId === null) {
            throw new AuthenticationException;
        }

        return "user:{$userId}";
    }

    /**
     * @return array{user_id: int|null, session_id: string|null}
     */
    public function ownerAttributes(string $workspaceKey): array
    {
        if (str_starts_with($workspaceKey, 'user:')) {
            return [
                'user_id' => (int) Str::after($workspaceKey, 'user:'),
                'session_id' => null,
            ];
        }

        return [
            'user_id' => null,
            'session_id' => $workspaceKey,
        ];
    }

    public function storageDirectory(string $workspaceKey): string
    {
        if (str_starts_with($workspaceKey, 'user:')) {
            return 'wallpapers/users/'.Str::after($workspaceKey, 'user:');
        }

        return "wallpapers/{$workspaceKey}";
    }
}
