<?php

declare(strict_types=1);

use App\MenuBar\SettingsWindow;

// Requirements 7.1: the footer has a Settings link, and requirements 7.2 says
// it opens the settings window.
it('opens the settings window from the footer', function (): void {
    $window = new class implements SettingsWindow
    {
        public int $opened = 0;

        public function open(): void
        {
            $this->opened++;
        }
    };
    app()->instance(SettingsWindow::class, $window);

    $this->postJson('/menu-bar/settings')->assertNoContent();

    expect($window->opened)->toBe(1);
});
