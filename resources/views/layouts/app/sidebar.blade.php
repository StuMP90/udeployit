<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <div class="flex min-h-screen">
            <aside class="hidden w-64 shrink-0 flex-col border-e border-zinc-200 bg-zinc-50 lg:flex dark:border-zinc-700 dark:bg-zinc-900">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-4" wire:navigate>
                    <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </span>
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <nav class="flex flex-1 flex-col gap-0.5 px-3">
                    <x-ui.navlist-item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        <flux:icon.home variant="micro" />
                        {{ __('Dashboard') }}
                    </x-ui.navlist-item>

                    @can('manage-users')
                        <x-ui.navlist-item :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            <flux:icon.users variant="micro" />
                            {{ __('Users') }}
                        </x-ui.navlist-item>
                    @endcan
                </nav>

                <div class="border-t border-zinc-200 p-3 dark:border-zinc-700">
                    <x-desktop-user-menu />
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3 lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
                        <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                            <x-app-logo-icon class="size-5 fill-current" />
                        </span>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <div class="flex-1"></div>

                    <x-desktop-user-menu />
                </header>

                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-ui.toast />

        @fluxScripts
    </body>
</html>
