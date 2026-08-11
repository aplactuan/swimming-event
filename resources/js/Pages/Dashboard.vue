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
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <div class="sm-label">All competition</div>
                    <h2 class="sm-heading mt-1">Meet command center</h2>
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
                    New competition
                </button>
            </div>

            <section class="space-y-4">
                <div>
                    <div class="sm-label">Schedule</div>
                    <h3 class="mt-1 font-serif text-2xl font-bold text-ink">
                        Upcoming competitions
                    </h3>
                </div>

                <div
                    v-if="competitions.length > 0"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
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
                    class="sm-card border border-dashed border-surface-muted text-center"
                >
                    <p class="font-serif text-lg font-semibold text-ink">
                        No upcoming competitions
                    </p>
                    <p class="mt-1 text-sm text-ink-muted">
                        Create a meet to start building your schedule.
                    </p>
                    <button
                        type="button"
                        class="sm-btn-primary mt-4"
                        @click="openCreateCompetitionModal"
                    >
                        New competition
                    </button>
                </div>
            </section>
        </div>

        <CompetitionFormModal ref="competitionFormModal" />
        <DeleteCompetitionModal ref="deleteCompetitionModal" />
    </AuthenticatedLayout>
</template>
