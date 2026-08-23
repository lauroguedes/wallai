<?php

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $inviteName = '';

    public string $inviteEmail = '';

    public function mount(): void
    {
        $user = $this->currentUser();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        Gate::authorize('viewAny', User::class);

        return User::query()->orderBy('name')->get();
    }

    public function saveProfile(): void
    {
        $user = $this->currentUser();
        Gate::authorize('update', $user);
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->getKey()),
            ],
        ]);

        $user->update($validated);
        $this->success('Profile updated.');
    }

    public function updatePassword(): void
    {
        $user = $this->currentUser();
        Gate::authorize('update', $user);

        $validated = $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', 'same:passwordConfirmation', Password::defaults()],
            'passwordConfirmation' => ['required', 'string'],
        ]);

        $user->update(['password' => $validated['password']]);
        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->success('Password updated.');
    }

    public function inviteUser(): void
    {
        $admin = $this->currentUser();
        Gate::authorize('create', User::class);
        $this->inviteEmail = Str::lower(trim($this->inviteEmail));

        $validated = $this->validate([
            'inviteName' => ['required', 'string', 'max:255'],
            'inviteEmail' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        ]);

        DB::transaction(function () use ($admin, $validated): void {
            $user = User::query()->create([
                'name' => $validated['inviteName'],
                'email' => $validated['inviteEmail'],
                'password' => Str::random(64),
            ]);

            $token = PasswordBroker::broker()->createToken($user);
            $user->notify(new UserInvitation($token, $admin->name));
        });

        $this->reset('inviteName', 'inviteEmail');
        unset($this->users);
        $this->success('Invitation sent.');
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login', navigate: false);
    }

    private function currentUser(): User
    {
        return Auth::user() ?? throw new \Illuminate\Auth\AuthenticationException;
    }
};
?>

<div class="flex flex-col gap-6">
    <section class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="font-semibold">Your profile</h3>
                <p class="text-sm text-base-content/60">Signed in as {{ auth()->user()->email }}</p>
            </div>
            @if(auth()->user()->is_admin)
                <x-badge value="Admin" class="badge-primary badge-sm" />
            @endif
        </div>

        <x-form wire:submit="saveProfile" class="gap-3">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-input label="Name" wire:model="name" autocomplete="name" />
                <x-input label="Email" wire:model="email" type="email" autocomplete="email" />
            </div>
            <x-slot:actions>
                <x-button type="submit" spinner="saveProfile" icon="lucide.save" class="btn-primary btn-sm" label="Save profile" />
            </x-slot:actions>
        </x-form>
    </section>

    <section class="flex flex-col gap-4">
        <div>
            <h3 class="font-semibold">Password</h3>
            <p class="text-sm text-base-content/60">Use a unique password for this installation.</p>
        </div>

        <x-form wire:submit="updatePassword" class="gap-3">
            <x-password label="Current password" wire:model="currentPassword" autocomplete="current-password" right />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-password label="New password" wire:model="password" autocomplete="new-password" right />
                <x-password label="Confirm password" wire:model="passwordConfirmation" autocomplete="new-password" right />
            </div>
            <x-slot:actions>
                <x-button type="submit" spinner="updatePassword" icon="lucide.key-round" class="btn-soft btn-sm" label="Update password" />
            </x-slot:actions>
        </x-form>
    </section>

    @if(auth()->user()->is_admin)
        <section class="flex flex-col gap-4">
            <div>
                <h3 class="font-semibold">Users</h3>
                <p class="text-sm text-base-content/60">Invite people to create their own private WallAI workspace.</p>
            </div>

            <x-form wire:submit="inviteUser" class="gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-input label="Name" wire:model="inviteName" />
                    <x-input label="Email" wire:model="inviteEmail" type="email" />
                </div>
                <x-slot:actions>
                    <x-button type="submit" spinner="inviteUser" icon="lucide.send" class="btn-primary btn-sm" label="Send invitation" />
                </x-slot:actions>
            </x-form>

            <div class="overflow-hidden rounded-2xl border border-base-300">
                @foreach($this->users as $user)
                    <div class="flex items-center justify-between gap-3 border-b border-base-200 px-4 py-3 last:border-b-0" wire:key="user-{{ $user->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                            <p class="truncate text-xs text-base-content/55">{{ $user->email }}</p>
                        </div>
                        <x-badge
                            :value="$user->is_admin ? 'Admin' : ($user->email_verified_at ? 'Active' : 'Invited')"
                            class="{{ $user->is_admin ? 'badge-primary' : ($user->email_verified_at ? 'badge-success' : 'badge-ghost') }} badge-sm" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-button type="button" wire:click="logout" icon="lucide.log-out" class="btn-ghost justify-start text-error" label="Sign out" />
</div>
