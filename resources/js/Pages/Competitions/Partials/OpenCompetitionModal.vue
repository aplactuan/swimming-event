<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
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

const form = useForm({
    is_close: false,
});

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    form.clearErrors();
    form.reset();
};

const openCompetition = () => {
    form.patch(route('competitions.open', props.competition.id), {
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
                Reopen this competition?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                Reopening
                <span class="font-semibold text-ink">{{ competition.name }}</span>
                lets you add participants, events, classifications and age
                brackets again, and regenerate heats and the program order.
            </p>

            <p class="mt-2 text-sm text-ink-muted">
                Existing heats and lanes are kept, but any change you make after
                reopening may leave them out of date.
            </p>

            <InputError class="mt-4" :message="form.errors.is_close" />

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    type="button"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="openCompetition"
                >
                    Reopen competition
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
