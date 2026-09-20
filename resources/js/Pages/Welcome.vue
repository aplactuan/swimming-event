<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();
</script>

<template>
    <Head title="Swimming competition management" />

    <div class="min-h-screen bg-surface text-ink">
        <header class="border-b border-surface-muted bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
                <ApplicationLogo compact class="text-pool" />

                <nav v-if="canLogin" class="flex items-center gap-2" aria-label="Account navigation">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="sm-btn-primary"
                    >
                        Open dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="sm-btn-secondary">Log in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="sm-btn-primary">Create account</Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto grid min-h-[calc(100vh-4rem)] max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:py-20">
                <div class="max-w-2xl">
                    <p class="sm-label">Swimming competition operations</p>
                    <h1 class="mt-5 text-5xl font-semibold tracking-[-0.045em] text-pool-deep sm:text-6xl sm:leading-[1.04]">
                        Run the meet.<br class="hidden sm:block" /> Keep every race on course.
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-ink-muted">
                        Organize classifications, swimmers, event entries, and the final race program from one focused workspace.
                    </p>

                    <div v-if="canLogin" class="mt-9 flex flex-wrap gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="sm-btn-primary px-5 py-3"
                        >
                            Open dashboard
                            <span aria-hidden="true">→</span>
                        </Link>
                        <template v-else>
                            <Link :href="route('login')" class="sm-btn-primary px-5 py-3">
                                Log in
                                <span aria-hidden="true">→</span>
                            </Link>
                            <Link v-if="canRegister" :href="route('register')" class="sm-btn-secondary px-5 py-3">
                                Create account
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="relative">
                    <div class="overflow-hidden rounded-card border border-surface-muted bg-white shadow-card">
                        <div class="flex items-center justify-between border-b border-surface-muted px-5 py-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-ink-faint">Meet workspace</p>
                                <p class="mt-1 font-semibold text-ink">Competition workflow</p>
                            </div>
                            <span class="rounded-full bg-mint-soft px-3 py-1 text-xs font-semibold text-pool">Race ready</span>
                        </div>

                        <ol class="divide-y divide-surface-muted">
                            <li class="flex gap-4 px-5 py-5 sm:px-6">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-pool text-xs font-bold text-white">01</span>
                                <div>
                                    <h2 class="font-semibold text-ink">Set the competition structure</h2>
                                    <p class="mt-1 text-sm leading-6 text-ink-muted">Create classifications and age brackets for the meet.</p>
                                </div>
                            </li>
                            <li class="flex gap-4 px-5 py-5 sm:px-6">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-pool text-xs font-bold text-white">02</span>
                                <div>
                                    <h2 class="font-semibold text-ink">Manage swimmers and entries</h2>
                                    <p class="mt-1 text-sm leading-6 text-ink-muted">Import participants, track payment, and place eligible swimmers.</p>
                                </div>
                            </li>
                            <li class="flex gap-4 px-5 py-5 sm:px-6">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-aqua text-xs font-bold text-pool-deep">03</span>
                                <div>
                                    <h2 class="font-semibold text-ink">Build the race program</h2>
                                    <p class="mt-1 text-sm leading-6 text-ink-muted">Generate and order events into a clear competition program.</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div class="absolute -bottom-3 left-8 right-8 -z-10 h-16 rounded-full bg-aqua/20 blur-2xl" aria-hidden="true" />
                </div>
            </section>
        </main>
    </div>
</template>
