<?php

namespace App\Services\Ssh;

use App\Enums\ServerAuthType;
use App\Models\GithubCredential;
use App\Models\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use Throwable;

class ConnectionTester
{
    public function testServer(Server $server): ConnectionTestResult
    {
        try {
            $ssh = new SSH2($server->host, $server->port, 10);

            $authenticated = $server->auth_type === ServerAuthType::Password
                ? $ssh->login($server->username, $server->password)
                : $ssh->login($server->username, PublicKeyLoader::load($server->private_key, $server->passphrase ?: ''));

            return $authenticated
                ? ConnectionTestResult::success('Connected and authenticated successfully.')
                : ConnectionTestResult::failure('Connected, but authentication failed. Check the credentials.');
        } catch (Throwable $e) {
            return ConnectionTestResult::failure('Could not connect: '.$e->getMessage());
        }
    }

    public function testGithubCredential(GithubCredential $credential): ConnectionTestResult
    {
        try {
            $ssh = new SSH2('github.com', 22, 10);
            $key = PublicKeyLoader::load($credential->private_key);

            return $ssh->login('git', $key)
                ? ConnectionTestResult::success('GitHub accepted this key.')
                : ConnectionTestResult::failure('GitHub did not accept this key.');
        } catch (Throwable $e) {
            return ConnectionTestResult::failure('Could not connect to GitHub: '.$e->getMessage());
        }
    }
}
