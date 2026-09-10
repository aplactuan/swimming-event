<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm<{
    file: File | null;
}>({
    file: null,
});

const open = () => {
    form.clearErrors();
    form.reset();
    show.value = true;

    nextTick(() => {
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    });
};

const close = () => {
    show.value = false;
    form.clearErrors();
    form.reset();

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    form.post(
        route('participants.import', {
            competition: props.competition.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => close(),
        },
    );
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
};

defineExpose({ open });
</script>

<template>
    <Modal :show="show" max-width="lg" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="sm-label">Bulk import</div>
            <h2 class="mt-1 font-serif text-2xl font-bold text-ink">
                Import participants
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Upload a CSV (max 2 MB) with First Name, Last Name, Team, Gen, Birthday, and Classification.
                Missing classifications are created. Matching first and last names are skipped.
            </p>

            <div class="mt-6">
                <InputLabel for="participant_import_file" value="CSV file" />
                <input
                    id="participant_import_file"
                    ref="fileInput"
                    type="file"
                    accept=".csv,text/csv,text/plain"
                    class="sm-input mt-1 block w-full"
                    required
                    @change="onFileChange"
                >
                <InputError class="mt-2" :message="form.errors.file" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Import
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
