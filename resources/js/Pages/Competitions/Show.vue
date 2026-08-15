<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AgeBracketFormModal from '@/Pages/Competitions/Partials/AgeBracketFormModal.vue';
import ClassificationFormModal from '@/Pages/Competitions/Partials/ClassificationFormModal.vue';
import DeleteAgeBracketModal from '@/Pages/Competitions/Partials/DeleteAgeBracketModal.vue';
import DeleteClassificationModal from '@/Pages/Competitions/Partials/DeleteClassificationModal.vue';
import DeleteEventModal from '@/Pages/Competitions/Partials/DeleteEventModal.vue';
import DeleteParticipantModal from '@/Pages/Competitions/Partials/DeleteParticipantModal.vue';
import EventFormModal from '@/Pages/Competitions/Partials/EventFormModal.vue';
import ParticipantFormModal from '@/Pages/Competitions/Partials/ParticipantFormModal.vue';
import CompetitionFormModal from '@/Pages/Dashboard/Partials/CompetitionFormModal.vue';
import DeleteCompetitionModal from '@/Pages/Dashboard/Partials/DeleteCompetitionModal.vue';
import type {
    AgeBracket,
    Classification,
    Competition,
    CompetitionEvent,
    EventGender,
    Participant,
    ParticipantGender,
} from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const competitionFormModal = ref<{ open: (competition?: Competition) => void } | null>(
    null,
);
const deleteCompetitionModal = ref<{ open: (competition: Competition) => void } | null>(
    null,
);
const classificationFormModal = ref<{
    open: (options?: { classification?: Classification; parent?: Classification }) => void;
} | null>(null);
const deleteClassificationModal = ref<{ open: (classification: Classification) => void } | null>(
    null,
);
const ageBracketFormModal = ref<{
    open: (classification: Classification, ageBracket?: AgeBracket) => void;
} | null>(null);
const deleteAgeBracketModal = ref<{
    open: (classification: Classification, ageBracket: AgeBracket) => void;
} | null>(null);
const eventFormModal = ref<{ open: (event?: CompetitionEvent) => void } | null>(null);
const deleteEventModal = ref<{ open: (event: CompetitionEvent) => void } | null>(null);
const participantFormModal = ref<{ open: (participant?: Participant) => void } | null>(null);
const deleteParticipantModal = ref<{ open: (participant: Participant) => void } | null>(null);

const classifications = computed(() => props.competition.classifications ?? []);
const events = computed(() => props.competition.events ?? []);
const participants = computed(() => props.competition.participants ?? []);

const openEditCompetitionModal = () => {
    competitionFormModal.value?.open(props.competition);
};

const openDeleteCompetitionModal = () => {
    deleteCompetitionModal.value?.open(props.competition);
};

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));

const formatShortDate = (value: string) =>
    new Intl.DateTimeFormat('en-US', {
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

const formatAgeBracketRange = (bracket: AgeBracket) => {
    if (bracket.start_birthday && bracket.end_birthday) {
        return `${formatShortDate(bracket.start_birthday)} – ${formatShortDate(bracket.end_birthday)}`;
    }

    if (bracket.start_birthday) {
        return `On or after ${formatShortDate(bracket.start_birthday)}`;
    }

    if (bracket.end_birthday) {
        return `On or before ${formatShortDate(bracket.end_birthday)}`;
    }

    return 'No birthday range';
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
</script>

<template>
    <Head :title="competition.name" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <Link
                        :href="route('dashboard')"
                        class="text-sm font-medium text-ink-muted transition hover:text-ink"
                    >
                        ← Back to dashboard
                    </Link>
                    <div class="sm-label mt-4">Competition</div>
                    <h2 class="sm-heading mt-1">{{ competition.name }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">
                        {{ competition.venue }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="sm-btn-secondary"
                        @click="openEditCompetitionModal"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-red-200 bg-white px-5 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:ring-offset-2"
                        @click="openDeleteCompetitionModal"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <div class="sm-card">
                <div class="sm-label">Meet details</div>
                <h3 class="mt-1 font-serif text-xl font-bold text-ink">
                    Schedule & entry
                </h3>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Competition date
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatDate(competition.competition_date) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Registration deadline
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatDate(competition.registration_deadline) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Warm-up time
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatTime(competition.warm_up_time) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Coaches meeting
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatTime(competition.coaches_meeting_time) }}
                        </dd>
                    </div>

                    <div class="rounded-xl bg-surface px-4 py-3 sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Entry fee
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatEntryFee(competition.entry_fee) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="sm-label">Meet structure</div>
                        <h3 class="mt-1 font-serif text-xl font-bold text-ink">
                            Classifications
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            Nest classes up to one level and add age brackets with
                            birthday cutoffs.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sm-btn-secondary"
                        @click="classificationFormModal?.open()"
                    >
                        Add classification
                    </button>
                </div>

                <div v-if="classifications.length === 0" class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted">
                    No classifications yet.
                </div>

                <ul v-else class="mt-6 space-y-4">
                    <li
                        v-for="classification in classifications"
                        :key="classification.id"
                        class="rounded-xl border border-surface-muted bg-white p-4"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h4 class="font-semibold text-ink">
                                    {{ classification.name }}
                                </h4>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-ink-muted hover:text-ink"
                                    @click="classificationFormModal?.open({ parent: classification })"
                                >
                                    Add class
                                </button>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-ink-muted hover:text-ink"
                                    @click="ageBracketFormModal?.open(classification)"
                                >
                                    Add age bracket
                                </button>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-ink-muted hover:text-ink"
                                    @click="classificationFormModal?.open({ classification })"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-red-700 hover:text-red-800"
                                    @click="deleteClassificationModal?.open(classification)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <ul
                            v-if="classification.age_brackets.length > 0"
                            class="mt-3 space-y-2"
                        >
                            <li
                                v-for="bracket in classification.age_brackets"
                                :key="bracket.id"
                                class="flex flex-col gap-2 rounded-lg bg-surface px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div class="text-sm font-medium text-ink">
                                        {{ bracket.name }}
                                    </div>
                                    <div class="text-xs text-ink-muted">
                                        {{ formatAgeBracketRange(bracket) }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-ink-muted hover:text-ink"
                                        @click="ageBracketFormModal?.open(classification, bracket)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-red-700 hover:text-red-800"
                                        @click="deleteAgeBracketModal?.open(classification, bracket)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </li>
                        </ul>

                        <ul
                            v-if="classification.children.length > 0"
                            class="mt-4 space-y-3 border-l border-surface-muted pl-4"
                        >
                            <li
                                v-for="child in classification.children"
                                :key="child.id"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <h5 class="font-medium text-ink">
                                        {{ child.name }}
                                    </h5>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            class="text-sm font-semibold text-ink-muted hover:text-ink"
                                            @click="ageBracketFormModal?.open(child)"
                                        >
                                            Add age bracket
                                        </button>
                                        <button
                                            type="button"
                                            class="text-sm font-semibold text-ink-muted hover:text-ink"
                                            @click="classificationFormModal?.open({ classification: child })"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="text-sm font-semibold text-red-700 hover:text-red-800"
                                            @click="deleteClassificationModal?.open(child)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <ul
                                    v-if="child.age_brackets.length > 0"
                                    class="mt-2 space-y-2"
                                >
                                    <li
                                        v-for="bracket in child.age_brackets"
                                        :key="bracket.id"
                                        class="flex flex-col gap-2 rounded-lg bg-surface px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <div class="text-sm font-medium text-ink">
                                                {{ bracket.name }}
                                                <span
                                                    v-if="child.inherits_age_brackets"
                                                    class="ml-2 text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted"
                                                >
                                                    Inherited
                                                </span>
                                            </div>
                                            <div class="text-xs text-ink-muted">
                                                {{ formatAgeBracketRange(bracket) }}
                                            </div>
                                        </div>
                                        <div
                                            v-if="! child.inherits_age_brackets"
                                            class="flex gap-2"
                                        >
                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-ink-muted hover:text-ink"
                                                @click="ageBracketFormModal?.open(child, bracket)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                class="text-sm font-semibold text-red-700 hover:text-red-800"
                                                @click="deleteAgeBracketModal?.open(child, bracket)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                                <p
                                    v-else-if="child.inherits_age_brackets"
                                    class="mt-2 text-xs text-ink-muted"
                                >
                                    Inherits parent age brackets (none set on parent yet).
                                </p>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="sm-label">Entries</div>
                        <h3 class="mt-1 font-serif text-xl font-bold text-ink">
                            Participants
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            Register swimmers. Marking paid auto-enters matching events.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sm-btn-secondary"
                        @click="participantFormModal?.open()"
                    >
                        Add participant
                    </button>
                </div>

                <div
                    v-if="participants.length === 0"
                    class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    No participants yet.
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
                                    · {{ participant.paid ? 'Paid' : 'Unpaid' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-ink-muted hover:text-ink"
                                    @click="participantFormModal?.open(participant)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-red-700 hover:text-red-800"
                                    @click="deleteParticipantModal?.open(participant)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="sm-label">Program</div>
                        <h3 class="mt-1 font-serif text-xl font-bold text-ink">
                            Events
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            Define swim events and which classification + age
                            bracket pairs may enter.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sm-btn-secondary"
                        @click="eventFormModal?.open()"
                    >
                        Add event
                    </button>
                </div>

                <div
                    v-if="events.length === 0"
                    class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    No events yet.
                </div>

                <ul v-else class="mt-6 space-y-3">
                    <li
                        v-for="event in events"
                        :key="event.id"
                        class="rounded-xl border border-surface-muted bg-white p-4"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h4 class="font-semibold text-ink">
                                    <Link
                                        :href="
                                            route('events.show', {
                                                competition: competition.id,
                                                event: event.id,
                                            })
                                        "
                                        class="hover:underline"
                                    >
                                        {{ event.name }}
                                    </Link>
                                </h4>
                                <p class="mt-1 text-sm text-ink-muted">
                                    {{ formatGender(event.gender) }}
                                    · {{ (event.participants ?? []).length }}
                                    {{
                                        (event.participants ?? []).length === 1
                                            ? 'participant'
                                            : 'participants'
                                    }}
                                </p>
                                <p class="mt-2 text-sm text-ink">
                                    {{ formatEligibilitySummary(event) }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    :href="
                                        route('events.show', {
                                            competition: competition.id,
                                            event: event.id,
                                        })
                                    "
                                    class="text-sm font-semibold text-ink-muted hover:text-ink"
                                >
                                    View
                                </Link>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-ink-muted hover:text-ink"
                                    @click="eventFormModal?.open(event)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-red-700 hover:text-red-800"
                                    @click="deleteEventModal?.open(event)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <CompetitionFormModal ref="competitionFormModal" />
        <DeleteCompetitionModal ref="deleteCompetitionModal" />
        <ClassificationFormModal
            ref="classificationFormModal"
            :competition="competition"
        />
        <DeleteClassificationModal
            ref="deleteClassificationModal"
            :competition="competition"
        />
        <AgeBracketFormModal
            ref="ageBracketFormModal"
            :competition="competition"
        />
        <DeleteAgeBracketModal
            ref="deleteAgeBracketModal"
            :competition="competition"
        />
        <ParticipantFormModal
            ref="participantFormModal"
            :competition="competition"
        />
        <DeleteParticipantModal
            ref="deleteParticipantModal"
            :competition="competition"
        />
        <EventFormModal ref="eventFormModal" :competition="competition" />
        <DeleteEventModal ref="deleteEventModal" :competition="competition" />
    </AuthenticatedLayout>
</template>
