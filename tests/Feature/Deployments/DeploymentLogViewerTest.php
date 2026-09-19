<?php

namespace Tests\Feature\Deployments;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\ProjectServer;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_view_a_deployments_logs(): void
    {
        $this->actingAs(User::factory()->create());

        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $projectServer = ProjectServer::create(['project_id' => $project->id, 'server_id' => $server->id]);
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'project_server_id' => $projectServer->id,
            'commit_sha' => str_repeat('a', 40),
            'type' => 'full',
            'status' => 'success',
        ]);
        $deployment->log('finish', 'Deployment succeeded.');

        $response = $this->get(route('deployments.show', $deployment));

        $response->assertOk();
        $response->assertSee('Deployment succeeded.');
    }

    public function test_a_running_deployment_polls_and_a_finished_one_does_not(): void
    {
        $this->actingAs(User::factory()->create());

        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $projectServer = ProjectServer::create(['project_id' => $project->id, 'server_id' => $server->id]);

        $running = Deployment::create([
            'project_id' => $project->id, 'project_server_id' => $projectServer->id,
            'commit_sha' => str_repeat('a', 40), 'type' => 'full', 'status' => 'running',
        ]);
        $finished = Deployment::create([
            'project_id' => $project->id, 'project_server_id' => $projectServer->id,
            'commit_sha' => str_repeat('a', 40), 'type' => 'full', 'status' => 'success',
        ]);

        Livewire::test('pages::deployments.show', ['deployment' => $running])->assertSeeHtml('wire:poll.2s="refresh"');
        Livewire::test('pages::deployments.show', ['deployment' => $finished])->assertDontSeeHtml('wire:poll.2s="refresh"');
    }

    public function test_refresh_picks_up_new_status_and_logs(): void
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
            'commit_sha' => str_repeat('a', 40), 'type' => 'full', 'status' => 'running',
        ]);

        $component = Livewire::test('pages::deployments.show', ['deployment' => $deployment]);

        $deployment->update(['status' => 'success']);
        $deployment->log('finish', 'Deployment succeeded.');

        $component->call('refresh')->assertSee('Deployment succeeded.');
    }
}
