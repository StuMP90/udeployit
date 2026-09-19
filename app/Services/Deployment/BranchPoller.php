<?php

namespace App\Services\Deployment;

use App\Enums\NotificationType;
use App\Exceptions\GitRepositoryException;
use App\Models\AppSetting;
use App\Models\Notification;
use App\Models\Project;
use App\Services\Git\GitRepositoryService;
use Illuminate\Support\Facades\Cache;

class BranchPoller
{
    public function __construct(
        private readonly GitRepositoryService $repositories,
        private readonly DeploymentDispatcher $dispatcher,
    ) {}

    /**
     * Sync a project's branches, but only if its per-project cache window has
     * elapsed — so multiple open dashboards don't multiply GitHub API calls.
     */
    public function pollProject(Project $project): void
    {
        $cacheKey = "project-poll:{$project->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, AppSetting::current()->poll_interval_seconds);

        $this->syncAndDispatchAutoDeploys($project);
    }

    /**
     * Sync immediately, ignoring the cache window (used by "refresh now").
     */
    public function forcePoll(Project $project): void
    {
        $this->syncAndDispatchAutoDeploys($project);

        Cache::put("project-poll:{$project->id}", true, AppSetting::current()->poll_interval_seconds);
    }

    private function syncAndDispatchAutoDeploys(Project $project): void
    {
        $previousShas = $project->projectBranches()->pluck('latest_sha', 'branch_name');

        try {
            $this->repositories->syncBranches($project);
        } catch (GitRepositoryException) {
            return;
        }

        $project->unsetRelation('projectBranches');

        foreach ($project->projectBranches as $branch) {
            $previousSha = $previousShas->get($branch->branch_name);

            if ($previousSha === null || $previousSha === $branch->latest_sha) {
                continue;
            }

            Notification::create([
                'type' => NotificationType::BranchUpdated,
                'project_id' => $project->id,
                'message' => "\"{$project->name}\" branch \"{$branch->branch_name}\" was updated.",
            ]);

            $autoDeployServers = $project->projectServers()
                ->where('branch', $branch->branch_name)
                ->where('auto_deploy', true)
                ->get();

            foreach ($autoDeployServers as $projectServer) {
                $this->dispatcher->dispatch($projectServer, $branch, 'incremental');
            }
        }
    }
}
