<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { CompetitionEvent, EventShowCompetition, Participant } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: EventShowCompetition | Pick<EventShowCompetition, 'id'>;
}>();

const show = ref(false);
const event = ref<CompetitionEvent | null>(null);
const participant = ref<Participant | null>(null);

const form = useForm({});

const participantName = computed(() => {
    if (! participant.value) {
        return 'this participant';
    }

    return `${participant.value.first_name} ${participant.value.last_name}`;
});

const eventName = computed(() => event.value?.name ?? 'this event');

const open = (selectedEvent: CompetitionEvent, selectedParticipant: Participant) => {
    event.value = selectedEvent;
    participant.value = selectedParticipant;
    show.value = true;
};

const close = () => {
    show.value = false;
    event.value = null;
    participant.value = null;
    form.clearErrors();
    form.reset();
};

const destroy = () => {
    if (! event.value || ! participant.value) {
        return;
    }

    form.delete(
        route('event-participants.destroy', {
            competition: props.competition.id,
            event: event.value.id,
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
                Remove from event?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                Remove
                <span class="font-semibold text-ink">{{ participantName }}</span>
                from
                <span class="font-semibold text-ink">{{ eventName }}</span>.
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
                    Remove participant
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
