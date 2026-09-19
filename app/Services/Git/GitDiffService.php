<?php

namespace App\Services\Git;

use App\Exceptions\GitRepositoryException;
use App\Models\Project;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class GitDiffService
{
    /**
     * The SHA-1 of the empty tree — a constant in every git repository.
     * Diffing against it yields every file in the target tree as "added".
     */
    public const string EMPTY_TREE_SHA = '4b825dc642cb6eb9a060e54bf8d69288fbee4904';

    public function __construct(private readonly GitRepositoryService $repositories) {}

    public function plan(Project $project, ?string $baseSha, string $targetSha): DeploymentPlan
    {
        $this->repositories->ensureMirror($project);

        $path = $this->repositories->mirrorPath($project);

        if ($baseSha === null || ! $this->isAncestor($path, $baseSha, $targetSha)) {
            return DeploymentPlan::full($targetSha, $this->extractArchive($path, $targetSha));
        }

        return DeploymentPlan::incremental($targetSha, $this->nameStatus($path, $baseSha, $targetSha));
    }

    public function content(Project $project, string $sha, string $path): string
    {
        $mirrorPath = $this->repositories->mirrorPath($project);

        return $this->run(['git', '--git-dir='.$mirrorPath, 'show', "{$sha}:{$path}"])->getOutput();
    }

    private function isAncestor(string $mirrorPath, string $baseSha, string $targetSha): bool
    {
        $process = new Process(['git', '--git-dir='.$mirrorPath, 'merge-base', '--is-ancestor', $baseSha, $targetSha]);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * @return array<int, FileChange>
     */
    private function nameStatus(string $mirrorPath, string $baseSha, string $targetSha): array
    {
        $output = $this->run([
            'git', '--git-dir='.$mirrorPath, 'diff', '--name-status', '--find-renames', '-z', $baseSha, $targetSha,
        ])->getOutput();

        $tokens = array_values(array_filter(explode("\0", $output), fn ($token) => $token !== ''));
        $changes = [];

        for ($i = 0; $i < count($tokens); $i++) {
            $status = $tokens[$i];

            if (Str::startsWith($status, ['R', 'C'])) {
                $oldPath = $tokens[++$i];
                $newPath = $tokens[++$i];

                $changes[] = FileChange::delete($oldPath);
                $changes[] = FileChange::put($newPath);

                continue;
            }

            $path = $tokens[++$i];

            $changes[] = match ($status) {
                'D' => FileChange::delete($path),
                default => FileChange::put($path),
            };
        }

        return $changes;
    }

    private function extractArchive(string $mirrorPath, string $sha): string
    {
        $tempDir = sys_get_temp_dir().'/udeployit-archive-'.uniqid();
        $archivePath = $tempDir.'.tar';

        File::ensureDirectoryExists($tempDir);

        $this->run(['git', '--git-dir='.$mirrorPath, 'archive', '--format=tar', '-o', $archivePath, $sha]);
        $this->run(['tar', '-xf', $archivePath, '-C', $tempDir]);

        File::delete($archivePath);

        return $tempDir;
    }

    /**
     * @param  array<int, string>  $command
     */
    private function run(array $command): Process
    {
        $process = new Process($command);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new GitRepositoryException('Git command failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return $process;
    }
}
