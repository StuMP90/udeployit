@props(['muted' => false])

<a {{ $attributes->merge([
    'class' => ($muted
        ? 'text-zinc-500 dark:text-zinc-400'
        : 'text-zinc-900 dark:text-white')
        .' underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-500 dark:decoration-zinc-600',
]) }}>{{ $slot }}</a>
