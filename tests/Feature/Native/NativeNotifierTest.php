<?php

declare(strict_types=1);

use App\Native\NativeNotifier;
use App\Notifications\Notifier;
use App\Notifications\Reminder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('asks Electron to show the notification', function (): void {
    Http::fake(['*' => Http::response(['reference' => NativeNotifier::REFERENCE])]);

    (new NativeNotifier)->notify(new Reminder('Sudo Play', "☕ Coffee Needed? Today's pick is ready: Slow jazz"));

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/notification')
        && $request['title'] === 'Sudo Play'
        && $request['body'] === "☕ Coffee Needed? Today's pick is ready: Slow jazz"
        && $request['reference'] === NativeNotifier::REFERENCE);
    Http::assertSentCount(1);
});

it('is the notifier the app uses', function (): void {
    expect(app(Notifier::class))->toBeInstanceOf(NativeNotifier::class);
});
