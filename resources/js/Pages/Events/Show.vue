<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AddEventParticipantModal from '@/Pages/Competitions/Partials/AddEventParticipantModal.vue';
import GenerateHeatsModal from '@/Pages/Competitions/Partials/GenerateHeatsModal.vue';
import RemoveEventParticipantModal from '@/Pages/Competitions/Partials/RemoveEventParticipantModal.vue';
import type {
    CompetitionEvent,
    EventGender,
    EventShowCompetition,
    HeatLane,
    Participant,
    ParticipantGender,
} from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: EventShowCompetition;
    event: CompetitionEvent;
}>();

type Tab = 'participants' | 'heats';

const activeTab = ref<Tab>('participants');

const addEventParticipantModal = ref<{ open: (event: CompetitionEvent) => void } | null>(null);
const removeEventParticipantModal = ref<{
    open: (event: CompetitionEvent, participant: Participant) => void;
} | null>(null);
const generateHeatsModal = ref<{ open: (event: CompetitionEvent) => void } | null>(null);

const participants = computed(() => props.event.participants ?? []);
const heats = computed(() => props.event.heats ?? []);
const hasHeats = computed(() => heats.value.length > 0);

const generateForm = useForm({});
const swapForm = useForm({
    from_lane_id: '',
    to_lane_id: '',
});

const selectedLaneId = ref<string | null>(null);

const generateHeats = () => {
    activeTab.value = 'heats';

    if (hasHeats.value) {
        generateHeatsModal.value?.open(props.event);

        return;
    }

    generateForm.post(
        route('event-heats.generate', {
            competition: props.competition.id,
            event: props.event.id,
        }),
        { preserveScroll: true },
    );
};

const selectLane = (lane: HeatLane) => {
    if (swapForm.processing) {
        return;
    }

    if (selectedLaneId.value === null) {
        selectedLaneId.value = lane.id;

        return;
    }

    if (selectedLaneId.value === lane.id) {
        selectedLaneId.value = null;

        return;
    }

    swapForm.from_lane_id = selectedLaneId.value;
    swapForm.to_lane_id = lane.id;

    swapForm.patch(
        route('event-heat-lanes.swap', {
            competition: props.competition.id,
            event: props.event.id,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                selectedLaneId.value = null;
                swapForm.reset();
            },
        },
    );
};

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

const tabClass = (tab: Tab) => [
    'border-b-2 px-1 pb-3 text-sm font-semibold transition',
    activeTab.value === tab
        ? 'border-pool text-pool'
        : 'border-transparent text-ink-muted hover:border-surface-muted hover:text-ink',
];

const laneClass = (lane: HeatLane) => [
    'flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-left transition',
    selectedLaneId.value === lane.id
        ? 'border-pool bg-mint-soft ring-2 ring-pool/30'
        : lane.participant
            ? 'border-surface-muted bg-white hover:border-pool/60'
            : 'border-dashed border-surface-muted bg-surface hover:border-pool/60',
];
</script>

<template>
    <Head :title="event.name" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="border-b border-surface-muted pb-7">
                <Link
                    :href="route('competitions.show', competition.id)"
                    class="inline-flex text-sm font-medium text-ink-muted transition hover:text-pool"
                >
                    ← Back to {{ competition.name }}
                </Link>
                <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="sm-label">Race event</div>
                        <h2 class="sm-heading mt-2">{{ event.name }}</h2>
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full bg-mint-soft px-3 py-1 font-semibold text-pool">{{ formatGender(event.gender) }}</span>
                            <span class="text-ink-muted">{{ formatEligibilitySummary(event) }}</span>
                        </div>
                    </div>
                    <button
                        v-if="! competition.is_close"
                        type="button"
                        class="sm-btn-primary"
                        :disabled="generateForm.processing || participants.length === 0"
                        @click="generateHeats"
                    >
                        {{ hasHeats ? 'Regenerate heats' : 'Generate heats' }}
                    </button>
                </div>
            </div>

            <div class="sm-card">
                <nav class="flex gap-6 border-b border-surface-muted" aria-label="Event sections">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'participants'"
                        :class="tabClass('participants')"
                        @click="activeTab = 'participants'"
                    >
                        Participants
                        <span class="ml-1 tabular-nums text-ink-muted">{{ participants.length }}</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'heats'"
                        :class="tabClass('heats')"
                        @click="activeTab = 'heats'"
                    >
                        Heats
                        <span class="ml-1 tabular-nums text-ink-muted">{{ heats.length }}</span>
                    </button>
                </nav>

                <section v-if="activeTab === 'participants'" class="mt-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div class="sm-label">Entries</div>
                            <h3 class="mt-1 text-xl font-semibold text-ink">
                                Participants
                            </h3>
                            <p class="mt-1 text-sm text-ink-muted">
                                View who is entered. Add paid participants manually, even when they do not match eligibility.
                            </p>
                        </div>
                        <button
                            v-if="! competition.is_close"
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
                                        · {{ participant.age }} yrs
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
                </section>

                <section v-else class="mt-6">
                    <div>
                        <div class="sm-label">Seeding</div>
                        <h3 class="mt-1 text-xl font-semibold text-ink">
                            Heats
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ competition.number_of_lane }} lanes per heat. Select a lane, then select another to swap
                            swimmers — within a heat or across heats.
                        </p>
                    </div>

                    <div
                        v-if="!hasHeats"
                        class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                    >
                        <template v-if="participants.length === 0">
                            Add participants before generating heats.
                        </template>
                        <template v-else>
                            No heats generated yet.
                        </template>
                    </div>

                    <div v-else class="mt-6 grid gap-4 md:grid-cols-2">
                        <div
                            v-for="heat in heats"
                            :key="heat.id"
                            class="rounded-xl border border-surface-muted bg-white p-4"
                        >
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-ink">Heat {{ heat.heat_number }}</h4>
                                <span class="text-xs text-ink-muted">
                                    {{ heat.lanes.filter((lane) => lane.participant).length }} swimmers
                                </span>
                            </div>

                            <ul class="mt-3 space-y-2">
                                <li v-for="lane in heat.lanes" :key="lane.id">
                                    <button
                                        type="button"
                                        :class="laneClass(lane)"
                                        :disabled="swapForm.processing"
                                        :aria-pressed="selectedLaneId === lane.id"
                                        @click="selectLane(lane)"
                                    >
                                        <span class="sm-lane-chip">{{ lane.lane_number }}</span>
                                        <span v-if="lane.participant" class="min-w-0 flex-1">
                                            <span class="block truncate font-medium text-ink">
                                                {{ formatParticipantName(lane.participant) }}
                                            </span>
                                            <span class="block truncate text-xs text-ink-muted">
                                                {{ lane.participant.team }}
                                                · {{ lane.participant.age }} yrs
                                            </span>
                                        </span>
                                        <span v-else class="flex-1 text-sm italic text-ink-muted">
                                            Empty lane
                                        </span>
                                        <span
                                            v-if="lane.finish_time"
                                            class="text-sm font-semibold tabular-nums text-ink"
                                        >
                                            {{ lane.finish_time }}
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <p
                        v-if="swapForm.errors.from_lane_id || swapForm.errors.to_lane_id"
                        class="mt-4 text-sm text-red-700"
                    >
                        {{ swapForm.errors.from_lane_id ?? swapForm.errors.to_lane_id }}
                    </p>
                </section>
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
        <GenerateHeatsModal
            ref="generateHeatsModal"
            :competition="competition"
        />
    </AuthenticatedLayout>
</template>
