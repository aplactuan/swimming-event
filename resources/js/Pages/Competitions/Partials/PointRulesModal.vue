<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Competition, CompetitionPointRule } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type RuleRow = {
    place: number | '';
    points: number | '';
};

const props = defineProps<{
    competition: Competition;
}>();

const show = ref(false);

const form = useForm<{
    rules: RuleRow[];
}>({
    rules: [],
});

const nextPlace = (): number => {
    const places = form.rules
        .map((rule) => Number(rule.place))
        .filter((place) => Number.isFinite(place));

    return places.length === 0 ? 1 : Math.max(...places) + 1;
};

const open = (rules: CompetitionPointRule[] = []) => {
    form.rules =
        rules.length > 0
            ? [...rules]
                  .sort((a, b) => a.place - b.place)
                  .map((rule) => ({ place: rule.place, points: rule.points }))
            : [{ place: 1, points: 0 }];
    form.clearErrors();
    show.value = true;
};

const close = () => {
    show.value = false;
    form.clearErrors();
};

const addRule = () => {
    form.rules.push({ place: nextPlace(), points: 0 });
};

const removeRule = (index: number) => {
    form.rules.splice(index, 1);
};

const ruleError = (index: number, field: 'place' | 'points'): string | undefined =>
    form.errors[`rules.${index}.${field}` as keyof typeof form.errors] as
        | string
        | undefined;

const submit = () => {
    form
        .transform((data) => ({
            rules: data.rules.map((rule) => ({
                place: Number(rule.place),
                points: Number(rule.points),
            })),
        }))
        .put(route('competition-point-rules.update', props.competition.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
};

defineExpose({ open });
</script>

<template>
    <Modal :show="show" max-width="lg" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="sm-label">Scoring</div>
            <h2 class="mt-1 text-2xl font-bold text-ink">
                Point rules
            </h2>
            <p class="mt-1 text-sm text-ink-muted">
                Points awarded for each finishing place. Tied swimmers share the
                average of the places they occupy.
            </p>

            <InputError class="mt-3" :message="form.errors.rules" />

            <div class="mt-6 space-y-3">
                <div
                    v-for="(rule, index) in form.rules"
                    :key="index"
                    class="grid gap-3 rounded-xl bg-surface p-3 sm:grid-cols-[1fr_1fr_auto]"
                >
                    <div>
                        <InputLabel :for="`rule_place_${index}`" value="Place" />
                        <input
                            :id="`rule_place_${index}`"
                            v-model.number="rule.place"
                            type="number"
                            min="1"
                            max="100"
                            class="sm-input mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="ruleError(index, 'place')" />
                    </div>

                    <div>
                        <InputLabel :for="`rule_points_${index}`" value="Points" />
                        <input
                            :id="`rule_points_${index}`"
                            v-model.number="rule.points"
                            type="number"
                            min="0"
                            max="1000"
                            class="sm-input mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="ruleError(index, 'points')" />
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            class="text-sm font-semibold text-red-700 hover:text-red-800 disabled:opacity-30"
                            :disabled="form.rules.length === 1"
                            @click="removeRule(index)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="mt-3 text-sm font-semibold text-ink-muted hover:text-ink"
                @click="addRule"
            >
                Add place
            </button>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="close">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Save point rules
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
