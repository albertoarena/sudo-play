<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { commandParts, wrapCommand } from '../command';

const props = defineProps<{
    genre: string | null;
    mood: string | null;
    /** Why the pick comes from a wider search, drawn as a terminal comment. */
    note: string | null;
}>();

// How many characters fit on a line. The font is monospace, so the width of
// the first line's text over its number of characters is the width of one.
// Until the window is laid out nothing has a width, and the command stays on
// one line.
const columns = ref(Infinity);
const command = ref<HTMLElement | null>(null);

function measure(): void {
    const text = command.value?.querySelector<HTMLElement>('[data-text]');
    const length = text?.textContent?.length ?? 0;
    const character = length > 0 ? (text?.getBoundingClientRect().width ?? 0) / length : 0;
    const available = command.value?.clientWidth ?? 0;

    columns.value = character > 0 && available > 0 ? Math.floor(available / character) : Infinity;
}

let observer: ResizeObserver | null = null;

onMounted(() => {
    measure();

    if (typeof ResizeObserver !== 'undefined' && command.value !== null) {
        observer = new ResizeObserver(measure);
        observer.observe(command.value);
    }
});

onBeforeUnmount(() => observer?.disconnect());

const lines = computed((): string[][] => wrapCommand(commandParts(props.genre, props.mood), columns.value));
</script>

<template>
    <!--
        The prompt sign is dim and the command is bright, as in a terminal. The
        command stays as asked. A fallback adds a dim comment line under it, so
        the header never claims a genre or mood the card does not show.

        A command too wide for the window goes on the next line the way a
        shell does: a trailing backslash, then a two space indent. It breaks
        only between flags. The browser wraps a line itself only when one flag
        alone is wider than the window.
    -->
    <p ref="command" class="font-mono text-sm text-term-command">
        <span v-for="(parts, index) in lines" :key="index" data-line class="block break-words whitespace-pre-wrap">
            <span data-text><span v-if="index === 0" class="text-term-dim">$</span>{{ index === 0 ? ' ' : '  ' }}{{ parts.join(' ') }}<span v-if="index < lines.length - 1" aria-hidden="true" class="text-term-dim">{{ ' \\' }}</span></span>
        </span>
    </p>
    <p v-if="note !== null" class="font-mono text-xs break-words text-term-dim"># {{ note }}</p>
</template>
