<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

type PublicCompetition = {
    id: string;
    name: string;
    venue: string;
    competition_date: string;
};

defineProps<{
    competitions: PublicCompetition[];
}>();

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
</script>

<template>
    <Head title="Competition results" />

    <PublicLayout>
        <div class="space-y-8">
            <div class="border-b border-surface-muted pb-7">
                <div class="sm-label">Public results</div>
                <h1 class="sm-heading mt-2">Competition results</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">
                    Browse official results for closed meets. Times are listed fastest first.
                </p>
            </div>

            <div
                v-if="competitions.length === 0"
                class="sm-card text-sm text-ink-muted"
            >
                No published results yet. Results appear here after a meet is closed.
            </div>

            <ul v-else class="grid gap-5 md:grid-cols-2">
                <li v-for="competition in competitions" :key="competition.id">
                    <article class="flex h-full flex-col overflow-hidden rounded-card border border-surface-muted bg-white shadow-card transition hover:-translate-y-0.5 hover:border-aqua-deep/40 hover:shadow-soft">
                        <div class="h-1 bg-aqua" aria-hidden="true" />
                        <div class="flex flex-1 flex-col p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-pool-muted">
                                {{ formatDate(competition.competition_date) }}
                            </p>
                            <h2 class="mt-2 text-xl font-semibold tracking-tight text-ink">
                                {{ competition.name }}
                            </h2>
                            <p class="mt-1 text-sm text-ink-muted">
                                {{ competition.venue }}
                            </p>
                            <div class="mt-auto pt-6">
                                <Link
                                    :href="route('public-results.index', competition.id)"
                                    class="sm-btn-primary"
                                >
                                    View results
                                </Link>
                            </div>
                        </div>
                    </article>
                </li>
            </ul>
        </div>
    </PublicLayout>
</template>
