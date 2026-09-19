<flux:dropdown position="top" align="start">
    <button type="button" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start hover:bg-zinc-100 dark:hover:bg-zinc-800" data-test="sidebar-menu-button">
        <x-ui.avatar :initials="auth()->user()->initials()" />
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>
            <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ '@'.auth()->user()->username }}</span>
        </span>
        <flux:icon.chevron-up-down variant="micro" class="text-zinc-400" />
    </button>

    <div class="w-56 rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center gap-2 px-2 py-1.5">
            <x-ui.avatar :initials="auth()->user()->initials()" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ '@'.auth()->user()->username }}</p>
            </div>
        </div>

        <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>

        <x-ui.menu-item :href="route('profile.edit')" wire:navigate>
            <flux:icon.cog-6-tooth variant="micro" />
            {{ __('Settings') }}
        </x-ui.menu-item>

        <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <x-ui.menu-item as="button" type="submit" class="cursor-pointer" data-test="logout-button">
                <flux:icon.arrow-right-start-on-rectangle variant="micro" />
                {{ __('Log out') }}
            </x-ui.menu-item>
        </form>
    </div>
</flux:dropdown>
