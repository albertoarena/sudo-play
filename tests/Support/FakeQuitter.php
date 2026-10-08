<?php

declare(strict_types=1);

namespace Tests\Support;

use App\MenuBar\Quitter;

final class FakeQuitter implements Quitter
{
    public int $quits = 0;

    public function quit(): void
    {
        $this->quits++;
    }
}
