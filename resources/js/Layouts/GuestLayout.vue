<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-surface">
        <header class="border-b border-surface-muted bg-white">
            <div class="mx-auto flex h-16 max-w-6xl items-center px-5 sm:px-8">
                <Link href="/" class="text-pool">
                    <ApplicationLogo compact />
                </Link>
            </div>
        </header>

        <main class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-10 sm:px-6">
            <div class="w-full max-w-md rounded-card border border-surface-muted bg-white p-6 shadow-card sm:p-8">
                <slot />
            </div>
        </main>
    </div>
</template>
