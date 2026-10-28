<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
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
    event: EventResult | null;
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
    <Head :title="event ? `${competition.name} · ${event.name}` : competition.name" />

    <PublicLayout>
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
            :back-href="route('home')"
            back-label="Back to all results"
            event-show-route="public-results.show"
        />
    </PublicLayout>
</template>
