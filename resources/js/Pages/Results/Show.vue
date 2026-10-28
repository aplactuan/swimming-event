<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EventSheet from '@/Pages/Results/Partials/EventSheet.vue';
import type { EventResult, EventResultEntry, ResultEventOption } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    competition: {
        id: string;
        name: string;
        venue?: string;
        competition_date?: string;
        is_close: boolean;
    };
    event: EventResult;
    entries: EventResultEntry[];
    ages: number[];
    options: ResultEventOption[];
    position: number;
    total: number;
    previous_event_id: string | null;
    next_event_id: string | null;
}>();
</script>

<template>
    <Head :title="`Results · ${event.name}`" />

    <AuthenticatedLayout>
        <EventSheet
            :competition="competition"
            :event="event"
            :entries="entries"
            :ages="ages"
            :options="options"
            :position="position"
            :total="total"
            :previous_event_id="previous_event_id"
            :next_event_id="next_event_id"
            :back-href="route('competitions.show', competition.id)"
            :back-label="`Back to ${competition.name}`"
            event-show-route="competition-results.show"
        />
    </AuthenticatedLayout>
</template>
