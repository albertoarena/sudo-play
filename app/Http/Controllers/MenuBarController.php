<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\MenuBar\MenuBarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
