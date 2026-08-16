<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AddEventParticipantModal from '@/Pages/Competitions/Partials/AddEventParticipantModal.vue';
import RemoveEventParticipantModal from '@/Pages/Competitions/Partials/RemoveEventParticipantModal.vue';
import type {
    CompetitionEvent,
    EventGender,
    EventShowCompetition,
    Participant,
    ParticipantGender,
} from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: EventShowCompetition;
    event: CompetitionEvent;
}>();

const addEventParticipantModal = ref<{ open: (event: CompetitionEvent) => void } | null>(null);
const removeEventParticipantModal = ref<{
    open: (event: CompetitionEvent, participant: Participant) => void;
} | null>(null);

const participants = computed(() => props.event.participants ?? []);

const formatGender = (gender: EventGender | ParticipantGender) => {
    if (gender === 'male') {
        return 'Male';
    }

    if (gender === 'female') {
        return 'Female';
    }

    return 'Mixed';
};

const formatEligibilitySummary = (event: CompetitionEvent) =>
    event.eligibilities
        .map((row) => {
            const classification = row.classification?.name ?? 'Unknown class';
            const bracket = row.age_bracket?.name ?? 'Unknown bracket';

            return `${classification} · ${bracket}`;
        })
        .join(', ');

const formatParticipantName = (participant: Participant) =>
    `${participant.last_name}, ${participant.first_name}`;

const formatShortDate = (value: string) =>
    new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
</script>

<template>
    <Head :title="event.name" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div>
                <Link
                    :href="route('competitions.show', competition.id)"
                    class="text-sm font-medium text-ink-muted transition hover:text-ink"
                >
                    ← Back to {{ competition.name }}
                </Link>
                <div class="sm-label mt-4">Event</div>
                <h2 class="sm-heading mt-1">{{ event.name }}</h2>
                <p class="mt-2 text-sm text-ink-muted">
                    {{ formatGender(event.gender) }}
                </p>
                <p class="mt-1 text-sm text-ink">
                    {{ formatEligibilitySummary(event) }}
                </p>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="sm-label">Entries</div>
                        <h3 class="mt-1 font-serif text-xl font-bold text-ink">
                            Participants
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            View who is entered. Add paid participants manually, even when they do not match eligibility.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sm-btn-secondary"
                        @click="addEventParticipantModal?.open(event)"
                    >
                        Add participant
                    </button>
                </div>

                <div
                    v-if="participants.length === 0"
                    class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    No participants entered yet.
                </div>

                <ul v-else class="mt-6 space-y-3">
                    <li
                        v-for="participant in participants"
                        :key="participant.id"
                        class="rounded-xl border border-surface-muted bg-white p-4"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h4 class="font-semibold text-ink">
                                    {{ formatParticipantName(participant) }}
                                </h4>
                                <p class="mt-1 text-sm text-ink-muted">
                                    {{ formatGender(participant.gender) }}
                                    · {{ participant.team }}
                                    · {{ formatShortDate(participant.birthdate) }}
                                    · {{ participant.classification?.name ?? 'Unknown class' }}
                                </p>
                            </div>
                            <button
                                v-if="participant.paid"
                                type="button"
                                class="text-sm font-semibold text-red-700 hover:text-red-800"
                                @click="
                                    removeEventParticipantModal?.open(
                                        event,
                                        participant,
                                    )
                                "
                            >
                                Remove
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <AddEventParticipantModal
            ref="addEventParticipantModal"
            :competition="competition"
        />
        <RemoveEventParticipantModal
            ref="removeEventParticipantModal"
            :competition="competition"
        />
    </AuthenticatedLayout>
</template>
