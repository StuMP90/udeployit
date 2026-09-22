<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectServer;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_view_the_notifications_index(): void
    {
        $this->actingAs(User::factory()->create());
        Notification::create(['type' => 'branch_updated', 'message' => 'Something happened.']);

        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Something happened.');
    }

    public function test_mark_all_read_clears_unread_notifications(): void
    {
        $this->actingAs(User::factory()->create());
        Notification::create(['type' => 'branch_updated', 'message' => 'Unread one.']);

        Livewire::test('pages::notifications.index')->call('markAllRead');

        $this->assertSame(0, Notification::whereNull('read_at')->count());
    }

    public function test_a_deployment_notification_links_to_the_deployment_log(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $projectServer = ProjectServer::create(['project_id' => $project->id, 'server_id' => $server->id]);
        $deployment = Deployment::create([
            'project_id' => $project->id, 'project_server_id' => $projectServer->id,
            'commit_sha' => str_repeat('a', 40), 'type' => 'full', 'status' => 'success',
        ]);
        Notification::create([
            'type' => 'deployment_success', 'project_id' => $project->id, 'deployment_id' => $deployment->id,
            'message' => 'Deployed "App" to "Prod".',
        ]);

        $dashboard = $this->get(route('dashboard'));
        $dashboard->assertSee(route('deployments.show', $deployment), false);

        $index = $this->get(route('notifications.index'));
        $index->assertSee(route('deployments.show', $deployment), false);
    }

    public function test_a_branch_update_notification_has_no_log_link(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        Notification::create(['type' => 'branch_updated', 'project_id' => $project->id, 'message' => 'Branch updated.']);

        $response = $this->get(route('dashboard'));

        $response->assertSee('Branch updated.');
        $response->assertDontSee(route('deployments.show', 1), false);
    }

    public function test_a_deployment_notification_link_is_visually_muted_once_read(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $projectServer = ProjectServer::create(['project_id' => $project->id, 'server_id' => $server->id]);
        $deployment = Deployment::create([
            'project_id' => $project->id, 'project_server_id' => $projectServer->id,
            'commit_sha' => str_repeat('a', 40), 'type' => 'full', 'status' => 'success',
        ]);
        $notification = Notification::create([
            'type' => 'deployment_success', 'project_id' => $project->id, 'deployment_id' => $deployment->id,
            'message' => 'Deployed "App" to "Prod".',
        ]);

        // Tie the class string to this specific notification's href, since the
        // unrelated "View all" link always renders with the unmuted style too —
        // asserting the class in isolation would pass regardless of read state.
        $href = route('deployments.show', $deployment);
        $unreadLink = "class=\"text-zinc-900 dark:text-white underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-500 dark:decoration-zinc-600\" href=\"{$href}\"";
        $readLink = "class=\"text-zinc-500 dark:text-zinc-400 underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-500 dark:decoration-zinc-600\" href=\"{$href}\"";

        // Unread: link renders with the full-strength text color.
        $this->get(route('dashboard'))->assertSee($unreadLink, false);

        $notification->update(['read_at' => now()]);

        // Read: link renders with the muted color instead — this is what makes read
        // notifications look different, since the underlying <a> tag would otherwise
        // always render at full strength regardless of the parent row's read state.
        $this->get(route('dashboard'))->assertSee($readLink, false);
    }
}
