<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Classification, Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const classification = ref<Classification | null>(null);

const form = useForm({});

const classificationName = computed(
    () => classification.value?.name ?? 'this classification',
);

const open = (selected: Classification) => {
    classification.value = selected;
    show.value = true;
};

const close = () => {
    show.value = false;
    classification.value = null;
    form.clearErrors();
    form.reset();
};

const destroy = () => {
    if (! classification.value) {
        return;
    }

    form.delete(
        route('classifications.destroy', {
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
    <Modal :show="show" max-width="md" @close="close">
        <div class="p-6">
            <h2 class="font-serif text-xl font-bold text-ink">
                Delete classification?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                This will permanently delete
                <span class="font-semibold text-ink">{{ classificationName }}</span>
                and any nested classes or age brackets.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    type="button"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="destroy"
                >
                    Delete classification
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
