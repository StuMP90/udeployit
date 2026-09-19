<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public ?string $email = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $this->email = filled($this->email) ? trim($this->email) : null;

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);
        $user->save();

        $this->dispatch('notify', text: __('Profile updated.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-ui.heading level="2" class="sr-only">{{ __('Profile settings') }}</x-ui.heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <x-ui.input wire:model="email" name="email" :label="__('Email (optional)')" type="email" autocomplete="email" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
