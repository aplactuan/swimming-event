<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type {
    AgeBracket,
    Classification,
    Competition,
    EventGender,
} from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

type EligibilityRow = {
    classification_id: string;
    age_bracket_id: string;
};

type FlatClassification = Classification & {
    label: string;
};

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const nameInput = ref<{ focus: () => void } | null>(null);
const genderOptions: { value: EventGender; label: string }[] = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'mixed', label: 'Mixed' },
];

const form = useForm<{
    name: string;
    genders: EventGender[];
    eligibilities: EligibilityRow[];
}>({
    name: '',
    genders: [],
    eligibilities: [],
});

const flatClassifications = computed((): FlatClassification[] => {
    const roots = props.competition.classifications ?? [];

    return roots.flatMap((root) => [
        { ...root, label: root.name },
        ...root.children.map((child) => ({
            ...child,
            label: `${root.name} / ${child.name}`,
        })),
    ]);
});

const generatedEventCount = computed(
    () => form.genders.length * form.eligibilities.length,
);

const genderError = computed(
    () =>
        form.errors.genders ??
        Object.entries(form.errors).find(([key]) => key.startsWith('genders.'))?.[1],
);

const eligibilityError = computed(
    () =>
        form.errors.eligibilities ??
        Object.entries(form.errors).find(([key]) =>
            key.startsWith('eligibilities.'),
        )?.[1],
);

const isEligibilitySelected = (
    classificationId: string,
    ageBracketId: string,
) =>
    form.eligibilities.some(
        (row) =>
            row.classification_id === classificationId &&
            row.age_bracket_id === ageBracketId,
    );

const selectedBracketCount = (classification: FlatClassification) =>
    classification.age_brackets.filter((bracket) =>
        isEligibilitySelected(classification.id, bracket.id),
    ).length;

const isClassificationSelected = (classification: FlatClassification) =>
    classification.age_brackets.length > 0 &&
    selectedBracketCount(classification) === classification.age_brackets.length;

const toggleEligibility = (
    classification: FlatClassification,
    ageBracket: AgeBracket,
    checked: boolean,
) => {
    if (checked && ! isEligibilitySelected(classification.id, ageBracket.id)) {
        form.eligibilities.push({
            classification_id: classification.id,
            age_bracket_id: ageBracket.id,
        });

        return;
    }

    if (! checked) {
        form.eligibilities = form.eligibilities.filter(
            (row) =>
                row.classification_id !== classification.id ||
                row.age_bracket_id !== ageBracket.id,
        );
    }
};

const toggleClassification = (
    classification: FlatClassification,
    checked: boolean,
) => {
    form.eligibilities = form.eligibilities.filter(
        (row) => row.classification_id !== classification.id,
    );

    if (checked) {
        form.eligibilities.push(
            ...classification.age_brackets.map((bracket) => ({
                classification_id: classification.id,
                age_bracket_id: bracket.id,
            })),
        );
    }
};

const open = () => {
    form.reset();
    form.clearErrors();
    show.value = true;

    nextTick(() => nameInput.value?.focus());
};

const close = () => {
    show.value = false;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    form.post(
        route('events.generate', {
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
    <Modal :show="show" max-width="2xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="sm-label">Event generator</div>
            <h2 class="mt-1 text-2xl font-bold text-ink">
                Generate event program
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                One event will be created for every selected gender and age bracket
                combination.
            </p>

            <div class="mt-6 grid gap-6">
                <div>
                    <InputLabel for="generated_event_name" value="Event name" />
                    <TextInput
                        id="generated_event_name"
                        ref="nameInput"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        maxlength="255"
                        autocomplete="off"
                        placeholder="25m Freestyle"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-ink">Gender</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        <label
                            v-for="option in genderOptions"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-surface-muted bg-surface px-4 py-3 text-sm font-medium text-ink"
                        >
                            <input
                                v-model="form.genders"
                                type="checkbox"
                                :value="option.value"
                                class="rounded border-surface-muted text-aqua shadow-sm focus:ring-aqua"
                            />
                            {{ option.label }}
                        </label>
                    </div>
                    <InputError class="mt-2" :message="genderError" />
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-medium text-ink">
                        Classifications and age brackets
                    </legend>
                    <p class="mt-1 text-sm text-ink-muted">
                        Selecting a classification selects all of its age brackets. You
                        can then adjust individual brackets.
                    </p>
                    <InputError class="mt-2" :message="eligibilityError" />

                    <div
                        v-if="flatClassifications.length > 0"
                        class="mt-3 grid gap-3 sm:grid-cols-2"
                    >
                        <div
                            v-for="classification in flatClassifications"
                            :key="classification.id"
                            class="rounded-xl border border-surface-muted p-4"
                        >
                            <label class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="mt-0.5 rounded border-surface-muted text-aqua shadow-sm focus:ring-aqua"
                                    :checked="isClassificationSelected(classification)"
                                    :disabled="classification.age_brackets.length === 0"
                                    @change="
                                        toggleClassification(
                                            classification,
                                            ($event.target as HTMLInputElement).checked,
                                        )
                                    "
                                />
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-ink">
                                        {{ classification.label }}
                                    </span>
                                    <span class="block text-xs text-ink-muted">
                                        {{ selectedBracketCount(classification) }} of
                                        {{ classification.age_brackets.length }} selected
                                    </span>
                                </span>
                            </label>

                            <div
                                v-if="classification.age_brackets.length > 0"
                                class="mt-3 grid gap-2 border-t border-surface-muted pt-3"
                            >
                                <label
                                    v-for="bracket in classification.age_brackets"
                                    :key="bracket.id"
                                    class="flex cursor-pointer items-center gap-3 text-sm text-ink"
                                >
                                    <input
                                        type="checkbox"
                                        class="rounded border-surface-muted text-aqua shadow-sm focus:ring-aqua"
                                        :checked="
                                            isEligibilitySelected(
                                                classification.id,
                                                bracket.id,
                                            )
                                        "
                                        @change="
                                            toggleEligibility(
                                                classification,
                                                bracket,
                                                ($event.target as HTMLInputElement)
                                                    .checked,
                                            )
                                        "
                                    />
                                    {{ bracket.name }}
                                </label>
                            </div>

                            <p v-else class="mt-3 text-xs text-ink-muted">
                                No age brackets configured.
                            </p>
                        </div>
                    </div>

                    <p v-else class="mt-3 rounded-xl bg-surface p-4 text-sm text-ink-muted">
                        Add classifications and age brackets before generating events.
                    </p>
                </fieldset>

                <div class="rounded-xl bg-surface px-4 py-3">
                    <p class="text-sm font-semibold text-ink">
                        {{ generatedEventCount }}
                        {{ generatedEventCount === 1 ? 'event' : 'events' }} will be
                        created
                    </p>
                    <p class="mt-1 text-xs text-ink-muted">
                        {{ form.genders.length }} selected
                        {{ form.genders.length === 1 ? 'gender' : 'genders' }} ×
                        {{ form.eligibilities.length }} selected age
                        {{ form.eligibilities.length === 1 ? 'bracket' : 'brackets' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing || generatedEventCount === 0"
                >
                    Generate events
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
