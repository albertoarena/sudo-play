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
// the line over the width of one character says it. One character is measured
// on a hidden probe that never wraps. The command text cannot serve: once the
// browser has wrapped it, its box is as wide as the line, whatever its length.
// Until the window is laid out nothing has a width, and the command stays on
// one line.
const columns = ref(Infinity);
const command = ref<HTMLElement | null>(null);
const probe = ref<HTMLElement | null>(null);

// The probe holds ten characters, so one is a tenth of its width.
const PROBE = '0000000000';

function measure(): void {
    const character = (probe.value?.getBoundingClientRect().width ?? 0) / PROBE.length;
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
    <p ref="command" class="relative font-mono text-sm text-term-command">
        <span
            ref="probe"
            data-probe
            aria-hidden="true"
            class="pointer-events-none invisible absolute whitespace-nowrap select-none"
            >{{ PROBE }}</span
        >
        <span v-for="(parts, index) in lines" :key="index" data-line class="block break-words whitespace-pre-wrap">
            <span><span v-if="index === 0" class="text-term-dim">$</span>{{ index === 0 ? ' ' : '  ' }}{{ parts.join(' ') }}<span v-if="index < lines.length - 1" aria-hidden="true" class="text-term-dim">{{ ' \\' }}</span></span>
        </span>
    </p>
    <p v-if="note !== null" class="font-mono text-xs break-words text-term-dim"># {{ note }}</p>
</template>
