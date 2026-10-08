<script setup lang="ts">
export interface ChipOption {
    id: string;
    label: string;
    emoji?: string;
}

const props = defineProps<{
    label: string;
    options: ChipOption[];
    selected: string | null;
}>();

const emit = defineEmits<{
    select: [id: string];
}>();

function choose(id: string): void {
    if (id !== props.selected) {
        emit('select', id);
    }
}

function text(option: ChipOption): string {
    return option.emoji ? `${option.emoji} ${option.label}` : option.label;
}
</script>

<template>
    <div role="group" :aria-label="label" class="flex flex-wrap gap-1">
        <button
            v-for="option in options"
            :key="option.id"
            type="button"
            :aria-pressed="option.id === selected ? 'true' : 'false'"
            class="rounded-full border px-2.5 py-1 text-xs transition-colors"
            :class="
                option.id === selected
                    ? 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-400 dark:bg-emerald-400 dark:text-zinc-950'
                    : 'border-zinc-300 text-zinc-700 hover:border-zinc-500 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-500'
            "
            @click="choose(option.id)"
        >
            {{ text(option) }}
        </button>
    </div>
</template>
