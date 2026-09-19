<?php

namespace App\Services\Git;

use App\Exceptions\GitRepositoryException;
use App\Models\GithubCredential;
use App\Models\Project;
use App\Models\ProjectBranch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GitRepositoryService
{
    public function resolveCredential(Project $project): GithubCredential
    {
        return $project->githubCredential
            ?? GithubCredential::where('is_default', true)->first()
            ?? throw new GitRepositoryException('No GitHub credential is configured. Add one, or mark one as the global default.');
    }

    public function mirrorPath(Project $project): string
    {
        return rtrim(config('udeployit.repos_path'), '/')."/{$project->id}";
    }

    public function ensureMirror(Project $project): void
    {
        $path = $this->mirrorPath($project);
        $keyFile = new SshKeyFile($this->resolveCredential($project)->private_key);

        File::ensureDirectoryExists(dirname($path));

        if (is_dir($path)) {
            $this->run(['git', '--git-dir='.$path, 'fetch', '--prune', 'origin'], $keyFile);

            return;
        }

        $this->run(['git', 'clone', '--mirror', $project->repo_url, $path], $keyFile);
    }

    /**
     * @return array<int, RemoteBranch>
     */
    public function branches(Project $project): array
    {
        $this->ensureMirror($project);

        $path = $this->mirrorPath($project);
        $format = '%(refname:short)%09%(objectname)%09%(committerdate:iso-strict)';
        $output = $this->run(['git', '--git-dir='.$path, 'for-each-ref', 'refs/heads/', '--format='.$format])->getOutput();

        $branches = [];

        foreach (explode("\n", trim($output)) as $line) {
            if ($line === '') {
                continue;
            }

            [$name, $sha, $committedAt] = explode("\t", $line);

            $branches[] = new RemoteBranch($name, $sha, $committedAt !== '' ? Carbon::parse($committedAt) : null);
        }

        return $branches;
    }

    /**
     * @return array<int, ProjectBranch>
     */
    public function syncBranches(Project $project): array
    {
        $branches = $this->branches($project);
        $now = Carbon::now();

        return collect($branches)->map(function (RemoteBranch $branch) use ($project, $now) {
            $existing = $project->projectBranches()->where('branch_name', $branch->name)->first();

            return $project->projectBranches()->updateOrCreate(
                ['branch_name' => $branch->name],
                [
                    'latest_sha' => $branch->sha,
                    'latest_committed_at' => $branch->committedAt,
                    'created_snapshot_sha' => $existing->created_snapshot_sha ?? $branch->sha,
                    'last_checked_at' => $now,
                ],
            );
        })->all();
    }

    /**
     * @param  array<int, string>  $command
     */
    private function run(array $command, ?SshKeyFile $keyFile = null): Process
    {
        $env = ['GIT_TERMINAL_PROMPT' => '0'];

        if ($keyFile) {
            $env['GIT_SSH_COMMAND'] = 'ssh -i '.$keyFile->path()
                .' -o IdentitiesOnly=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null';
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new GitRepositoryException(
                'Git command failed: '.trim($process->getErrorOutput() ?: $process->getOutput()),
                previous: new ProcessFailedException($process),
            );
        }

        return $process;
    }
}
