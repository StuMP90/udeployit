<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_it_lists_projects_and_notifications(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'My App', 'repo_url' => 'git@github.com:org/repo.git']);
        Notification::create(['type' => 'branch_updated', 'project_id' => $project->id, 'message' => 'Something happened.']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My App');
        $response->assertSee('Something happened.');
    }

    public function test_mark_all_read_clears_unread_notifications(): void
    {
        $this->actingAs(User::factory()->create());
        Notification::create(['type' => 'branch_updated', 'message' => 'Unread one.']);

        Livewire::test('pages::dashboard')->call('markAllRead');

        $this->assertSame(0, Notification::whereNull('read_at')->count());
    }
}
