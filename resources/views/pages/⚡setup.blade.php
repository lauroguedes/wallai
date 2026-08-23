<?php

use App\Services\ApplicationSetup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component
{
    public bool $showAdminForm = false;

    public bool $showSessionInstallModal = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function chooseAuthentication(): void
    {
        $this->showAdminForm = true;
    }

    public function installWithoutAuthentication(ApplicationSetup $setup): void
    {
        $this->showSessionInstallModal = false;
        $setup->installWithoutAuthentication();
        session()->regenerate();

        $this->redirectRoute('home', navigate: false);
    }

    public function createAdmin(ApplicationSetup $setup): void
    {
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'same:passwordConfirmation', Password::defaults()],
            'passwordConfirmation' => ['required', 'string'],
        ]);

        $user = $setup->installWithAuthentication(
            $validated['name'],
            $validated['email'],
            $validated['password'],
        );

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('home', navigate: false);
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-base-200 px-4 py-10">
    <div class="w-full max-w-3xl">
        <div class="mb-8 flex flex-col items-center gap-3 text-center">
            <x-logo />
            <div>
                <h1 class="text-3xl font-bold">Set up your WallAI installation</h1>
                <p class="mt-2 text-base-content/65">Choose how people will access this self-hosted instance.</p>
            </div>
        </div>

        @if(! $showAdminForm)
            <div class="grid gap-4 md:grid-cols-2">
                <button
                    type="button"
                    wire:click="chooseAuthentication"
                    wire:key="authenticated-install-choice-v2"
                    class="group rounded-3xl border border-primary/40 bg-base-100 p-6 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-primary hover:shadow-md">
                    <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <x-icon name="lucide.shield-check" class="size-6" />
                    </div>
                    <h2 class="text-xl font-semibold">Use authentication</h2>
                    <p class="mt-2 text-sm leading-6 text-base-content/65">Accounts, private provider keys, persistent image libraries, and admin-managed invitations.</p>
                    <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                        Recommended <x-icon name="lucide.arrow-right" class="size-4 transition group-hover:translate-x-1" />
                    </span>
                </button>

                <button
                    type="button"
                    x-on:click="$wire.showSessionInstallModal = true"
                    wire:key="session-install-choice-v2"
                    class="group rounded-3xl border border-base-300 bg-base-100 p-6 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-base-content/30 hover:shadow-md">
                    <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-base-200 text-base-content/70">
                        <x-icon name="lucide.monitor-smartphone" class="size-6" />
                    </div>
                    <h2 class="text-xl font-semibold">Use browser sessions</h2>
                    <p class="mt-2 text-sm leading-6 text-base-content/65">No login screen. Settings and images remain isolated to each browser session, as WallAI works today.</p>
                    <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-base-content/70">
                        Continue without accounts <x-icon name="lucide.arrow-right" class="size-4 transition group-hover:translate-x-1" />
                    </span>
                </button>
            </div>
        @else
            <x-card title="Create the administrator" subtitle="This first account controls invitations and user access." shadow separator class="mx-auto max-w-xl">
                <x-form wire:submit="createAdmin">
                    <x-input label="Name" wire:model="name" icon="lucide.user" autocomplete="name" />
                    <x-input label="Email" wire:model="email" icon="lucide.mail" type="email" autocomplete="email" />
                    <x-password label="Password" wire:model="password" autocomplete="new-password" right />
                    <x-password label="Confirm password" wire:model="passwordConfirmation" autocomplete="new-password" right />

                    <x-slot:actions>
                        <x-button type="button" wire:click="$set('showAdminForm', false)" class="btn-ghost" label="Back" />
                        <x-button type="submit" spinner="createAdmin" icon="lucide.shield-check" class="btn-primary" label="Create admin and continue" />
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif
    </div>

    @teleport('body')
        <x-modal
            wire:model="showSessionInstallModal"
            title="Install without authentication?"
            subtitle="WallAI will use browser sessions instead of user accounts."
            separator
            box-class="max-w-lg">
            <div class="flex flex-col gap-4">
                <x-alert
                    icon="lucide.triangle-alert"
                    class="alert-warning"
                    title="This mode cannot be changed from the interface"
                    description="To choose authentication later, run php artisan wallai:reset. That command permanently deletes all users, sessions, generated images, provider settings, and queued jobs." />
                <p class="text-sm text-base-content/70">Each browser session will keep its own settings and generated images without a login.</p>
            </div>

            <x-slot:actions>
                <x-button
                    type="button"
                    wire:click="$set('showSessionInstallModal', false)"
                    class="btn-ghost"
                    label="Cancel" />
                <x-button
                    type="button"
                    wire:click="installWithoutAuthentication"
                    spinner="installWithoutAuthentication"
                    icon="lucide.monitor-smartphone"
                    class="btn-warning"
                    label="Install without authentication" />
            </x-slot:actions>
        </x-modal>
    @endteleport
</div>
