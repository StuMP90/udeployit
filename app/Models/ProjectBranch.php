<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $branch_name
 * @property string $latest_sha
 * @property Carbon|null $latest_committed_at
 * @property string $created_snapshot_sha
 * @property Carbon|null $last_checked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'branch_name', 'latest_sha', 'latest_committed_at', 'created_snapshot_sha', 'last_checked_at'])]
class ProjectBranch extends Model
{
    protected function casts(): array
    {
        return [
            'latest_committed_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
