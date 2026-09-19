<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add user')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public string $name = '';
    public string $username = '';
    public ?string $email = null;
    public string $role = 'staff';
    public string $password = '';
    public string $password_confirmation = '';

    public function save(): void
    {
        $this->email = filled($this->email) ? trim($this->email) : null;

        $validated = $this->validate([
            ...$this->profileRules(),
            'username' => $this->usernameRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => $this->passwordRules(),
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        $this->redirect(route('users.index'), navigate: true);
    }
}; ?>

<div class="max-w-lg">
        <x-ui.heading size="xl" level="1">{{ __('Add user') }}</x-ui.heading>
        <x-ui.subheading class="mb-6">{{ __('Create a login for a new admin or staff user.') }}</x-ui.subheading>

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus />

            <x-ui.input wire:model="username" name="username" :label="__('Username')" type="text" required />

            <x-ui.input wire:model="email" name="email" :label="__('Email (optional)')" type="email" />

            <x-ui.select wire:model="role" name="role" :label="__('Role')">
                @foreach (UserRole::cases() as $roleOption)
                    <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input wire:model="password" name="password" :label="__('Password')" type="password" required autocomplete="new-password" viewable />

            <x-ui.input wire:model="password_confirmation" name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">{{ __('Create user') }}</flux:button>
                <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
