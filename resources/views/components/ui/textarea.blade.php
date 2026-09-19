@props([
    'label' => null,
    'name' => null,
])

<div class="{{ $attributes->get('class') ?: 'w-full' }}">
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
        </label>
    @endif

    <textarea
        @if ($name) id="{{ $name }}" name="{{ $name }}" @endif
        {{ $attributes->except(['class'])->merge([
            'class' => 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white',
            'rows' => 8,
        ]) }}
    ></textarea>

    @if ($name)
        @error($name)
            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    @endif
</div>
