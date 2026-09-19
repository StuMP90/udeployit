<?php

namespace App\Models;

use App\Enums\DeploymentScriptType;
use App\Enums\ScriptFailureAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property DeploymentScriptType $type
 * @property string $command
 * @property int $timeout_seconds
 * @property ScriptFailureAction $on_failure
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'type', 'command', 'timeout_seconds', 'on_failure'])]
class DeploymentScript extends Model
{
    protected function casts(): array
    {
        return [
            'type' => DeploymentScriptType::class,
            'on_failure' => ScriptFailureAction::class,
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
