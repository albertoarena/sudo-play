<?php

declare(strict_types=1);

use App\MenuBar\SettingsWindow;
use App\Native\NativeSettingsWindow;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * NativePHP ships a fake for windows, but it records only the id. Faking HTTP
 * shows the whole window that would have reached Electron.
 */

it('opens the settings page in a window of its own', function (): void {
    Http::fake();

    (new NativeSettingsWindow)->open();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'window/open')
        && $request['id'] === NativeSettingsWindow::ID
        && $request['url'] === route('settings')
        && $request['title'] === 'Sudo Play Settings'
        && $request['width'] === NativeSettingsWindow::WIDTH
        && $request['height'] === NativeSettingsWindow::HEIGHT
        && $request['resizable'] === false
        && $request['minimizable'] === false
        && $request['maximizable'] === false
        && $request['fullscreenable'] === false);
});

// NativePHP turns the inspector on whenever app.debug is true. In the 440px
// window it takes half the width, so development would not look like the app.
it('opens without the inspector, even in debug mode', function (): void {
    config(['app.debug' => true]);
    Http::fake();

    (new NativeSettingsWindow)->open();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'window/open')
        && $request['showDevTools'] === false);
});

it('is the settings window the app uses', function (): void {
    expect(app(SettingsWindow::class))->toBeInstanceOf(NativeSettingsWindow::class);
});
