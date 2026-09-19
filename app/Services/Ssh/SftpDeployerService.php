<?php

namespace App\Services\Ssh;

use App\Enums\ServerAuthType;
use App\Exceptions\SshDeploymentException;
use App\Models\Server;
use App\Services\Git\FileChange;
use Closure;
use Illuminate\Support\Facades\File;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

class SftpDeployerService
{
    public function connect(Server $server): SFTP
    {
        $sftp = new SFTP($server->host, $server->port, 10);

        $authenticated = $server->auth_type === ServerAuthType::Password
            ? $sftp->login($server->username, $server->password)
            : $sftp->login($server->username, PublicKeyLoader::load($server->private_key, $server->passphrase ?: ''));

        if (! $authenticated) {
            throw new SshDeploymentException("Could not authenticate with \"{$server->name}\".");
        }

        return $sftp;
    }

    public function uploadDirectory(SFTP $sftp, string $localPath, string $remotePath): void
    {
        foreach (File::allFiles($localPath) as $file) {
            $relative = $file->getRelativePathname();

            $this->putFile($sftp, rtrim($remotePath, '/').'/'.$relative, $file->getContents());
        }
    }

    /**
     * @param  array<int, FileChange>  $changes
     */
    public function applyChanges(SFTP $sftp, string $remotePath, array $changes, Closure $contentResolver, ?Closure $onChange = null): void
    {
        foreach ($changes as $change) {
            $remote = rtrim($remotePath, '/').'/'.$change->path;

            if ($change->action === 'delete') {
                $sftp->delete($remote);
            } else {
                $this->putFile($sftp, $remote, $contentResolver($change->path));
            }

            if ($onChange) {
                $onChange($change);
            }
        }
    }

    public function runScript(SFTP $sftp, string $command, int $timeoutSeconds): ScriptResult
    {
        $sftp->setTimeout($timeoutSeconds);

        $output = $sftp->exec($command);
        $exitStatus = $sftp->getExitStatus();

        return new ScriptResult(
            successful: $exitStatus === 0,
            output: (string) $output,
            exitStatus: $exitStatus === false ? null : $exitStatus,
        );
    }

    private function putFile(SFTP $sftp, string $remotePath, string $contents): void
    {
        $this->ensureRemoteDirectory($sftp, dirname($remotePath));

        if (! $sftp->put($remotePath, $contents)) {
            throw new SshDeploymentException("Failed to upload \"{$remotePath}\".");
        }
    }

    private function ensureRemoteDirectory(SFTP $sftp, string $directory): void
    {
        if ($directory === '.' || $directory === '/' || $sftp->is_dir($directory)) {
            return;
        }

        $sftp->mkdir($directory, -1, true);
    }
}
