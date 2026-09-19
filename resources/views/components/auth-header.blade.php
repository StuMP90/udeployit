@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <x-ui.heading size="xl" level="1">{{ $title }}</x-ui.heading>
    <x-ui.subheading>{{ $description }}</x-ui.subheading>
</div>
