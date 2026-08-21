<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    options: Record<string, string>;
    modelValue: string | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    select: [value: string];
}>();
</script>

<template>
    <div class="grid gap-2">
        <button
            v-for="(label, key) in options"
            :key="key"
            type="button"
            :disabled="disabled"
            :class="
                cn(
                    'flex min-h-12 items-center rounded-md border border-input px-4 text-left text-base transition-colors',
                    modelValue === key
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'bg-background hover:bg-accent hover:text-accent-foreground',
                )
            "
            @click="emit('select', key)"
        >
            {{ label }}
        </button>
    </div>
</template>
