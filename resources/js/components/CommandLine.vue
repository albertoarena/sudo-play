<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    genre: string | null;
    mood: string | null;
}>();

const command = computed((): string => {
    const parts = ['$ sudo play'];

    if (props.genre !== null) {
        parts.push(`--genre ${props.genre}`);
    }

    if (props.mood !== null) {
        parts.push(`--mood ${props.mood}`);
    }

    return parts.join(' ');
});
</script>

<template>
    <p class="truncate font-mono text-sm text-emerald-600 dark:text-emerald-400" :title="command">{{ command }}</p>
</template>
