<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

it('serves the menu bar page', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('MenuBar'));
});
