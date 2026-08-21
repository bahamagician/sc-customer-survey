<script setup lang="ts">
import { computed } from 'vue';
import { Progress } from '@/components/ui/progress';

const props = defineProps<{
    position: number;
    total: number;
}>();

const percentage = computed(() => (props.position / props.total) * 100);
</script>

<template>
    <div class="grid gap-2">
        <Progress :model-value="percentage" class="h-2" />
        <p class="text-xs text-muted-foreground">
            Question {{ position }} of {{ total }}
        </p>
    </div>
</template>
