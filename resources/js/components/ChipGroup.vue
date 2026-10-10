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
                    ? 'border-accent bg-accent text-on-accent'
                    : 'border-edge text-ink hover:border-ink'
            "
            @click="choose(option.id)"
        >
            {{ text(option) }}
        </button>
    </div>
</template>
