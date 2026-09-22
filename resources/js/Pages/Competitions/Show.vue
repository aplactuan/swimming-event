<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AgeBracketFormModal from '@/Pages/Competitions/Partials/AgeBracketFormModal.vue';
import ClassificationFormModal from '@/Pages/Competitions/Partials/ClassificationFormModal.vue';
import CloseCompetitionModal from '@/Pages/Competitions/Partials/CloseCompetitionModal.vue';
import DeleteAgeBracketModal from '@/Pages/Competitions/Partials/DeleteAgeBracketModal.vue';
import DeleteClassificationModal from '@/Pages/Competitions/Partials/DeleteClassificationModal.vue';
import DeleteEventModal from '@/Pages/Competitions/Partials/DeleteEventModal.vue';
import DeleteParticipantModal from '@/Pages/Competitions/Partials/DeleteParticipantModal.vue';
import EventFormModal from '@/Pages/Competitions/Partials/EventFormModal.vue';
import EventGeneratorModal from '@/Pages/Competitions/Partials/EventGeneratorModal.vue';
import GenerateAllHeatsModal from '@/Pages/Competitions/Partials/GenerateAllHeatsModal.vue';
import ImportParticipantsModal from '@/Pages/Competitions/Partials/ImportParticipantsModal.vue';
import OpenCompetitionModal from '@/Pages/Competitions/Partials/OpenCompetitionModal.vue';
import ParticipantFormModal from '@/Pages/Competitions/Partials/ParticipantFormModal.vue';
import ProgramGeneratorModal from '@/Pages/Competitions/Partials/ProgramGeneratorModal.vue';
import CompetitionFormModal from '@/Pages/Dashboard/Partials/CompetitionFormModal.vue';
import DeleteCompetitionModal from '@/Pages/Dashboard/Partials/DeleteCompetitionModal.vue';
import type {
    AgeBracket,
    Classification,
    Competition,
    CompetitionEvent,
    EventGender,
    Paginated,
    Participant,
    ParticipantGender,
} from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, toRaw, watch } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps<{
    competition: Competition;
    participants: Paginated<Participant>;
    events: Paginated<CompetitionEvent>;
    event_names: string[];
    age_bracket_names: string[];
    filters: {
        participant_search: string;
        event_name: string;
        event_classification: string;
        event_age_bracket: string;
        event_gender: string;
    };
}>();

const competitionFormModal = ref<{ open: (competition?: Competition) => void } | null>(
    null,
);
const deleteCompetitionModal = ref<{ open: (competition: Competition) => void } | null>(
    null,
);
const closeCompetitionModal = ref<{ open: () => void } | null>(null);
const openCompetitionModal = ref<{ open: () => void } | null>(null);
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
const eventGeneratorModal = ref<{ open: () => void } | null>(null);
const programGeneratorModal = ref<{ open: () => void } | null>(null);
const generateAllHeatsModal = ref<{ open: () => void } | null>(null);
const deleteEventModal = ref<{ open: (event: CompetitionEvent) => void } | null>(null);
const participantFormModal = ref<{ open: (participant?: Participant) => void } | null>(null);
const importParticipantsModal = ref<{ open: () => void } | null>(null);
const deleteParticipantModal = ref<{ open: (participant: Participant) => void } | null>(null);

const page = usePage();
const importSummary = computed(() => page.props.flash.import_summary);
const importSummaryMessage = computed(() => {
    const summary = importSummary.value;

    if (summary === null) {
        return '';
    }

    const parts = [
        `Imported ${summary.imported} ${summary.imported === 1 ? 'participant' : 'participants'}.`,
    ];

    if (summary.skipped_duplicates > 0) {
        parts.push(
            `Skipped ${summary.skipped_duplicates} ${summary.skipped_duplicates === 1 ? 'duplicate' : 'duplicates'}.`,
        );
    }

    if (summary.skipped_invalid > 0) {
        parts.push(
            `Skipped ${summary.skipped_invalid} invalid ${summary.skipped_invalid === 1 ? 'row' : 'rows'}.`,
        );
    }

    if (summary.classifications_created > 0) {
        parts.push(
            `Created ${summary.classifications_created} ${summary.classifications_created === 1 ? 'classification' : 'classifications'}.`,
        );
    }

    return parts.join(' ');
});

const classificationList = ref<Classification[]>([]);

/**
 * Deep clone Inertia page props, which are reactive proxies that
 * `structuredClone` refuses to handle.
 */
const cloneClassifications = (value: Classification[] | undefined): Classification[] =>
    JSON.parse(JSON.stringify(toRaw(value) ?? [])) as Classification[];

watch(
    () => props.competition.classifications,
    (value) => {
        classificationList.value = cloneClassifications(value);
    },
    { immediate: true },
);

const reorderClassifications = (parentId: string | null, items: Classification[]) => {
    router.patch(
        route('classifications.reorder', props.competition.id),
        {
            parent_id: parentId,
            ids: items.map((item) => item.id),
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['competition'],
        },
    );
};

const reorderAgeBrackets = (classificationId: string, items: AgeBracket[]) => {
    router.patch(
        route('age-brackets.reorder', [props.competition.id, classificationId]),
        {
            ids: items.map((item) => item.id),
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['competition'],
        },
    );
};
const detailsOpen = ref(false);
const participantSearch = ref(props.filters.participant_search);

let participantSearchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.filters.participant_search,
    (value) => {
        participantSearch.value = value;
    },
);

const eventFilters = ref({
    name: props.filters.event_name,
    classification: props.filters.event_classification,
    ageBracket: props.filters.event_age_bracket,
    gender: props.filters.event_gender,
});

watch(
    () => [
        props.filters.event_name,
        props.filters.event_classification,
        props.filters.event_age_bracket,
        props.filters.event_gender,
    ],
    ([name, classification, ageBracket, gender]) => {
        eventFilters.value = { name, classification, ageBracket, gender };
    },
);

const rootClassifications = computed(() => props.competition.classifications ?? []);

const genderOptions: { value: EventGender; label: string }[] = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'mixed', label: 'Mixed' },
];

const ageBracketOptions = computed(() => {
    const scoped: Classification[] = [];

    for (const root of rootClassifications.value) {
        const children = root.children ?? [];

        if (eventFilters.value.classification) {
            if (root.id === eventFilters.value.classification) {
                scoped.push(root, ...children);
            }

            continue;
        }

        scoped.push(root, ...children);
    }

    const scopedNames = new Set(
        scoped.flatMap((classification) =>
            (classification.age_brackets ?? []).map((bracket) => bracket.name),
        ),
    );

    return props.age_bracket_names.filter((name) => scopedNames.has(name));
});

const hasEventFilters = computed(() =>
    Object.values(eventFilters.value).some((value) => value !== ''),
);

const visitLists = (
    overrides: Record<string, string | number | undefined> = {},
    only: string[] = ['participants', 'events', 'filters'],
) => {
    router.get(
        route('competitions.show', props.competition.id),
        {
            participant_search: props.filters.participant_search || undefined,
            event_name: props.filters.event_name || undefined,
            event_classification: props.filters.event_classification || undefined,
            event_age_bracket: props.filters.event_age_bracket || undefined,
            event_gender: props.filters.event_gender || undefined,
            participants_page:
                props.participants.meta.current_page > 1
                    ? props.participants.meta.current_page
                    : undefined,
            events_page:
                props.events.meta.current_page > 1
                    ? props.events.meta.current_page
                    : undefined,
            ...overrides,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only,
        },
    );
};

watch(participantSearch, (value) => {
    if (participantSearchTimeout) {
        clearTimeout(participantSearchTimeout);
    }

    participantSearchTimeout = setTimeout(() => {
        if (value === props.filters.participant_search) {
            return;
        }

        visitLists(
            {
                participant_search: value || undefined,
                participants_page: undefined,
            },
            ['participants', 'filters'],
        );
    }, 300);
});

const applyEventFilters = () => {
    visitLists(
        {
            event_name: eventFilters.value.name || undefined,
            event_classification: eventFilters.value.classification || undefined,
            event_age_bracket: eventFilters.value.ageBracket || undefined,
            event_gender: eventFilters.value.gender || undefined,
            events_page: undefined,
        },
        ['events', 'filters'],
    );
};

/**
 * Drop an age bracket that the newly chosen classification no longer offers.
 */
const applyEventFiltersFromClassification = () => {
    if (! ageBracketOptions.value.includes(eventFilters.value.ageBracket)) {
        eventFilters.value.ageBracket = '';
    }

    applyEventFilters();
};

const clearEventFilters = () => {
    eventFilters.value = { name: '', classification: '', ageBracket: '', gender: '' };

    applyEventFilters();
};

const goToParticipantsPage = (page: number) => {
    if (page < 1 || page > props.participants.meta.last_page || page === props.participants.meta.current_page) {
        return;
    }

    visitLists(
        {
            participants_page: page > 1 ? page : undefined,
        },
        ['participants', 'filters'],
    );
};

const goToEventsPage = (page: number) => {
    if (page < 1 || page > props.events.meta.last_page || page === props.events.meta.current_page) {
        return;
    }

    visitLists(
        {
            events_page: page > 1 ? page : undefined,
        },
        ['events', 'filters'],
    );
};

const participantCountLabel = (event: CompetitionEvent) => {
    const count = event.participants_count ?? event.participants?.length ?? 0;

    return `${count} ${count === 1 ? 'participant' : 'participants'}`;
};

const isCompetitionClosed = computed(() => props.competition.is_close);

const openCloseCompetitionModal = () => {
    closeCompetitionModal.value?.open();
};

const openOpenCompetitionModal = () => {
    openCompetitionModal.value?.open();
};

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
        return `Birthday is ${formatShortDate(bracket.start_birthday)} – ${formatShortDate(bracket.end_birthday)}`;
    }

    if (bracket.start_birthday) {
        return `Birthday is on or after ${formatShortDate(bracket.start_birthday)}`;
    }

    if (bracket.end_birthday) {
        return `Birthday is on or before ${formatShortDate(bracket.end_birthday)}`;
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
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex flex-col gap-5 border-b border-surface-muted pb-7 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center text-sm font-medium text-ink-muted transition hover:text-pool"
                    >
                        ← Back to dashboard
                    </Link>
                    <div class="sm-label mt-5">Competition</div>
                    <h2 class="sm-heading mt-2">{{ competition.name }}</h2>
                    <p class="mt-2 text-sm font-medium text-ink-muted">
                        {{ competition.venue }}
                    </p>
                    <span
                        v-if="isCompetitionClosed"
                        class="mt-3 inline-flex items-center rounded-full bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted"
                    >
                        Closed
                    </span>
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
                        v-if="! isCompetitionClosed"
                        type="button"
                        class="sm-btn-primary"
                        @click="openCloseCompetitionModal"
                    >
                        Close competition
                    </button>
                    <button
                        v-if="isCompetitionClosed"
                        type="button"
                        class="sm-btn-secondary"
                        @click="openOpenCompetitionModal"
                    >
                        Reopen competition
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:ring-offset-2"
                        @click="openDeleteCompetitionModal"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <div class="sm-card">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-4 text-left"
                    :aria-expanded="detailsOpen"
                    @click="detailsOpen = !detailsOpen"
                >
                    <div>
                        <div class="sm-label">Meet details</div>
                        <h3 class="mt-1 text-xl font-semibold text-ink">
                            Schedule & entry
                        </h3>
                    </div>
                    <span
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-surface-muted bg-white text-ink transition"
                        :class="{ 'rotate-180': detailsOpen }"
                        aria-hidden="true"
                    >
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                            <path
                                d="M6 9l6 6 6-6"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                </button>

                <dl
                    v-show="detailsOpen"
                    class="mt-6 grid gap-px overflow-hidden rounded-lg border border-surface-muted bg-surface-muted sm:grid-cols-2"
                >
                    <div class="bg-white px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Competition date
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatDate(competition.competition_date) }}
                        </dd>
                    </div>

                    <div class="bg-white px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Registration deadline
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatDate(competition.registration_deadline) }}
                        </dd>
                    </div>

                    <div class="bg-white px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Warm-up time
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatTime(competition.warm_up_time) }}
                        </dd>
                    </div>

                    <div class="bg-white px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Coaches meeting
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatTime(competition.coaches_meeting_time) }}
                        </dd>
                    </div>

                    <div class="bg-white px-4 py-4 sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">
                            Entry fee
                        </dt>
                        <dd class="mt-1 font-medium text-ink">
                            {{ formatEntryFee(competition.entry_fee) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
                <div class="sm-card">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div class="sm-label">Entries</div>
                            <h3 class="mt-1 text-xl font-semibold text-ink">
                                Participants
                            </h3>
                            <p class="mt-1 text-sm text-ink-muted">
                                Register swimmers. Marking paid auto-enters matching events.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-if="! isCompetitionClosed"
                                type="button"
                                class="sm-btn-secondary"
                                @click="importParticipantsModal?.open()"
                            >
                                Import
                            </button>
                            <button
                                v-if="! isCompetitionClosed"
                                type="button"
                                class="sm-btn-secondary"
                                @click="participantFormModal?.open()"
                            >
                                Add participant
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="importSummaryMessage"
                        class="mt-4 rounded-xl bg-surface px-4 py-3 text-sm text-ink"
                    >
                        {{ importSummaryMessage }}
                    </div>

                    <div class="mt-4">
                        <label class="sr-only" for="participant-search">
                            Search participants
                        </label>
                        <input
                            id="participant-search"
                            v-model="participantSearch"
                            type="search"
                            class="sm-input block w-full"
                            placeholder="Search by full name or last name"
                            autocomplete="off"
                        />
                    </div>

                    <div
                        v-if="participants.meta.total === 0 && ! filters.participant_search"
                        class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                    >
                        No participants yet.
                    </div>

                    <div
                        v-else-if="participants.data.length === 0"
                        class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                    >
                        No participants match “{{ filters.participant_search }}”.
                    </div>

                    <template v-else>
                        <ul class="mt-6 space-y-3">
                            <li
                                v-for="participant in participants.data"
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

                        <div
                            v-if="participants.meta.last_page > 1"
                            class="mt-4 flex items-center justify-between gap-3 text-sm text-ink-muted"
                        >
                            <p>
                                Showing
                                {{ participants.meta.from }}–{{ participants.meta.to }}
                                of {{ participants.meta.total }}
                            </p>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="sm-btn-secondary px-3 py-1.5"
                                    :disabled="participants.meta.current_page <= 1"
                                    @click="goToParticipantsPage(participants.meta.current_page - 1)"
                                >
                                    Previous
                                </button>
                                <button
                                    type="button"
                                    class="sm-btn-secondary px-3 py-1.5"
                                    :disabled="
                                        participants.meta.current_page >= participants.meta.last_page
                                    "
                                    @click="goToParticipantsPage(participants.meta.current_page + 1)"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="sm-card">
                    <div>
                        <div class="sm-label">Program</div>
                        <h3 class="mt-1 text-xl font-semibold text-ink">
                            Events
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            Define swim events and which classification + age
                            bracket pairs may enter.
                        </p>
                    </div>

                    <div class="mt-4 rounded-xl border border-surface-muted bg-surface p-3">
                        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                            <Link
                                v-if="isCompetitionClosed"
                                :href="route('competition-heats.index', competition.id)"
                                class="sm-btn-primary justify-center"
                            >
                                Go to Heat
                            </Link>
                            <Link
                                v-if="isCompetitionClosed"
                                :href="route('competition-results.index', competition.id)"
                                class="sm-btn-secondary justify-center"
                            >
                                View Result
                            </Link>
                            <button
                                v-if="! isCompetitionClosed"
                                type="button"
                                class="sm-btn-secondary"
                                @click="generateAllHeatsModal?.open()"
                            >
                                Generate heats
                            </button>
                            <button
                                v-if="! isCompetitionClosed"
                                type="button"
                                class="sm-btn-secondary"
                                @click="programGeneratorModal?.open()"
                            >
                                Generate program
                            </button>
                            <button
                                v-if="! isCompetitionClosed"
                                type="button"
                                class="sm-btn-secondary"
                                @click="eventGeneratorModal?.open()"
                            >
                                Generate events
                            </button>
                            <button
                                v-if="! isCompetitionClosed"
                                type="button"
                                class="sm-btn-secondary"
                                @click="eventFormModal?.open()"
                            >
                                Add event
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="sm-label" for="event-filter-name">Name</label>
                            <select
                                id="event-filter-name"
                                v-model="eventFilters.name"
                                class="sm-input mt-1 block w-full"
                                @change="applyEventFilters"
                            >
                                <option value="">All names</option>
                                <option
                                    v-for="name in event_names"
                                    :key="name"
                                    :value="name"
                                >
                                    {{ name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" for="event-filter-classification">
                                Classification
                            </label>
                            <select
                                id="event-filter-classification"
                                v-model="eventFilters.classification"
                                class="sm-input mt-1 block w-full"
                                @change="applyEventFiltersFromClassification"
                            >
                                <option value="">All classifications</option>
                                <option
                                    v-for="classification in rootClassifications"
                                    :key="classification.id"
                                    :value="classification.id"
                                >
                                    {{ classification.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" for="event-filter-gender">
                                Gender
                            </label>
                            <select
                                id="event-filter-gender"
                                v-model="eventFilters.gender"
                                class="sm-input mt-1 block w-full"
                                @change="applyEventFilters"
                            >
                                <option value="">All genders</option>
                                <option
                                    v-for="option in genderOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" for="event-filter-age-bracket">
                                Age bracket
                            </label>
                            <select
                                id="event-filter-age-bracket"
                                v-model="eventFilters.ageBracket"
                                class="sm-input mt-1 block w-full"
                                :disabled="ageBracketOptions.length === 0"
                                @change="applyEventFilters"
                            >
                                <option value="">
                                    {{ ageBracketOptions.length === 0 ? 'No age brackets' : 'All age brackets' }}
                                </option>
                                <option
                                    v-for="name in ageBracketOptions"
                                    :key="name"
                                    :value="name"
                                >
                                    {{ name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div
                        v-if="events.meta.total === 0 && ! hasEventFilters"
                        class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                    >
                        No events yet.
                    </div>

                    <div
                        v-else-if="events.data.length === 0"
                        class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                    >
                        <p>No events match the selected filters.</p>
                        <button
                            type="button"
                            class="mt-3 text-sm font-semibold text-pool hover:underline"
                            @click="clearEventFilters"
                        >
                            Clear filters
                        </button>
                    </div>

                    <template v-else>
                        <ul class="mt-6 space-y-3">
                            <li
                                v-for="event in events.data"
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
                                            · {{ participantCountLabel(event) }}
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

                        <div
                            v-if="events.meta.last_page > 1"
                            class="mt-4 flex items-center justify-between gap-3 text-sm text-ink-muted"
                        >
                            <p>
                                Showing
                                {{ events.meta.from }}–{{ events.meta.to }}
                                of {{ events.meta.total }}
                            </p>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="sm-btn-secondary px-3 py-1.5"
                                    :disabled="events.meta.current_page <= 1"
                                    @click="goToEventsPage(events.meta.current_page - 1)"
                                >
                                    Previous
                                </button>
                                <button
                                    type="button"
                                    class="sm-btn-secondary px-3 py-1.5"
                                    :disabled="events.meta.current_page >= events.meta.last_page"
                                    @click="goToEventsPage(events.meta.current_page + 1)"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="sm-label">Meet structure</div>
                        <h3 class="mt-1 text-xl font-semibold text-ink">
                            Classifications
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            Nest classes up to one level and add age brackets with
                            birthday cutoffs.
                        </p>
                    </div>
                    <button
                        v-if="! isCompetitionClosed"
                        type="button"
                        class="sm-btn-secondary"
                        @click="classificationFormModal?.open()"
                    >
                        Add classification
                    </button>
                </div>

                <div v-if="classificationList.length === 0" class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted">
                    No classifications yet.
                </div>

                <draggable
                    v-else
                    v-model="classificationList"
                    tag="ul"
                    item-key="id"
                    handle=".drag-handle"
                    class="mt-6 space-y-4"
                    ghost-class="opacity-50"
                    @end="reorderClassifications(null, classificationList)"
                >
                    <template #item="{ element: classification }">
                        <li class="rounded-xl border border-surface-muted bg-white p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="drag-handle cursor-grab select-none text-ink-muted hover:text-ink"
                                        title="Drag to reorder"
                                        aria-label="Drag to reorder classification"
                                    >⋮⋮</span>
                                    <h4 class="font-semibold text-ink">
                                        {{ classification.name }}
                                    </h4>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-if="! isCompetitionClosed"
                                        type="button"
                                        class="text-sm font-semibold text-ink-muted hover:text-ink"
                                        @click="classificationFormModal?.open({ parent: classification })"
                                    >
                                        Add class
                                    </button>
                                    <button
                                        v-if="! isCompetitionClosed"
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

                            <draggable
                                v-if="classification.age_brackets.length > 0"
                                v-model="classification.age_brackets"
                                tag="ul"
                                item-key="id"
                                handle=".drag-handle"
                                class="mt-3 space-y-2"
                                ghost-class="opacity-50"
                                @end="reorderAgeBrackets(classification.id, classification.age_brackets)"
                            >
                                <template #item="{ element: bracket }">
                                    <li class="flex flex-col gap-2 rounded-lg bg-surface px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="drag-handle cursor-grab select-none text-ink-muted hover:text-ink"
                                                title="Drag to reorder"
                                                aria-label="Drag to reorder age bracket"
                                            >⋮⋮</span>
                                            <div>
                                                <div class="text-sm font-medium text-ink">
                                                    {{ bracket.name }}
                                                </div>
                                                <div class="text-xs text-ink-muted">
                                                    {{ formatAgeBracketRange(bracket) }}
                                                </div>
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
                                </template>
                            </draggable>

                            <draggable
                                v-if="classification.children.length > 0"
                                v-model="classification.children"
                                tag="ul"
                                item-key="id"
                                handle=".drag-handle"
                                class="mt-4 space-y-3 border-l border-surface-muted pl-4"
                                ghost-class="opacity-50"
                                @end="reorderClassifications(classification.id, classification.children)"
                            >
                                <template #item="{ element: child }">
                                    <li>
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="drag-handle cursor-grab select-none text-ink-muted hover:text-ink"
                                                    title="Drag to reorder"
                                                    aria-label="Drag to reorder classification"
                                                >⋮⋮</span>
                                                <h5 class="font-medium text-ink">
                                                    {{ child.name }}
                                                </h5>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-if="! isCompetitionClosed"
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

                                        <draggable
                                            v-if="child.age_brackets.length > 0 && ! child.inherits_age_brackets"
                                            v-model="child.age_brackets"
                                            tag="ul"
                                            item-key="id"
                                            handle=".drag-handle"
                                            class="mt-2 space-y-2"
                                            ghost-class="opacity-50"
                                            @end="reorderAgeBrackets(child.id, child.age_brackets)"
                                        >
                                            <template #item="{ element: bracket }">
                                                <li class="flex flex-col gap-2 rounded-lg bg-surface px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="drag-handle cursor-grab select-none text-ink-muted hover:text-ink"
                                                            title="Drag to reorder"
                                                            aria-label="Drag to reorder age bracket"
                                                        >⋮⋮</span>
                                                        <div>
                                                            <div class="text-sm font-medium text-ink">
                                                                {{ bracket.name }}
                                                            </div>
                                                            <div class="text-xs text-ink-muted">
                                                                {{ formatAgeBracketRange(bracket) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-2">
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
                                            </template>
                                        </draggable>

                                        <ul
                                            v-else-if="child.age_brackets.length > 0 && child.inherits_age_brackets"
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
                                                        <span class="ml-2 text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">
                                                            Inherited
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-ink-muted">
                                                        {{ formatAgeBracketRange(bracket) }}
                                                    </div>
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
                                </template>
                            </draggable>
                        </li>
                    </template>
                </draggable>
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
        <ImportParticipantsModal
            ref="importParticipantsModal"
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
        <EventGeneratorModal
            ref="eventGeneratorModal"
            :competition="competition"
        />
        <ProgramGeneratorModal
            ref="programGeneratorModal"
            :competition="competition"
            :event-names="event_names"
        />
        <GenerateAllHeatsModal
            ref="generateAllHeatsModal"
            :competition="competition"
        />
        <DeleteEventModal ref="deleteEventModal" :competition="competition" />
        <CloseCompetitionModal
            ref="closeCompetitionModal"
            :competition="competition"
        />
        <OpenCompetitionModal
            ref="openCompetitionModal"
            :competition="competition"
        />
    </AuthenticatedLayout>
</template>
