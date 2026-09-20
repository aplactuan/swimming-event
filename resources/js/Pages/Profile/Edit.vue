<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="border-b border-surface-muted pb-7">
                <div class="sm-label">Account</div>
                <h2 class="sm-heading mt-2">Account settings</h2>
                <p class="mt-2 text-sm text-ink-muted">
                    Manage your profile, password, and account access.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
                <div class="sm-card">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <div class="sm-card">
                    <UpdatePasswordForm />
                </div>
            </div>

            <div class="sm-card">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
