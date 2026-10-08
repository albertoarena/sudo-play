<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ChipGroup from '../components/ChipGroup.vue';
import CommandLine from '../components/CommandLine.vue';
import PickCard from '../components/PickCard.vue';
import { createMenuBarApi } from '../api';
import type { MenuBarApi } from '../api';
import type { MenuBarState } from '../types/menuBar';

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

const footerDate = computed((): string =>
    current.value.catalogDate === null ? 'Bundled catalog' : `Catalog updated ${current.value.catalogDate}`,
);

async function run(action: () => Promise<MenuBarState>): Promise<void> {
    busy.value = true;
    message.value = null;

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
    message.value = null;

    try {
        message.value = (await props.api.play(pick.id)).message;
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

async function quit(): Promise<void> {
    try {
        await props.api.quit();
    } catch {
        message.value = 'Could not quit. Please try again.';
    }
}

// The window stays alive between clicks on the icon. Asking again on focus
// picks up a catalog the background sync replaced and a day that turned over.
function refresh(): void {
    void run(() => props.api.state(current.value.presses, current.value.date));
}

onMounted(() => window.addEventListener('focus', refresh));
onBeforeUnmount(() => window.removeEventListener('focus', refresh));
</script>

<template>
    <main class="flex h-full flex-col gap-4 bg-white p-4 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <header class="rounded-md bg-zinc-100 px-3 py-2 dark:bg-zinc-900">
            <CommandLine :genre="current.selection.genre" :mood="current.selection.mood" />
        </header>

        <ChipGroup label="Genre" :options="current.genres" :selected="current.selection.genre" @select="selectGenre" />
        <ChipGroup label="Mood" :options="current.moods" :selected="current.selection.mood" @select="selectMood" />

        <PickCard
            :pick="current.pick"
            :note="current.note"
            :empty="current.empty"
            @add-playlist="openContributing"
        />

        <div class="flex gap-2">
            <button
                type="button"
                class="flex-1 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-40"
                :disabled="!current.actions.play || busy"
                @click="play"
            >
                Play
            </button>
            <button
                type="button"
                class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:border-zinc-500 disabled:opacity-40 dark:border-zinc-700"
                :disabled="!current.actions.next || busy"
                @click="next"
            >
                Next
            </button>
            <button
                type="button"
                class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:border-zinc-500 disabled:opacity-40 dark:border-zinc-700"
                :disabled="!current.actions.surprise || busy"
                @click="surprise"
            >
                Surprise me
            </button>
        </div>

        <p v-if="message !== null" role="status" class="text-xs text-amber-700 dark:text-amber-400">{{ message }}</p>
        <p v-else-if="current.hint !== null" class="text-xs text-zinc-500">{{ current.hint }}</p>

        <footer class="mt-auto flex items-center gap-3 border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-zinc-800">
            <span class="mr-auto">{{ footerDate }}</span>
            <button type="button" class="hover:text-zinc-900 dark:hover:text-zinc-100" @click="openContributing">
                Add a playlist
            </button>
            <button type="button" class="hover:text-zinc-900 dark:hover:text-zinc-100" @click="quit">
                Quit
            </button>
        </footer>
    </main>
</template>
