<?php

declare(strict_types=1);

use App\Native\NativeNotifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Events\Notifications\NotificationClicked;

/*
 * Requirements 7.3: clicking the reminder opens the menu bar window. NativePHP
 * turns the click into a NotificationClicked event in Laravel, carrying the
 * reference the notification was sent with.
 */

function menuBarShown(): bool
{
    return Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), 'menu-bar/show'))->isNotEmpty();
}

it('opens the menu bar window when the reminder is clicked', function (): void {
    Http::fake();

    event(new NotificationClicked(NativeNotifier::REFERENCE, '{}'));

    expect(menuBarShown())->toBeTrue();
});

it('leaves other notifications alone', function (): void {
    Http::fake();

    event(new NotificationClicked('someone-else', '{}'));

    expect(menuBarShown())->toBeFalse();
});
