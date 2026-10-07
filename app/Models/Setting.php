<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One stored setting. Reached only through `DatabaseSettingsStore`, per
 * requirements 11.1, so nothing else in the app knows this table exists.
 *
 * The key is the primary key, which is what makes a write an upsert rather
 * than a lookup followed by an insert.
 */
final class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];
}
