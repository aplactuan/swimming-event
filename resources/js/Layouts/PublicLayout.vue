<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="min-h-screen bg-surface text-ink">
        <header class="border-b border-surface-muted bg-white">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-5 sm:px-8">
                <Link :href="route('home')" class="text-pool">
                    <ApplicationLogo compact />
                </Link>

                <nav class="flex items-center gap-2" aria-label="Account">
                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="sm-btn-primary"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="sm-btn-secondary"
                    >
                        Staff log in
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-5 py-8 sm:px-8 sm:py-10">
            <slot />
        </main>
    </div>
</template>
