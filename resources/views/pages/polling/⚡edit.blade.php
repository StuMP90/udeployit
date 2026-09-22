<?php

use App\Models\AppSetting;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Polling')] class extends Component {
    public int $poll_interval_seconds = 30;

    public bool $poll_in_background = false;

    public function mount(): void
    {
        $settings = AppSetting::current();
        $this->poll_interval_seconds = $settings->poll_interval_seconds;
        $this->poll_in_background = $settings->poll_in_background;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'poll_interval_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
            'poll_in_background' => ['boolean'],
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

        <div>
            <x-ui.checkbox wire:model="poll_in_background" name="poll_in_background" :label="__('Poll in background tabs')" />

            <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __("By default, polling pauses when the dashboard tab isn't the one you're looking at, to avoid unnecessary GitHub requests. Turn this on to keep it polling (and auto-deploying) while you work in another tab — the dashboard tab just needs to stay open somewhere, active or not. Closing it, or the browser, still stops polling entirely. Browsers also slow down background tabs to save power, so an interval of a few seconds may not be hit exactly.") }}
            </p>
        </div>

        <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
    </form>
</div>
