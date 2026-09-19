<?php

namespace App\Services\Git;

class SshKeyFile
{
    private readonly string $path;

    public function __construct(string $privateKey)
    {
        $this->path = tempnam(sys_get_temp_dir(), 'udeployit-key-');

        file_put_contents($this->path, rtrim($privateKey)."\n");
        chmod($this->path, 0600);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function __destruct()
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }
}
