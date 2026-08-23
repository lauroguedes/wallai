<?php

use App\Models\User;
use App\Notifications\UserInvitation;
use App\Services\WallpaperService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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

    public bool $showUserConfirmationModal = false;

    public ?int $pendingUserId = null;

    public string $pendingUserAction = '';

    public string $pendingUserName = '';

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
                'is_active' => false,
            ]);

            $this->sendInvitation($user, $admin);
        });

        $this->reset('inviteName', 'inviteEmail');
        $this->refreshUsers();
        $this->success('Invitation sent.');
    }

    public function activateUser(int $userId): void
    {
        $user = $this->managedUser($userId);
        Gate::authorize('manageAccess', $user);

        $user->update(['is_active' => true]);
        $this->refreshUsers();
        $this->success("{$user->name} can now sign in.");
    }

    public function deactivateUser(int $userId): void
    {
        $user = $this->managedUser($userId);
        Gate::authorize('manageAccess', $user);

        $user->update(['is_active' => false]);
        $this->refreshUsers();
        $this->success("{$user->name} has been deactivated.");
    }

    public function requestUserAction(int $userId, string $action): void
    {
        if (! in_array($action, ['deactivate', 'delete'], true)) {
            throw ValidationException::withMessages([
                'userAction' => 'The selected user action is invalid.',
            ]);
        }

        $user = $this->managedUser($userId);
        Gate::authorize($action === 'delete' ? 'delete' : 'manageAccess', $user);

        $this->pendingUserId = $user->getKey();
        $this->pendingUserAction = $action;
        $this->pendingUserName = $user->name;
        $this->showUserConfirmationModal = true;
    }

    public function confirmUserAction(WallpaperService $wallpapers): void
    {
        if (! $this->showUserConfirmationModal || $this->pendingUserId === null) {
            throw ValidationException::withMessages([
                'userAction' => 'Select a user before confirming this action.',
            ]);
        }

        match ($this->pendingUserAction) {
            'deactivate' => $this->deactivateUser($this->pendingUserId),
            'delete' => $this->deleteUser($this->pendingUserId, $wallpapers),
            default => throw ValidationException::withMessages([
                'userAction' => 'The selected user action is invalid.',
            ]),
        };

        $this->resetUserConfirmation();
    }

    public function cancelUserAction(): void
    {
        $this->resetUserConfirmation();
    }

    public function resendInvitation(int $userId): void
    {
        $admin = $this->currentUser();
        $user = $this->managedUser($userId);
        Gate::authorize('resendInvitation', $user);

        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'invitation' => 'Only pending invitations can be resent.',
            ]);
        }

        $this->sendInvitation($user, $admin);
        $this->success("Invitation resent to {$user->email}.");
    }

    public function deleteUser(int $userId, WallpaperService $wallpapers): void
    {
        $user = $this->managedUser($userId);
        Gate::authorize('delete', $user);

        $name = $user->name;
        PasswordBroker::broker()->deleteToken($user);
        $wallpapers->deleteUserWorkspace($user);
        $this->refreshUsers();
        $this->success("{$name} and their workspace were deleted.");
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

    private function managedUser(int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    private function sendInvitation(User $user, User $admin): void
    {
        $token = PasswordBroker::broker()->createToken($user);
        $user->notify(new UserInvitation($token, $admin->name));
    }

    private function refreshUsers(): void
    {
        unset($this->users);
    }

    private function resetUserConfirmation(): void
    {
        $this->reset(
            'showUserConfirmationModal',
            'pendingUserId',
            'pendingUserAction',
            'pendingUserName',
        );
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
                    <div class="flex flex-col gap-3 border-b border-base-200 px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between" wire:key="user-{{ $user->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                            <p class="truncate text-xs text-base-content/55">{{ $user->email }}</p>
                        </div>

                        <div class="flex items-center justify-between gap-2 sm:justify-end">
                            <x-badge
                                :value="$user->is_admin ? 'Admin' : ($user->email_verified_at === null ? 'Invited' : ($user->is_active ? 'Active' : 'Inactive'))"
                                class="{{ $user->is_admin ? 'badge-primary' : ($user->email_verified_at === null ? 'badge-ghost' : ($user->is_active ? 'badge-success' : 'badge-warning')) }} badge-sm" />

                            @if(! $user->is_admin && ! auth()->user()->is($user))
                                <div class="flex items-center gap-1">
                                    @if($user->email_verified_at === null)
                                        <x-button
                                            type="button"
                                            wire:click="resendInvitation({{ $user->id }})"
                                            icon="lucide.mail-plus"
                                            class="btn-ghost btn-sm btn-square"
                                            tooltip-left="Resend invitation"
                                            aria-label="Resend invitation to {{ $user->name }}" />
                                    @elseif($user->is_active)
                                        <x-button
                                            type="button"
                                            wire:click="requestUserAction({{ $user->id }}, 'deactivate')"
                                            icon="lucide.user-x"
                                            class="btn-ghost btn-sm btn-square"
                                            tooltip-left="Deactivate user"
                                            aria-label="Deactivate {{ $user->name }}" />
                                    @else
                                        <x-button
                                            type="button"
                                            wire:click="activateUser({{ $user->id }})"
                                            icon="lucide.user-check"
                                            class="btn-ghost btn-sm btn-square text-success"
                                            tooltip-left="Activate user"
                                            aria-label="Activate {{ $user->name }}" />
                                    @endif

                                    <x-button
                                        type="button"
                                        wire:click="requestUserAction({{ $user->id }}, 'delete')"
                                        icon="lucide.trash-2"
                                        class="btn-ghost btn-sm btn-square text-error"
                                        tooltip-left="Delete user"
                                        aria-label="Delete {{ $user->name }}" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-button type="button" wire:click="logout" icon="lucide.log-out" class="btn-ghost justify-start text-error" label="Sign out" />

    @teleport('body')
        <x-modal
            wire:model="showUserConfirmationModal"
            title="{{ $pendingUserAction === 'delete' ? 'Delete user?' : 'Deactivate user?' }}"
            subtitle="Review the consequences before continuing."
            separator
            box-class="max-w-lg">
            <div class="flex flex-col gap-4">
                <x-alert
                    icon="lucide.triangle-alert"
                    class="alert-warning"
                    title="This action affects {{ $pendingUserName }}"
                    description="{{ $pendingUserAction === 'delete' ? 'Their account, provider settings, and generated images will be permanently deleted. This cannot be undone.' : 'They will be signed out and unable to access WallAI until an administrator activates them again.' }}" />
            </div>

            <x-slot:actions>
                <x-button
                    type="button"
                    wire:click="cancelUserAction"
                    class="btn-ghost"
                    label="Cancel" />
                <x-button
                    type="button"
                    wire:click="confirmUserAction"
                    spinner="confirmUserAction"
                    icon="{{ $pendingUserAction === 'delete' ? 'lucide.trash-2' : 'lucide.user-x' }}"
                    class="btn-error"
                    label="{{ $pendingUserAction === 'delete' ? 'Delete user' : 'Deactivate user' }}" />
            </x-slot:actions>
        </x-modal>
    @endteleport
</div>
