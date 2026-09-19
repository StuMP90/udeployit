<?php

namespace App\Services\Deployment;

use App\Enums\DeploymentStatus;
use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\ProjectBranch;
use App\Models\ProjectServer;

class DeploymentDispatcher
{
    public function dispatch(
        ProjectServer $projectServer,
        ProjectBranch $projectBranch,
        string $mode = 'incremental',
        ?int $triggeredBy = null,
    ): Deployment {
        $previousSha = match (true) {
            $projectServer->last_deployed_sha !== null => $projectServer->last_deployed_sha,
            $mode === 'incremental' => $projectBranch->created_snapshot_sha,
            default => null,
        };

        $deployment = Deployment::create([
            'project_id' => $projectServer->project_id,
            'project_server_id' => $projectServer->id,
            'triggered_by' => $triggeredBy,
            'commit_sha' => $projectBranch->latest_sha,
            'previous_sha' => $previousSha,
            'type' => $previousSha === null ? 'full' : 'incremental',
            'status' => DeploymentStatus::Pending,
        ]);

        DeployProjectJob::dispatch($deployment->id);

        return $deployment;
    }
}
