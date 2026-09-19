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
}
