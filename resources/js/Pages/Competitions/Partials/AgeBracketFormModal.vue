<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { AgeBracket, Classification, Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const classification = ref<Classification | null>(null);
const editingAgeBracket = ref<AgeBracket | null>(null);
const nameInput = ref<{ focus: () => void } | null>(null);

const form = useForm({
    name: '',
    start_birthday: '',
    end_birthday: '',
});

const isEditing = computed(() => editingAgeBracket.value !== null);

const title = computed(() =>
    isEditing.value ? 'Edit age bracket' : 'Add age bracket',
);

const submitLabel = computed(() =>
    isEditing.value ? 'Save changes' : 'Create age bracket',
);

const open = (selectedClassification: Classification, ageBracket?: AgeBracket) => {
    classification.value = selectedClassification;
    editingAgeBracket.value = ageBracket ?? null;
    form.name = ageBracket?.name ?? '';
    form.start_birthday = ageBracket?.start_birthday ?? '';
    form.end_birthday = ageBracket?.end_birthday ?? '';
    form.clearErrors();
    show.value = true;

    nextTick(() => nameInput.value?.focus());
};

const close = () => {
    show.value = false;
    classification.value = null;
    editingAgeBracket.value = null;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (! classification.value) {
        return;
    }

    if (editingAgeBracket.value) {
        form.put(
            route('age-brackets.update', {
                competition: props.competition.id,
                classification: classification.value.id,
                age_bracket: editingAgeBracket.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => close(),
            },
        );

        return;
    }

    form.post(
        route('age-brackets.store', {
            competition: props.competition.id,
            classification: classification.value.id,
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
    <Modal :show="show" max-width="lg" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="sm-label">{{ isEditing ? 'Update bracket' : 'New bracket' }}</div>
            <h2 class="mt-1 font-serif text-2xl font-bold text-ink">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Leave start or end empty for an open-ended bracket. At least one
                birthday is required.
            </p>

            <div class="mt-6 grid gap-4">
                <div>
                    <InputLabel for="age_bracket_name" value="Name" />
                    <TextInput
                        id="age_bracket_name"
                        ref="nameInput"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            for="age_bracket_start_birthday"
                            value="Start birthday"
                        />
                        <TextInput
                            id="age_bracket_start_birthday"
                            v-model="form.start_birthday"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.start_birthday"
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="age_bracket_end_birthday"
                            value="End birthday"
                        />
                        <TextInput
                            id="age_bracket_end_birthday"
                            v-model="form.end_birthday"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.end_birthday"
                        />
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
