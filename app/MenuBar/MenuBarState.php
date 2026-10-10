<?php

declare(strict_types=1);

namespace App\MenuBar;

use App\Catalog\Catalog;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Rotation\CalendarDate;
use App\Rotation\Pick;
use DateTimeImmutable;

/**
 * Everything the menu bar window draws, in one value. Requirements 7.1.
 *
 * `toArray()` is the shape `resources/js/types/menuBar.ts` describes. The two
 * have to change together.
 */
final readonly class MenuBarState
{
    public const string EMPTY_CATALOG = 'No playlists in the catalog yet';

    public const string EMPTY_PAIR = 'No playlists for this genre or mood yet';

    public function __construct(
        public CalendarDate $date,
        public ?Catalog $catalog,
        public ?string $genre,
        public ?string $mood,
        public ?Pick $pick,
        public int $presses,
        public ?DateTimeImmutable $syncedAt,
        public ?string $hint,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $catalog = $this->catalog;
        $hasPlaylists = $catalog !== null && $catalog->playlists !== [];

        return [
            'date' => $this->date->value,
            'genres' => array_map(fn (Genre $genre): array => [
                'id' => $genre->id,
                'label' => $genre->label,
            ], $catalog->genres ?? []),
            'moods' => array_map(fn (Mood $mood): array => [
                'id' => $mood->id,
                'label' => $mood->label,
                'emoji' => $mood->emoji,
                'description' => $mood->description,
            ], $catalog->moods ?? []),
            'selection' => ['genre' => $this->genre, 'mood' => $this->mood],
            'pick' => $this->pick(),
            'presses' => $this->presses,
            'note' => $this->note(),
            'empty' => match (true) {
                $this->pick !== null => null,
                $hasPlaylists => self::EMPTY_PAIR,
                default => self::EMPTY_CATALOG,
            },
            'actions' => [
                'play' => $this->pick !== null,
                'next' => $this->pick !== null,
                'surprise' => $hasPlaylists,
            ],
            'catalogDate' => $this->syncedAt?->format('Y-m-d'),
            'hint' => $this->hint,
        ];
    }

    /**
     * @return array{id: string, title: string, curator: string|null, genre: array{id: string, label: string}, mood: array{id: string, label: string, emoji: string}}|null
     */
    private function pick(): ?array
    {
        if ($this->pick === null) {
            return null;
        }

        $playlist = $this->pick->playlist;
        $genre = $this->genreFor($playlist->genre);
        $mood = $this->shownMood();

        return [
            'id' => $playlist->id,
            'title' => $playlist->title,
            'curator' => $playlist->curator,
            'genre' => ['id' => $genre->id, 'label' => $genre->label],
            'mood' => ['id' => $mood->id, 'label' => $mood->label, 'emoji' => $mood->emoji],
        ];
    }

    private function note(): ?string
    {
        if ($this->pick === null) {
            return null;
        }

        return FallbackNote::for($this->pick, $this->shownMood()->id);
    }

    /**
     * The mood the card names. It is the one the user chose when the playlist
     * carries it. A pick from another mood names its own first mood, so the
     * card never claims a playlist fits a mood it was not made for.
     */
    private function shownMood(): Mood
    {
        assert($this->pick !== null);

        $moods = $this->pick->playlist->moods;
        $id = in_array($this->pick->mood, $moods, true) ? $this->pick->mood : $moods[0];

        foreach ($this->catalog->moods ?? [] as $mood) {
            if ($mood->id === $id) {
                return $mood;
            }
        }

        return new Mood($id, $id);
    }

    private function genreFor(string $id): Genre
    {
        foreach ($this->catalog->genres ?? [] as $genre) {
            if ($genre->id === $id) {
                return $genre;
            }
        }

        return new Genre($id, $id);
    }
}
