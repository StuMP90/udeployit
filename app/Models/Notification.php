<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property NotificationType $type
 * @property int|null $project_id
 * @property int|null $deployment_id
 * @property string $message
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'project_id', 'deployment_id', 'message', 'read_at'])]
class Notification extends Model
{
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'read_at' => 'datetime',
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
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
