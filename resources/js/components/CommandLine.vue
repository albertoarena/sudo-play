<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    genre: string | null;
    mood: string | null;
}>();

const command = computed((): string => {
    const parts = ['sudo play'];

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
    <!-- The prompt sign is dim and the command is bright, as in a terminal. -->
    <p class="font-mono text-sm break-words text-term-command"><span class="text-term-dim">$</span> {{ command }}</p>
</template>
