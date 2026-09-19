<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <x-ui.navlist aria-label="{{ __('Settings') }}">
            <x-ui.navlist-item :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>{{ __('Profile') }}</x-ui.navlist-item>
            <x-ui.navlist-item :href="route('password.edit')" :current="request()->routeIs('password.edit')" wire:navigate>{{ __('Password') }}</x-ui.navlist-item>
            <x-ui.navlist-item :href="route('appearance.edit')" :current="request()->routeIs('appearance.edit')" wire:navigate>{{ __('Appearance') }}</x-ui.navlist-item>
        </x-ui.navlist>
    </div>

    <div class="border-t border-zinc-200 md:hidden dark:border-zinc-700"></div>

    <div class="flex-1 self-stretch max-md:pt-6">
        <x-ui.heading>{{ $heading ?? '' }}</x-ui.heading>
        <x-ui.subheading>{{ $subheading ?? '' }}</x-ui.subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
