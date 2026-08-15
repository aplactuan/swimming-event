<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type {
    Classification,
    Competition,
    Participant,
    ParticipantGender,
} from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

type FlatClassification = Classification & {
    label: string;
};

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const editingParticipant = ref<Participant | null>(null);
const firstNameInput = ref<{ focus: () => void } | null>(null);

const form = useForm<{
    first_name: string;
    last_name: string;
    gender: ParticipantGender | '';
    team: string;
    birthdate: string;
    classification_id: string;
    paid: boolean;
}>({
    first_name: '',
    last_name: '',
    gender: '',
    team: '',
    birthdate: '',
    classification_id: '',
    paid: false,
});

const isEditing = computed(() => editingParticipant.value !== null);

const title = computed(() =>
    isEditing.value ? 'Edit participant' : 'Add participant',
);

const submitLabel = computed(() =>
    isEditing.value ? 'Save changes' : 'Create participant',
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

const open = (participant?: Participant) => {
    editingParticipant.value = participant ?? null;
    form.first_name = participant?.first_name ?? '';
    form.last_name = participant?.last_name ?? '';
    form.gender = participant?.gender ?? '';
    form.team = participant?.team ?? '';
    form.birthdate = participant?.birthdate ?? '';
    form.classification_id = participant?.classification_id ?? '';
    form.paid = participant?.paid ?? false;
    form.clearErrors();
    show.value = true;

    nextTick(() => firstNameInput.value?.focus());
};

const close = () => {
    show.value = false;
    editingParticipant.value = null;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (editingParticipant.value) {
        form.put(
            route('participants.update', {
                competition: props.competition.id,
                participant: editingParticipant.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => close(),
            },
        );

        return;
    }

    form.post(
        route('participants.store', {
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
            <div class="sm-label">{{ isEditing ? 'Update participant' : 'New participant' }}</div>
            <h2 class="mt-1 font-serif text-2xl font-bold text-ink">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Paid participants are auto-entered into matching events.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="participant_first_name" value="First name" />
                    <TextInput
                        id="participant_first_name"
                        ref="firstNameInput"
                        v-model="form.first_name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div>
                    <InputLabel for="participant_last_name" value="Last name" />
                    <TextInput
                        id="participant_last_name"
                        v-model="form.last_name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>

                <div>
                    <InputLabel for="participant_gender" value="Gender" />
                    <select
                        id="participant_gender"
                        v-model="form.gender"
                        class="sm-input mt-1 block w-full"
                        required
                    >
                        <option disabled value="">Select gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.gender" />
                </div>

                <div>
                    <InputLabel for="participant_team" value="Team" />
                    <TextInput
                        id="participant_team"
                        v-model="form.team"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.team" />
                </div>

                <div>
                    <InputLabel for="participant_birthdate" value="Birthdate" />
                    <TextInput
                        id="participant_birthdate"
                        v-model="form.birthdate"
                        type="date"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.birthdate" />
                </div>

                <div>
                    <InputLabel for="participant_classification" value="Classification" />
                    <select
                        id="participant_classification"
                        v-model="form.classification_id"
                        class="sm-input mt-1 block w-full"
                        required
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
                        :message="form.errors.classification_id"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-3">
                        <input
                            v-model="form.paid"
                            type="checkbox"
                            class="rounded border-surface-muted text-ink focus:ring-ink/20"
                        >
                        <span class="text-sm font-medium text-ink">Paid</span>
                    </label>
                    <InputError class="mt-2" :message="form.errors.paid" />
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
