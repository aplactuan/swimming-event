<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import SwimmerIllustration from '@/Components/SwimmerIllustration.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};

const features = [
    'Race-ready event cards',
    'Live lane readiness',
    'Athlete-first planning',
];
</script>

<template>
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <Head title="Log in" />

        <aside
            class="sm-water relative hidden overflow-hidden px-10 py-10 text-white lg:flex lg:flex-col"
        >
            <div
                class="sm-caustics pointer-events-none absolute inset-0 opacity-[0.12]"
                aria-hidden="true"
            />

            <div class="relative z-10">
                <ApplicationLogo class="text-white" />
            </div>

            <div class="relative z-10 flex flex-1 flex-col justify-center py-10">
                <SwimmerIllustration
                    class="mb-10 h-64 w-full max-w-md animate-drift drop-shadow-2xl"
                />

                <p
                    class="text-xs font-semibold uppercase tracking-[0.22em] text-aqua"
                >
                    Your meet, in rhythm
                </p>
                <h1
                    class="mt-4 max-w-md text-5xl font-bold leading-tight tracking-tight"
                >
                    Make every lane count.
                </h1>
                <p class="mt-5 max-w-md text-base leading-relaxed text-white/70">
                    The calm behind race day. Organize events, seed heats, and
                    keep your swimmers moving forward.
                </p>

                <div class="sm-rope mt-10 max-w-md opacity-80" aria-hidden="true" />

                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-3">
                    <li
                        v-for="feature in features"
                        :key="feature"
                        class="flex items-center gap-2 text-sm text-white/85"
                    >
                        <span
                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-aqua/20 text-aqua"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                class="h-3.5 w-3.5"
                            >
                                <path
                                    d="m5 12 4.5 4.5L19 7"
                                    stroke="currentColor"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        {{ feature }}
                    </li>
                </ul>
            </div>
        </aside>

        <section
            class="sm-shallows flex min-h-screen flex-col justify-center px-6 py-10 sm:px-10"
        >
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <ApplicationLogo class="text-pool" />
                </div>

                <p class="sm-label text-ink">Welcome back, coach</p>
                <h2
                    class="mt-2 text-4xl font-bold tracking-tight text-pool"
                >
                    Sign in to your lane.
                </h2>
                <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                    Pick up where you left off and keep your next competition on
                    course.
                </p>

                <div
                    v-if="status"
                    class="mt-6 rounded-xl bg-mint px-4 py-3 text-sm font-medium text-pool"
                >
                    {{ status }}
                </div>

                <form
                    class="mt-8 rounded-card bg-white p-6 shadow-card sm:p-8"
                    @submit.prevent="submit"
                >
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-ink"
                        >
                            Email address
                        </label>
                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-faint"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                    <path
                                        d="m5.5 8 6.5 5 6.5-5"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="coach@aquaticsclub.com"
                                class="block w-full rounded-xl border-0 bg-surface py-3 pl-11 pr-4 text-ink shadow-none placeholder:text-ink-faint focus:ring-2 focus:ring-aqua"
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mt-5">
                        <label
                            for="password"
                            class="block text-sm font-medium text-ink"
                        >
                            Password
                        </label>
                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-faint"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M8 11V8a4 4 0 1 1 8 0v3M7 11h10v9H7v-9Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="block w-full rounded-xl border-0 bg-surface py-3 pl-11 pr-12 text-ink shadow-none placeholder:text-ink-faint focus:ring-2 focus:ring-aqua"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-faint transition hover:text-ink"
                                :aria-label="
                                    showPassword
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                                @click="showPassword = !showPassword"
                            >
                                <svg
                                    v-if="!showPassword"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M2.5 12S6.5 5.5 12 5.5 21.5 12 21.5 12 17.5 18.5 12 18.5 2.5 12 2.5 12Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="2.8"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="m4 4 16 16M10.5 10.7a2.8 2.8 0 0 0 3.8 3.8M9.2 5.9A9.7 9.7 0 0 1 12 5.5C17.5 5.5 21.5 12 21.5 12a17 17 0 0 1-4.1 4.5M6.4 7.5A17.4 17.4 0 0 0 2.5 12S6.5 18.5 12 18.5c1.1 0 2.2-.2 3.1-.6"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </button>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <label class="flex items-center">
                            <Checkbox
                                name="remember"
                                v-model:checked="form.remember"
                            />
                            <span class="ms-2 text-sm text-ink-muted"
                                >Remember me</span
                            >
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-semibold text-aqua-deep transition hover:text-pool"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-aqua px-5 py-3.5 text-sm font-semibold text-pool transition hover:bg-aqua-deep focus:outline-none focus:ring-2 focus:ring-aqua focus:ring-offset-2 disabled:opacity-40"
                        :disabled="form.processing"
                    >
                        Sign in
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                            <path
                                d="M5 12h14M13 6l6 6-6 6"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-ink-muted">
                    New to SwimMeet?
                    <Link
                        :href="route('register')"
                        class="font-semibold text-aqua-deep transition hover:text-pool"
                    >
                        Create an account
                    </Link>
                </p>

                <p
                    class="mt-10 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-ink-faint"
                >
                    Secure access · Built for race day
                </p>
            </div>
        </section>
    </div>
</template>
