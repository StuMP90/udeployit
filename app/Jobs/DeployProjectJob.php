<?php

namespace App\Jobs;

use App\Enums\DeploymentScriptType;
use App\Enums\DeploymentStatus;
use App\Enums\DeploymentType;
use App\Enums\ScriptFailureAction;
use App\Exceptions\DeploymentAbortedException;
use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Services\Git\GitDiffService;
use App\Services\Ssh\SftpDeployerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use phpseclib3\Net\SFTP;
use Throwable;

class DeployProjectJob implements ShouldQueue
{
    use Queueable;

    /**
     * Script timeouts are capped at 3600s (see DeploymentScript validation). This must stay
     * comfortably above that, and the queue worker's own --timeout (and retry_after) must in
     * turn stay above this — see README for the required `queue:work` configuration.
     */
    public int $timeout = 3660;

    public int $tries = 1;

    public function __construct(public readonly int $deploymentId) {}

    public function handle(GitDiffService $diffService, SftpDeployerService $deployer): void
    {
        $deployment = Deployment::with(['project.deploymentScripts', 'projectServer.server'])->findOrFail($this->deploymentId);

        $deployment->update(['status' => DeploymentStatus::Running, 'started_at' => now()]);
        $deployment->log('setup', 'Deployment started.');

        $plan = null;

        try {
            $project = $deployment->project;
            $projectServer = $deployment->projectServer;
            $server = $projectServer->server;

            $plan = $diffService->plan($project, $deployment->previous_sha, $deployment->commit_sha);

            $deployment->update(['type' => $plan->type]);
            $deployment->log('plan', $plan->type === DeploymentType::Full
                ? 'Performing a full deploy.'
                : count($plan->changes).' file(s) changed.');

            $sftp = $deployer->connect($server);
            $deployment->log('setup', "Connected to \"{$server->name}\".");

            $this->runScriptIfPresent($project->deploymentScripts, DeploymentScriptType::Before, $deployment, $deployer, $sftp, 'before');

            $deployment->log('transfer', 'Uploading files...');

            if ($plan->extractedPath !== null) {
                $deployer->uploadDirectory($sftp, $plan->extractedPath, (string) $projectServer->deployment_path);
            } else {
                $deployer->applyChanges(
                    $sftp,
                    (string) $projectServer->deployment_path,
                    $plan->changes,
                    fn (string $path) => $diffService->content($project, $plan->targetSha, $path),
                );
            }

            $deployment->log('transfer', 'Files uploaded.');

            $this->runScriptIfPresent($project->deploymentScripts, DeploymentScriptType::After, $deployment, $deployer, $sftp, 'after');

            $projectServer->update([
                'last_deployed_sha' => $deployment->commit_sha,
                'last_deployed_at' => now(),
            ]);

            $deployment->update(['status' => DeploymentStatus::Success, 'finished_at' => now()]);
            $deployment->log('finish', 'Deployment succeeded.');
        } catch (Throwable $e) {
            $deployment->update(['status' => DeploymentStatus::Failed, 'finished_at' => now()]);
            $deployment->log('finish', 'Deployment failed: '.$e->getMessage(), 'error');
        } finally {
            $plan?->cleanup();
        }
    }

    /**
     * @param  Collection<int, DeploymentScript>  $scripts
     */
    private function runScriptIfPresent(
        $scripts,
        DeploymentScriptType $type,
        Deployment $deployment,
        SftpDeployerService $deployer,
        SFTP $sftp,
        string $stage,
    ): void {
        $script = $scripts->firstWhere('type', $type);

        if (! $script) {
            return;
        }

        $deployment->log($stage, 'Running: '.$script->command);

        $result = $deployer->runScript($sftp, $script->command, $script->timeout_seconds);

        $deployment->log($stage, $result->output !== '' ? $result->output : '(no output)');

        if (! $result->successful) {
            $message = "Script exited with status {$result->exitStatus}.";

            if ($script->on_failure === ScriptFailureAction::Abort) {
                throw new DeploymentAbortedException($message);
            }

            $deployment->log($stage, $message.' Continuing anyway.', 'error');
        }
    }
}
