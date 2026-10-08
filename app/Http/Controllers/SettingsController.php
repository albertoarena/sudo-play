<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveSettingsRequest;
use App\Player\UrlOpener;
use App\SettingsWindow\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The settings window and its buttons. Requirements 7.2.
 */
final class SettingsController extends Controller
{
    /** Where the GitHub link in About goes. */
    public const string REPOSITORY_URL = 'https://github.com/albertoarena/sudo-play';

    public const string NOTICE = 'Not affiliated with Spotify.';

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Settings', [
            ...$this->settings->state(),
            'about' => [
                // The version comes from the release tag at build time, never
                // from a commit. Requirements 12.1.
                'version' => config()->string('nativephp.version'),
                'repository' => self::REPOSITORY_URL,
                'notice' => self::NOTICE,
            ],
        ]);
    }

    public function save(SaveSettingsRequest $request): JsonResponse
    {
        return response()->json($this->settings->save($request->values()));
    }

    public function updateCatalog(): JsonResponse
    {
        return response()->json($this->settings->updateCatalog());
    }

    /**
     * Opens the GitHub page in the browser. A link in the window would open
     * inside the settings window itself, so the server hands it to macOS.
     */
    public function openRepository(UrlOpener $opener): HttpResponse
    {
        $opener->open(self::REPOSITORY_URL);

        return response()->noContent();
    }
}
