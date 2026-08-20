<script setup lang="ts">
import { Check } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    options: Record<string, string>;
    modelValue: string[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

function toggle(key: string): void {
    emit(
        'update:modelValue',
        props.modelValue.includes(key)
            ? props.modelValue.filter((selected) => selected !== key)
            : [...props.modelValue, key],
    );
}
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            v-for="(label, key) in options"
            :key="key"
            type="button"
            :class="
                cn(
                    'border-input flex min-h-11 items-center gap-1.5 rounded-full border px-4 text-sm font-medium transition-colors',
                    modelValue.includes(key)
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'bg-background hover:bg-accent hover:text-accent-foreground',
                )
            "
            @click="toggle(key)"
        >
            <Check v-if="modelValue.includes(key)" class="size-4" />
            {{ label }}
        </button>
    </div>
</template>
