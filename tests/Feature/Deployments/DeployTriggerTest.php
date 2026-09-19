<?php

namespace Tests\Feature\Deployments;

use App\Jobs\DeployProjectJob;
use App\Models\Project;
use App\Models\ProjectServer;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class DeployTriggerTest extends TestCase
{
    use RefreshDatabase;

    private function makeProjectServer(?string $branch = 'main', ?string $lastDeployedSha = null): ProjectServer
    {
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);

        $project->projectBranches()->create([
            'branch_name' => 'main',
            'latest_sha' => str_repeat('a', 40),
            'created_snapshot_sha' => str_repeat('c', 40),
        ]);

        return $project->projectServers()->create([
            'server_id' => $server->id,
            'branch' => $branch,
            'deployment_path' => '/var/www/app',
            'last_deployed_sha' => $lastDeployedSha,
        ]);
    }

    public function test_deploying_without_a_branch_shows_an_error_and_does_not_dispatch(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $projectServer = $this->makeProjectServer(branch: null);

        Livewire::test('pages::projects.show', ['project' => $projectServer->project])
            ->call('deploy', $projectServer->id)
            ->assertDispatched('notify', text: 'Set a branch for this server before deploying.');

        Queue::assertNotPushed(DeployProjectJob::class);
    }

    public function test_a_subsequent_deploy_uses_the_last_deployed_sha_as_the_base(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $projectServer = $this->makeProjectServer(lastDeployedSha: str_repeat('b', 40));

        Livewire::test('pages::projects.show', ['project' => $projectServer->project])
            ->call('deploy', $projectServer->id);

        $this->assertDatabaseHas('deployments', [
            'project_server_id' => $projectServer->id,
            'commit_sha' => str_repeat('a', 40),
            'previous_sha' => str_repeat('b', 40),
        ]);

        Queue::assertPushed(DeployProjectJob::class);
    }

    public function test_a_first_deploy_in_full_mode_has_no_previous_sha(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $projectServer = $this->makeProjectServer();

        Livewire::test('pages::projects.show', ['project' => $projectServer->project])
            ->call('deploy', $projectServer->id, 'full');

        $this->assertDatabaseHas('deployments', [
            'project_server_id' => $projectServer->id,
            'previous_sha' => null,
            'type' => 'full',
        ]);
    }

    public function test_a_first_deploy_in_incremental_mode_uses_the_creation_snapshot(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $projectServer = $this->makeProjectServer();

        Livewire::test('pages::projects.show', ['project' => $projectServer->project])
            ->call('deploy', $projectServer->id, 'incremental');

        $this->assertDatabaseHas('deployments', [
            'project_server_id' => $projectServer->id,
            'previous_sha' => str_repeat('c', 40),
            'type' => 'incremental',
        ]);
    }
}
