<?php

namespace App\Livewire;

use App\Models\AppSetting;
use App\Models\Notification;
use App\Models\Project;
use App\Services\Deployment\BranchPoller;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Lives in the app layout (persisted across wire:navigate page swaps), so branch
 * polling and auto-deploy keep running no matter which page is currently open —
 * not just the dashboard.
 */
class BackgroundPoller extends Component
{
    public int $lastSeenNotificationId = 0;

    public function mount(): void
    {
        // Only notify about notifications created from this point on — not the
        // whole backlog that already existed when the page was loaded.
        $this->lastSeenNotificationId = (int) Notification::max('id');
    }

    public function poll(): void
    {
        $poller = app(BranchPoller::class);

        foreach (Project::all() as $project) {
            $poller->pollProject($project);
        }

        $this->notifyBrowserOfNewNotifications();

        $this->dispatch('background-poll-completed');
    }

    private function notifyBrowserOfNewNotifications(): void
    {
        if (! AppSetting::current()->browser_notifications) {
            return;
        }

        $new = Notification::where('id', '>', $this->lastSeenNotificationId)->orderBy('id')->get();

        if ($new->isEmpty()) {
            return;
        }

        $this->lastSeenNotificationId = (int) $new->max('id');

        $this->dispatch('browser-notify', messages: $new->pluck('message')->all());
    }

    #[Computed]
    public function pollIntervalSeconds(): int
    {
        return AppSetting::current()->poll_interval_seconds;
    }

    #[Computed]
    public function pollInBackground(): bool
    {
        return AppSetting::current()->poll_in_background;
    }

    #[Computed]
    public function browserNotificationsEnabled(): bool
    {
        return AppSetting::current()->browser_notifications;
    }

    public function render(): View
    {
        return view('livewire.background-poller');
    }
}
