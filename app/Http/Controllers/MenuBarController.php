<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\MenuBar\MenuBarService;
use App\MenuBar\Quitter;
use App\Player\PlayerInterface;
use App\Player\UrlOpener;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The menu bar window and its buttons. Requirements 7.1.
 *
 * The page renders with its state as a prop, so the first paint needs no
 * second request. Every button then answers with the whole state again, so the
 * window only ever draws what the server last said.
 */
final class MenuBarController extends Controller
{
    /** Where "Add a playlist" goes: the rules for adding one. */
    public const string CONTRIBUTING_URL = 'https://github.com/albertoarena/sudo-play/blob/main/CONTRIBUTING.md';

    public function __construct(
        private readonly MenuBarService $menuBar,
    ) {}

    public function show(): Response
    {
        return Inertia::render('MenuBar', ['state' => $this->menuBar->state()->toArray()]);
    }

    public function state(Request $request): JsonResponse
    {
        [$presses, $date] = $this->counted($request);

        return response()->json($this->menuBar->state($presses, $date)->toArray());
    }

    public function select(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'genre' => ['required', 'string', Rule::in($this->menuBar->genreIds())],
            'mood' => ['required', 'string', Rule::in($this->menuBar->moodIds())],
        ]);

        return response()->json($this->menuBar->select($validated['genre'], $validated['mood'])->toArray());
    }

    public function next(Request $request): JsonResponse
    {
        [$presses, $date] = $this->counted($request);

        return response()->json($this->menuBar->next($presses, $date)->toArray());
    }

    public function surprise(): JsonResponse
    {
        return response()->json($this->menuBar->surprise()->toArray());
    }

    /**
     * Plays the playlist the window shows. The window sends its id rather than
     * the genre, the mood and the count, so Play always plays what is on the
     * card, even if the day turned over since it was drawn.
     */
    public function play(Request $request, PlayerInterface $player): JsonResponse
    {
        $validated = $request->validate([
            'playlist' => ['required', 'string'],
        ]);

        $playlist = $this->menuBar->playlist($validated['playlist']);

        if ($playlist === null) {
            throw ValidationException::withMessages(['playlist' => 'That playlist is not in the catalog any more.']);
        }

        $result = $player->play($playlist);

        return response()->json(['outcome' => $result->outcome->value, 'message' => $result->message]);
    }

    /**
     * Opens the contributing page in the browser. A link in the window would
     * open inside the menu bar window itself, so the server hands it to macOS.
     */
    public function addPlaylist(UrlOpener $opener): HttpResponse
    {
        $opener->open(self::CONTRIBUTING_URL);

        return response()->noContent();
    }

    public function quit(Quitter $quitter): HttpResponse
    {
        $quitter->quit();

        return response()->noContent();
    }

    /**
     * The Next count the window sends back, and the day it was counted on.
     *
     * @return array{int, string|null}
     */
    private function counted(Request $request): array
    {
        $validated = $request->validate([
            'presses' => ['sometimes', 'integer', 'min:0'],
            'date' => ['sometimes', 'nullable', 'string'],
        ]);

        $date = $validated['date'] ?? null;

        return [(int) ($validated['presses'] ?? 0), is_string($date) ? $date : null];
    }
}
