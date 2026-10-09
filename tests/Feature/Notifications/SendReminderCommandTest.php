<?php

declare(strict_types=1);

use App\MenuBar\Today;
use App\Notifications\Notifier;
use App\Settings\SettingKey;
use App\Settings\SettingsStore;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\Support\FixedToday;
use Tests\Support\MenuBarCatalog;
use Tests\Support\MenuBarSetup;
use Tests\Support\RecordingNotifier;

/*
 * `reminder:send` is what the scheduler runs every minute. The decision is
 * DailyReminder's, which has its own tests. These check the wiring: the local
 * time goes in, and a reminder comes out through the notifier.
 */

/** @param  array<string, mixed>  $settings */
function reminderAt(string $time, array $settings = [], bool $withCatalog = true): RecordingNotifier
{
    MenuBarSetup::with($withCatalog ? MenuBarCatalog::full() : null, [
        SettingKey::ReminderEnabled->value => true,
        SettingKey::DefaultGenre->value => 'jazz',
        SettingKey::DefaultMood->value => 'debugging',
        ...$settings,
    ]);
    app()->instance(Today::class, new FixedToday(MenuBarSetup::TODAY, $time));

    $notifier = RecordingNotifier::working();
    app()->instance(Notifier::class, $notifier);

    return $notifier;
}

it('sends the reminder at the chosen time', function (): void {
    $notifier = reminderAt('09:00');

    $this->artisan('reminder:send')->assertSuccessful();

    expect($notifier->shown)->toHaveCount(1)
        ->and($notifier->shown[0]->body)->toBe("🐛 Debugging? Today's pick is ready: Slow jazz for long stack traces");
});

it('sends a missed reminder when the Mac wakes late', function (): void {
    $notifier = reminderAt('10:30');

    $this->artisan('reminder:send')->assertSuccessful();

    expect($notifier->shown)->toHaveCount(1);
});

it('sends one reminder a day, however often it is asked', function (): void {
    $notifier = reminderAt('09:00');

    $this->artisan('reminder:send')->assertSuccessful();
    app(Today::class)->moveTo(MenuBarSetup::TODAY, '09:01');
    $this->artisan('reminder:send')->assertSuccessful();

    expect($notifier->shown)->toHaveCount(1);
});

it('records the local day once the reminder is shown', function (): void {
    reminderAt('09:00');

    $this->artisan('reminder:send')->assertSuccessful();

    expect(app(SettingsStore::class)->get(SettingKey::ReminderSentOn->value))->toBe(MenuBarSetup::TODAY);
});

// A reminder that could not be shown was not sent, so the next minute in the
// window tries again.
it('tries again the next minute when the notification could not be shown', function (): void {
    reminderAt('09:00');
    app()->instance(Notifier::class, RecordingNotifier::failing());

    $this->artisan('reminder:send')->assertSuccessful();

    expect(app(SettingsStore::class)->get(SettingKey::ReminderSentOn->value))->toBeNull();

    $notifier = RecordingNotifier::working();
    app()->instance(Notifier::class, $notifier);
    app(Today::class)->moveTo(MenuBarSetup::TODAY, '09:01');

    $this->artisan('reminder:send')->assertSuccessful();

    expect($notifier->shown)->toHaveCount(1);
});

it('sends nothing at any other time', function (): void {
    $notifier = reminderAt('08:59');

    $this->artisan('reminder:send')->assertSuccessful();

    expect($notifier->shown)->toBe([]);
});

it('sends nothing when the reminder is off', function (): void {
    $notifier = reminderAt('09:00', [SettingKey::ReminderEnabled->value => false]);

    $this->artisan('reminder:send')->assertSuccessful();

    expect($notifier->shown)->toBe([]);
});

it('sends nothing when there is no catalog yet', function (): void {
    $notifier = reminderAt('09:00', withCatalog: false);

    $this->artisan('reminder:send')->assertSuccessful();

    expect($notifier->shown)->toBe([]);
});

// The scheduler would only try again tomorrow, so a failure is a log line and
// not a crash in the scheduler's output.
it('does not fail when the notification cannot be shown', function (): void {
    reminderAt('09:00');
    app()->instance(Notifier::class, RecordingNotifier::failing());

    $this->artisan('reminder:send')->assertSuccessful();
});

it('is asked every minute', function (): void {
    // schedule:list makes the console kernel load routes/console.php.
    $this->artisan('schedule:list')->run();

    $events = array_values(array_filter(
        app(Schedule::class)->events(),
        static fn (Event $event): bool => str_contains((string) $event->command, 'reminder:send'),
    ));

    expect($events)->toHaveCount(1)
        ->and($events[0]->expression)->toBe('* * * * *');
});
