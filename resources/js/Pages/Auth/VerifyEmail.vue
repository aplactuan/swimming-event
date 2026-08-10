<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="mb-8 text-center">
            <div class="sm-label">Almost there</div>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight text-ink">
                Verify your email
            </h1>
            <p class="mt-2 text-sm text-ink-muted">
                Thanks for signing up! Click the link we emailed you, or request
                another one below.
            </p>
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-4 rounded-xl bg-mint px-4 py-3 text-sm font-medium text-navy"
        >
            A new verification link has been sent to your email address.
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <PrimaryButton
                class="w-full"
                :class="{ 'opacity-40': form.processing }"
                :disabled="form.processing"
            >
                Resend verification email
            </PrimaryButton>

            <p class="text-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-semibold text-ink underline decoration-gold underline-offset-4"
                >
                    Log out
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
