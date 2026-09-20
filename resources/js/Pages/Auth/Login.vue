<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-white lg:grid lg:grid-cols-2">
        <Head title="Log in" />

        <aside class="relative hidden min-h-screen overflow-hidden bg-pool-deep lg:block">
            <img
                src="/images/login-kids-teens-swim-cover.png"
                alt="Children and teenagers cheering for a teammate at an indoor swimming competition"
                class="absolute inset-0 h-full w-full object-cover object-center"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-pool-deep/65 via-pool-deep/10 to-pool-deep/90" aria-hidden="true" />

            <div class="relative z-10 flex min-h-screen flex-col justify-between p-10 xl:p-14">
                <div class="w-fit text-white">
                    <ApplicationLogo />
                </div>

                <div class="max-w-lg text-white">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-aqua">Competition operations</p>
                    <h2 class="mt-4 text-4xl font-semibold leading-tight tracking-[-0.035em] xl:text-5xl">
                        From entries to race day, stay in control.
                    </h2>
                    <p class="mt-4 max-w-md text-sm leading-6 text-white/70">
                        Keep swimmers, events, and the competition program organized in one focused workspace.
                    </p>
                </div>
            </div>
        </aside>

        <main class="flex min-h-screen flex-col bg-surface lg:min-h-0">
            <div class="flex h-16 items-center border-b border-surface-muted bg-white px-5 sm:px-8 lg:hidden">
                <div class="text-pool">
                    <ApplicationLogo compact />
                </div>
            </div>

            <div class="flex flex-1 items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
                <div class="w-full max-w-md">
                    <div class="sm-label">Welcome back</div>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-pool-deep sm:text-4xl">Log in to SwimMeet</h1>
                    <p class="mt-3 text-sm leading-6 text-ink-muted">Continue managing your competitions and race programs.</p>

                    <div v-if="status" class="mt-6 rounded-lg border border-aqua-soft bg-mint-soft px-4 py-3 text-sm font-medium text-pool">
                        {{ status }}
                    </div>

                    <form class="mt-8 space-y-5" @submit.prevent="submit">
                        <div>
                            <InputLabel for="email" value="Email address" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-2 block w-full bg-white py-3"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <InputLabel for="password" value="Password" />
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-sm font-medium text-pool-muted hover:text-pool-deep"
                                >
                                    Forgot password?
                                </Link>
                            </div>
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-2 block w-full bg-white py-3"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <label class="flex items-center gap-2.5 text-sm text-ink-muted">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            Keep me logged in
                        </label>

                        <button type="submit" class="sm-btn-primary w-full py-3" :disabled="form.processing">
                            Log in
                        </button>
                    </form>

                    <p class="mt-7 text-center text-sm text-ink-muted">
                        New to SwimMeet?
                        <Link :href="route('register')" class="font-semibold text-pool-muted hover:text-pool-deep">Create an account</Link>
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
