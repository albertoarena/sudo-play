<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * The stored catalog. Reached only through `DatabaseCatalogStore`, so nothing
 * else in the app knows the catalog is a JSON column.
 *
 * @property array<array-key, mixed> $payload
 * @property CarbonImmutable|null $synced_at
 */
final class CatalogSnapshot extends Model
{
    protected $fillable = ['payload', 'synced_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'synced_at' => 'immutable_datetime',
        ];
    }
}
