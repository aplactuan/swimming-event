<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CompetitionCard from '@/Pages/Dashboard/Partials/CompetitionCard.vue';
import CompetitionFormModal from '@/Pages/Dashboard/Partials/CompetitionFormModal.vue';
import DeleteCompetitionModal from '@/Pages/Dashboard/Partials/DeleteCompetitionModal.vue';
import type { Competition } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    competitions: Competition[];
}>();

const competitionFormModal = ref<{ open: (competition?: Competition) => void } | null>(
    null,
);
const deleteCompetitionModal = ref<{ open: (competition: Competition) => void } | null>(
    null,
);

const openCreateCompetitionModal = () => {
    competitionFormModal.value?.open();
};

const openEditCompetitionModal = (competition: Competition) => {
    competitionFormModal.value?.open(competition);
};

const openDeleteCompetitionModal = (competition: Competition) => {
    deleteCompetitionModal.value?.open(competition);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl space-y-8">
            <div
                class="flex flex-col gap-5 border-b border-surface-muted pb-7 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <div class="sm-label">Competition workspace</div>
                    <h2 class="sm-heading mt-2">Your competitions</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">
                        Set up each meet, manage swimmers, and prepare the race program.
                    </p>
                </div>

                <button
                    type="button"
                    class="sm-btn-primary self-start sm:self-auto"
                    @click="openCreateCompetitionModal"
                >
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                        <path
                            d="M12 5v14M5 12h14"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        />
                    </svg>
                    Create competition
                </button>
            </div>

            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-base font-semibold text-ink">Upcoming</h3>
                    <span v-if="competitions.length > 0" class="text-sm text-ink-muted">
                        {{ competitions.length }} {{ competitions.length === 1 ? 'competition' : 'competitions' }}
                    </span>
                </div>

                <div
                    v-if="competitions.length > 0"
                    class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
                >
                    <CompetitionCard
                        v-for="competition in competitions"
                        :key="competition.id"
                        :competition="competition"
                        @edit="openEditCompetitionModal"
                        @delete="openDeleteCompetitionModal"
                    />
                </div>

                <div
                    v-else
                    class="rounded-card border border-dashed border-ink-faint/50 bg-white px-6 py-14 text-center"
                >
                    <p class="text-lg font-semibold text-ink">
                        No competitions scheduled
                    </p>
                    <p class="mt-1 text-sm text-ink-muted">
                        Create your first competition to begin setting up events and entries.
                    </p>
                    <button
                        type="button"
                        class="sm-btn-primary mt-4"
                        @click="openCreateCompetitionModal"
                    >
                        Create competition
                    </button>
                </div>
            </section>
        </div>

        <CompetitionFormModal ref="competitionFormModal" />
        <DeleteCompetitionModal ref="deleteCompetitionModal" />
    </AuthenticatedLayout>
</template>
