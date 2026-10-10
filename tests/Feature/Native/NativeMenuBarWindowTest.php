<?php

declare(strict_types=1);

use App\MenuBar\MenuBarWindow;
use App\Native\NativeMenuBarWindow;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * `MenuBar::hide()` in nativephp/laravel 1.3.1 posts to `menu-bar/close`, a
 * route the Electron plugin 1.3.0 does not have. Its route is `menu-bar/hide`.
 */

it('asks Electron to hide the menu bar window, on the route Electron serves', function (): void {
    Http::fake();

    app(NativeMenuBarWindow::class)->hide();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'menu-bar/hide')
        && $request->method() === 'POST');
    Http::assertSentCount(1);
});

it('is the menu bar window the app uses', function (): void {
    expect(app(MenuBarWindow::class))->toBeInstanceOf(NativeMenuBarWindow::class);
});
