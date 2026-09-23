<?php

namespace Tests\Feature;

use App\Livewire\BackgroundPoller;
use App\Models\AppSetting;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BackgroundPollerTest extends TestCase
{
    use RefreshDatabase;

    public function test_polling_pauses_in_the_background_by_default(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BackgroundPoller::class)
            ->assertSeeHtml('wire:poll.30s.visible=')
            ->assertDontSeeHtml('keep-alive');
    }

    public function test_polling_keeps_alive_when_enabled(): void
    {
        $this->actingAs(User::factory()->create());
        AppSetting::current()->update(['poll_in_background' => true]);

        Livewire::test(BackgroundPoller::class)->assertSeeHtml('wire:poll.30s.visible.keep-alive=');
    }

    public function test_it_appears_on_pages_other_than_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('projects.index'));

        $response->assertSeeHtml('wire:poll.30s.visible=');
    }

    public function test_it_is_not_display_none(): void
    {
        // wire:poll's .visible modifier gates on getBoundingClientRect(), which is
        // always zero for a display:none element — that would silently stop this
        // component from ever polling, on every page, forever. Must stay merely
        // visually hidden (e.g. sr-only), not display:none (e.g. Tailwind's `hidden`).
        $this->actingAs(User::factory()->create());

        Livewire::test(BackgroundPoller::class)
            ->assertDontSeeHtml('class="hidden"')
            ->assertSeeHtml('sr-only');
    }

    public function test_a_tick_dispatches_a_completion_event(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BackgroundPoller::class)
            ->call('poll')
            ->assertDispatched('background-poll-completed');
    }

    public function test_browser_notifications_are_off_by_default(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BackgroundPoller::class)->assertSeeHtml('data-browser-notifications="0"');
    }

    public function test_browser_notifications_flag_reflects_the_setting(): void
    {
        $this->actingAs(User::factory()->create());
        AppSetting::current()->update(['browser_notifications' => true]);

        Livewire::test(BackgroundPoller::class)->assertSeeHtml('data-browser-notifications="1"');
    }

    public function test_a_tick_notifies_the_browser_of_notifications_created_since_the_page_loaded(): void
    {
        $this->actingAs(User::factory()->create());
        AppSetting::current()->update(['browser_notifications' => true]);

        // Pre-existing at mount time — should never be notified about.
        Notification::create(['type' => 'branch_updated', 'message' => 'Old news.']);

        $component = Livewire::test(BackgroundPoller::class);

        Notification::create(['type' => 'branch_updated', 'message' => 'Fresh update.']);

        $component->call('poll')
            ->assertDispatched('browser-notify', messages: ['Fresh update.']);
    }

    public function test_a_tick_does_not_notify_the_browser_when_the_setting_is_disabled(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(BackgroundPoller::class);

        Notification::create(['type' => 'branch_updated', 'message' => 'Fresh update.']);

        $component->call('poll')->assertNotDispatched('browser-notify');
    }
}
