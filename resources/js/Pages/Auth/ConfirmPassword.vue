<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-8 text-center">
            <div class="sm-label">Secure area</div>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight text-ink">
                Confirm password
            </h1>
            <p class="mt-2 text-sm text-ink-muted">
                Please confirm your password before continuing.
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton
                class="w-full"
                :class="{ 'opacity-40': form.processing }"
                :disabled="form.processing"
            >
                Confirm
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
