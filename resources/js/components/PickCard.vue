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
    <section class="rounded-lg border border-divider p-2.5">
        <template v-if="pick !== null">
            <p class="font-mono text-xs text-muted">today's pick</p>
            <h2 class="mt-1 text-base font-semibold">{{ pick.title }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ pick.genre.label }} · {{ pick.mood.emoji }} {{ pick.mood.label }}
                <span v-if="pick.curator !== null"> · @{{ pick.curator }}</span>
            </p>
            <p v-if="note !== null" class="mt-1.5 text-xs text-warning">{{ note }}</p>
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
