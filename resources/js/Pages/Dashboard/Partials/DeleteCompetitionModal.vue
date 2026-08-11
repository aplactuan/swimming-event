<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const show = ref(false);
const competition = ref<Competition | null>(null);

const form = useForm({});

const competitionName = computed(() => competition.value?.name ?? 'this competition');

const open = (selected: Competition) => {
    competition.value = selected;
    show.value = true;
};

const close = () => {
    show.value = false;
    competition.value = null;
    form.clearErrors();
    form.reset();
};

const destroy = () => {
    if (! competition.value) {
        return;
    }

    form.delete(route('competitions.destroy', competition.value.id), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};

defineExpose({ open });
</script>

<template>
    <Modal :show="show" max-width="md" @close="close">
        <div class="p-6">
            <h2 class="font-serif text-xl font-bold text-ink">
                Delete competition?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                This will permanently delete
                <span class="font-semibold text-ink">{{ competitionName }}</span>.
                This action cannot be undone.
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
                    Delete competition
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
