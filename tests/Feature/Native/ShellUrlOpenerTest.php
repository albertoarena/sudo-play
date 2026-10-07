<?php

declare(strict_types=1);

use App\Native\ShellUrlOpener;
use App\Player\PlayerInterface;
use App\Player\PlayerResolver;
use App\Player\UrlOpener;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * NativePHP ships no fake for `Shell`. Its client talks to Electron through the
 * Laravel HTTP client, so faking HTTP shows what would have reached Electron.
 */

it('asks Electron to open the link with the operating system', function (): void {
    Http::fake();

    (new ShellUrlOpener)->open('https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'shell/open-external')
        && $request['url'] === 'https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M');
    Http::assertSentCount(1);
});

it('resolves the shell opener for the rest of the app', function (): void {
    expect(app(UrlOpener::class))->toBeInstanceOf(ShellUrlOpener::class);
});

it('gives the rest of the app the resolver as its player', function (): void {
    expect(app(PlayerInterface::class))->toBeInstanceOf(PlayerResolver::class);
});
