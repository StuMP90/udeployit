<?php

namespace Tests\Feature\Projects;

use App\Exceptions\GitRepositoryException;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Models\User;
use App\Services\Git\GitRepositoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_the_projects_index(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('projects.index'))->assertOk();
    }

    public function test_the_project_name_links_to_the_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'My App', 'repo_url' => 'git@github.com:org/repo.git']);

        $this->get(route('projects.index'))->assertSee(route('projects.show', $project), false);
    }

    public function test_a_user_can_view_the_full_deployment_history(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $projectServer = $project->projectServers()->create(['server_id' => $server->id]);
        $projectServer->deployments()->create([
            'project_id' => $project->id, 'commit_sha' => str_repeat('a', 40), 'type' => 'full', 'status' => 'success',
        ]);

        $response = $this->get(route('projects.deployments', $project));

        $response->assertOk();
        $response->assertSee('Prod');
    }

    public function test_staff_cannot_create_a_project(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('projects.create'))->assertForbidden();
    }

    public function test_admin_can_create_a_project(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->mock(GitRepositoryService::class, function ($mock) {
            $mock->shouldReceive('syncBranches')->once()->andReturn([]);
        });

        Livewire::test('pages::projects.create')
            ->set('name', 'My App')
            ->set('repo_url', 'git@github.com:org/repo.git')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', ['name' => 'My App', 'repo_url' => 'git@github.com:org/repo.git']);
    }

    public function test_project_is_not_created_if_the_repository_cannot_be_read(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->mock(GitRepositoryService::class, function ($mock) {
            $mock->shouldReceive('syncBranches')->once()->andThrow(new GitRepositoryException('permission denied'));
        });

        Livewire::test('pages::projects.create')
            ->set('name', 'My App')
            ->set('repo_url', 'git@github.com:org/repo.git')
            ->call('save')
            ->assertHasErrors(['repo_url']);

        $this->assertDatabaseMissing('projects', ['name' => 'My App']);
    }

    public function test_creating_from_a_template_copies_its_servers(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $template = ProjectTemplate::create(['name' => 'Standard']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'a.example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $template->templateServers()->create(['server_id' => $server->id, 'default_deployment_path' => '/var/www/app']);

        $this->mock(GitRepositoryService::class, function ($mock) {
            $mock->shouldReceive('syncBranches')->once()->andReturn([]);
        });

        Livewire::test('pages::projects.create')
            ->set('name', 'My App')
            ->set('repo_url', 'git@github.com:org/repo.git')
            ->set('project_template_id', (string) $template->id)
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('name', 'My App')->firstOrFail();

        $this->assertDatabaseHas('project_servers', [
            'project_id' => $project->id,
            'server_id' => $server->id,
            'deployment_path' => '/var/www/app',
        ]);
    }

    public function test_staff_can_update_project_overview(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);

        Livewire::test('pages::projects.show', ['project' => $project])
            ->set('name', 'Renamed App')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Renamed App', $project->fresh()->name);
    }

    public function test_staff_cannot_delete_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);

        Livewire::test('pages::projects.show', ['project' => $project])->call('deleteProject');

        $this->assertNotNull($project->fresh());
    }

    public function test_admin_can_delete_a_project(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);

        Livewire::test('pages::projects.show', ['project' => $project])->call('deleteProject');

        $this->assertNull($project->fresh());
    }

    public function test_refresh_branches_dispatches_a_notification(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);

        $this->mock(GitRepositoryService::class, function ($mock) {
            $mock->shouldReceive('syncBranches')->once()->andReturn([]);
        });

        Livewire::test('pages::projects.show', ['project' => $project])
            ->call('refreshBranches')
            ->assertDispatched('notify', text: 'Branches refreshed.');
    }

    public function test_staff_can_attach_update_and_remove_a_server(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'a.example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);

        $component = Livewire::test('pages::projects.show', ['project' => $project])
            ->set('newServerId', (string) $server->id)
            ->set('newBranch', 'main')
            ->set('newDeploymentPath', '/var/www/app')
            ->call('addServer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_servers', [
            'project_id' => $project->id,
            'server_id' => $server->id,
            'branch' => 'main',
        ]);

        $projectServer = $project->projectServers()->firstOrFail();

        $component
            ->set("deploymentPath.{$projectServer->id}", '/var/www/new-path')
            ->call('updateServer', $projectServer->id);

        $this->assertSame('/var/www/new-path', $projectServer->fresh()->deployment_path);

        $component->call('removeServer', $projectServer->id);

        $this->assertNull($projectServer->fresh());
    }

    public function test_two_projects_with_the_same_name_get_distinct_slugs(): void
    {
        $first = Project::create(['name' => 'My App', 'repo_url' => 'git@github.com:org/repo.git']);
        $second = Project::create(['name' => 'My App', 'repo_url' => 'git@github.com:org/repo2.git']);

        $this->assertNotSame($first->slug, $second->slug);
    }
}
