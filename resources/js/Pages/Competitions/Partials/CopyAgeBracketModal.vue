<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { AgeBracket, Classification, Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
    classifications: Classification[];
}>();

const show = ref(false);
const sourceClassification = ref<Classification | null>(null);
const ageBracket = ref<AgeBracket | null>(null);

const form = useForm({
    classification_ids: [] as string[],
});

const ageBracketName = computed(() => ageBracket.value?.name ?? 'this age bracket');

const sourceClassificationName = computed(
    () => sourceClassification.value?.name ?? 'this classification',
);

type TargetOption = {
    id: string;
    label: string;
};

const targetOptions = computed((): TargetOption[] => {
    const sourceId = sourceClassification.value?.id;
    const options: TargetOption[] = [];

    for (const root of props.classifications) {
        if (root.id !== sourceId) {
            options.push({ id: root.id, label: root.name });
        }

        for (const child of root.children ?? []) {
            if (child.id === sourceId) {
                continue;
            }

            options.push({
                id: child.id,
                label: `${root.name} / ${child.name}`,
            });
        }
    }

    return options;
});

const toggleTarget = (classificationId: string) => {
    if (form.classification_ids.includes(classificationId)) {
        form.classification_ids = form.classification_ids.filter(
            (id) => id !== classificationId,
        );

        return;
    }

    form.classification_ids = [...form.classification_ids, classificationId];
};

const open = (classification: Classification, selected: AgeBracket) => {
    sourceClassification.value = classification;
    ageBracket.value = selected;
    form.classification_ids = [];
    form.clearErrors();
    show.value = true;
};

const close = () => {
    show.value = false;
    sourceClassification.value = null;
    ageBracket.value = null;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (! sourceClassification.value || ! ageBracket.value) {
        return;
    }

    form.post(
        route('age-brackets.copy', {
            competition: props.competition.id,
            classification: sourceClassification.value.id,
            age_bracket: ageBracket.value.id,
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
            <div class="sm-label">Copy bracket</div>
            <h2 class="mt-1 text-2xl font-bold text-ink">
                Copy age bracket
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Copy
                <span class="font-semibold text-ink">{{ ageBracketName }}</span>
                from
                <span class="font-semibold text-ink">{{ sourceClassificationName }}</span>
                to one or more other classifications.
            </p>

            <div class="mt-6">
                <div class="sm-label">Copy to</div>
                <div
                    v-if="targetOptions.length === 0"
                    class="mt-2 rounded-xl bg-surface px-4 py-6 text-sm text-ink-muted"
                >
                    There are no other classifications to copy to.
                </div>
                <ul v-else class="mt-2 max-h-64 space-y-2 overflow-y-auto">
                    <li
                        v-for="option in targetOptions"
                        :key="option.id"
                    >
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-surface-muted px-3 py-2.5 hover:border-pool/60"
                        >
                            <input
                                type="checkbox"
                                class="rounded border-surface-muted text-pool focus:ring-pool"
                                :checked="form.classification_ids.includes(option.id)"
                                @change="toggleTarget(option.id)"
                            >
                            <span class="text-sm font-medium text-ink">
                                {{ option.label }}
                            </span>
                        </label>
                    </li>
                </ul>
                <InputError class="mt-2" :message="form.errors.classification_ids" />
                <InputError class="mt-2" :message="form.errors['classification_ids.0']" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing || form.classification_ids.length === 0"
                >
                    Copy age bracket
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
