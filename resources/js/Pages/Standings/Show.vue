<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StandingsSheet from '@/Pages/Standings/Partials/StandingsSheet.vue';
import type { StandingMvpRow, StandingTeamRow } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    competition: {
        id: string;
        name: string;
        venue?: string;
        competition_date?: string;
        is_close: boolean;
    };
    mvp: StandingMvpRow[];
    teams: StandingTeamRow[];
    public: boolean;
}>();
</script>

<template>
    <Head :title="`Standings · ${competition.name}`" />

    <AuthenticatedLayout>
        <StandingsSheet
            :competition="competition"
            :mvp="mvp"
            :teams="teams"
            :back-href="route('competitions.show', competition.id)"
            :back-label="`Back to ${competition.name}`"
            :results-href="route('competition-results.index', competition.id)"
        />
    </AuthenticatedLayout>
</template>
