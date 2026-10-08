<script setup lang="ts">
import type { Pick } from '../types/menuBar';

defineProps<{
    pick: Pick | null;
    note: string | null;
    empty: string | null;
}>();

const emit = defineEmits<{
    'add-playlist': [];
}>();
</script>

<template>
    <section class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
        <template v-if="pick !== null">
            <p class="font-mono text-xs text-zinc-500">today's pick</p>
            <h2 class="mt-1 text-base font-semibold">{{ pick.title }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ pick.genre.label }} · {{ pick.mood.emoji }} {{ pick.mood.label }}
                <span v-if="pick.curator !== null"> · @{{ pick.curator }}</span>
            </p>
            <p v-if="note !== null" class="mt-2 text-xs text-amber-700 dark:text-amber-400">{{ note }}</p>
        </template>

        <template v-else>
            <p class="text-sm">{{ empty }}</p>
            <button
                type="button"
                class="mt-2 text-sm text-emerald-700 underline dark:text-emerald-400"
                @click="emit('add-playlist')"
            >
                Add a playlist
            </button>
        </template>
    </section>
</template>
