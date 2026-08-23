<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function acceptInvitation(): void
    {
        $validated = $this->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'same:passwordConfirmation', Password::defaults()],
            'passwordConfirmation' => ['required', 'string'],
        ]);

        $status = PasswordBroker::broker()->reset([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'password_confirmation' => $validated['passwordConfirmation'],
            'token' => $validated['token'],
        ], function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'email_verified_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
            Auth::login($user);
        });

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => trans($status),
            ]);
        }

        session()->regenerate();
        $this->redirectRoute('home', navigate: false);
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-base-200 px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-7 flex justify-center"><x-logo /></div>

        <x-card title="Accept your invitation" subtitle="Choose a password to activate your WallAI account." shadow separator>
            <x-form wire:submit="acceptInvitation">
                <x-input label="Email" wire:model="email" icon="lucide.mail" type="email" readonly />
                <x-password label="Password" wire:model="password" autocomplete="new-password" right />
                <x-password label="Confirm password" wire:model="passwordConfirmation" autocomplete="new-password" right />

                <x-slot:actions>
                    <x-button type="submit" spinner="acceptInvitation" icon="lucide.user-check" class="btn-primary w-full" label="Activate account" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
