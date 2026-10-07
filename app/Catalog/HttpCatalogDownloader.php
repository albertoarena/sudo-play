<?php

declare(strict_types=1);

namespace App\Catalog;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * Downloads the catalog with the Laravel HTTP client, under the two limits from
 * requirements 4.4: five seconds and one megabyte.
 *
 * The timeout matters because the app syncs at startup. A server that accepts
 * the connection and then says nothing would otherwise hold a PHP process open
 * for as long as it liked.
 *
 * The size limit matters because the catalog URL is a setting, so it can point
 * anywhere. The body is read in chunks and the read stops as soon as it goes
 * over, rather than loading the whole response and measuring it afterwards.
 */
final class HttpCatalogDownloader implements CatalogDownloader
{
    public const int TIMEOUT_SECONDS = 5;

    /** One megabyte. A catalog of a few thousand playlists is well under this. */
    public const int SIZE_LIMIT_BYTES = 1048576;

    private const int CHUNK_BYTES = 65536;

    public function download(string $url): DownloadResult
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::TIMEOUT_SECONDS)
                ->withOptions(['stream' => true])
                ->get($url);
        } catch (ConnectionException $exception) {
            // No network, no DNS, connection refused, or the timeout above.
            return DownloadResult::failed($exception->getMessage());
        } catch (Throwable $exception) {
            // Anything else the client can throw. This call runs at startup, so
            // it reports a failure rather than taking the app down with it.
            return DownloadResult::failed($exception->getMessage());
        }

        if (! $response->successful()) {
            return DownloadResult::failed(sprintf(
                'The server answered %d. While the repository is private the catalog URL answers 404, which is handled the same way as no network at all.',
                $response->status(),
            ));
        }

        $announced = $response->header('Content-Length');

        if ($announced !== '' && (int) $announced > self::SIZE_LIMIT_BYTES) {
            return $this->tooBig();
        }

        return $this->read($response->toPsrResponse()->getBody());
    }

    /**
     * Reads the body a chunk at a time and stops the moment it goes over the
     * limit, so an endless response cannot fill memory.
     *
     * The stream is closed only when the read is abandoned, which is what hangs
     * up on a server still sending. A stream read to the end has nothing left to
     * release, and closing it would leave the response unreadable for anyone
     * else holding it.
     */
    private function read(StreamInterface $stream): DownloadResult
    {
        $body = '';

        try {
            while (! $stream->eof()) {
                $body .= $stream->read(self::CHUNK_BYTES);

                if (strlen($body) > self::SIZE_LIMIT_BYTES) {
                    $stream->close();

                    return $this->tooBig();
                }
            }
        } catch (Throwable $exception) {
            $stream->close();

            return DownloadResult::failed($exception->getMessage());
        }

        return DownloadResult::received($body);
    }

    private function tooBig(): DownloadResult
    {
        return DownloadResult::failed('The catalog is larger than the 1 MB limit, so it was not read.');
    }
}
