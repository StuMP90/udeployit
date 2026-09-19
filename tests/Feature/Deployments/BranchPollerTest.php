<?php

namespace Tests\Feature\Deployments;

use App\Jobs\DeployProjectJob;
use App\Models\GithubCredential;
use App\Models\Project;
use App\Models\Server;
use App\Services\Deployment\BranchPoller;
use App\Services\Git\GitDiffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BranchPollerTest extends TestCase
{
    use RefreshDatabase;

    private string $originPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originPath = sys_get_temp_dir().'/udeployit-poll-origin-'.uniqid();
        File::ensureDirectoryExists($this->originPath);

        foreach ([
            ['git', 'init', '--initial-branch=main', $this->originPath],
            ['git', '-C', $this->originPath, 'config', 'user.email', 'test@example.com'],
            ['git', '-C', $this->originPath, 'config', 'user.name', 'Test'],
        ] as $command) {
            (new Process($command))->mustRun();
        }

        File::put($this->originPath.'/a.txt', 'v1');
        (new Process(['git', '-C', $this->originPath, 'add', '.']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'commit', '-m', 'first']))->mustRun();

        GithubCredential::create(['name' => 'Default', 'private_key' => 'unused-for-local-repo', 'is_default' => true]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->originPath);
        File::deleteDirectory(storage_path('app/repos'));

        parent::tearDown();
    }

    private function pushNewCommit(): void
    {
        File::put($this->originPath.'/a.txt', 'v2');
        (new Process(['git', '-C', $this->originPath, 'add', '.']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'commit', '-m', 'second']))->mustRun();
    }

    public function test_polling_syncs_branches_and_then_skips_within_the_cache_window(): void
    {
        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);

        app(BranchPoller::class)->pollProject($project);
        $this->assertSame(1, $project->projectBranches()->count());

        $this->assertTrue(Cache::has("project-poll:{$project->id}"));

        $this->pushNewCommit();
        app(BranchPoller::class)->pollProject($project);

        $this->assertSame('v1', app(GitDiffService::class)->content($project, $project->projectBranches()->firstOrFail()->latest_sha, 'a.txt'));
    }

    public function test_force_poll_bypasses_the_cache_window(): void
    {
        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);
        app(BranchPoller::class)->pollProject($project);

        $this->pushNewCommit();
        app(BranchPoller::class)->forcePoll($project);

        $this->assertSame('v2', app(GitDiffService::class)->content($project, $project->projectBranches()->firstOrFail()->latest_sha, 'a.txt'));
    }

    public function test_a_branch_update_creates_a_notification(): void
    {
        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);
        app(BranchPoller::class)->pollProject($project);

        $this->pushNewCommit();
        app(BranchPoller::class)->forcePoll($project);

        $this->assertDatabaseHas('notifications', [
            'project_id' => $project->id,
            'type' => 'branch_updated',
        ]);
    }

    public function test_an_auto_deploy_server_gets_dispatched_on_branch_update(): void
    {
        Queue::fake();

        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $projectServer = $project->projectServers()->create([
            'server_id' => $server->id, 'branch' => 'main', 'deployment_path' => '/var/www/app', 'auto_deploy' => true,
        ]);

        app(BranchPoller::class)->pollProject($project);
        $this->pushNewCommit();
        app(BranchPoller::class)->forcePoll($project);

        Queue::assertPushed(DeployProjectJob::class);
        $this->assertDatabaseHas('deployments', ['project_server_id' => $projectServer->id]);
    }

    public function test_a_server_without_auto_deploy_is_not_dispatched(): void
    {
        Queue::fake();

        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);
        $server = Server::create([
            'name' => 'Prod', 'host' => 'example.com', 'port' => 22, 'auth_type' => 'key',
            'username' => 'deploy', 'private_key' => 'k',
        ]);
        $project->projectServers()->create([
            'server_id' => $server->id, 'branch' => 'main', 'deployment_path' => '/var/www/app', 'auto_deploy' => false,
        ]);

        app(BranchPoller::class)->pollProject($project);
        $this->pushNewCommit();
        app(BranchPoller::class)->forcePoll($project);

        Queue::assertNotPushed(DeployProjectJob::class);
    }
}
