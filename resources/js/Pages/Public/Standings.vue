<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StandingsSheet from '@/Pages/Standings/Partials/StandingsSheet.vue';
import type { StandingMvpGroup, StandingTeamRow } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    competition: {
        id: string;
        name: string;
        venue?: string;
        competition_date?: string;
        is_close: boolean;
    };
    mvp: StandingMvpGroup[];
    teams: StandingTeamRow[];
    public: boolean;
}>();
</script>

<template>
    <Head :title="`Standings · ${competition.name}`" />

    <PublicLayout>
        <StandingsSheet
            :competition="competition"
            :mvp="mvp"
            :teams="teams"
            :back-href="route('home')"
            back-label="Back to all results"
            :results-href="route('public-results.index', competition.id)"
        />
    </PublicLayout>
</template>
