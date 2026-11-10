<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { AgeBracket, Classification, Competition } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps<{
    competition: Competition;
    classifications: Classification[];
}>();

const show = ref(false);
const classification = ref<Classification | null>(null);
const editingAgeBracket = ref<AgeBracket | null>(null);
const copyFromKey = ref('');
const nameInput = ref<{ focus: () => void } | null>(null);

const form = useForm({
    name: '',
    start_birthday: '',
    end_birthday: '',
});

const isEditing = computed(() => editingAgeBracket.value !== null);

const title = computed(() =>
    isEditing.value ? 'Edit age bracket' : 'Add age bracket',
);

const submitLabel = computed(() =>
    isEditing.value ? 'Save changes' : 'Create age bracket',
);

type CopyOption = {
    key: string;
    label: string;
    bracket: AgeBracket;
};

const copyFromOptions = computed((): CopyOption[] => {
    if (isEditing.value || classification.value === null) {
        return [];
    }

    const targetId = classification.value.id;
    const options: CopyOption[] = [];

    for (const root of props.classifications) {
        if (root.id !== targetId) {
            for (const bracket of root.age_brackets ?? []) {
                options.push({
                    key: `${root.id}:${bracket.id}`,
                    label: `${root.name} · ${bracket.name}`,
                    bracket,
                });
            }
        }

        for (const child of root.children ?? []) {
            if (child.id === targetId || child.inherits_age_brackets) {
                continue;
            }

            for (const bracket of child.age_brackets ?? []) {
                options.push({
                    key: `${child.id}:${bracket.id}`,
                    label: `${root.name} / ${child.name} · ${bracket.name}`,
                    bracket,
                });
            }
        }
    }

    return options;
});

const applyCopyFrom = () => {
    const selected = copyFromOptions.value.find(
        (option) => option.key === copyFromKey.value,
    );

    if (! selected) {
        return;
    }

    form.name = selected.bracket.name;
    form.start_birthday = selected.bracket.start_birthday ?? '';
    form.end_birthday = selected.bracket.end_birthday ?? '';
    form.clearErrors();
};

const open = (selectedClassification: Classification, ageBracket?: AgeBracket) => {
    classification.value = selectedClassification;
    editingAgeBracket.value = ageBracket ?? null;
    copyFromKey.value = '';
    form.name = ageBracket?.name ?? '';
    form.start_birthday = ageBracket?.start_birthday ?? '';
    form.end_birthday = ageBracket?.end_birthday ?? '';
    form.clearErrors();
    show.value = true;

    nextTick(() => nameInput.value?.focus());
};

const close = () => {
    show.value = false;
    classification.value = null;
    editingAgeBracket.value = null;
    copyFromKey.value = '';
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (! classification.value) {
        return;
    }

    if (editingAgeBracket.value) {
        form.put(
            route('age-brackets.update', {
                competition: props.competition.id,
                classification: classification.value.id,
                age_bracket: editingAgeBracket.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => close(),
            },
        );

        return;
    }

    form.post(
        route('age-brackets.store', {
            competition: props.competition.id,
            classification: classification.value.id,
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
            <div class="sm-label">{{ isEditing ? 'Update bracket' : 'New bracket' }}</div>
            <h2 class="mt-1 text-2xl font-bold text-ink">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Leave start or end empty for an open-ended bracket. At least one
                birthday is required.
            </p>

            <div class="mt-6 grid gap-4">
                <div v-if="! isEditing && copyFromOptions.length > 0">
                    <InputLabel for="age_bracket_copy_from" value="Copy from" />
                    <select
                        id="age_bracket_copy_from"
                        v-model="copyFromKey"
                        class="sm-input mt-1 block w-full"
                        @change="applyCopyFrom"
                    >
                        <option value="">Start from scratch</option>
                        <option
                            v-for="option in copyFromOptions"
                            :key="option.key"
                            :value="option.key"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-ink-muted">
                        Prefill this form from an age bracket on another classification.
                        You can still edit the values before saving.
                    </p>
                </div>

                <div>
                    <InputLabel for="age_bracket_name" value="Name" />
                    <TextInput
                        id="age_bracket_name"
                        ref="nameInput"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            for="age_bracket_start_birthday"
                            value="Start birthday"
                        />
                        <TextInput
                            id="age_bracket_start_birthday"
                            v-model="form.start_birthday"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.start_birthday"
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="age_bracket_end_birthday"
                            value="End birthday"
                        />
                        <TextInput
                            id="age_bracket_end_birthday"
                            v-model="form.end_birthday"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.end_birthday"
                        />
                    </div>
                </div>
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
