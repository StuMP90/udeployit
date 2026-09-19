@props([
    'href' => null,
    'as' => 'a',
])

@if ($as === 'button')
    <button {{ $attributes->merge(['class' => 'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700']) }}>
        {{ $slot }}
    </button>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700']) }}>
        {{ $slot }}
    </a>
@endif
