<?php

namespace Tests\Feature\Infrastructure;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PollingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_polling_settings(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('polling.edit'))->assertForbidden();
    }

    public function test_admin_can_update_the_poll_interval(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::polling.edit')
            ->set('poll_interval_seconds', 60)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(60, AppSetting::current()->poll_interval_seconds);
    }

    public function test_the_interval_must_be_within_bounds(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::polling.edit')
            ->set('poll_interval_seconds', 5)
            ->call('save')
            ->assertHasErrors(['poll_interval_seconds']);
    }

    public function test_poll_in_background_defaults_to_off(): void
    {
        $this->assertFalse(AppSetting::current()->poll_in_background);
    }

    public function test_admin_can_enable_polling_in_background(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::polling.edit')
            ->set('poll_in_background', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(AppSetting::current()->poll_in_background);
    }

    public function test_browser_notifications_defaults_to_off(): void
    {
        $this->assertFalse(AppSetting::current()->browser_notifications);
    }

    public function test_admin_can_enable_browser_notifications(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::polling.edit')
            ->set('browser_notifications', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(AppSetting::current()->browser_notifications);
    }
}
