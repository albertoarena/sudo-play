<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ChipGroup from '../components/ChipGroup.vue';
import CommandLine from '../components/CommandLine.vue';
import PickCard from '../components/PickCard.vue';
import { createMenuBarApi } from '../api';
import type { MenuBarApi } from '../api';
import type { MenuBarState } from '../types/menuBar';
import type { PlayerMode } from '../types/settings';

const props = withDefaults(
    defineProps<{
        state: MenuBarState;
        api?: MenuBarApi;
    }>(),
    { api: () => createMenuBarApi() },
);

// The server draws the state and the window keeps it. That includes the Next
// count, which lives here in memory only, so it is gone on a restart.
const current = ref<MenuBarState>(props.state);
const message = ref<string | null>(null);
const busy = ref(false);

// What a Play that worked says, one line per mode. Only autoplay starts the
// music. In Open only mode Spotify waits for its own Play press, and the web
// player does not start a playlist opened from a link, so those two say where
// the playlist opened instead.
const playedLines: Record<PlayerMode, (title: string) => string> = {
    autoplay: (title) => `playing "${title}"`,
    'open-app': (title) => `opened "${title}" in Spotify`,
    web: (title) => `opened "${title}" in your browser`,
};

// Spotify is running but did not take the playlist in time. The line stays
// until the next action, because it asks for one.
const STARTING_LINE = 'spotify is still starting, press Play again';

// The line that Play just earned, as proof the press worked. It has no timer:
// Spotify or the browser takes the focus and the window hides, so the line
// stays until the next action and is still there when the window opens again.
const played = ref<string | null>(null);
const starting = ref(false);

function confirm(title: string, mode: PlayerMode): void {
    played.value = playedLines[mode](title);
}

// True when no line is on screen.
const quiet = computed((): boolean => message.value === null && played.value === null && !starting.value);

// A new pick, said to screen readers only, because the card already shows it.
// A line on screen wins, so the announcement waits its turn behind it.
const announced = ref<string | null>(null);

watch(
    () => current.value.pick?.id,
    () => {
        const pick = current.value.pick;

        if (pick !== null && quiet.value) {
            announced.value = `Today's pick: ${pick.title}`;
        }
    },
);

// Every line goes through the one live region, which stays in the page so
// screen readers notice when its text changes. With nothing to show, it is
// taken out of the layout but not out of the page.
const status = computed((): string => {
    if (message.value !== null) {
        return 'text-xs text-warning';
    }

    return played.value !== null || starting.value ? 'font-mono text-xs text-muted' : 'sr-only';
});

// What a new action replaces. Clearing the announcement too means an old pick
// is not read out again when a message goes away.
function clearStatus(): void {
    message.value = null;
    played.value = null;
    starting.value = false;
    announced.value = null;
}

// The label of the genre that was asked for, when the pick comes from a wider
// search. The card names it in its "Add a ... playlist" link.
const askedGenre = computed((): string | null => {
    const id = current.value.selection.genre;

    if (current.value.note === null || current.value.pick === null || id === null) {
        return null;
    }

    return current.value.genres.find((genre) => genre.id === id)?.label ?? id;
});

const footerDate = computed((): string =>
    current.value.catalogDate === null ? 'Built-in playlists' : `Catalog updated ${current.value.catalogDate}`,
);

// Counts the actions the user started, so a slow focus refresh can tell that
// its answer is older than a click.
let actions = 0;

async function run(action: () => Promise<MenuBarState>): Promise<void> {
    actions++;
    busy.value = true;
    clearStatus();

    try {
        current.value = await action();
    } catch {
        message.value = 'Something went wrong. Please try again.';
    } finally {
        busy.value = false;
    }
}

function selectGenre(genre: string): void {
    const mood = current.value.selection.mood;

    if (mood !== null) {
        void run(() => props.api.select(genre, mood));
    }
}

function selectMood(mood: string): void {
    const genre = current.value.selection.genre;

    if (genre !== null) {
        void run(() => props.api.select(genre, mood));
    }
}

function next(): void {
    void run(() => props.api.next(current.value.presses, current.value.date));
}

function surprise(): void {
    void run(() => props.api.surprise());
}

async function play(): Promise<void> {
    const pick = current.value.pick;

    if (pick === null) {
        return;
    }

    busy.value = true;
    clearStatus();

    try {
        const result = await props.api.play(pick.id);

        if (result.outcome === 'played') {
            confirm(pick.title, result.mode);
        } else if (result.outcome === 'starting') {
            starting.value = true;
        } else {
            message.value = result.message;
        }
    } catch {
        message.value = 'Could not open the playlist.';
    } finally {
        busy.value = false;
    }
}

async function openContributing(): Promise<void> {
    try {
        await props.api.addPlaylist();
    } catch {
        message.value = 'Could not open the browser.';
    }
}

async function openSettings(): Promise<void> {
    try {
        await props.api.openSettings();
    } catch {
        message.value = 'Could not open the settings. Please try again.';
    }
}

async function quit(): Promise<void> {
    try {
        await props.api.quit();
    } catch {
        message.value = 'Could not quit. Please try again.';
    }
}

async function hide(): Promise<void> {
    try {
        await props.api.hide();
    } catch {
        message.value = 'Could not hide the window. Please try again.';
    }
}

// The window stays alive between clicks on the icon. Asking again on focus
// picks up a catalog the background sync replaced and a day that turned over.
// It happens on every open, so it is silent: the buttons stay as they are, a
// failure says nothing, and the state is replaced only when it changed. An
// answer that arrives after a click is older than that click, so it is dropped.
async function refresh(): Promise<void> {
    const started = actions;
    let answer: MenuBarState;

    try {
        answer = await props.api.state(current.value.presses, current.value.date);
    } catch {
        return;
    }

    if (started === actions && JSON.stringify(answer) !== JSON.stringify(current.value)) {
        current.value = answer;
    }
}

onMounted(() => window.addEventListener('focus', refresh));
onBeforeUnmount(() => window.removeEventListener('focus', refresh));

// Controls that take typing. A select counts, because letters jump through
// its options.
const typing = 'input, textarea, select, [contenteditable]:not([contenteditable="false"])';

// Controls that answer Enter and Space themselves.
const control = `button, a[href], [role="radio"], ${typing}`;

const canPlay = computed((): boolean => current.value.actions.play && !busy.value);
const canNext = computed((): boolean => current.value.actions.next && !busy.value);
const canSurprise = computed((): boolean => current.value.actions.surprise && !busy.value);

// The keyboard shortcuts. Each one does what its button does, and nothing
// when the button is disabled. Esc has no button and works wherever the focus
// is. Command, Control and Option are left to the system, so Command N and the
// like keep their meaning.
function shortcut(event: KeyboardEvent): void {
    if (event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }

    const focused = event.target instanceof Element ? event.target : null;

    if (focused?.closest(typing)) {
        return;
    }

    const key = event.key.length === 1 ? event.key.toLowerCase() : event.key;

    if (key === 'Escape') {
        event.preventDefault();
        void hide();
    } else if (key === 'Enter' || key === ' ') {
        if (focused?.closest(control) || !canPlay.value) {
            return;
        }

        event.preventDefault();
        void play();
    } else if (key === 'n' && canNext.value) {
        event.preventDefault();
        next();
    } else if (key === 's' && canSurprise.value) {
        event.preventDefault();
        surprise();
    }
}

onMounted(() => window.addEventListener('keydown', shortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', shortcut));

// An outline button shows its edge and nothing else, so the edge has to carry
// enough contrast on its own. A disabled one is dimmed and takes the plain
// cursor, because there is nothing to click.
const outline = 'rounded-md border border-edge px-3 py-2 text-sm hover:border-ink';
const disabled = 'disabled:cursor-default disabled:opacity-40';

// A footer link is small text, but a whole button to click: the padding takes
// the target to 24px, and the underline shows it is a link on hover and focus.
const footerLink = 'min-h-6 rounded px-1.5 hover:text-ink hover:underline focus-visible:underline';
</script>

<template>
    <!--
        The window has a fixed size. Only the top part scrolls when the content is
        taller, so Play and the footer always stay in the window. The line above
        the buttons shows where the scrolling part ends.
    -->
    <main class="flex h-full flex-col bg-page text-ink">
        <div class="flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto p-3 pb-2.5">
            <header class="rounded-md border border-term-edge bg-term-bg px-3 py-2">
                <CommandLine
                    :genre="current.selection.genre"
                    :mood="current.selection.mood"
                    :note="current.note"
                />
            </header>

            <ChipGroup label="Genre" :options="current.genres" :selected="current.selection.genre" @select="selectGenre" />
            <ChipGroup label="Mood" :options="current.moods" :selected="current.selection.mood" @select="selectMood" />

            <PickCard
                :pick="current.pick"
                :asked-genre="askedGenre"
                :empty="current.empty"
                @add-playlist="openContributing"
            />
        </div>

        <div class="flex flex-col gap-2.5 border-t border-divider px-3 pt-2.5 pb-3">
            <div class="flex gap-2">
                <button
                    type="button"
                    :class="[
                        'flex-1 rounded-md bg-accent px-3 py-2 text-sm font-semibold text-on-accent hover:bg-accent-hover',
                        disabled,
                    ]"
                    :disabled="!canPlay"
                    title="Play (Enter)"
                    aria-keyshortcuts="Enter Space"
                    @click="play"
                >
                    Play
                </button>
                <button
                    type="button"
                    :class="[outline, disabled]"
                    :disabled="!canNext"
                    title="Next (N)"
                    aria-keyshortcuts="N"
                    @click="next"
                >
                    Next
                </button>
                <button
                    type="button"
                    :class="[outline, disabled]"
                    :disabled="!canSurprise"
                    title="Surprise me (S)"
                    aria-keyshortcuts="S"
                    @click="surprise"
                >
                    Surprise me
                </button>
            </div>

            <p role="status" :class="status">
                <template v-if="message !== null">{{ message }}</template>
                <template v-else-if="played !== null"><span aria-hidden="true">▶</span> {{ played }}</template>
                <template v-else-if="starting"><span aria-hidden="true">#</span> {{ STARTING_LINE }}</template>
                <template v-else-if="announced !== null">{{ announced }}</template>
            </p>
            <p v-if="quiet && current.hint !== null" class="text-xs text-muted">
                {{ current.hint }}
            </p>

            <!-- The divider keeps Quit apart from Settings, so a slip does not close the app. -->
            <footer class="flex items-center gap-1 text-xs text-muted">
                <span class="mr-auto">{{ footerDate }}</span>
                <button type="button" :class="footerLink" @click="openContributing">Add a playlist</button>
                <button type="button" :class="footerLink" @click="openSettings">Settings</button>
                <span aria-hidden="true" class="mx-1 h-3 w-px bg-divider"></span>
                <button type="button" :class="footerLink" @click="quit">Quit</button>
            </footer>
        </div>
    </main>
</template>
