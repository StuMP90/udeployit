<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit user')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public User $user;

    public string $name = '';
    public string $username = '';
    public ?string $email = null;
    public string $role = 'staff';

    public string $password = '';
    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->role = $user->role->value;
    }

    public function save(): void
    {
        $this->email = filled($this->email) ? trim($this->email) : null;

        $validated = $this->validate([
            ...$this->profileRules($this->user->id),
            'username' => $this->usernameRules($this->user->id),
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        if ($validated['role'] !== UserRole::Admin->value && $this->lastAdmin()) {
            $this->addError('role', __('You cannot demote the last remaining admin.'));

            return;
        }

        $this->user->update($validated);

        $this->dispatch('notify', text: __('User updated.'));
    }

    public function resetPassword(): void
    {
        $validated = $this->validate([
            'password' => $this->passwordRules(),
        ]);

        $this->user->update(['password' => Hash::make($validated['password'])]);

        $this->reset('password', 'password_confirmation');

        $this->dispatch('notify', text: __('Password reset.'));
    }

    private function lastAdmin(): bool
    {
        return $this->user->role === UserRole::Admin
            && User::where('role', UserRole::Admin)->count() <= 1;
    }
}; ?>

<div class="max-w-lg space-y-10">
        <div>
            <x-ui.heading size="xl" level="1">{{ __('Edit user') }}</x-ui.heading>
            <x-ui.subheading class="mb-6">{{ '@'.$user->username }}</x-ui.subheading>

            <form wire:submit="save" class="space-y-6">
                <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus />

                <x-ui.input wire:model="username" name="username" :label="__('Username')" type="text" required />

                <x-ui.input wire:model="email" name="email" :label="__('Email (optional)')" type="email" />

                <x-ui.select wire:model="role" name="role" :label="__('Role')">
                    @foreach (UserRole::cases() as $roleOption)
                        <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                    @endforeach
                </x-ui.select>

                <div class="flex items-center gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate>{{ __('Back') }}</flux:button>
                </div>
            </form>
        </div>

        <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <x-ui.heading size="md">{{ __('Reset password') }}</x-ui.heading>
            <x-ui.subheading class="mb-4">{{ __('Set a new password for this user.') }}</x-ui.subheading>

            <form wire:submit="resetPassword" class="space-y-6">
                <x-ui.input wire:model="password" name="password" :label="__('New password')" type="password" autocomplete="new-password" viewable />

                <x-ui.input wire:model="password_confirmation" name="password_confirmation" :label="__('Confirm new password')" type="password" autocomplete="new-password" viewable />

                <flux:button type="submit">{{ __('Reset password') }}</flux:button>
            </form>
        </div>
    </div>
