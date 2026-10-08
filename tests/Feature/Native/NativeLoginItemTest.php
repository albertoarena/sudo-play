<?php

declare(strict_types=1);

use App\Native\NativeLoginItem;
use App\SettingsWindow\LoginItem;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('asks Electron to open the app at login, or not', function (bool $open): void {
    Http::fake();

    (new NativeLoginItem)->set($open);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'app/open-at-login')
        && $request->method() === 'POST'
        && $request['open'] === $open);
    Http::assertSentCount(1);
})->with([
    'on' => [true],
    'off' => [false],
]);

it('is the login item the app uses', function (): void {
    expect(app(LoginItem::class))->toBeInstanceOf(NativeLoginItem::class);
});
