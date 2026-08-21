<script setup lang="ts">
// Minimal public shell for the survey — no app chrome, mobile-first.
</script>

<template>
    <div
        class="flex min-h-dvh flex-col items-center bg-background px-4 py-6 text-foreground"
    >
        <main class="flex w-full max-w-md flex-1 flex-col">
            <slot />
        </main>
    </div>
</template>
