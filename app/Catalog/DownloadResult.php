<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * What came back from trying to download a catalog.
 *
 * Either a body or a reason, never both. The reason is a sentence for the log,
 * because every way this can fail ends the same way for the user: the app keeps
 * the catalog it already had and says nothing. Requirements 4.4.
 */
final readonly class DownloadResult
{
    private function __construct(
        public ?string $body,
        public ?string $failure,
    ) {}

    public static function received(string $body): self
    {
        return new self(body: $body, failure: null);
    }

    public static function failed(string $reason): self
    {
        return new self(body: null, failure: $reason);
    }

    public function succeeded(): bool
    {
        return $this->failure === null;
    }
}
