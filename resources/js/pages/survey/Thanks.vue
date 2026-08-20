<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
</script>

<template>
    <div class="flex flex-1 flex-col items-center justify-center gap-4 text-center">
        <Head title="Thank You" />

        <CircleCheck class="text-primary size-14" />

        <h1 class="text-2xl font-semibold">Thank you!</h1>

        <p class="text-muted-foreground text-base">
            Your feedback helps us make every visit better.
        </p>

        <p class="text-muted-foreground text-sm">
            If you left your email, you've been entered into this month's gift
            card draw.
        </p>
    </div>
</template>
