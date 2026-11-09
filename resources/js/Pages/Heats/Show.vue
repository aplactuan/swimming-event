<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    EventShowCompetition,
    HeatLane,
    HeatLaneStatus,
    RaceHeat,
    RaceHeatOption,
} from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    competition: EventShowCompetition;
    heat: RaceHeat;
    options: RaceHeatOption[];
    position: number;
    total: number;
    previous_heat_id: string | null;
    next_heat_id: string | null;
}>();

const page = usePage();
const saved = computed(() => page.props.flash.status === 'heat-times-saved');

const swumLanes = computed(() =>
    props.heat.lanes.filter(
        (lane) => lane.participant_id !== null || lane.team_id !== null,
    ),
);

const isRelay = computed(() => props.heat.event.type === 'relay');

const initialLanes = () =>
    swumLanes.value.map((lane) => ({
        id: lane.id,
        finish_time: lane.status === 'ns' ? '' : (lane.finish_time ?? ''),
        status: (lane.status ?? 'swam') as HeatLaneStatus,
    }));

const form = useForm({ lanes: initialLanes() });

const inputs = ref<HTMLInputElement[]>([]);

const setInputRef = (element: unknown, index: number) => {
    if (element instanceof HTMLInputElement) {
        inputs.value[index] = element;
    }
};

/**
 * Render typed digits as a time, filling from the hundredths up:
 * 3 → 0.03, 38 → 0.38, 3820 → 38.20, 10245 → 1:02.45.
 */
const formatDigits = (digits: string): string => {
    if (digits === '') {
        return '';
    }

    const padded = digits.padStart(3, '0');
    const hundredths = padded.slice(-2);
    const seconds = padded.slice(-4, -2);
    const minutes = padded.slice(0, -4);

    if (minutes === '') {
        return `${Number(seconds)}.${hundredths}`;
    }

    return `${Number(minutes)}:${seconds.padStart(2, '0')}.${hundredths}`;
};

/**
 * Keep only the last six digits typed, so the value shifts left like a stopwatch.
 */
const onTimeInput = (index: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    const formatted = formatDigits(input.value.replace(/\D/g, '').slice(-6));

    if (! form.lanes[index]) {
        return;
    }

    form.lanes[index].finish_time = formatted;
    input.value = formatted;

    nextTick(() => input.setSelectionRange(formatted.length, formatted.length));
};

const clearTime = (index: number) => {
    if (form.lanes[index]) {
        form.lanes[index].finish_time = '';
    }

    focusLane(index);
};

const focusLane = (index: number) => {
    const input = inputs.value[index];

    input?.focus();
    input?.select();
};

/**
 * Enter moves down the lanes, and saves from the last one.
 */
const onEnter = (index: number) => {
    const nextIndex = form.lanes.findIndex(
        (lane, laneIndex) => laneIndex > index && lane.status !== 'ns',
    );

    if (nextIndex !== -1) {
        focusLane(nextIndex);

        return;
    }

    save();
};

const isNs = (index: number): boolean => form.lanes[index]?.status === 'ns';

/**
 * Mark a lane as did-not-start; NS lanes never keep a finish time.
 */
const toggleNs = (index: number, checked: boolean) => {
    const lane = form.lanes[index];

    if (! lane) {
        return;
    }

    lane.status = checked ? 'ns' : 'swam';

    if (checked) {
        lane.finish_time = '';
    } else {
        nextTick(() => focusLane(index));
    }
};

/**
 * Seconds only make sense up to 59 once a minute has been typed.
 */
const hasTooManySeconds = (value: string) => {
    const [, seconds] = value.split(':');

    return seconds !== undefined && Number(seconds.split('.')[0]) > 59;
};

const laneError = (index: number): string | undefined => {
    if (hasTooManySeconds(form.lanes[index]?.finish_time ?? '')) {
        return 'Seconds must be under 60 — keep typing to roll them into minutes.';
    }

    return form.errors[`lanes.${index}.finish_time` as keyof typeof form.errors] as
        | string
        | undefined;
};

const hasInvalidTime = computed(() =>
    form.lanes.some(
        (lane) => lane.status !== 'ns' && hasTooManySeconds(lane.finish_time),
    ),
);

const goToHeat = (heatId: string | null) => {
    if (! heatId) {
        return;
    }

    router.get(
        route('competition-heats.show', [props.competition.id, heatId]),
        {},
        { preserveScroll: true },
    );
};

const save = () => {
    if (hasInvalidTime.value) {
        return;
    }

    form.patch(
        route('competition-heats.times.update', [props.competition.id, props.heat.id]),
        { preserveScroll: true },
    );
};

/**
 * Land on the first lane still waiting for a time.
 */
const focusFirstEmptyLane = () => {
    const index = form.lanes.findIndex(
        (lane) => lane.status !== 'ns' && lane.finish_time === '',
    );

    if (index !== -1) {
        focusLane(index);
    }
};

watch(
    () => props.heat.id,
    () => {
        inputs.value = [];
        form.defaults({ lanes: initialLanes() });
        form.reset();
        form.clearErrors();

        nextTick(focusFirstEmptyLane);
    },
);

onMounted(() => nextTick(focusFirstEmptyLane));

const laneName = (lane: HeatLane) => {
    if (lane.participant) {
        return `${lane.participant.last_name}, ${lane.participant.first_name}`;
    }

    return lane.team?.name ?? '—';
};

const isGuestLane = (lane: HeatLane): boolean =>
    lane.entry_event_id !== null && lane.entry_event_id !== props.heat.event.id;

const guestLaneLabel = (lane: HeatLane): string => {
    const entryEvent = lane.entry_event;

    if (! entryEvent) {
        return 'Guest';
    }

    const brackets = entryEvent.eligibilities
        .map((row) => row.age_bracket?.name)
        .filter((name): name is string => Boolean(name));

    if (brackets.length > 0) {
        return brackets.join(' / ');
    }

    return entryEvent.name;
};

const relaySwimmerNames = (lane: HeatLane): string =>
    (lane.relay_swimmers ?? [])
        .filter((swimmer) => swimmer.participant !== null)
        .map(
            (swimmer) =>
                `${swimmer.participant?.last_name}, ${swimmer.participant?.first_name}`,
        )
        .join(' · ');
</script>

<template>
    <Head :title="`Heat ${heat.heat_number} · ${heat.event.name}`" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="border-b border-surface-muted pb-7">
                <Link
                    :href="route('competitions.show', competition.id)"
                    class="inline-flex items-center text-sm font-medium text-ink-muted transition hover:text-pool"
                >
                    ← Back to {{ competition.name }}
                </Link>
                <div class="sm-label mt-5">
                    Heat {{ position }} of {{ total }}
                </div>
                <h2 class="sm-heading mt-2">{{ heat.label }}</h2>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="sm-label" for="heat-jump">Jump to heat</label>
                        <select
                            id="heat-jump"
                            class="mt-1 w-full rounded-lg border-surface-muted text-sm focus:border-pool focus:ring-pool"
                            :value="heat.id"
                            @change="goToHeat(($event.target as HTMLSelectElement).value)"
                        >
                            <option
                                v-for="option in options"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="sm-btn-secondary disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="previous_heat_id === null"
                            @click="goToHeat(previous_heat_id)"
                        >
                            ← Previous
                        </button>
                        <button
                            type="button"
                            class="sm-btn-secondary disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="next_heat_id === null"
                            @click="goToHeat(next_heat_id)"
                        >
                            Next →
                        </button>
                    </div>
                </div>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="sm-label">Results</div>
                        <h3 class="mt-1 text-xl font-semibold text-ink">
                            Finish times
                        </h3>
                        <p class="mt-1 text-sm text-ink-muted">
                            Just type the digits — <span class="font-semibold text-ink">10245</span>
                            becomes 1:02.45 and <span class="font-semibold text-ink">3820</span>
                            becomes 38.20. Enter jumps to the next lane.
                        </p>
                    </div>
                    <span
                        v-if="saved && ! form.isDirty"
                        class="text-sm font-semibold text-pool"
                    >
                        Times saved
                    </span>
                </div>

                <div
                    v-if="form.lanes.length === 0"
                    class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    {{ isRelay ? 'No teams are assigned to this heat.' : 'No swimmers are assigned to this heat.' }}
                </div>

                <form v-else class="mt-6 space-y-3" @submit.prevent="save">
                    <div
                        v-for="(lane, index) in swumLanes"
                        :key="lane.id"
                        class="rounded-xl border border-surface-muted px-4 py-3"
                    >
                        <div class="flex flex-wrap items-center gap-4">
                            <span
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface text-sm font-semibold tabular-nums text-ink"
                            >
                                {{ lane.lane_number }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold text-ink">
                                    {{ laneName(lane) }}
                                </div>
                                <div v-if="lane.participant" class="truncate text-sm text-ink-muted">
                                    {{ lane.participant.team }}
                                    <template v-if="lane.participant.classification">
                                        · {{ lane.participant.classification.name }}
                                    </template>
                                    <template v-if="isGuestLane(lane)">
                                        · Guest · {{ guestLaneLabel(lane) }}
                                    </template>
                                </div>
                                <div v-else-if="isRelay" class="truncate text-sm text-ink-muted">
                                    Relay team<template v-if="relaySwimmerNames(lane)">
                                        · {{ relaySwimmerNames(lane) }}
                                    </template>
                                </div>
                            </div>

                            <label
                                class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-ink-muted"
                            >
                                <input
                                    type="checkbox"
                                    class="rounded border-surface-muted text-pool focus:ring-pool"
                                    :checked="isNs(index)"
                                    :aria-label="`Mark lane ${lane.lane_number} as did not start`"
                                    @change="toggleNs(index, ($event.target as HTMLInputElement).checked)"
                                />
                                NS
                            </label>

                            <div class="flex items-center gap-2">
                                <label class="sr-only" :for="`lane-${lane.id}`">
                                    Finish time for lane {{ lane.lane_number }}
                                </label>
                                <input
                                    :id="`lane-${lane.id}`"
                                    :ref="(element) => setInputRef(element, index)"
                                    :value="form.lanes[index]?.finish_time ?? ''"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    :placeholder="isNs(index) ? 'NS' : '0.00'"
                                    :disabled="isNs(index)"
                                    class="w-28 rounded-lg border-surface-muted text-right text-lg font-semibold tabular-nums focus:border-pool focus:ring-pool disabled:bg-surface disabled:opacity-60"
                                    :class="{ 'border-red-300': laneError(index) }"
                                    @input="onTimeInput(index, $event)"
                                    @focus="($event.target as HTMLInputElement).select()"
                                    @keydown.enter.prevent="onEnter(index)"
                                />
                                <button
                                    type="button"
                                    class="rounded-lg px-2 py-1 text-sm font-semibold text-ink-muted transition hover:text-ink disabled:opacity-30"
                                    :disabled="isNs(index) || (form.lanes[index]?.finish_time ?? '') === ''"
                                    :aria-label="`Clear time for lane ${lane.lane_number}`"
                                    @click="clearTime(index)"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <InputError class="mt-2" :message="laneError(index)" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="sm-btn-primary"
                            :class="{ 'opacity-25': form.processing || hasInvalidTime }"
                            :disabled="form.processing || hasInvalidTime"
                        >
                            Save times
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
