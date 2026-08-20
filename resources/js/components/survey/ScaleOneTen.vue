<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    modelValue: number | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    select: [value: number];
}>();
</script>

<template>
    <div class="grid gap-3">
        <div class="grid grid-cols-5 gap-2">
            <button
                v-for="score in [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]"
                :key="score"
                type="button"
                :disabled="disabled"
                :class="
                    cn(
                        'border-input flex min-h-12 items-center justify-center rounded-md border text-base font-medium transition-colors',
                        modelValue === score
                            ? 'bg-primary text-primary-foreground border-primary'
                            : 'bg-background hover:bg-accent hover:text-accent-foreground',
                    )
                "
                @click="emit('select', score)"
            >
                {{ score }}
            </button>
        </div>
    </div>
</template>
