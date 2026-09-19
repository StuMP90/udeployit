@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'viewable' => false,
])

@php
$inputName = $name ?? $attributes->whereStartsWith('wire:model')->first();
$errorKey = $name ?? (is_string($inputName) ? $inputName : null);
@endphp

<div x-data="{ show: false }" class="{{ $attributes->get('class') ?: 'w-full' }}">
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <input
            @if ($name) id="{{ $name }}" name="{{ $name }}" @endif
            @if ($viewable) :type="show ? 'text' : '{{ $type }}'" @else type="{{ $type }}" @endif
            {{ $attributes->except(['label', 'viewable', 'class'])->merge([
                'class' => 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white',
            ]) }}
        />

        @if ($viewable)
            <button type="button" x-on:click="show = !show" class="absolute inset-y-0 end-0 flex items-center px-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" tabindex="-1">
                <flux:icon.eye x-show="!show" class="size-4" />
                <flux:icon.eye-slash x-show="show" class="size-4" x-cloak />
            </button>
        @endif
    </div>

    @if ($errorKey)
        @error($errorKey)
            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    @endif
</div>
