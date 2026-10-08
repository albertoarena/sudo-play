// Catalog data as the menu bar receives it. Mirrors app/MenuBar/MenuBarState.php.

export interface Genre {
    id: string;
    label: string;
}

export interface Mood {
    id: string;
    label: string;
    emoji: string;
    description: string;
}
