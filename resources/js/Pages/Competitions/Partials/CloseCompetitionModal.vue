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
    is_close: true,
});

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    form.clearErrors();
    form.reset();
};

const closeCompetition = () => {
    form.patch(route('competitions.close', props.competition.id), {
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
                Close this competition?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                Every event must already have heats, and every heat must have
                lanes, before
                <span class="font-semibold text-ink">{{ competition.name }}</span>
                can be closed.
            </p>

            <p class="mt-2 text-sm text-ink-muted">
                Once closed, you can no longer add participants, regenerate the
                program order, or add classifications and age brackets.
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
                    @click="closeCompetition"
                >
                    Close competition
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
