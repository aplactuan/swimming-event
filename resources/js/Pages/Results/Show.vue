<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { EventResult, EventResultEntry, ResultEventOption } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    competition: { id: string; name: string; is_close: boolean };
    event: EventResult;
    entries: EventResultEntry[];
    ages: number[];
    options: ResultEventOption[];
    position: number;
    total: number;
    previous_event_id: string | null;
    next_event_id: string | null;
}>();

const selectedAges = ref<number[]>([]);

/**
 * With no age ticked every swimmer is shown, so the sheet reads as a full result
 * until the reader narrows it to the ages they are ranking.
 */
const filteredEntries = computed(() =>
    selectedAges.value.length === 0
        ? props.entries
        : props.entries.filter((entry) =>
              selectedAges.value.includes(entry.participant.age),
          ),
);

/**
 * Place within what is on screen, so ticking ages ranks the fastest of those ages.
 */
const placeOf = (index: number): string => {
    const entry = filteredEntries.value[index];

    if (! entry || entry.finish_time_hundredths === null) {
        return '—';
    }

    return String(index + 1);
};

const timedCount = computed(
    () =>
        filteredEntries.value.filter((entry) => entry.finish_time_hundredths !== null)
            .length,
);

const participantName = (entry: EventResultEntry) =>
    `${entry.participant.last_name}, ${entry.participant.first_name}`;

const goToEvent = (eventId: string | null) => {
    if (! eventId) {
        return;
    }

    router.get(
        route('competition-results.show', [props.competition.id, eventId]),
        {},
        { preserveScroll: true },
    );
};

watch(
    () => props.event.id,
    () => {
        selectedAges.value = [];
    },
);
</script>

<template>
    <Head :title="`Results · ${event.name}`" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="border-b border-surface-muted pb-7">
                <Link
                    :href="route('competitions.show', competition.id)"
                    class="inline-flex items-center text-sm font-medium text-ink-muted transition hover:text-pool"
                >
                    ← Back to {{ competition.name }}
                </Link>
                <div class="sm-label mt-5">Event {{ position }} of {{ total }}</div>
                <h2 class="sm-heading mt-2">{{ event.label }}</h2>
            </div>

            <div class="sm-card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="sm-label" for="result-jump">Jump to event</label>
                        <select
                            id="result-jump"
                            class="mt-1 w-full rounded-lg border-surface-muted text-sm focus:border-pool focus:ring-pool"
                            :value="event.id"
                            @change="goToEvent(($event.target as HTMLSelectElement).value)"
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
            </div>

            <div v-if="ages.length > 0" class="sm-card">
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
                        v-for="age in ages"
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
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ timedCount }} of {{ filteredEntries.length }} swimmers have a
                        recorded time.
                    </p>
                </div>

                <div
                    v-if="filteredEntries.length === 0"
                    class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    {{
                        entries.length === 0
                            ? 'No swimmers are entered in this event yet.'
                            : 'No swimmers match the ages you ticked.'
                    }}
                </div>

                <div v-else class="mt-6 space-y-3">
                    <div
                        v-for="(entry, index) in filteredEntries"
                        :key="entry.id"
                        class="rounded-xl border border-surface-muted px-4 py-3"
                        :class="{ 'opacity-60': entry.finish_time_hundredths === null }"
                    >
                        <div class="flex flex-wrap items-center gap-4">
                            <span
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface text-sm font-semibold tabular-nums text-ink"
                            >
                                {{ placeOf(index) }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold text-ink">
                                    {{ participantName(entry) }}
                                </div>
                                <div class="truncate text-sm text-ink-muted">
                                    Age {{ entry.participant.age }}
                                    <template v-if="entry.participant.team">
                                        · {{ entry.participant.team }}
                                    </template>
                                    <template v-if="entry.participant.classification">
                                        · {{ entry.participant.classification.name }}
                                    </template>
                                    · Heat {{ entry.heat_number }} lane
                                    {{ entry.lane_number }}
                                </div>
                            </div>

                            <span class="text-lg font-semibold tabular-nums text-ink">
                                {{ entry.finish_time ?? 'No time' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
