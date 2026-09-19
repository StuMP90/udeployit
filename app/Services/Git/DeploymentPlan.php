<?php

namespace App\Services\Git;

use App\Enums\DeploymentType;
use Illuminate\Support\Facades\File;

final class DeploymentPlan
{
    private function __construct(
        public readonly DeploymentType $type,
        public readonly string $targetSha,
        public readonly ?string $extractedPath,
        /** @var array<int, FileChange> */
        public readonly array $changes,
    ) {}

    public static function full(string $targetSha, string $extractedPath): self
    {
        return new self(DeploymentType::Full, $targetSha, $extractedPath, []);
    }

    /**
     * @param  array<int, FileChange>  $changes
     */
    public static function incremental(string $targetSha, array $changes): self
    {
        return new self(DeploymentType::Incremental, $targetSha, null, $changes);
    }

    public function fileCount(): int
    {
        if ($this->extractedPath === null) {
            return count(array_filter($this->changes, fn (FileChange $change) => $change->action === 'put'));
        }

        return is_dir($this->extractedPath) ? count(File::allFiles($this->extractedPath)) : 0;
    }

    public function deleteCount(): int
    {
        return count(array_filter($this->changes, fn (FileChange $change) => $change->action === 'delete'));
    }

    public function cleanup(): void
    {
        if ($this->extractedPath && is_dir($this->extractedPath)) {
            File::deleteDirectory($this->extractedPath);
        }
    }
}
