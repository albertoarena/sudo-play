// The settings window. Mirrors SettingsValues::toArray() in
// app/SettingsWindow/SettingsValues.php and the props SettingsController
// renders, so the two change together.

import type { Genre, Mood } from './catalog';

export type PlayerMode = 'autoplay' | 'open-app' | 'web';

export interface SettingsValues {
    /** null means not chosen: the window opens on the first genre of the catalog. */
    defaultGenre: string | null;
    defaultMood: string | null;
    playerMode: PlayerMode;
    reminderEnabled: boolean;
    /** 24 hour HH:MM, local time. */
    reminderTime: string;
    weekdaysOnly: boolean;
    launchAtLogin: boolean;
    catalogUrl: string;
}

export interface SettingsOptions {
    genres: Genre[];
    moods: Omit<Mood, 'description'>[];
    playerModes: { id: PlayerMode; label: string }[];
}

export interface About {
    version: string;
    repository: string;
    notice: string;
    /** One line on what the app sends and stores. PRIVACY.md has the rest. */
    privacy: string;
    /** GitHub handles of the people who curated the current catalog. */
    curators: string[];
}

export interface SettingsPageProps {
    settings: SettingsValues;
    options: SettingsOptions;
    /** The day of the last successful sync, or null for the bundled catalog. */
    catalogDate: string | null;
    about: About;
}

export interface SaveResponse {
    settings: SettingsValues;
    message: string;
}

export interface UpdateCatalogResponse {
    updated: boolean;
    message: string;
    catalogDate: string | null;
}
