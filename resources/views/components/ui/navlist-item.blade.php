@props([
    'href' => '#',
    'current' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => ($current
            ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white'
            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white')
            .' flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium',
    ]) }}
>
    {{ $slot }}
</a>
