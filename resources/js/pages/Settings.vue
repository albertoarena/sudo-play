<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { createSettingsApi, ValidationError } from '../api';
import type { SettingsApi } from '../api';
import type { About, PlayerMode, SettingsOptions, SettingsValues } from '../types/settings';

const props = withDefaults(
    defineProps<{
        settings: SettingsValues;
        options: SettingsOptions;
        catalogDate: string | null;
        about: About;
        api?: SettingsApi;
    }>(),
    { api: () => createSettingsApi() },
);

// A select cannot hold null, so "not chosen" is the empty string while the
// form is open, and null again on the way out.
interface Form {
    defaultGenre: string;
    defaultMood: string;
    playerMode: PlayerMode;
    reminderEnabled: boolean;
    reminderTime: string;
    weekdaysOnly: boolean;
    launchAtLogin: boolean;
    catalogUrl: string;
}

function toForm(values: SettingsValues): Form {
    return { ...values, defaultGenre: values.defaultGenre ?? '', defaultMood: values.defaultMood ?? '' };
}

function fromForm(form: Form): SettingsValues {
    return { ...form, defaultGenre: form.defaultGenre || null, defaultMood: form.defaultMood || null };
}

const form = reactive<Form>(toForm(props.settings));
const errors = ref<Record<string, string>>({});
const message = ref<string | null>(null);
const saving = ref(false);

const syncedOn = ref<string | null>(props.catalogDate);
const catalogMessage = ref<string | null>(null);
const updating = ref(false);

const catalogLine = computed((): string =>
    syncedOn.value === null ? 'Bundled catalog' : `Catalog updated ${syncedOn.value}`,
);

async function save(): Promise<void> {
    saving.value = true;
    message.value = null;
    errors.value = {};

    try {
        const answer = await props.api.save(fromForm(form));

        // The server can keep less than it was sent: launch at login stays off
        // when macOS refuses it. Draw what it kept.
        Object.assign(form, toForm(answer.settings));
        message.value = answer.message;
    } catch (error) {
        if (error instanceof ValidationError) {
            errors.value = error.errors;
            message.value = 'Please check the values marked below.';
        } else {
            message.value = 'Could not save. Please try again.';
        }
    } finally {
        saving.value = false;
    }
}

async function updateCatalog(): Promise<void> {
    updating.value = true;
    catalogMessage.value = null;

    try {
        const answer = await props.api.updateCatalog();
        syncedOn.value = answer.catalogDate;
        catalogMessage.value = answer.message;
    } catch {
        catalogMessage.value = 'Could not update the catalog. Please try again.';
    } finally {
        updating.value = false;
    }
}

// Links go through the server, which hands them to the browser. A plain link
// would open inside the settings window itself.
async function openLink(open: () => Promise<void>): Promise<void> {
    try {
        await open();
    } catch {
        message.value = 'Could not open the browser.';
    }
}

const field = 'w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900';
const fieldError = 'border-amber-600 dark:border-amber-400';
const heading = 'mb-2 text-xs font-semibold tracking-wide text-zinc-500 uppercase';
const errorText = 'mt-1 text-xs text-amber-700 dark:text-amber-400';
const link = 'text-sm text-emerald-700 hover:underline dark:text-emerald-400';
</script>

<template>
    <main class="flex h-full flex-col bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <form class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto p-4" @submit.prevent="save">
            <section>
                <h2 :class="heading">Defaults</h2>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-sm">
                        Genre
                        <select
                            id="default-genre"
                            v-model="form.defaultGenre"
                            :class="[field, 'mt-1', errors.defaultGenre ? fieldError : '']"
                            :aria-invalid="errors.defaultGenre ? 'true' : undefined"
                        >
                            <option value="">First in the catalog</option>
                            <option v-for="genre in options.genres" :key="genre.id" :value="genre.id">
                                {{ genre.label }}
                            </option>
                        </select>
                        <p v-if="errors.defaultGenre" :class="errorText">{{ errors.defaultGenre }}</p>
                    </label>
                    <label class="text-sm">
                        Mood
                        <select
                            id="default-mood"
                            v-model="form.defaultMood"
                            :class="[field, 'mt-1', errors.defaultMood ? fieldError : '']"
                            :aria-invalid="errors.defaultMood ? 'true' : undefined"
                        >
                            <option value="">First in the catalog</option>
                            <option v-for="mood in options.moods" :key="mood.id" :value="mood.id">
                                {{ mood.emoji ? `${mood.emoji} ${mood.label}` : mood.label }}
                            </option>
                        </select>
                        <p v-if="errors.defaultMood" :class="errorText">{{ errors.defaultMood }}</p>
                    </label>
                </div>
                <p class="mt-1 text-xs text-zinc-500">The window opens on these, and the daily reminder uses them.</p>
            </section>

            <section>
                <h2 :class="heading">Play with</h2>
                <div class="flex flex-col gap-1.5">
                    <label v-for="mode in options.playerModes" :key="mode.id" class="flex items-center gap-2 text-sm">
                        <input
                            :id="`player-mode-${mode.id}`"
                            v-model="form.playerMode"
                            type="radio"
                            name="player-mode"
                            :value="mode.id"
                        />
                        {{ mode.label }}
                    </label>
                </div>
                <p v-if="errors.playerMode" :class="errorText">{{ errors.playerMode }}</p>
            </section>

            <section>
                <h2 :class="heading">Daily reminder</h2>
                <div class="flex flex-col gap-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input id="reminder-enabled" v-model="form.reminderEnabled" type="checkbox" />
                        Remind me of today's pick
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        At
                        <input
                            id="reminder-time"
                            v-model="form.reminderTime"
                            type="time"
                            :disabled="!form.reminderEnabled"
                            :class="[field, 'w-28 disabled:opacity-40', errors.reminderTime ? fieldError : '']"
                            :aria-invalid="errors.reminderTime ? 'true' : undefined"
                        />
                    </label>
                    <p v-if="errors.reminderTime" :class="errorText">{{ errors.reminderTime }}</p>
                    <label class="flex items-center gap-2 text-sm" :class="{ 'opacity-40': !form.reminderEnabled }">
                        <input
                            id="weekdays-only"
                            v-model="form.weekdaysOnly"
                            type="checkbox"
                            :disabled="!form.reminderEnabled"
                        />
                        Weekdays only
                    </label>
                </div>
            </section>

            <section>
                <h2 :class="heading">System</h2>
                <label class="flex items-center gap-2 text-sm">
                    <input id="launch-at-login" v-model="form.launchAtLogin" type="checkbox" />
                    Open Sudo Play when I log in
                </label>
            </section>

            <section>
                <h2 :class="heading">Catalog</h2>
                <label class="text-sm">
                    Catalog URL
                    <input
                        id="catalog-url"
                        v-model="form.catalogUrl"
                        type="url"
                        :class="[field, 'mt-1 font-mono text-xs', errors.catalogUrl ? fieldError : '']"
                        :aria-invalid="errors.catalogUrl ? 'true' : undefined"
                        :aria-describedby="errors.catalogUrl ? 'catalog-url-error' : undefined"
                    />
                </label>
                <p v-if="errors.catalogUrl" id="catalog-url-error" :class="errorText">{{ errors.catalogUrl }}</p>
                <p class="mt-1 text-xs text-zinc-500">Only change this if you run a fork. Save before you update.</p>
                <div class="mt-2 flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm hover:border-zinc-500 disabled:opacity-40 dark:border-zinc-700"
                        :disabled="updating"
                        @click="updateCatalog"
                    >
                        Update catalog now
                    </button>
                    <span class="text-xs text-zinc-500">{{ catalogLine }}</span>
                </div>
                <p v-if="catalogMessage !== null" id="catalog-status" class="mt-1 text-xs text-zinc-500">
                    {{ catalogMessage }}
                </p>
            </section>

            <section>
                <h2 :class="heading">About</h2>
                <p class="text-sm">Sudo Play {{ about.version }}</p>
                <p class="text-xs text-zinc-500">{{ about.notice }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ about.privacy }}</p>
                <div class="mt-1 flex gap-4">
                    <button type="button" :class="link" @click="openLink(api.openRepository)">GitHub</button>
                    <button type="button" :class="link" @click="openLink(api.openPrivacy)">Privacy</button>
                </div>
            </section>

            <section>
                <h2 :class="heading">Credits</h2>
                <template v-if="about.curators.length > 0">
                    <p class="text-xs text-zinc-500">Playlists in this catalog were picked by:</p>
                    <ul id="curators" class="mt-1 flex flex-wrap gap-x-3 gap-y-1 font-mono text-xs">
                        <li v-for="curator in about.curators" :key="curator">@{{ curator }}</li>
                    </ul>
                </template>
                <button type="button" :class="[link, 'mt-1']" @click="openLink(api.openContributors)">
                    Contributors on GitHub
                </button>
            </section>
        </form>

        <div class="flex items-center gap-3 border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <button
                type="button"
                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-40"
                :disabled="saving"
                @click="save"
            >
                Save
            </button>
            <p v-if="message !== null" role="status" class="text-xs text-zinc-600 dark:text-zinc-400">{{ message }}</p>
        </div>
    </main>
</template>
