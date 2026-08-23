<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $throttleKey = Str::lower($validated['email']).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => true,
        ], $validated['remember'])) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $this->redirectIntended(default: route('home'), navigate: false);
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-base-200 px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-7 flex justify-center"><x-logo /></div>

        <x-card title="Welcome back" subtitle="Sign in to your WallAI workspace." shadow separator>
            @if(session('status'))
                <x-alert icon="lucide.circle-alert" class="alert-warning mb-4" :title="session('status')" />
            @endif

            <x-form wire:submit="login">
                <x-input label="Email" wire:model="email" icon="lucide.mail" type="email" autocomplete="email" autofocus />
                <x-password label="Password" wire:model="password" autocomplete="current-password" right />
                <x-checkbox label="Keep me signed in" wire:model="remember" />

                <x-slot:actions>
                    <x-button type="submit" spinner="login" icon="lucide.log-in" class="btn-primary w-full" label="Sign in" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
