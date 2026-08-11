<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CompetitionFormModal from '@/Pages/Dashboard/Partials/CompetitionFormModal.vue';
import DeleteCompetitionModal from '@/Pages/Dashboard/Partials/DeleteCompetitionModal.vue';
import type { Competition } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const competitionFormModal = ref<{ open: (competition?: Competition) => void } | null>(
    null,
);
const deleteCompetitionModal = ref<{ open: (competition: Competition) => void } | null>(
    null,
);

const openEditCompetitionModal = () => {
    competitionFormModal.value?.open(props.competition);
};

const openDeleteCompetitionModal = () => {
    deleteCompetitionModal.value?.open(props.competition);
};

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));

const formatTime = (value: string | null) => {
    if (! value) {
        return 'TBD';
    }

    const [hours, minutes] = value.split(':').map(Number);
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);

    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
};

const formatEntryFee = (value: number) =>
    new Intl.NumberFormat('en-US').format(value);
</script>

<template>
    <Head :title="competition.name" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <Link
                        :href="route('dashboard')"
                        class="text-sm font-medium text-ink-muted transition hover:text-ink"
                    >
                        ← Back to dashboard
                    </Link>
                    <div class="sm-label mt-4">Competition</div>
                    <h2 class="sm-heading mt-1">{{ competition.name }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">
                        {{ competition.venue }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="sm-btn-secondary"
                        @click="openEditCompetitionModal"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-red-200 bg-white px-5 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:ring-offset-2"
                        @click="openDeleteCompetitionModal"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <div class="sm-card">
                <div class="sm-label">Meet details</div>
                <h3 class="mt-1 font-serif text-xl font-bold text-ink">
                    Schedule & entry
                </h3>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Competition date
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatDate(competition.competition_date) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Registration deadline
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatDate(competition.registration_deadline) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Warm-up time
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatTime(competition.warm_up_time) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Coaches meeting
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatTime(competition.coaches_meeting_time) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3 sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Entry fee
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatEntryFee(competition.entry_fee) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <CompetitionFormModal ref="competitionFormModal" />
        <DeleteCompetitionModal ref="deleteCompetitionModal" />
    </AuthenticatedLayout>
</template>
