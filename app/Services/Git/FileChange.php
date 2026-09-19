<?php

namespace App\Services\Git;

final class FileChange
{
    public function __construct(
        public readonly string $action,
        public readonly string $path,
    ) {}

    public static function put(string $path): self
    {
        return new self('put', $path);
    }

    public static function delete(string $path): self
    {
        return new self('delete', $path);
    }
}
