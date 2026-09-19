<?php

namespace App\Models;

use App\Enums\DeploymentLogLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $deployment_id
 * @property string $stage
 * @property DeploymentLogLevel $level
 * @property string $message
 * @property Carbon|null $created_at
 */
#[Fillable(['deployment_id', 'stage', 'level', 'message', 'created_at'])]
class DeploymentLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'level' => DeploymentLogLevel::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
