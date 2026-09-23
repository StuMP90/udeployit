<?php

use App\Models\AppSetting;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Polling')] class extends Component {
    public int $poll_interval_seconds = 30;

    public bool $poll_in_background = false;

    public bool $browser_notifications = false;

    public function mount(): void
    {
        $settings = AppSetting::current();
        $this->poll_interval_seconds = $settings->poll_interval_seconds;
        $this->poll_in_background = $settings->poll_in_background;
        $this->browser_notifications = $settings->browser_notifications;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'poll_interval_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
            'poll_in_background' => ['boolean'],
            'browser_notifications' => ['boolean'],
        ]);

        AppSetting::current()->update($validated);

        $this->dispatch('notify', text: __('Polling settings saved.'));
    }
}; ?>

<div class="max-w-lg">
    <x-ui.heading size="xl" level="1">{{ __('Polling') }}</x-ui.heading>
    <x-ui.subheading class="mb-6">{{ __('How often uDeployIt checks GitHub for branch updates while any page of the site is open in a browser tab. This never runs when nobody has the site open.') }}</x-ui.subheading>

    <form wire:submit="save" class="space-y-6">
        <x-ui.input wire:model="poll_interval_seconds" name="poll_interval_seconds" :label="__('Interval (seconds)')" type="number" min="10" max="3600" required />

        <div>
            <x-ui.checkbox wire:model="poll_in_background" name="poll_in_background" :label="__('Poll in background tabs')" />

            <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __("By default, polling pauses when the tab with the site open isn't the one you're looking at, to avoid unnecessary GitHub requests. Turn this on to keep it polling (and auto-deploying) while you work in another tab — a tab with any page of the site just needs to stay open somewhere, active or not. Closing it, or the browser, still stops polling entirely. Browsers also slow down background tabs to save power, so an interval of a few seconds may not be hit exactly.") }}
            </p>
        </div>

        <div>
            <x-ui.checkbox wire:model="browser_notifications" name="browser_notifications" :label="__('Show browser notifications')" />

            <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __("Pops up a browser notification for new branch updates and deployment results, for as long as a tab with the site stays open (same rule as polling itself, including the background-tab option above). The first time this is turned on, your browser will separately ask you to grant notification permission — if you don't see that prompt, reload the page. This is per-browser: each device or browser you use the site from needs to grant permission on its own.") }}
            </p>
        </div>

        <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
    </form>
</div>
