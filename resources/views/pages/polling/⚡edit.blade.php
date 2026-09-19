<?php

use App\Models\AppSetting;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Polling')] class extends Component {
    public int $poll_interval_seconds = 30;

    public function mount(): void
    {
        $this->poll_interval_seconds = AppSetting::current()->poll_interval_seconds;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'poll_interval_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
        ]);

        AppSetting::current()->update($validated);

        $this->dispatch('notify', text: __('Polling settings saved.'));
    }
}; ?>

<div class="max-w-lg">
    <x-ui.heading size="xl" level="1">{{ __('Polling') }}</x-ui.heading>
    <x-ui.subheading class="mb-6">{{ __('How often the dashboard checks GitHub for branch updates while it is open. This never runs when nobody has the dashboard open.') }}</x-ui.subheading>

    <form wire:submit="save" class="space-y-6">
        <x-ui.input wire:model="poll_interval_seconds" name="poll_interval_seconds" :label="__('Interval (seconds)')" type="number" min="10" max="3600" required />

        <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
    </form>
</div>
