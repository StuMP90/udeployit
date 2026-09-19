<?php

namespace App\Services\Ssh;

final class ScriptResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $output,
        public readonly ?int $exitStatus,
    ) {}
}
