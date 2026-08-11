<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const show = ref(false);
const editingCompetition = ref<Competition | null>(null);
const nameInput = ref<{ focus: () => void } | null>(null);

const form = useForm({
    name: '',
    venue: '',
    competition_date: '',
    warm_up_time: '',
    coaches_meeting_time: '',
    registration_deadline: '',
    entry_fee: '',
});

const isEditing = computed(() => editingCompetition.value !== null);

const title = computed(() =>
    isEditing.value ? 'Edit competition' : 'Add competition',
);

const submitLabel = computed(() =>
    isEditing.value ? 'Save changes' : 'Create competition',
);

const fillForm = (competition?: Competition) => {
    form.name = competition?.name ?? '';
    form.venue = competition?.venue ?? '';
    form.competition_date = competition?.competition_date ?? '';
    form.warm_up_time = competition?.warm_up_time ?? '';
    form.coaches_meeting_time = competition?.coaches_meeting_time ?? '';
    form.registration_deadline = competition?.registration_deadline ?? '';
    form.entry_fee =
        competition?.entry_fee !== undefined ? String(competition.entry_fee) : '';
};

const open = (competition?: Competition) => {
    editingCompetition.value = competition ?? null;
    fillForm(competition);
    form.clearErrors();
    show.value = true;

    nextTick(() => nameInput.value?.focus());
};

const close = () => {
    show.value = false;
    editingCompetition.value = null;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (editingCompetition.value) {
        form.put(route('competitions.update', editingCompetition.value.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });

        return;
    }

    form.post(route('competitions.store'), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};

defineExpose({ open });
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="sm-label">{{ isEditing ? 'Update meet' : 'New meet' }}</div>
            <h2 class="mt-1 font-serif text-2xl font-bold text-ink">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Enter the meet details below. Warm-up and coaches meeting times
                are optional.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel for="competition_name" value="Name" />
                    <TextInput
                        id="competition_name"
                        ref="nameInput"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="competition_venue" value="Venue" />
                    <TextInput
                        id="competition_venue"
                        v-model="form.venue"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.venue" />
                </div>

                <div>
                    <InputLabel
                        for="competition_date"
                        value="Competition date"
                    />
                    <TextInput
                        id="competition_date"
                        v-model="form.competition_date"
                        type="date"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.competition_date"
                    />
                </div>

                <div>
                    <InputLabel
                        for="registration_deadline"
                        value="Registration deadline"
                    />
                    <TextInput
                        id="registration_deadline"
                        v-model="form.registration_deadline"
                        type="date"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.registration_deadline"
                    />
                </div>

                <div>
                    <InputLabel for="warm_up_time" value="Warm-up time" />
                    <TextInput
                        id="warm_up_time"
                        v-model="form.warm_up_time"
                        type="time"
                        class="mt-1 block w-full"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.warm_up_time"
                    />
                </div>

                <div>
                    <InputLabel
                        for="coaches_meeting_time"
                        value="Coaches meeting time"
                    />
                    <TextInput
                        id="coaches_meeting_time"
                        v-model="form.coaches_meeting_time"
                        type="time"
                        class="mt-1 block w-full"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.coaches_meeting_time"
                    />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="entry_fee" value="Entry fee" />
                    <TextInput
                        id="entry_fee"
                        v-model="form.entry_fee"
                        type="number"
                        min="0"
                        step="1"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.entry_fee" />
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
