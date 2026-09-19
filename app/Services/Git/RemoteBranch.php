<?php

namespace App\Services\Git;

use Illuminate\Support\Carbon;

final class RemoteBranch
{
    public function __construct(
        public readonly string $name,
        public readonly string $sha,
        public readonly ?Carbon $committedAt,
    ) {}
}
