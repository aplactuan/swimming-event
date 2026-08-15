<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Classification, Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const editingClassification = ref<Classification | null>(null);
const parentClassification = ref<Classification | null>(null);
const nameInput = ref<{ focus: () => void } | null>(null);

const form = useForm({
    name: '',
    parent_id: '',
});

const isEditing = computed(() => editingClassification.value !== null);

const title = computed(() => {
    if (isEditing.value) {
        return 'Edit classification';
    }

    return parentClassification.value
        ? `Add class under ${parentClassification.value.name}`
        : 'Add classification';
});

const submitLabel = computed(() =>
    isEditing.value ? 'Save changes' : 'Create classification',
);

const open = (options?: {
    classification?: Classification;
    parent?: Classification;
}) => {
    editingClassification.value = options?.classification ?? null;
    parentClassification.value = options?.parent ?? null;
    form.name = options?.classification?.name ?? '';
    form.parent_id = options?.parent?.id ?? '';
    form.clearErrors();
    show.value = true;

    nextTick(() => nameInput.value?.focus());
};

const close = () => {
    show.value = false;
    editingClassification.value = null;
    parentClassification.value = null;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (editingClassification.value) {
        form.put(
            route('classifications.update', {
                competition: props.competition.id,
                classification: editingClassification.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => close(),
            },
        );

        return;
    }

    form.post(
        route('classifications.store', {
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
    <Modal :show="show" max-width="lg" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="sm-label">{{ isEditing ? 'Update class' : 'New class' }}</div>
            <h2 class="mt-1 font-serif text-2xl font-bold text-ink">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Classifications group age brackets for this meet.
            </p>

            <div class="mt-6">
                <InputLabel for="classification_name" value="Name" />
                <TextInput
                    id="classification_name"
                    ref="nameInput"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="off"
                />
                <InputError class="mt-2" :message="form.errors.name" />
                <InputError class="mt-2" :message="form.errors.parent_id" />
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
