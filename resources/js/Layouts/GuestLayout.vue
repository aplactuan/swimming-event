<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-surface px-4 py-10"
    >
        <div class="mb-8 text-navy">
            <Link href="/">
                <ApplicationLogo />
            </Link>
        </div>

        <div
            class="w-full max-w-md rounded-card bg-surface-card px-8 py-8 shadow-card"
        >
            <slot />
        </div>
    </div>
</template>
