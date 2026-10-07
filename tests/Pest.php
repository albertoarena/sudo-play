<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Feature tests get the framework and a migrated in-memory database. Unit tests
// get neither, which is what keeps `app/Catalog/` and `app/Rotation/` honest
// about having no framework dependencies.
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
