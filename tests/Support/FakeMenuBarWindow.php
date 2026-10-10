<?php

declare(strict_types=1);

namespace Tests\Support;

use App\MenuBar\MenuBarWindow;

final class FakeMenuBarWindow implements MenuBarWindow
{
    public int $hides = 0;

    public function hide(): void
    {
        $this->hides++;
    }
}
