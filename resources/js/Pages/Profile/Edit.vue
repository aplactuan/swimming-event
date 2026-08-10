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
        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <div class="sm-label">Account</div>
                <h2 class="sm-heading mt-1">Profile</h2>
                <p class="mt-2 text-sm text-ink-muted">
                    Manage your coach identity, password, and account access.
                </p>
            </div>

            <div class="sm-card">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="sm-card">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="sm-card">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
