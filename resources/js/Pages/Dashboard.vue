<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const metrics = [
    {
        label: 'Registered',
        value: '184',
        detail: 'Athletes',
        tone: 'bg-mint-soft text-navy',
        icon: 'users',
    },
    {
        label: 'Seeded',
        value: '42',
        detail: 'Events',
        tone: 'bg-gold/20 text-navy',
        icon: 'flag',
    },
    {
        label: 'Live',
        value: '16',
        detail: 'Heats queued',
        tone: 'bg-navy/10 text-navy',
        icon: 'bolt',
    },
];

const laneHeights = [42, 68, 55, 82, 61, 74, 48, 90];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <div class="sm-label">Active competition</div>
                    <h2 class="sm-heading mt-1">Meet command center</h2>
                    <p class="mt-2 max-w-xl text-sm text-ink-muted">
                        Track registrations, lane readiness, and heat progress
                        for your next session.
                    </p>
                </div>

                <button type="button" class="sm-btn-primary self-start sm:self-auto">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                        <path
                            d="M12 5v14M5 12h14"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        />
                    </svg>
                    New competition
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="metric in metrics"
                    :key="metric.label"
                    class="sm-card relative"
                >
                    <button
                        type="button"
                        class="absolute right-4 top-4 inline-flex h-8 w-8 items-center justify-center rounded-full text-ink-muted transition hover:bg-surface hover:text-ink"
                        aria-label="Open detail"
                    >
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                            <path
                                d="M7 17 17 7M9 7h8v8"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>

                    <div
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl"
                        :class="metric.tone"
                    >
                        <svg
                            v-if="metric.icon === 'users'"
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-5 w-5"
                        >
                            <path
                                d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M14 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                        <svg
                            v-else-if="metric.icon === 'flag'"
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-5 w-5"
                        >
                            <path
                                d="M5 21V4m0 0h9l-1.5 3L14 10H5"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <svg
                            v-else
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-5 w-5"
                        >
                            <path
                                d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <div class="mt-6 font-serif text-4xl font-bold tracking-tight">
                        {{ metric.value }}
                    </div>
                    <div class="mt-1 text-sm text-ink-muted">
                        {{ metric.detail }}
                        <span class="text-ink-faint">· {{ metric.label }}</span>
                    </div>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-5">
                <section class="sm-card xl:col-span-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="sm-label">Today</div>
                            <h3 class="mt-1 font-serif text-2xl font-bold">
                                Session pulse
                            </h3>
                        </div>
                        <span
                            class="rounded-full bg-mint px-3 py-1 text-xs font-semibold text-navy"
                        >
                            On schedule
                        </span>
                    </div>

                    <div class="mt-8 space-y-5">
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium">Warm-ups complete</span>
                                <span class="text-ink-muted">78%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-mint">
                                <div
                                    class="h-full rounded-full bg-navy"
                                    style="width: 78%"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium">Entries verified</span>
                                <span class="text-ink-muted">64%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-mint">
                                <div
                                    class="h-full rounded-full bg-navy"
                                    style="width: 64%"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium">Heat sheets published</span>
                                <span class="text-ink-muted">41%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-mint">
                                <div
                                    class="h-full rounded-full bg-gold"
                                    style="width: 41%"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-card bg-navy p-6 text-white shadow-card xl:col-span-2"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="sm-label text-white/50">Deck</div>
                            <h3 class="mt-1 font-serif text-2xl font-bold">
                                Lane readiness
                            </h3>
                        </div>
                        <span class="text-sm text-white/60">Live</span>
                    </div>

                    <div
                        class="mt-8 flex h-40 items-end justify-between gap-2"
                    >
                        <div
                            v-for="(height, index) in laneHeights"
                            :key="index"
                            class="flex-1 rounded-t-lg bg-gold"
                            :style="{ height: `${height}%` }"
                        />
                    </div>

                    <div
                        class="mt-4 flex justify-between text-[11px] font-semibold uppercase tracking-[0.14em] text-white/45"
                    >
                        <span>L1</span>
                        <span>L8</span>
                    </div>
                </section>
            </div>

            <section class="sm-card">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="sm-label">Next up</div>
                        <h3 class="mt-1 font-serif text-2xl font-bold">
                            Women’s 100 Free — Finals
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            Call room opens in 12 minutes · 8 lanes seeded
                        </p>
                    </div>
                    <Link :href="route('profile.edit')" class="sm-btn-secondary">
                        Review profile
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
