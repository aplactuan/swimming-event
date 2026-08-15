<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { CompetitionEvent, EventShowCompetition, Participant } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: EventShowCompetition | Pick<EventShowCompetition, 'id' | 'participants'>;
}>();

const show = ref(false);
const event = ref<CompetitionEvent | null>(null);

const form = useForm<{
    participant_id: string;
}>({
    participant_id: '',
});

const availableParticipants = computed((): Participant[] => {
    if (! event.value) {
        return [];
    }

    const enrolledIds = new Set(
        (event.value.participants ?? []).map((participant) => participant.id),
    );

    return (props.competition.participants ?? []).filter(
        (participant) =>
            participant.paid && ! enrolledIds.has(participant.id),
    );
});

const open = (selected: CompetitionEvent) => {
    event.value = selected;
    form.participant_id = '';
    form.clearErrors();
    show.value = true;
};

const close = () => {
    show.value = false;
    event.value = null;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (! event.value) {
        return;
    }

    form.post(
        route('event-participants.store', {
            competition: props.competition.id,
            event: event.value.id,
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
            <div class="sm-label">Event entry</div>
            <h2 class="mt-1 font-serif text-2xl font-bold text-ink">
                Add participant
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Only paid participants can be added. Matching rules can be overridden.
            </p>

            <div class="mt-6">
                <InputLabel for="event_participant_id" value="Participant" />
                <select
                    id="event_participant_id"
                    v-model="form.participant_id"
                    class="sm-input mt-1 block w-full"
                    required
                    :disabled="availableParticipants.length === 0"
                >
                    <option disabled value="">
                        {{
                            availableParticipants.length === 0
                                ? 'No paid participants available'
                                : 'Select participant'
                        }}
                    </option>
                    <option
                        v-for="participant in availableParticipants"
                        :key="participant.id"
                        :value="participant.id"
                    >
                        {{ participant.last_name }}, {{ participant.first_name }}
                        · {{ participant.team }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.participant_id" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing || availableParticipants.length === 0"
                >
                    Add to event
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
