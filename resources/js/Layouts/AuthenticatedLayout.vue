<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const mobileNavOpen = ref(false);
const user = computed(() => page.props.auth.user);

const initials = computed(() =>
    (user.value?.name ?? 'SM')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join(''),
);

type NavItem = {
    label: string;
    routeName: string;
    href: string;
    icon: 'meets' | 'account';
};

const navigation: NavItem[] = [
    {
        label: 'Competitions',
        routeName: 'dashboard',
        href: route('dashboard'),
        icon: 'meets',
    },
    {
        label: 'Account',
        routeName: 'profile.edit',
        href: route('profile.edit'),
        icon: 'account',
    },
];

const isActive = (item: NavItem) => {
    if (item.routeName === 'dashboard') {
        return route().current('dashboard')
            || route().current('competitions.*')
            || route().current('events.*');
    }

    return route().current(item.routeName);
};
</script>

<template>
    <div class="min-h-screen bg-surface text-ink">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-white/10 bg-pool-deep lg:flex lg:flex-col">
            <div class="px-6 py-7">
                <Link :href="route('dashboard')" class="text-white">
                    <ApplicationLogo />
                </Link>
            </div>

            <div class="px-4">
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/40">
                    Workspace
                </p>
                <nav class="mt-3 flex flex-col gap-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.label"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                        :class="isActive(item) ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/[0.06] hover:text-white'"
                    >
                        <svg v-if="item.icon === 'meets'" viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                            <path d="M5 4v3M19 4v3M4 9h16M6.5 13h3M6.5 17h3M13 13h4.5M13 17h4.5M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6" />
                            <path d="M5.5 20c.4-3.7 2.5-5.5 6.5-5.5s6.1 1.8 6.5 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                        {{ item.label }}
                        <span v-if="isActive(item)" class="ml-auto h-1.5 w-1.5 rounded-full bg-aqua" />
                    </Link>
                </nav>
            </div>

            <div class="mt-auto border-t border-white/10 p-4">
                <Dropdown align="left" placement="top" width="48">
                    <template #trigger>
                        <button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition hover:bg-white/[0.06]">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-aqua text-xs font-bold text-pool-deep">
                                {{ initials }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-white">{{ user.name }}</span>
                                <span class="block truncate text-xs text-white/45">{{ user.email }}</span>
                            </span>
                            <svg viewBox="0 0 24 24" fill="none" class="ml-auto h-4 w-4 text-white/40" aria-hidden="true">
                                <path d="m8 10 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Account settings</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">Log out</DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-surface-muted bg-white/95 backdrop-blur">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <Link :href="route('dashboard')" class="text-pool lg:hidden">
                        <ApplicationLogo compact />
                    </Link>

                    <p class="hidden text-sm font-medium text-ink-muted lg:block">Competition operations</p>

                    <div class="flex items-center gap-2">
                        <Dropdown align="right" width="48" class="lg:hidden">
                            <template #trigger>
                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-pool text-xs font-bold text-white">
                                    {{ initials }}
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Account settings</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log out</DropdownLink>
                            </template>
                        </Dropdown>
                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-surface-muted bg-white text-ink lg:hidden" aria-label="Toggle navigation" @click="mobileNavOpen = !mobileNavOpen">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                                <path d="M5 7h14M5 12h14M5 17h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>
                </div>

                <nav v-if="mobileNavOpen" class="grid gap-1 border-t border-surface-muted bg-white p-3 lg:hidden">
                    <Link
                        v-for="item in navigation"
                        :key="`mobile-${item.label}`"
                        :href="item.href"
                        class="rounded-lg px-3 py-2.5 text-sm font-medium"
                        :class="isActive(item) ? 'bg-mint-soft text-pool' : 'text-ink-muted'"
                        @click="mobileNavOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </header>

            <main class="px-4 py-7 sm:px-6 lg:px-8 lg:py-10">
                <slot />
            </main>
        </div>
    </div>
</template>
