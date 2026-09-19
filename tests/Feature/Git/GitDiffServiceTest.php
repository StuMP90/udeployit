<?php

namespace Tests\Feature\Git;

use App\Enums\DeploymentType;
use App\Models\GithubCredential;
use App\Models\Project;
use App\Services\Git\GitDiffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class GitDiffServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $originPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originPath = sys_get_temp_dir().'/udeployit-diff-origin-'.uniqid();
        File::ensureDirectoryExists($this->originPath);

        foreach ([
            ['git', 'init', '--initial-branch=main', $this->originPath],
            ['git', '-C', $this->originPath, 'config', 'user.email', 'test@example.com'],
            ['git', '-C', $this->originPath, 'config', 'user.name', 'Test'],
        ] as $command) {
            (new Process($command))->mustRun();
        }

        GithubCredential::create(['name' => 'Default', 'private_key' => 'unused-for-local-repo', 'is_default' => true]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->originPath);

        parent::tearDown();
    }

    private function commit(string $file, string $contents, string $message): string
    {
        File::put($this->originPath.'/'.$file, $contents);
        (new Process(['git', '-C', $this->originPath, 'add', '.']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'commit', '-m', $message]))->mustRun();

        return trim((new Process(['git', '-C', $this->originPath, 'rev-parse', 'HEAD']))->mustRun()->getOutput());
    }

    public function test_a_true_first_deploy_is_a_full_deploy(): void
    {
        $sha = $this->commit('a.txt', 'hello', 'first');
        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);

        $plan = app(GitDiffService::class)->plan($project, null, $sha);

        $this->assertSame(DeploymentType::Full, $plan->type);
        $this->assertNotNull($plan->extractedPath);
        $this->assertFileExists($plan->extractedPath.'/a.txt');

        $plan->cleanup();
        $this->assertDirectoryDoesNotExist($plan->extractedPath);
    }

    public function test_a_deploy_from_a_known_ancestor_is_incremental(): void
    {
        $first = $this->commit('a.txt', 'v1', 'first');
        File::put($this->originPath.'/b.txt', 'new file');
        File::delete($this->originPath.'/a.txt');
        (new Process(['git', '-C', $this->originPath, 'add', '-A']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'commit', '-m', 'second']))->mustRun();
        $second = trim((new Process(['git', '-C', $this->originPath, 'rev-parse', 'HEAD']))->mustRun()->getOutput());

        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);

        $plan = app(GitDiffService::class)->plan($project, $first, $second);

        $this->assertSame(DeploymentType::Incremental, $plan->type);

        $actions = collect($plan->changes)->mapWithKeys(fn ($c) => [$c->path => $c->action]);
        $this->assertSame('delete', $actions->get('a.txt'));
        $this->assertSame('put', $actions->get('b.txt'));
    }

    public function test_a_rename_is_treated_as_a_delete_and_a_put(): void
    {
        $first = $this->commit('old-name.txt', str_repeat('content that is long enough to be detected as a rename ', 5), 'first');
        (new Process(['git', '-C', $this->originPath, 'mv', 'old-name.txt', 'new-name.txt']))->mustRun();
        (new Process(['git', '-C', $this->originPath, 'commit', '-m', 'rename']))->mustRun();
        $second = trim((new Process(['git', '-C', $this->originPath, 'rev-parse', 'HEAD']))->mustRun()->getOutput());

        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);

        $plan = app(GitDiffService::class)->plan($project, $first, $second);

        $actions = collect($plan->changes)->mapWithKeys(fn ($c) => [$c->path => $c->action]);
        $this->assertSame('delete', $actions->get('old-name.txt'));
        $this->assertSame('put', $actions->get('new-name.txt'));
    }

    public function test_an_unreachable_base_falls_back_to_a_full_deploy(): void
    {
        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);
        $this->commit('a.txt', 'v1', 'first');
        $target = trim((new Process(['git', '-C', $this->originPath, 'rev-parse', 'HEAD']))->mustRun()->getOutput());

        $plan = app(GitDiffService::class)->plan($project, '0000000000000000000000000000000000000000', $target);

        $this->assertSame(DeploymentType::Full, $plan->type);
        $plan->cleanup();
    }

    public function test_content_reads_a_files_contents_at_a_given_sha(): void
    {
        $sha = $this->commit('a.txt', 'hello world', 'first');
        $project = Project::create(['name' => 'App', 'repo_url' => $this->originPath]);

        app(GitDiffService::class)->plan($project, null, $sha)->cleanup();

        $this->assertSame('hello world', app(GitDiffService::class)->content($project, $sha, 'a.txt'));
    }
}
