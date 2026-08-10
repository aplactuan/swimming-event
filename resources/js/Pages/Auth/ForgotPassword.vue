<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="mb-8 text-center">
            <div class="sm-label">Account recovery</div>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight text-ink">
                Reset password
            </h1>
            <p class="mt-2 text-sm text-ink-muted">
                Enter your email and we’ll send a reset link.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-xl bg-mint px-4 py-3 text-sm font-medium text-navy"
        >
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <PrimaryButton
                class="w-full"
                :class="{ 'opacity-40': form.processing }"
                :disabled="form.processing"
            >
                Email reset link
            </PrimaryButton>

            <p class="text-center text-sm text-ink-muted">
                <Link
                    :href="route('login')"
                    class="font-semibold text-ink underline decoration-gold underline-offset-4"
                >
                    Back to login
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
