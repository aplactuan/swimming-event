<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition, Participant } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const participant = ref<Participant | null>(null);

const form = useForm({});

const participantName = computed(() => {
    if (! participant.value) {
        return 'this participant';
    }

    return `${participant.value.first_name} ${participant.value.last_name}`;
});

const open = (selected: Participant) => {
    participant.value = selected;
    show.value = true;
};

const close = () => {
    show.value = false;
    participant.value = null;
    form.clearErrors();
    form.reset();
};

const destroy = () => {
    if (! participant.value) {
        return;
    }

    form.delete(
        route('participants.destroy', {
            competition: props.competition.id,
            participant: participant.value.id,
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
                Delete participant?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                This will permanently delete
                <span class="font-semibold text-ink">{{ participantName }}</span>
                and remove them from all events.
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
                    Delete participant
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
