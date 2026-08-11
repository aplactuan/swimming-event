<script setup lang="ts">
import type { Competition } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    competition: Competition;
}>();

const emit = defineEmits<{
    edit: [competition: Competition];
    delete: [competition: Competition];
}>();

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        month: 'short',
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
    <article class="sm-card flex h-full flex-col">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="sm-label">Upcoming meet</div>
                <h3 class="mt-1 truncate font-serif text-xl font-bold text-ink">
                    {{ competition.name }}
                </h3>
                <p class="mt-1 text-sm text-ink-muted">
                    {{ competition.venue }}
                </p>
            </div>
        </div>

        <dl class="mt-5 grid gap-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted">Date</dt>
                <dd class="font-medium text-ink">
                    {{ formatDate(competition.competition_date) }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted">Registration</dt>
                <dd class="font-medium text-ink">
                    {{ formatDate(competition.registration_deadline) }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted">Warm-up</dt>
                <dd class="font-medium text-ink">
                    {{ formatTime(competition.warm_up_time) }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted">Coaches meeting</dt>
                <dd class="font-medium text-ink">
                    {{ formatTime(competition.coaches_meeting_time) }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted">Entry fee</dt>
                <dd class="font-medium text-ink">
                    {{ formatEntryFee(competition.entry_fee) }}
                </dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-2 border-t border-surface-muted pt-4">
            <Link
                :href="route('competitions.show', competition.id)"
                class="sm-btn-primary"
            >
                View
            </Link>
            <button
                type="button"
                class="sm-btn-secondary"
                @click="emit('edit', competition)"
            >
                Edit
            </button>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-full border border-red-200 bg-white px-5 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:ring-offset-2"
                @click="emit('delete', competition)"
            >
                Delete
            </button>
        </div>
    </article>
</template>
