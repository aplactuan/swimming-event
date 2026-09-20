<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition, CompetitionEvent } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: Pick<Competition, 'id'>;
}>();

const show = ref(false);
const event = ref<CompetitionEvent | null>(null);

const form = useForm({});

const heatCount = computed(() => event.value?.heats?.length ?? 0);

const open = (selected: CompetitionEvent) => {
    event.value = selected;
    show.value = true;
};

const close = () => {
    show.value = false;
    event.value = null;
    form.clearErrors();
    form.reset();
};

const generate = () => {
    if (! event.value) {
        return;
    }

    form.post(
        route('event-heats.generate', {
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
    <Modal :show="show" max-width="md" @close="close">
        <div class="p-6">
            <h2 class="text-xl font-bold text-ink">
                Regenerate heats?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                This will delete the existing
                <span class="font-semibold text-ink">{{ heatCount }} {{ heatCount === 1 ? 'heat' : 'heats' }}</span>
                for this event, including any lane changes and recorded times, and
                build fresh heats from the current participants.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    type="button"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="generate"
                >
                    Regenerate heats
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
