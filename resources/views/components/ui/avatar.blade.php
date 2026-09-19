@props(['initials'])

<span {{ $attributes->merge(['class' => 'flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-800 text-xs font-medium text-white dark:bg-zinc-200 dark:text-zinc-900']) }}>
    {{ $initials }}
</span>
