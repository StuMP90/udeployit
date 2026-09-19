@props(['size' => 'md'])

@php
$sizes = [
    'lg' => 'text-sm',
    'md' => 'text-sm',
];
@endphp

<p {{ $attributes->merge(['class' => $sizes[$size].' text-zinc-500 dark:text-zinc-400']) }}>{{ $slot }}</p>
