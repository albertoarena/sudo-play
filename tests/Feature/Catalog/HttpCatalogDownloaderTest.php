<?php

declare(strict_types=1);

use App\Catalog\CatalogDownloader;
use App\Catalog\HttpCatalogDownloader;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const CATALOG_URL = 'https://raw.githubusercontent.com/albertoarena/sudo-play/catalog-live/catalog/playlists.yaml';

function downloader(): HttpCatalogDownloader
{
    return new HttpCatalogDownloader;
}

it('hands back the body it downloaded', function (): void {
    Http::fake([CATALOG_URL => Http::response("version: 1\nplaylists: []\n")]);

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeTrue()
        ->and($result->body)->toBe("version: 1\nplaylists: []\n")
        ->and($result->failure)->toBeNull();
});

it('asks for the url it was given, and nothing else', function (): void {
    Http::fake([CATALOG_URL => Http::response('version: 1')]);

    downloader()->download(CATALOG_URL);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === CATALOG_URL && $request->method() === 'GET');
});

it('gives up after five seconds', function (): void {
    expect(HttpCatalogDownloader::TIMEOUT_SECONDS)->toBe(5);
});

it('treats a connection failure as a failure, with a reason', function (): void {
    Http::fake(fn (): never => throw new ConnectionException('Could not resolve host'));

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeFalse()
        ->and($result->body)->toBeNull()
        ->and($result->failure)->toContain('Could not resolve host');
});

it('treats a timeout as a failure, like any other network problem', function (): void {
    Http::fake(fn (): never => throw new ConnectionException('Operation timed out after 5000 milliseconds'));

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeFalse()
        ->and($result->failure)->toContain('timed out');
});

it('refuses a response that is not a success', function (int $status): void {
    Http::fake([CATALOG_URL => Http::response('nope', $status)]);

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeFalse()
        ->and($result->body)->toBeNull()
        ->and($result->failure)->toContain((string) $status);
})->with([
    'not found, which is also a private repository' => [404],
    'server error' => [500],
    'rate limited' => [429],
]);

it('refuses a body over one megabyte', function (): void {
    Http::fake([CATALOG_URL => Http::response(str_repeat('a', HttpCatalogDownloader::SIZE_LIMIT_BYTES + 1))]);

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeFalse()
        ->and($result->body)->toBeNull()
        ->and($result->failure)->toContain('1 MB');
});

it('accepts a body right on the limit', function (): void {
    Http::fake([CATALOG_URL => Http::response(str_repeat('a', HttpCatalogDownloader::SIZE_LIMIT_BYTES))]);

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeTrue()
        ->and($result->body)->toHaveLength(HttpCatalogDownloader::SIZE_LIMIT_BYTES);
});

it('refuses a response that announces it is too big, without reading it', function (): void {
    Http::fake([
        CATALOG_URL => Http::response('version: 1', 200, [
            'Content-Length' => (string) (HttpCatalogDownloader::SIZE_LIMIT_BYTES + 1),
        ]),
    ]);

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeFalse()
        ->and($result->failure)->toContain('1 MB');
});

it('accepts an empty body and leaves the parser to reject it', function (): void {
    Http::fake([CATALOG_URL => Http::response('')]);

    $result = downloader()->download(CATALOG_URL);

    expect($result->succeeded())->toBeTrue()
        ->and($result->body)->toBe('');
});

it('resolves the http downloader for the rest of the app', function (): void {
    expect(app(CatalogDownloader::class))->toBeInstanceOf(HttpCatalogDownloader::class);
});
