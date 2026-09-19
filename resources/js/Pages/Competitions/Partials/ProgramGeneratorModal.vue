<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type {
    Competition,
    EventGender,
    ProgramSortColumn,
} from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps<{
    competition: Competition;
    eventNames: string[];
}>();

const DEFAULT_COLUMNS: ProgramSortColumn[] = [
    'classification',
    'age_bracket',
    'gender',
    'name',
];

const DEFAULT_GENDER_ORDER: EventGender[] = ['female', 'male', 'mixed'];

const COLUMN_LABELS: Record<ProgramSortColumn, string> = {
    classification: 'Classification',
    age_bracket: 'Age Bracket',
    gender: 'Gender',
    name: 'Name',
};

const GENDER_LABELS: Record<EventGender, string> = {
    female: 'Female',
    male: 'Male',
    mixed: 'Mixed',
};

const show = ref(false);

const form = useForm<{
    columns: ProgramSortColumn[];
    gender_order: EventGender[];
    name_order: string[];
}>({
    columns: [...DEFAULT_COLUMNS],
    gender_order: [...DEFAULT_GENDER_ORDER],
    name_order: [],
});

/**
 * Classifications in the order the program will follow, parents before children.
 */
const classificationOrder = computed((): string[] =>
    (props.competition.classifications ?? []).flatMap((root) => [
        root.name,
        ...root.children.map((child) => `${root.name} / ${child.name}`),
    ]),
);

/**
 * Age brackets in the order the program will follow. Brackets that share a sort
 * position and name across classifications are listed once.
 */
const ageBracketOrder = computed((): string[] => {
    const brackets = (props.competition.classifications ?? []).flatMap((root) => [
        ...root.age_brackets,
        ...root.children.flatMap((child) => child.age_brackets),
    ]);

    return [
        ...new Map(
            brackets
                .slice()
                .sort(
                    (a, b) =>
                        a.sort_order - b.sort_order || a.name.localeCompare(b.name),
                )
                .map((bracket) => [
                    `${bracket.sort_order}|${bracket.name}`,
                    bracket.name,
                ]),
        ).values(),
    ];
});

const previewOrder = (column: ProgramSortColumn): string[] =>
    column === 'classification' ? classificationOrder.value : ageBracketOrder.value;

const columnError = computed(
    () =>
        form.errors.columns ??
        Object.entries(form.errors).find(([key]) => key.startsWith('columns.'))?.[1],
);

const genderOrderError = computed(
    () =>
        form.errors.gender_order ??
        Object.entries(form.errors).find(([key]) =>
            key.startsWith('gender_order.'),
        )?.[1],
);

const nameOrderError = computed(
    () =>
        form.errors.name_order ??
        Object.entries(form.errors).find(([key]) =>
            key.startsWith('name_order.'),
        )?.[1],
);

const open = () => {
    form.defaults({
        columns: [...DEFAULT_COLUMNS],
        gender_order: [...DEFAULT_GENDER_ORDER],
        name_order: [...props.eventNames],
    });
    form.reset();
    form.clearErrors();
    show.value = true;
};

const close = () => {
    show.value = false;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    form.post(
        route('events.program', {
            competition: props.competition.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => close(),
        },
    );
};

defineExpose({ open });
</script>

<template>
    <Modal :show="show" max-width="4xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="sm-label">Program generator</div>
            <h2 class="mt-1 text-2xl font-bold text-ink">
                Generate program
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Drag the four items into the order the program should be sorted by.
                Column 1 is applied first, then column 2, and so on.
            </p>

            <InputError class="mt-2" :message="columnError" />

            <div
                v-if="eventNames.length === 0"
                class="mt-6 rounded-xl bg-surface p-4 text-sm text-ink-muted"
            >
                Add events before generating a program.
            </div>

            <draggable
                v-else
                v-model="form.columns"
                tag="ol"
                item-key="."
                handle=".drag-handle"
                :group="{ name: 'program-columns' }"
                class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                ghost-class="opacity-50"
            >
                <template #item="{ element: column, index }">
                    <li
                        class="flex flex-col rounded-xl border border-surface-muted bg-white p-4"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="drag-handle cursor-grab select-none text-ink-muted hover:text-ink"
                                title="Drag to reorder"
                                :aria-label="`Drag to reorder ${COLUMN_LABELS[column as ProgramSortColumn]}`"
                            >⋮⋮</span>
                            <span class="min-w-0">
                                <span class="block text-xs uppercase tracking-wide text-ink-muted">
                                    Column {{ index + 1 }}
                                </span>
                                <span class="block font-semibold text-ink">
                                    {{ COLUMN_LABELS[column as ProgramSortColumn] }}
                                </span>
                            </span>
                        </div>

                        <div
                            v-if="column === 'gender'"
                            class="mt-3 border-t border-surface-muted pt-3"
                        >
                            <p class="text-xs text-ink-muted">
                                Drag to set the gender order.
                            </p>
                            <draggable
                                v-model="form.gender_order"
                                tag="ol"
                                item-key="."
                                :group="{ name: 'program-genders' }"
                                class="mt-2 space-y-1"
                                ghost-class="opacity-50"
                            >
                                <template #item="{ element: gender, index: genderIndex }">
                                    <li
                                        class="flex cursor-grab items-center gap-2 rounded-lg bg-surface px-3 py-1.5 text-sm text-ink"
                                    >
                                        <span class="text-ink-muted">{{ genderIndex + 1 }}.</span>
                                        {{ GENDER_LABELS[gender as EventGender] }}
                                    </li>
                                </template>
                            </draggable>
                            <InputError class="mt-2" :message="genderOrderError" />
                        </div>

                        <div
                            v-else-if="column === 'name'"
                            class="mt-3 border-t border-surface-muted pt-3"
                        >
                            <p class="text-xs text-ink-muted">
                                Drag to set the event name order.
                            </p>
                            <draggable
                                v-model="form.name_order"
                                tag="ol"
                                item-key="."
                                :group="{ name: 'program-names' }"
                                class="mt-2 max-h-56 space-y-1 overflow-y-auto"
                                ghost-class="opacity-50"
                            >
                                <template #item="{ element: name, index: nameIndex }">
                                    <li
                                        class="flex cursor-grab items-center gap-2 rounded-lg bg-surface px-3 py-1.5 text-sm text-ink"
                                    >
                                        <span class="text-ink-muted">{{ nameIndex + 1 }}.</span>
                                        <span class="min-w-0 truncate">{{ name }}</span>
                                    </li>
                                </template>
                            </draggable>
                            <InputError class="mt-2" :message="nameOrderError" />
                        </div>

                        <div v-else class="mt-3 border-t border-surface-muted pt-3">
                            <p class="text-xs text-ink-muted">
                                Follows the sort order you configured above.
                            </p>
                            <ol
                                v-if="previewOrder(column as ProgramSortColumn).length > 0"
                                class="mt-2 max-h-56 space-y-1 overflow-y-auto"
                            >
                                <li
                                    v-for="(label, labelIndex) in previewOrder(
                                        column as ProgramSortColumn,
                                    )"
                                    :key="label"
                                    class="flex items-center gap-2 rounded-lg bg-surface px-3 py-1.5 text-sm text-ink"
                                >
                                    <span class="text-ink-muted">{{ labelIndex + 1 }}.</span>
                                    <span class="min-w-0 truncate">{{ label }}</span>
                                </li>
                            </ol>
                            <p v-else class="mt-2 text-xs text-ink-muted">
                                Nothing configured yet.
                            </p>
                        </div>
                    </li>
                </template>
            </draggable>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing || eventNames.length === 0"
                >
                    Generate program
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
