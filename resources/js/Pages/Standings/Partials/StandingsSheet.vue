<script setup lang="ts">
import type { StandingMvpRow, StandingTeamRow } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    competition: {
        id: string;
        name: string;
        venue?: string;
        competition_date?: string;
        is_close: boolean;
    };
    mvp: StandingMvpRow[];
    teams: StandingTeamRow[];
    backHref: string;
    backLabel: string;
    resultsHref: string;
}>();

const formatPoints = (value: number): string =>
    new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);

/**
 * Rank rows by points, letting equal totals share a place.
 */
const rankRows = <T extends { points: number }>(rows: T[]): (T & { rank: number })[] => {
    let previousPoints: number | null = null;
    let previousRank = 0;

    return rows.map((row, index) => {
        const rank = row.points === previousPoints ? previousRank : index + 1;

        previousPoints = row.points;
        previousRank = rank;

        return { ...row, rank };
    });
};

const rankedMvp = (rows: StandingMvpRow[]) => rankRows(rows);
const rankedTeams = (rows: StandingTeamRow[]) => rankRows(rows);
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
                <Link :href="resultsHref" class="sm-btn-secondary">
                    Event results
                </Link>
            </div>
            <div class="sm-label mt-5">Standings</div>
            <h2 class="sm-heading mt-2">{{ competition.name }}</h2>
            <p v-if="competition.venue" class="mt-2 text-sm font-medium text-ink-muted">
                {{ competition.venue }}
            </p>
            <span
                v-if="! competition.is_close"
                class="mt-3 inline-flex items-center rounded-full bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted"
            >
                Provisional — meet still open
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <div class="sm-card">
                <div class="sm-label">Individual</div>
                <h3 class="mt-1 text-xl font-semibold text-ink">MVP</h3>
                <p class="mt-1 text-sm text-ink-muted">
                    Points from individual events only. Relay points are not counted.
                </p>

                <div
                    v-if="mvp.length === 0"
                    class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    No points have been awarded yet.
                </div>

                <div v-else class="mt-6 space-y-3">
                    <div
                        v-for="row in rankedMvp(mvp)"
                        :key="row.participant_id"
                        class="rounded-xl border border-surface-muted px-4 py-3"
                    >
                        <div class="flex items-center gap-4">
                            <span
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface text-sm font-semibold tabular-nums text-ink"
                            >
                                {{ row.rank }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold text-ink">
                                    {{ row.last_name }}, {{ row.first_name }}
                                </div>
                                <div class="truncate text-sm text-ink-muted">
                                    {{ row.team ?? 'No team' }}
                                </div>
                            </div>
                            <span class="text-lg font-semibold tabular-nums text-ink">
                                {{ formatPoints(row.points) }}
                                <span class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">pts</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sm-card">
                <div class="sm-label">Team</div>
                <h3 class="mt-1 text-xl font-semibold text-ink">Team champion</h3>
                <p class="mt-1 text-sm text-ink-muted">
                    Individual and relay points combined.
                </p>

                <div
                    v-if="teams.length === 0"
                    class="mt-6 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    No points have been awarded yet.
                </div>

                <div v-else class="mt-6 space-y-3">
                    <div
                        v-for="row in rankedTeams(teams)"
                        :key="row.team_id"
                        class="rounded-xl border border-surface-muted px-4 py-3"
                    >
                        <div class="flex items-center gap-4">
                            <span
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface text-sm font-semibold tabular-nums text-ink"
                            >
                                {{ row.rank }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold text-ink">
                                    {{ row.name }}
                                </div>
                            </div>
                            <span class="text-lg font-semibold tabular-nums text-ink">
                                {{ formatPoints(row.points) }}
                                <span class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">pts</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
