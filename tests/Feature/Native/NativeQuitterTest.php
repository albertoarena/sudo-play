<?php

declare(strict_types=1);

use App\MenuBar\Quitter;
use App\Native\NativeQuitter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('asks Electron to quit the app', function (): void {
    Http::fake();

    (new NativeQuitter)->quit();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'app/quit'));
    Http::assertSentCount(1);
});

it('is the quitter the app uses', function (): void {
    expect(app(Quitter::class))->toBeInstanceOf(NativeQuitter::class);
});
