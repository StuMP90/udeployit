<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $repo_url
 * @property int|null $github_credential_id
 * @property int|null $project_template_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'repo_url', 'github_credential_id', 'project_template_id', 'created_by'])]
class Project extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $project) {
            if (blank($project->slug)) {
                $project->slug = static::uniqueSlug($project->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $suffix = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$suffix;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<GithubCredential, $this>
     */
    public function githubCredential(): BelongsTo
    {
        return $this->belongsTo(GithubCredential::class);
    }

    /**
     * @return BelongsTo<ProjectTemplate, $this>
     */
    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ProjectBranch, $this>
     */
    public function projectBranches(): HasMany
    {
        return $this->hasMany(ProjectBranch::class);
    }

    /**
     * @return HasMany<ProjectServer, $this>
     */
    public function projectServers(): HasMany
    {
        return $this->hasMany(ProjectServer::class);
    }

    /**
     * @return HasMany<DeploymentScript, $this>
     */
    public function deploymentScripts(): HasMany
    {
        return $this->hasMany(DeploymentScript::class);
    }

    /**
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
