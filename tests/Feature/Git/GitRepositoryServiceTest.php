<?php

namespace Tests\Feature\Git;

use App\Models\GithubCredential;
use App\Models\Project;
use App\Services\Git\GitRepositoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class GitRepositoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $originPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originPath = sys_get_temp_dir().'/udeployit-origin-'.uniqid();
        File::ensureDirectoryExists($this->originPath);

        foreach ([
            ['git', 'init', '--initial-branch=main', $this->originPath],
            ['git', '-C', $this->originPath, 'config', 'user.email', 'test@example.com'],
            ['git', '-C', $this->originPath, 'config', 'user.name', 'Test'],
        ] as $command) {
            (new Process($command))->mustRun();
        }

        File::put($this->originPath.'/file.txt', 'hello');
        (new Process(['git', '-C', $this->originPath, 'add', '.']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'commit', '-m', 'initial commit']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'checkout', '-b', 'develop']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'checkout', 'main']))->mustRun();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->originPath);

        parent::tearDown();
    }

    public function test_it_syncs_branches_from_a_local_repository(): void
    {
        GithubCredential::create(['name' => 'Default', 'private_key' => 'unused-for-local-repo', 'is_default' => true]);

        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);

        $branches = app(GitRepositoryService::class)->syncBranches($project);

        $this->assertCount(2, $branches);

        $main = $project->projectBranches()->where('branch_name', 'main')->firstOrFail();
        $this->assertSame($main->latest_sha, $main->created_snapshot_sha);
        $this->assertNotNull($main->latest_committed_at);
        $this->assertFalse($main->latest_committed_at->isFuture(), 'commit time must be stored as UTC, not a local time read as UTC');
    }

    public function test_a_second_sync_keeps_the_original_creation_snapshot(): void
    {
        GithubCredential::create(['name' => 'Default', 'private_key' => 'unused-for-local-repo', 'is_default' => true]);

        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);

        app(GitRepositoryService::class)->syncBranches($project);
        $originalSnapshot = $project->projectBranches()->where('branch_name', 'main')->firstOrFail()->created_snapshot_sha;

        File::put($this->originPath.'/file.txt', 'updated');
        (new Process(['git', '-C', $this->originPath, 'add', '.']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'commit', '-m', 'second commit']))->mustRun();

        app(GitRepositoryService::class)->syncBranches($project);
        $main = $project->projectBranches()->where('branch_name', 'main')->firstOrFail();

        $this->assertSame($originalSnapshot, $main->created_snapshot_sha);
        $this->assertNotSame($originalSnapshot, $main->latest_sha);
    }
}
