<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);

const form = useForm({});

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    form.clearErrors();
    form.reset();
};

const generate = () => {
    form.post(route('event-heats.generate-all', props.competition.id), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};

defineExpose({ open });
</script>

<template>
    <Modal :show="show" max-width="md" @close="close">
        <div class="p-6">
            <h2 class="text-xl font-bold text-ink">
                Generate heats for every event?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                Each event is seeded into heats of
                <span class="font-semibold text-ink">{{ competition.number_of_lane }} lanes</span>,
                filling the middle lanes first.
            </p>

            <p class="mt-2 text-sm text-ink-muted">
                Any heats that already exist will be deleted first, including lane
                changes and recorded finish times. Events with no participants are
                skipped.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    type="button"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="generate"
                >
                    Generate heats
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
