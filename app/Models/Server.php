<?php

namespace App\Models;

use App\Enums\ServerAuthType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property ServerAuthType $auth_type
 * @property string $username
 * @property string|null $password
 * @property string|null $private_key
 * @property string|null $passphrase
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'host', 'port', 'auth_type', 'username', 'password', 'private_key', 'passphrase'])]
#[Hidden(['password', 'private_key', 'passphrase'])]
class Server extends Model
{
    protected function casts(): array
    {
        return [
            'auth_type' => ServerAuthType::class,
            'password' => 'encrypted',
            'private_key' => 'encrypted',
            'passphrase' => 'encrypted',
        ];
    }

    /**
     * @return HasMany<TemplateServer, $this>
     */
    public function templateServers(): HasMany
    {
        return $this->hasMany(TemplateServer::class);
    }
}
