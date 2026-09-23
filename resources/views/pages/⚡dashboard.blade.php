<?php

use App\Models\AppSetting;
use App\Models\Notification;
use App\Models\Project;
use App\Services\Deployment\BranchPoller;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[On('background-poll-completed')]
    public function refreshFromBackgroundPoll(): void
    {
        unset($this->projects, $this->notifications);
    }

    public function refreshNow(): void
    {
        $poller = app(BranchPoller::class);

        foreach (Project::all() as $project) {
            $poller->forcePoll($project);
        }

        unset($this->projects, $this->notifications);

        $this->dispatch('notify', text: __('Refreshed.'));
    }

    public function markAllRead(): void
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);

        unset($this->notifications);
    }

    #[Computed]
    public function projects()
    {
        return Project::withCount('projectServers')->with('projectBranches')->orderBy('name')->get();
    }

    #[Computed]
    public function notifications()
    {
        return Notification::latest()->limit(20)->get();
    }

    #[Computed]
    public function pollIntervalSeconds()
    {
        return AppSetting::current()->poll_interval_seconds;
    }

    #[Computed]
    public function pollInBackground()
    {
        return AppSetting::current()->poll_in_background;
    }
}; ?>

<div class="flex flex-col gap-8">
    <div class="flex items-center justify-between">
        <div>
            <x-ui.heading size="xl" level="1">{{ __('Dashboard') }}</x-ui.heading>
            <x-ui.subheading>
                @if ($this->pollInBackground)
                    {{ __('Polling every :seconds seconds as long as a tab with the site stays open somewhere, even in the background.', ['seconds' => $this->pollIntervalSeconds]) }}
                @else
                    {{ __('Polling every :seconds seconds as long as a tab with the site is open and active — on any page, not just this one.', ['seconds' => $this->pollIntervalSeconds]) }}
                @endif
            </x-ui.subheading>
        </div>

        <flux:button type="button" wire:click="refreshNow">{{ __('Refresh now') }}</flux:button>
    </div>

    <div>
        <x-ui.heading size="md">{{ __('Projects') }}</x-ui.heading>
        <x-ui.subheading class="mb-4">{{ __('Latest known commit per branch.') }}</x-ui.subheading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->projects as $project)
                <a href="{{ route('projects.show', $project) }}" wire:navigate class="block rounded-lg border border-zinc-200 p-4 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600">
                    <p class="font-medium text-zinc-900 dark:text-white">{{ $project->name }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __(':count server(s)', ['count' => $project->project_servers_count]) }}</p>

                    <ul class="mt-3 space-y-1">
                        @forelse ($project->projectBranches as $branch)
                            <li class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                                <span>{{ $branch->branch_name }}</span>
                                <span class="font-mono">{{ substr($branch->latest_sha, 0, 8) }}</span>
                            </li>
                        @empty
                            <li class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('No branches synced yet.') }}</li>
                        @endforelse
                    </ul>
                </a>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No projects yet.') }}</p>
            @endforelse
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between">
            <x-ui.heading size="md">{{ __('Notifications') }}</x-ui.heading>

            <div class="flex items-center gap-4">
                <x-ui.link href="{{ route('notifications.index') }}" wire:navigate class="text-sm">{{ __('View all') }}</x-ui.link>

                <button type="button" wire:click="markAllRead" class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">
                    {{ __('Mark all read') }}
                </button>
            </div>
        </div>

        <div class="mt-4 divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($this->notifications as $notification)
                <div wire:key="notification-{{ $notification->id }}" class="flex items-center justify-between gap-4 px-4 py-3 text-sm {{ $notification->read_at ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-900 dark:text-white' }}">
                    @if ($notification->deployment_id)
                        <x-ui.link href="{{ route('deployments.show', $notification->deployment_id) }}" wire:navigate :muted="(bool) $notification->read_at">{{ $notification->message }}</x-ui.link>
                    @else
                        <span>{{ $notification->message }}</span>
                    @endif
                    <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No notifications yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
