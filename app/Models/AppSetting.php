<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $poll_interval_seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['poll_interval_seconds'])]
class AppSetting extends Model
{
    public const int DEFAULT_POLL_INTERVAL_SECONDS = 30;

    public static function current(): self
    {
        // firstOrCreate([]) would insert using the DB column default, but the in-memory
        // model wouldn't know that value without an extra round-trip — set it explicitly.
        return static::query()->firstOrCreate([], ['poll_interval_seconds' => self::DEFAULT_POLL_INTERVAL_SECONDS]);
    }
}
