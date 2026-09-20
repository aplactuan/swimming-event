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
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));

const formatTime = (value: string | null) => {
    if (! value) {
        return 'Not set';
    }

    const [hours, minutes] = value.split(':').map(Number);
    const date = new Date();
    date.setHours(hours, minutes, 0, 0);

    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
};

const formatEntryFee = (value: number) => new Intl.NumberFormat('en-US').format(value);
</script>

<template>
    <article class="group flex h-full flex-col overflow-hidden rounded-card border border-surface-muted bg-white shadow-card transition hover:-translate-y-0.5 hover:border-aqua-deep/40 hover:shadow-soft">
        <div class="h-1 bg-aqua" aria-hidden="true" />

        <div class="flex flex-1 flex-col p-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-pool-muted">
                    {{ formatDate(competition.competition_date) }}
                </p>
                <h3 class="mt-2 text-xl font-semibold tracking-tight text-ink">
                    <Link :href="route('competitions.show', competition.id)" class="transition group-hover:text-pool-muted">
                        {{ competition.name }}
                    </Link>
                </h3>
                <p class="mt-1 text-sm text-ink-muted">{{ competition.venue }}</p>
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-surface-muted pt-5 text-sm">
                <div>
                    <dt class="text-xs text-ink-faint">Registration closes</dt>
                    <dd class="mt-1 font-medium text-ink">{{ formatDate(competition.registration_deadline) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-faint">Entry fee</dt>
                    <dd class="mt-1 font-medium text-ink">{{ formatEntryFee(competition.entry_fee) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-faint">Warm-up</dt>
                    <dd class="mt-1 font-medium text-ink">{{ formatTime(competition.warm_up_time) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-faint">Coaches meeting</dt>
                    <dd class="mt-1 font-medium text-ink">{{ formatTime(competition.coaches_meeting_time) }}</dd>
                </div>
            </dl>

            <div class="mt-auto flex items-center gap-4 pt-6">
                <Link :href="route('competitions.show', competition.id)" class="sm-btn-primary">
                    Open competition
                </Link>
                <button type="button" class="text-sm font-semibold text-ink-muted transition hover:text-ink" @click="emit('edit', competition)">
                    Edit
                </button>
                <button type="button" class="ml-auto text-sm font-semibold text-red-700 transition hover:text-red-800" @click="emit('delete', competition)">
                    Delete
                </button>
            </div>
        </div>
    </article>
</template>
