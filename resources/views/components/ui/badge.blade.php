@props(['color' => 'zinc'])

@php
$colors = [
    'zinc' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
    'green' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    'red' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.$colors[$color]]) }}>
    {{ $slot }}
</span>
