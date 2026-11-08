<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition, Team } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const team = ref<Team | null>(null);

const form = useForm({});

const teamName = computed(() => team.value?.name ?? 'this team');

const open = (selected: Team) => {
    team.value = selected;
    show.value = true;
};

const close = () => {
    show.value = false;
    team.value = null;
    form.clearErrors();
    form.reset();
};

const destroy = () => {
    if (! team.value) {
        return;
    }

    form.delete(
        route('teams.destroy', {
            competition: props.competition.id,
            team: team.value.id,
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
                Delete team?
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                This will permanently delete
                <span class="font-semibold text-ink">{{ teamName }}</span>.
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
                    Delete team
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
