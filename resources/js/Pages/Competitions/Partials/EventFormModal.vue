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
    CompetitionEvent,
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
const editingEvent = ref<CompetitionEvent | null>(null);
const nameInput = ref<{ focus: () => void } | null>(null);

const form = useForm<{
    name: string;
    gender: EventGender | '';
    eligibilities: EligibilityRow[];
}>({
    name: '',
    gender: '',
    eligibilities: [{ classification_id: '', age_bracket_id: '' }],
});

const isEditing = computed(() => editingEvent.value !== null);

const title = computed(() =>
    isEditing.value ? 'Edit event' : 'Add event',
);

const submitLabel = computed(() =>
    isEditing.value ? 'Save changes' : 'Create event',
);

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

const bracketsFor = (classificationId: string): AgeBracket[] => {
    const classification = flatClassifications.value.find(
        (item) => item.id === classificationId,
    );

    return classification?.age_brackets ?? [];
};

const open = (event?: CompetitionEvent) => {
    editingEvent.value = event ?? null;
    form.name = event?.name ?? '';
    form.gender = event?.gender ?? '';
    form.eligibilities =
        event?.eligibilities.map((row) => ({
            classification_id: row.classification_id,
            age_bracket_id: row.age_bracket_id,
        })) ?? [{ classification_id: '', age_bracket_id: '' }];
    form.clearErrors();
    show.value = true;

    nextTick(() => nameInput.value?.focus());
};

const close = () => {
    show.value = false;
    editingEvent.value = null;
    form.clearErrors();
    form.reset();
    form.eligibilities = [{ classification_id: '', age_bracket_id: '' }];
};

const addEligibilityRow = () => {
    form.eligibilities.push({ classification_id: '', age_bracket_id: '' });
};

const removeEligibilityRow = (index: number) => {
    if (form.eligibilities.length === 1) {
        form.eligibilities[0] = {
            classification_id: '',
            age_bracket_id: '',
        };

        return;
    }

    form.eligibilities.splice(index, 1);
};

const onClassificationChange = (index: number) => {
    form.eligibilities[index].age_bracket_id = '';
};

const submit = () => {
    if (editingEvent.value) {
        form.put(
            route('events.update', {
                competition: props.competition.id,
                event: editingEvent.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => close(),
            },
        );

        return;
    }

    form.post(
        route('events.store', {
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
            <div class="sm-label">{{ isEditing ? 'Update event' : 'New event' }}</div>
            <h2 class="mt-1 font-serif text-2xl font-bold text-ink">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Choose gender and one or more classification + age bracket pairs.
            </p>

            <div class="mt-6 grid gap-4">
                <div>
                    <InputLabel for="event_name" value="Name" />
                    <TextInput
                        id="event_name"
                        ref="nameInput"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                        placeholder="25m Breaststroke (Novice 1)"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="event_gender" value="Gender" />
                    <select
                        id="event_gender"
                        v-model="form.gender"
                        class="sm-input mt-1 block w-full"
                        required
                    >
                        <option disabled value="">Select gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="mixed">Mixed</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.gender" />
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <InputLabel value="Eligibilities" />
                        <button
                            type="button"
                            class="text-sm font-semibold text-ink-muted hover:text-ink"
                            @click="addEligibilityRow"
                        >
                            Add pair
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.eligibilities" />

                    <div class="mt-3 space-y-3">
                        <div
                            v-for="(row, index) in form.eligibilities"
                            :key="index"
                            class="grid gap-3 rounded-xl bg-surface p-3 sm:grid-cols-[1fr_1fr_auto]"
                        >
                            <div>
                                <InputLabel
                                    :for="`event_classification_${index}`"
                                    value="Classification"
                                />
                                <select
                                    :id="`event_classification_${index}`"
                                    v-model="row.classification_id"
                                    class="sm-input mt-1 block w-full"
                                    required
                                    @change="onClassificationChange(index)"
                                >
                                    <option disabled value="">Select class</option>
                                    <option
                                        v-for="classification in flatClassifications"
                                        :key="classification.id"
                                        :value="classification.id"
                                    >
                                        {{ classification.label }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="
                                        form.errors[
                                            `eligibilities.${index}.classification_id`
                                        ]
                                    "
                                />
                            </div>

                            <div>
                                <InputLabel
                                    :for="`event_age_bracket_${index}`"
                                    value="Age bracket"
                                />
                                <select
                                    :id="`event_age_bracket_${index}`"
                                    v-model="row.age_bracket_id"
                                    class="sm-input mt-1 block w-full"
                                    required
                                    :disabled="! row.classification_id"
                                >
                                    <option disabled value="">Select bracket</option>
                                    <option
                                        v-for="bracket in bracketsFor(
                                            row.classification_id,
                                        )"
                                        :key="bracket.id"
                                        :value="bracket.id"
                                    >
                                        {{ bracket.name }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="
                                        form.errors[
                                            `eligibilities.${index}.age_bracket_id`
                                        ]
                                    "
                                />
                            </div>

                            <div class="flex items-end">
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-red-700 hover:text-red-800"
                                    @click="removeEligibilityRow(index)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ submitLabel }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
