<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition, CompetitionEvent } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const event = ref<CompetitionEvent | null>(null);

const form = useForm({});

const eventName = computed(() => event.value?.name ?? 'this event');

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

const destroy = () => {
    if (! event.value) {
        return;
    }

    form.delete(
        route('events.destroy', {
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
            <h2 class="font-serif text-xl font-bold text-ink">
                Delete event?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                This will permanently delete
                <span class="font-semibold text-ink">{{ eventName }}</span>
                and its eligibility rules.
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
                    Delete event
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
