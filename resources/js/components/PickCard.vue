<script setup lang="ts">
import { computed } from 'vue';
import type { Pick } from '../types/menuBar';

const props = defineProps<{
    pick: Pick | null;
    /**
     * The label of the genre that was asked for, when the pick comes from a
     * wider search, else null. The command line says why.
     */
    askedGenre: string | null;
    empty: string | null;
}>();

// The link says what it adds: "Add a classical playlist".
const addLink = computed((): string | null => {
    if (props.askedGenre === null) {
        return null;
    }

    const genre = props.askedGenre.toLowerCase();

    return `Add ${/^[aeiou]/.test(genre) ? 'an' : 'a'} ${genre} playlist`;
});

const emit = defineEmits<{
    'add-playlist': [];
}>();
</script>

<template>
    <section class="rounded-lg border border-divider p-2.5">
        <template v-if="pick !== null">
            <p class="font-mono text-xs text-muted">today's pick</p>
            <h2 class="mt-1 text-base font-semibold">{{ pick.title }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ pick.genre.label }} · <span aria-hidden="true">{{ pick.mood.emoji }}</span> {{ pick.mood.label }}
                <template v-if="pick.curator !== null">
                    · <span data-curator class="font-mono text-xs">@{{ pick.curator }}</span>
                </template>
            </p>
            <button
                v-if="addLink !== null"
                type="button"
                class="mt-1.5 text-xs text-muted underline hover:text-ink"
                @click="emit('add-playlist')"
            >
                {{ addLink }}
            </button>
        </template>

        <template v-else>
            <p class="text-sm">{{ empty }}</p>
            <button
                type="button"
                class="mt-2 text-sm text-accent underline"
                @click="emit('add-playlist')"
            >
                Add a playlist
            </button>
        </template>
    </section>
</template>
