<?php

declare(strict_types=1);

use App\Catalog\Playlist;
use App\Player\AppleScriptPlayer;
use App\Player\OpenAppPlayer;
use App\Player\PlayerResolver;
use App\Player\PlayOutcome;
use App\Player\UrlOpener;
use App\Player\WebPlayer;
use App\Settings\PlayerMode;
use App\Settings\SettingKey;
use Illuminate\Support\Facades\Process;
use Tests\Support\InMemorySettingsStore;
use Tests\Support\RecordingLogger;
use Tests\Support\RecordingUrlOpener;

const RESOLVER_WEB_URL = 'https://open.spotify.com/playlist/37i9dQZF1DX5trt9i14X7j';

function resolverPlaylist(): Playlist
{
    return new Playlist(
        id: 'electronic-shipped-it-01',
        title: 'Green builds only',
        spotifyId: '37i9dQZF1DX5trt9i14X7j',
        genre: 'electronic',
        moods: ['shipped-it'],
    );
}

/**
 * @param  array<string, mixed>  $settings
 */
function resolver(
    array $settings = [],
    ?RecordingUrlOpener $opener = null,
    ?RecordingLogger $log = null,
): PlayerResolver {
    $opener ??= RecordingUrlOpener::working();

    return new PlayerResolver(
        new InMemorySettingsStore($settings),
        new AppleScriptPlayer,
        new OpenAppPlayer($opener),
        new WebPlayer($opener),
        $log ?? new RecordingLogger,
    );
}

function failingScript(): void
{
    Process::fake([
        '*' => Process::result(errorOutput: 'execution error: Not authorized to send Apple events to Spotify. (-1743)', exitCode: 1),
    ]);
}

it('returns the player matching the setting', function (PlayerMode $mode, string $player): void {
    expect(resolver([SettingKey::PlayerMode->value => $mode->value])->resolve())->toBeInstanceOf($player);
})->with([
    'autoplay' => [PlayerMode::Autoplay, AppleScriptPlayer::class],
    'open app' => [PlayerMode::OpenApp, OpenAppPlayer::class],
    'web' => [PlayerMode::Web, WebPlayer::class],
]);

it('autoplays when the setting was never changed', function (): void {
    expect(resolver()->resolve())->toBeInstanceOf(AppleScriptPlayer::class);
});

it('autoplays when the stored setting is not a player mode', function (mixed $stored): void {
    expect(resolver([SettingKey::PlayerMode->value => $stored])->resolve())->toBeInstanceOf(AppleScriptPlayer::class);
})->with(['an unknown mode' => 'cassette', 'not a string' => 3, 'null' => null]);

it('reads the setting again on every play, so a change needs no restart', function (): void {
    $opener = RecordingUrlOpener::working();
    $settings = new InMemorySettingsStore([SettingKey::PlayerMode->value => PlayerMode::OpenApp->value]);
    $resolver = new PlayerResolver(
        $settings,
        new AppleScriptPlayer,
        new OpenAppPlayer($opener),
        new WebPlayer($opener),
        new RecordingLogger,
    );

    $resolver->play(resolverPlaylist());
    $settings->set(SettingKey::PlayerMode->value, PlayerMode::Web->value);
    $resolver->play(resolverPlaylist());

    expect($opener->opened)->toBe(['spotify:playlist:37i9dQZF1DX5trt9i14X7j', RESOLVER_WEB_URL]);
});

it('plays through the chosen player and says nothing when it works', function (): void {
    Process::fake();
    $opener = RecordingUrlOpener::working();

    $result = resolver(opener: $opener)->play(resolverPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Played)
        ->and($result->message)->toBeNull()
        ->and($opener->opened)->toBe([]);
    Process::assertRanTimes(fn (): bool => true, 1);
});

it('says which mode ran', function (PlayerMode $mode): void {
    Process::fake();

    $result = resolver([SettingKey::PlayerMode->value => $mode->value])->play(resolverPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Played)
        ->and($result->mode)->toBe($mode);
})->with([
    'autoplay' => [PlayerMode::Autoplay],
    'open app' => [PlayerMode::OpenApp],
    'web' => [PlayerMode::Web],
]);

it('says autoplay ran when the stored setting is not a player mode', function (): void {
    Process::fake();

    expect(resolver([SettingKey::PlayerMode->value => 'cassette'])->play(resolverPlaylist())->mode)
        ->toBe(PlayerMode::Autoplay);
});

it('names the chosen mode, not the browser, when it falls back', function (): void {
    failingScript();

    expect(resolver()->play(resolverPlaylist())->mode)->toBe(PlayerMode::Autoplay);
});

it('names the chosen mode when nothing opened', function (): void {
    failingScript();

    expect(resolver(opener: RecordingUrlOpener::failing())->play(resolverPlaylist())->mode)->toBe(PlayerMode::Autoplay);
});

it('falls back to the web player when autoplay fails, and says why', function (): void {
    failingScript();
    $opener = RecordingUrlOpener::working();

    $result = resolver(opener: $opener)->play(resolverPlaylist());

    expect($opener->opened)->toBe([RESOLVER_WEB_URL])
        ->and($result->outcome)->toBe(PlayOutcome::FellBack)
        ->and($result->message)->toBe('Sudo Play is not allowed to control Spotify. You can allow it in System Settings, Privacy & Security, Automation. Opened the playlist in your browser instead.');
});

it('logs the raw error when it falls back', function (): void {
    failingScript();
    $log = new RecordingLogger;

    resolver(log: $log)->play(resolverPlaylist());

    expect($log->lines)->toHaveCount(1)
        ->and($log->lines[0]['level'])->toBe('warning')
        ->and($log->everything())->toContain('(-1743)');
});

it('falls back to the web player when the app cannot be opened', function (): void {
    $settings = [SettingKey::PlayerMode->value => PlayerMode::OpenApp->value];
    $opener = new class implements UrlOpener
    {
        /** @var list<string> */
        public array $opened = [];

        public function open(string $url): void
        {
            if (str_starts_with($url, 'spotify:')) {
                throw new RuntimeException('no handler for spotify:');
            }

            $this->opened[] = $url;
        }
    };
    $resolver = new PlayerResolver(
        new InMemorySettingsStore($settings),
        new AppleScriptPlayer,
        new OpenAppPlayer($opener),
        new WebPlayer($opener),
        new RecordingLogger,
    );

    $result = $resolver->play(resolverPlaylist());

    expect($opener->opened)->toBe([RESOLVER_WEB_URL])
        ->and($result->outcome)->toBe(PlayOutcome::FellBack)
        ->and($result->message)->toBe('Could not open Spotify. Opened the playlist in your browser instead.');
});

it('does not try the web player twice when the web player was the choice', function (): void {
    $log = new RecordingLogger;

    $result = resolver(
        [SettingKey::PlayerMode->value => PlayerMode::Web->value],
        RecordingUrlOpener::failing(),
        $log,
    )->play(resolverPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Failed)
        ->and($result->message)->toBe('Could not open the playlist.')
        ->and($log->everything())->toContain('Electron is not listening');
});

it('fails when the fallback fails too', function (): void {
    failingScript();
    $log = new RecordingLogger;

    $result = resolver(opener: RecordingUrlOpener::failing(), log: $log)->play(resolverPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Failed)
        ->and($result->message)->toBe('Could not open the playlist.')
        ->and($log->everything())->toContain('(-1743)')
        ->and($log->everything())->toContain('Electron is not listening');
});
