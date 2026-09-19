<?php

namespace Tests\Feature\Deployments;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentScriptManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_save_a_before_script(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);

        Livewire::test('pages::projects.show', ['project' => $project])
            ->set('scriptCommand.before', 'cd /var/www/app && npm install')
            ->set('scriptTimeout.before', 120)
            ->set('scriptOnFailure.before', 'abort')
            ->call('saveScript', 'before')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('deployment_scripts', [
            'project_id' => $project->id,
            'type' => 'before',
            'command' => 'cd /var/www/app && npm install',
            'timeout_seconds' => 120,
        ]);
    }

    public function test_timeout_cannot_exceed_one_hour(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);

        Livewire::test('pages::projects.show', ['project' => $project])
            ->set('scriptCommand.before', 'npm install')
            ->set('scriptTimeout.before', 999999)
            ->set('scriptOnFailure.before', 'abort')
            ->call('saveScript', 'before')
            ->assertHasErrors(['scriptTimeout.before']);
    }

    public function test_saving_again_updates_rather_than_duplicates(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);

        $component = Livewire::test('pages::projects.show', ['project' => $project])
            ->set('scriptCommand.before', 'first')
            ->set('scriptTimeout.before', 60)
            ->set('scriptOnFailure.before', 'abort')
            ->call('saveScript', 'before');

        $component
            ->set('scriptCommand.before', 'second')
            ->call('saveScript', 'before');

        $this->assertSame(1, $project->deploymentScripts()->where('type', 'before')->count());
        $this->assertSame('second', $project->deploymentScripts()->where('type', 'before')->first()->command);
    }

    public function test_staff_can_remove_a_script(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $project->deploymentScripts()->create(['type' => 'before', 'command' => 'x', 'timeout_seconds' => 60, 'on_failure' => 'abort']);

        Livewire::test('pages::projects.show', ['project' => $project])->call('removeScript', 'before');

        $this->assertDatabaseMissing('deployment_scripts', ['project_id' => $project->id, 'type' => 'before']);
    }
}
