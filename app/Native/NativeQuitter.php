<?php

declare(strict_types=1);

namespace App\Native;

use App\MenuBar\Quitter;
use Native\Laravel\Facades\App;

final class NativeQuitter implements Quitter
{
    public function quit(): void
    {
        App::quit();
    }
}
