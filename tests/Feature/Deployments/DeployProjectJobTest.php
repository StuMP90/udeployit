<?php

namespace Tests\Feature\Deployments;

use App\Enums\DeploymentStatus;
use App\Enums\DeploymentType;
use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\ProjectServer;
use App\Models\Server;
use App\Services\Git\DeploymentPlan;
use App\Services\Git\FileChange;
use App\Services\Git\GitDiffService;
use App\Services\Ssh\ScriptResult;
use App\Services\Ssh\SftpDeployerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use phpseclib3\Net\SFTP;
use Tests\TestCase;

class DeployProjectJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeployment(): Deployment
    {
        $project = Project::create(['name' => 'App', 'repo_url' => 'git@github.com:org/repo.git']);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $projectServer = ProjectServer::create([
            'project_id' => $project->id, 'server_id' => $server->id,
            'branch' => 'main', 'deployment_path' => '/var/www/app',
        ]);

        return Deployment::create([
            'project_id' => $project->id,
            'project_server_id' => $projectServer->id,
            'commit_sha' => str_repeat('a', 40),
            'previous_sha' => str_repeat('b', 40),
            'type' => 'incremental',
            'status' => DeploymentStatus::Pending,
        ]);
    }

    public function test_a_successful_incremental_deploy_updates_state(): void
    {
        $deployment = $this->makeDeployment();
        $sftp = new SFTP('unused', 22, 1);

        $this->mock(GitDiffService::class, function ($mock) use ($deployment) {
            $mock->shouldReceive('plan')->once()->andReturn(
                DeploymentPlan::incremental($deployment->commit_sha, [FileChange::put('a.txt')]),
            );
        });

        $this->mock(SftpDeployerService::class, function ($mock) use ($sftp) {
            $mock->shouldReceive('connect')->once()->andReturn($sftp);
            $mock->shouldReceive('applyChanges')->once();
        });

        (new DeployProjectJob($deployment->id))->handle(app(GitDiffService::class), app(SftpDeployerService::class));

        $deployment->refresh();
        $this->assertSame(DeploymentStatus::Success, $deployment->status);
        $this->assertSame(DeploymentType::Incremental, $deployment->type);
        $this->assertNotNull($deployment->started_at);
        $this->assertNotNull($deployment->finished_at);

        $projectServer = $deployment->projectServer->fresh();
        $this->assertSame($deployment->commit_sha, $projectServer->last_deployed_sha);
        $this->assertNotNull($projectServer->last_deployed_at);

        $this->assertTrue($deployment->logs()->where('stage', 'finish')->where('message', 'Deployment succeeded.')->exists());
    }

    public function test_a_full_deploy_uploads_the_extracted_directory(): void
    {
        $deployment = $this->makeDeployment();
        $sftp = new SFTP('unused', 22, 1);
        // A path that deliberately doesn't exist on disk: SftpDeployerService is mocked below,
        // so nothing ever reads from it, and DeploymentPlan::cleanup() no-ops on a missing dir
        // (unlike a real, existing directory such as sys_get_temp_dir(), which it would recursively delete).
        $fakeExtractedPath = sys_get_temp_dir().'/udeployit-test-fake-'.uniqid();

        $this->mock(GitDiffService::class, function ($mock) use ($deployment, $fakeExtractedPath) {
            $mock->shouldReceive('plan')->once()->andReturn(
                DeploymentPlan::full($deployment->commit_sha, $fakeExtractedPath),
            );
        });

        $this->mock(SftpDeployerService::class, function ($mock) use ($sftp, $fakeExtractedPath) {
            $mock->shouldReceive('connect')->once()->andReturn($sftp);
            $mock->shouldReceive('uploadDirectory')->once()->with($sftp, $fakeExtractedPath, '/var/www/app');
        });

        (new DeployProjectJob($deployment->id))->handle(app(GitDiffService::class), app(SftpDeployerService::class));

        $this->assertSame(DeploymentStatus::Success, $deployment->fresh()->status);
    }

    public function test_a_before_script_failure_aborts_the_deployment_without_uploading(): void
    {
        $deployment = $this->makeDeployment();
        $deployment->project->deploymentScripts()->create([
            'type' => 'before', 'command' => 'exit 1', 'timeout_seconds' => 60, 'on_failure' => 'abort',
        ]);
        $sftp = new SFTP('unused', 22, 1);

        $this->mock(GitDiffService::class, function ($mock) use ($deployment) {
            $mock->shouldReceive('plan')->once()->andReturn(
                DeploymentPlan::incremental($deployment->commit_sha, []),
            );
        });

        $this->mock(SftpDeployerService::class, function ($mock) use ($sftp) {
            $mock->shouldReceive('connect')->once()->andReturn($sftp);
            $mock->shouldReceive('runScript')->once()->andReturn(new ScriptResult(false, 'boom', 1));
            $mock->shouldNotReceive('applyChanges');
            $mock->shouldNotReceive('uploadDirectory');
        });

        (new DeployProjectJob($deployment->id))->handle(app(GitDiffService::class), app(SftpDeployerService::class));

        $deployment->refresh();
        $this->assertSame(DeploymentStatus::Failed, $deployment->status);
        $this->assertNull($deployment->projectServer->fresh()->last_deployed_sha);
    }

    public function test_an_after_script_failure_with_continue_still_succeeds(): void
    {
        $deployment = $this->makeDeployment();
        $deployment->project->deploymentScripts()->create([
            'type' => 'after', 'command' => 'exit 1', 'timeout_seconds' => 60, 'on_failure' => 'continue',
        ]);
        $sftp = new SFTP('unused', 22, 1);

        $this->mock(GitDiffService::class, function ($mock) use ($deployment) {
            $mock->shouldReceive('plan')->once()->andReturn(
                DeploymentPlan::incremental($deployment->commit_sha, []),
            );
        });

        $this->mock(SftpDeployerService::class, function ($mock) use ($sftp) {
            $mock->shouldReceive('connect')->once()->andReturn($sftp);
            $mock->shouldReceive('applyChanges')->once();
            $mock->shouldReceive('runScript')->once()->andReturn(new ScriptResult(false, 'boom', 1));
        });

        (new DeployProjectJob($deployment->id))->handle(app(GitDiffService::class), app(SftpDeployerService::class));

        $this->assertSame(DeploymentStatus::Success, $deployment->fresh()->status);
    }
}
