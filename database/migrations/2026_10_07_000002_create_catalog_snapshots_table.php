<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The active catalog, as one JSON payload in one row.
 *
 * Tables for genres, moods, playlists and the moods on each playlist would be
 * four tables and a pivot, and nothing would ever query them. The rotation is a
 * pure function over the whole list, so the app reads the catalog whole, uses it
 * whole and replaces it whole. One column says that.
 *
 * `synced_at` is the footer date from requirements 4.4: the moment of the last
 * successful download. It is null for the bundled copy, which never was
 * downloaded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->json('payload');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_snapshots');
    }
};
