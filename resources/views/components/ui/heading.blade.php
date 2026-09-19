@props([
    'level' => 1,
    'size' => 'lg',
])

@php
$sizes = [
    'xl' => 'text-2xl font-semibold',
    'lg' => 'text-xl font-semibold',
    'md' => 'text-base font-semibold',
    'sm' => 'text-sm font-semibold',
];
$tag = 'h'.$level;
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $sizes[$size].' text-zinc-900 dark:text-white']) }}>{{ $slot }}</{{ $tag }}>
