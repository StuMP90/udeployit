<?php

use App\Models\Notification;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Notifications')] class extends Component {
    use WithPagination;

    public function markAllRead(): void
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);
    }

    public function with(): array
    {
        return [
            'notifications' => Notification::latest()->paginate(30),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <x-ui.heading size="xl" level="1">{{ __('Notifications') }}</x-ui.heading>
            <x-ui.subheading>{{ __('Deployment results and branch updates, newest first.') }}</x-ui.subheading>
        </div>

        <button type="button" wire:click="markAllRead" class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">
            {{ __('Mark all read') }}
        </button>
    </div>

    <div class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
        @forelse ($notifications as $notification)
            <div wire:key="notification-{{ $notification->id }}" class="flex items-center justify-between gap-4 px-4 py-3 text-sm {{ $notification->read_at ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-900 dark:text-white' }}">
                @if ($notification->deployment_id)
                    <x-ui.link href="{{ route('deployments.show', $notification->deployment_id) }}" wire:navigate>{{ $notification->message }}</x-ui.link>
                @else
                    <span>{{ $notification->message }}</span>
                @endif
                <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</span>
            </div>
        @empty
            <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No notifications yet.') }}</p>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
