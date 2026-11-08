<script setup lang="ts">
import type {
    EventResult,
    EventResultEntry,
    ResultEventOption,
    ResultEventOptionEligibility,
} from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    competition: {
        id: string;
        name: string;
        venue?: string;
        competition_date?: string;
        is_close: boolean;
    };
    event: EventResult | null;
    entries: EventResultEntry[];
    ages: (number | null)[];
    options: ResultEventOption[];
    position: number;
    total: number;
    previous_event_id: string | null;
    next_event_id: string | null;
    backHref: string;
    backLabel: string;
    eventShowRoute: string;
    standingsHref?: string;
}>();

const selectedAges = ref<number[]>([]);
const selectedClassificationId = ref('');
const selectedAgeBracketId = ref('');
const selectedEventName = ref('');
const selectedGender = ref('');

const availableAges = computed(() =>
    props.ages.filter((age): age is number => age !== null && age !== undefined),
);

const isRelay = computed(() => props.event?.type === 'relay');

const filteredEntries = computed(() =>
    selectedAges.value.length === 0
        ? props.entries
        : props.entries.filter(
              (entry) =>
                  entry.participant !== null
                  && selectedAges.value.includes(entry.participant.age),
          ),
);

const currentOption = computed(() =>
    props.options.find((option) => option.id === props.event?.id) ?? null,
);

const classificationOptions = computed(() => {
    const byId = new Map<string, ResultEventOptionEligibility>();

    for (const option of props.options) {
        for (const eligibility of option.eligibilities) {
            if (! byId.has(eligibility.classification_id)) {
                byId.set(eligibility.classification_id, eligibility);
            }
        }
    }

    return [...byId.values()].sort(
        (a, b) =>
            a.classification_sort_order - b.classification_sort_order
            || a.classification.localeCompare(b.classification),
    );
});

const ageBracketOptions = computed(() => {
    const byId = new Map<string, ResultEventOptionEligibility>();

    for (const option of props.options) {
        for (const eligibility of option.eligibilities) {
            if (eligibility.classification_id !== selectedClassificationId.value) {
                continue;
            }

            if (! byId.has(eligibility.age_bracket_id)) {
                byId.set(eligibility.age_bracket_id, eligibility);
            }
        }
    }

    return [...byId.values()].sort(
        (a, b) =>
            a.age_bracket_sort_order - b.age_bracket_sort_order
            || a.age_bracket.localeCompare(b.age_bracket),
    );
});

const eventNameOptions = computed(() => {
    const names = new Set<string>();

    for (const option of props.options) {
        if (! matchesEligibility(option, selectedClassificationId.value, selectedAgeBracketId.value)) {
            continue;
        }

        names.add(option.name);
    }

    return [...names].sort((a, b) => a.localeCompare(b));
});

const genderOptions = computed(() => {
    const genders = new Set<string>();

    for (const option of props.options) {
        if (option.name !== selectedEventName.value) {
            continue;
        }

        if (! matchesEligibility(option, selectedClassificationId.value, selectedAgeBracketId.value)) {
            continue;
        }

        genders.add(option.gender);
    }

    return [...genders].sort((a, b) => a.localeCompare(b));
});

const placeOf = (entry: EventResultEntry): string =>
    entry.status === 'ns' || entry.place === null ? '—' : String(entry.place);

const isNs = (entry: EventResultEntry): boolean => entry.status === 'ns';

const timedCount = computed(
    () =>
        filteredEntries.value.filter(
            (entry) => entry.status !== 'ns' && entry.finish_time_hundredths !== null,
        ).length,
);

const entryName = (entry: EventResultEntry): string => {
    if (entry.participant) {
        return `${entry.participant.last_name}, ${entry.participant.first_name}`;
    }

    return entry.team?.name ?? 'Unknown entry';
};

const teamName = (entry: EventResultEntry): string | null =>
    entry.team?.name ?? entry.participant?.team ?? null;

const relaySwimmerNames = (entry: EventResultEntry): string =>
    (entry.relay_swimmers ?? [])
        .filter((swimmer) => swimmer.participant !== null)
        .map(
            (swimmer) =>
                `${swimmer.participant?.last_name}, ${swimmer.participant?.first_name}`,
        )
        .join(' · ');

const formatPoints = (value: number): string =>
    new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);

const formatGender = (gender: string): string => {
    if (gender === 'female') {
        return 'Female';
    }

    if (gender === 'mixed') {
        return 'Mixed';
    }

    return 'Male';
};

const matchesEligibility = (
    option: ResultEventOption,
    classificationId: string,
    ageBracketId: string,
): boolean =>
    option.eligibilities.some(
        (eligibility) =>
            eligibility.classification_id === classificationId
            && eligibility.age_bracket_id === ageBracketId,
    );

const findMatchingEventId = (
    classificationId: string,
    ageBracketId: string,
    eventName: string,
    gender: string,
): string | null => {
    const match = props.options.find(
        (option) =>
            option.name === eventName
            && option.gender === gender
            && matchesEligibility(option, classificationId, ageBracketId),
    );

    return match?.id ?? null;
};

const syncJumpFromEvent = () => {
    const option = currentOption.value;

    if (! option) {
        selectedClassificationId.value = '';
        selectedAgeBracketId.value = '';
        selectedEventName.value = '';
        selectedGender.value = '';

        return;
    }

    const eligibility = option.eligibilities[0];

    selectedClassificationId.value = eligibility?.classification_id ?? '';
    selectedAgeBracketId.value = eligibility?.age_bracket_id ?? '';
    selectedEventName.value = option.name;
    selectedGender.value = option.gender;
};

const goToEvent = (eventId: string | null) => {
    if (! eventId || eventId === props.event?.id) {
        return;
    }

    router.get(
        route(props.eventShowRoute, [props.competition.id, eventId]),
        {},
        { preserveScroll: true },
    );
};

const onClassificationChange = () => {
    selectedAgeBracketId.value = '';
    selectedEventName.value = '';
    selectedGender.value = '';
};

const onAgeBracketChange = () => {
    selectedEventName.value = '';
    selectedGender.value = '';
};

const onEventNameChange = () => {
    selectedGender.value = '';
};

const onGenderChange = () => {
    goToEvent(
        findMatchingEventId(
            selectedClassificationId.value,
            selectedAgeBracketId.value,
            selectedEventName.value,
            selectedGender.value,
        ),
    );
};

watch(
    () => props.event?.id,
    () => {
        selectedAges.value = [];
        syncJumpFromEvent();
    },
    { immediate: true },
);
</script>

<template>
    <div class="space-y-6">
        <div class="border-b border-surface-muted pb-7">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="backHref"
                    class="inline-flex items-center text-sm font-medium text-ink-muted transition hover:text-pool"
                >
                    ← {{ backLabel }}
                </Link>
                <Link
                    v-if="standingsHref"
                    :href="standingsHref"
                    class="sm-btn-secondary"
                >
                    Standings
                </Link>
            </div>
            <template v-if="event">
                <div class="sm-label mt-5">Event {{ position }} of {{ total }}</div>
                <h2 class="sm-heading mt-2">{{ event.label }}</h2>
            </template>
            <template v-else>
                <div class="sm-label mt-5">Results</div>
                <h2 class="sm-heading mt-2">{{ competition.name }}</h2>
            </template>
        </div>

        <div v-if="event" class="sm-card">
            <div class="sm-label">Jump to event</div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted" for="result-jump-classification">
                        Classification
                    </label>
                    <select
                        id="result-jump-classification"
                        v-model="selectedClassificationId"
                        class="mt-1 w-full rounded-lg border-surface-muted text-sm focus:border-pool focus:ring-pool"
                        @change="onClassificationChange"
                    >
                        <option disabled value="">Select classification</option>
                        <option
                            v-for="option in classificationOptions"
                            :key="option.classification_id"
                            :value="option.classification_id"
                        >
                            {{ option.classification }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted" for="result-jump-age-bracket">
                        Age bracket
                    </label>
                    <select
                        id="result-jump-age-bracket"
                        v-model="selectedAgeBracketId"
                        class="mt-1 w-full rounded-lg border-surface-muted text-sm focus:border-pool focus:ring-pool"
                        :disabled="selectedClassificationId === ''"
                        @change="onAgeBracketChange"
                    >
                        <option disabled value="">Select age bracket</option>
                        <option
                            v-for="option in ageBracketOptions"
                            :key="option.age_bracket_id"
                            :value="option.age_bracket_id"
                        >
                            {{ option.age_bracket }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted" for="result-jump-event">
                        Event
                    </label>
                    <select
                        id="result-jump-event"
                        v-model="selectedEventName"
                        class="mt-1 w-full rounded-lg border-surface-muted text-sm focus:border-pool focus:ring-pool"
                        :disabled="selectedAgeBracketId === ''"
                        @change="onEventNameChange"
                    >
                        <option disabled value="">Select event</option>
                        <option
                            v-for="name in eventNameOptions"
                            :key="name"
                            :value="name"
                        >
                            {{ name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted" for="result-jump-gender">
                        Gender
                    </label>
                    <select
                        id="result-jump-gender"
                        v-model="selectedGender"
                        class="mt-1 w-full rounded-lg border-surface-muted text-sm focus:border-pool focus:ring-pool"
                        :disabled="selectedEventName === ''"
                        @change="onGenderChange"
                    >
                        <option disabled value="">Select gender</option>
                        <option
                            v-for="gender in genderOptions"
                            :key="gender"
                            :value="gender"
                        >
                            {{ formatGender(gender) }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button
                    type="button"
                    class="sm-btn-secondary disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="previous_event_id === null"
                    @click="goToEvent(previous_event_id)"
                >
                    ← Previous
                </button>
                <button
                    type="button"
                    class="sm-btn-secondary disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="next_event_id === null"
                    @click="goToEvent(next_event_id)"
                >
                    Next →
                </button>
            </div>
        </div>

        <div v-if="event && availableAges.length > 0" class="sm-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="sm-label">Filter</div>
                    <h3 class="mt-1 text-xl font-semibold text-ink">Age</h3>
                    <p class="mt-1 text-sm text-ink-muted">
                        Tick the ages you want ranked. With none ticked every
                        swimmer in the event is shown.
                    </p>
                </div>
                <button
                    v-if="selectedAges.length > 0"
                    type="button"
                    class="text-sm font-semibold text-ink-muted transition hover:text-ink"
                    @click="selectedAges = []"
                >
                    Clear
                </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <label
                    v-for="age in availableAges"
                    :key="age"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-surface-muted px-3 py-2 text-sm font-semibold text-ink transition hover:bg-surface"
                    :class="{
                        'border-pool bg-pool/5 text-pool': selectedAges.includes(age),
                    }"
                >
                    <input
                        v-model="selectedAges"
                        type="checkbox"
                        :value="age"
                        class="rounded border-surface-muted text-pool focus:ring-pool"
                    />
                    {{ age }}
                </label>
            </div>
        </div>

        <div class="sm-card">
            <div>
                <div class="sm-label">Results</div>
                <h3 class="mt-1 text-xl font-semibold text-ink">Fastest first</h3>
                <p v-if="event" class="mt-1 text-sm text-ink-muted">
                    {{ timedCount }} of {{ filteredEntries.length }}
                    {{ isRelay ? 'teams' : 'swimmers' }} have a recorded time.
                </p>
            </div>

            <div
                v-if="! event"
                class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
            >
                This meet has no events to show yet.
            </div>

            <div
                v-else-if="filteredEntries.length === 0"
                class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
            >
                {{
                    entries.length === 0
                        ? (isRelay ? 'No teams are entered in this event yet.' : 'No swimmers are entered in this event yet.')
                        : 'No swimmers match the ages you ticked.'
                }}
            </div>

            <div v-else class="mt-6 space-y-3">
                <div
                    v-for="entry in filteredEntries"
                    :key="entry.id"
                    class="rounded-xl border border-surface-muted px-4 py-3"
                    :class="{
                        'opacity-60': isNs(entry) || entry.finish_time_hundredths === null,
                    }"
                >
                    <div class="flex flex-wrap items-center gap-4">
                        <span
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface text-sm font-semibold tabular-nums text-ink"
                        >
                            {{ placeOf(entry) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-ink">
                                {{ entryName(entry) }}
                            </div>
                            <div class="truncate text-sm text-ink-muted">
                                <template v-if="entry.participant">
                                    Age {{ entry.participant.age }}
                                    <template v-if="teamName(entry)">
                                        · {{ teamName(entry) }}
                                    </template>
                                    <template v-if="entry.participant.classification">
                                        · {{ entry.participant.classification.name }}
                                    </template>
                                    ·
                                </template>
                                <template v-else-if="relaySwimmerNames(entry)">
                                    {{ relaySwimmerNames(entry) }} ·
                                </template>
                                Heat {{ entry.heat_number }} lane
                                {{ entry.lane_number }}
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <span
                                v-if="entry.points !== null"
                                class="text-sm font-semibold tabular-nums text-pool"
                            >
                                {{ formatPoints(entry.points) }} pts
                            </span>
                            <span
                                v-if="isNs(entry)"
                                class="text-lg font-semibold uppercase tracking-[0.12em] text-ink-muted"
                            >
                                NS
                            </span>
                            <span
                                v-else
                                class="text-lg font-semibold tabular-nums text-ink"
                            >
                                {{ entry.finish_time ?? 'No time' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
