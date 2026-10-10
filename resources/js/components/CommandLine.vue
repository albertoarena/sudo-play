<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    genre: string | null;
    mood: string | null;
    /** Why the pick comes from a wider search, drawn as a terminal comment. */
    note: string | null;
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
    <!--
        The prompt sign is dim and the command is bright, as in a terminal. The
        command stays as asked. A fallback adds a dim comment line under it, so
        the header never claims a genre or mood the card does not show.
    -->
    <p class="font-mono text-sm break-words text-term-command"><span class="text-term-dim">$</span> {{ command }}</p>
    <p v-if="note !== null" class="font-mono text-xs break-words text-term-dim"># {{ note }}</p>
</template>
