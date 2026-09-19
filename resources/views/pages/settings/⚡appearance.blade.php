<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-ui.heading level="2" class="sr-only">{{ __('Appearance settings') }}</x-ui.heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div x-data="{ appearance: $flux.appearance }" class="inline-flex rounded-lg border border-zinc-200 p-1 dark:border-zinc-700">
            <template x-for="option in ['light', 'dark', 'system']" :key="option">
                <button
                    type="button"
                    x-on:click="appearance = option; $flux.appearance = option"
                    :class="appearance === option
                        ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                        : 'text-zinc-600 dark:text-zinc-400'"
                    class="rounded-md px-3 py-1.5 text-sm font-medium capitalize"
                    x-text="option"
                ></button>
            </template>
        </div>
    </x-pages::settings.layout>
</section>
