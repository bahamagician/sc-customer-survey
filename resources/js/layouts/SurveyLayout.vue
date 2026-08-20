<script setup lang="ts">
// Minimal public shell for the survey — no app chrome, mobile-first.
</script>

<template>
    <div
        class="bg-background text-foreground flex min-h-dvh flex-col items-center px-4 py-6"
    >
        <main class="flex w-full max-w-md flex-1 flex-col">
            <slot />
        </main>
    </div>
</template>
