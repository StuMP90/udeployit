<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class ProjectTemplate extends Model
{
    /**
     * @return HasMany<TemplateServer, $this>
     */
    public function templateServers(): HasMany
    {
        return $this->hasMany(TemplateServer::class);
    }
}
