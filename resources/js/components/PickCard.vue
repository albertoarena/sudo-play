<script setup lang="ts">
import type { Pick } from '../types/menuBar';

defineProps<{
    pick: Pick | null;
    /** The pick comes from a wider search. The command line says why. */
    fallback: boolean;
    empty: string | null;
}>();

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
                <span v-if="pick.curator !== null"> · @{{ pick.curator }}</span>
            </p>
            <button
                v-if="fallback"
                type="button"
                class="mt-1.5 text-xs text-muted underline hover:text-ink"
                @click="emit('add-playlist')"
            >
                Add one
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
