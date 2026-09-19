<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use App\Enums\DeploymentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $project_server_id
 * @property int|null $triggered_by
 * @property string $commit_sha
 * @property string|null $previous_sha
 * @property DeploymentType $type
 * @property DeploymentStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'project_server_id', 'triggered_by', 'commit_sha', 'previous_sha', 'type', 'status', 'started_at', 'finished_at'])]
class Deployment extends Model
{
    protected function casts(): array
    {
        return [
            'type' => DeploymentType::class,
            'status' => DeploymentStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<ProjectServer, $this>
     */
    public function projectServer(): BelongsTo
    {
        return $this->belongsTo(ProjectServer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * @return HasMany<DeploymentLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(DeploymentLog::class);
    }

    public function log(string $stage, string $message, string $level = 'info'): DeploymentLog
    {
        return $this->logs()->create([
            'stage' => $stage,
            'level' => $level,
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
