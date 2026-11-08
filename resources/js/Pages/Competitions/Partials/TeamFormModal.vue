<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Competition, Team } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const editingTeam = ref<Team | null>(null);
const nameInput = ref<{ focus: () => void } | null>(null);

const form = useForm({
    name: '',
});

const isEditing = computed(() => editingTeam.value !== null);

const title = computed(() => (isEditing.value ? 'Edit team' : 'Add team'));

const submitLabel = computed(() =>
    isEditing.value ? 'Save changes' : 'Create team',
);

const open = (team?: Team) => {
    editingTeam.value = team ?? null;
    form.name = team?.name ?? '';
    form.clearErrors();
    show.value = true;

    nextTick(() => nameInput.value?.focus());
};

const close = () => {
    show.value = false;
    editingTeam.value = null;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (editingTeam.value) {
        form.put(
            route('teams.update', {
                competition: props.competition.id,
                team: editingTeam.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => close(),
            },
        );

        return;
    }

    form.post(
        route('teams.store', {
            competition: props.competition.id,
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
            <div class="sm-label">{{ isEditing ? 'Update team' : 'New team' }}</div>
            <h2 class="mt-1 text-2xl font-bold text-ink">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Teams group participants and score points toward the team champion.
            </p>

            <div class="mt-6">
                <InputLabel for="team_name" value="Name" />
                <TextInput
                    id="team_name"
                    ref="nameInput"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="off"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ submitLabel }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
