<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per setting the user has changed. A setting that was never touched
 * has no row and answers with its default, so the defaults live in code and
 * change with an update rather than being frozen into the database on install.
 *
 * The value is JSON, so a boolean comes back a boolean rather than the string
 * "1". `SettingsStore` is the only thing that reads or writes this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
