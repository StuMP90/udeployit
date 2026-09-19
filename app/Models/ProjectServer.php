<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $server_id
 * @property string|null $branch
 * @property string|null $deployment_path
 * @property string|null $last_deployed_sha
 * @property Carbon|null $last_deployed_at
 * @property bool $auto_deploy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'server_id', 'branch', 'deployment_path', 'last_deployed_sha', 'last_deployed_at', 'auto_deploy'])]
class ProjectServer extends Model
{
    protected function casts(): array
    {
        return [
            'last_deployed_at' => 'datetime',
            'auto_deploy' => 'boolean',
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
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
