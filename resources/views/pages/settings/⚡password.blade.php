<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Password settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('notify', text: __('Password updated.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-ui.heading level="2" class="sr-only">{{ __('Password settings') }}</x-ui.heading>

    <x-pages::settings.layout :heading="__('Password')" :subheading="__('Update your account password')">
        <form wire:submit="updatePassword" class="my-6 w-full space-y-6">
            <x-ui.input wire:model="current_password" name="current_password" :label="__('Current password')" type="password" required autocomplete="current-password" viewable />

            <x-ui.input wire:model="password" name="password" :label="__('New password')" type="password" required autocomplete="new-password" viewable />

            <x-ui.input wire:model="password_confirmation" name="password_confirmation" :label="__('Confirm new password')" type="password" required autocomplete="new-password" viewable />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-password-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
