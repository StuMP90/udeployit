<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $private_key
 * @property string|null $public_key
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'private_key', 'public_key', 'is_default'])]
#[Hidden(['private_key'])]
class GithubCredential extends Model
{
    protected function casts(): array
    {
        return [
            'private_key' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $credential) {
            if ($credential->is_default) {
                static::query()
                    ->when($credential->exists, fn ($query) => $query->whereKeyNot($credential->id))
                    ->update(['is_default' => false]);
            }
        });
    }
}
