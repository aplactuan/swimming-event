<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SwimmerIllustration from '@/Components/SwimmerIllustration.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen lg:grid lg:grid-cols-[minmax(0,26rem)_1fr]">
        <aside
            class="sm-water relative hidden overflow-hidden px-8 py-10 text-white lg:flex lg:flex-col lg:justify-center"
        >
            <div
                class="sm-caustics pointer-events-none absolute inset-0 opacity-[0.12]"
                aria-hidden="true"
            />

            <div class="relative z-10">
                <Link href="/" class="text-white">
                    <ApplicationLogo />
                </Link>

                <SwimmerIllustration
                    class="mt-10 h-56 w-full animate-drift drop-shadow-2xl"
                />

                <div class="sm-rope mt-10 opacity-80" aria-hidden="true" />

                <p class="mt-8 text-sm leading-relaxed text-white/70">
                    Lane assignments, heats and entries — all in one place, ready
                    before the first whistle.
                </p>
            </div>
        </aside>

        <div
            class="sm-shallows flex min-h-screen flex-col items-center justify-center px-4 py-10"
        >
            <div class="mb-8 text-pool lg:hidden">
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
    </div>
</template>
