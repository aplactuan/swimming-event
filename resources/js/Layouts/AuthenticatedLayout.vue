<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const mobileNavOpen = ref(false);

const user = computed(() => page.props.auth.user);

const firstName = computed(() => {
    const name = user.value?.name ?? 'Coach';
    return name.split(' ')[0] ?? name;
});

const initials = computed(() => {
    const parts = (user.value?.name ?? 'SM').trim().split(/\s+/);
    return parts
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
});

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    }).format(new Date()),
);

type NavItem = {
    label: string;
    routeName: string;
    href: string;
    icon: 'grid' | 'user';
};

const navigation: NavItem[] = [
    {
        label: 'Dashboard',
        routeName: 'dashboard',
        href: route('dashboard'),
        icon: 'grid',
    },
    {
        label: 'Profile',
        routeName: 'profile.edit',
        href: route('profile.edit'),
        icon: 'user',
    },
];

const isActive = (item: NavItem) => route().current(item.routeName);
</script>

<template>
    <div class="min-h-screen bg-surface text-ink">
        <div class="lg:flex">
            <aside
                class="hidden min-h-screen w-64 shrink-0 flex-col bg-navy px-5 py-6 text-white lg:flex"
            >
                <Link :href="route('dashboard')" class="mb-10 text-white">
                    <ApplicationLogo />
                </Link>

                <nav class="flex flex-1 flex-col gap-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.label"
                        :href="item.href"
                        class="group flex items-center justify-between rounded-full px-4 py-2.5 text-sm font-medium transition"
                        :class="
                            isActive(item)
                                ? 'bg-white text-navy'
                                : 'text-white/80 hover:bg-white/10 hover:text-white'
                        "
                    >
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center"
                                aria-hidden="true"
                            >
                                <svg
                                    v-if="item.icon === 'grid'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z"
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
                                        d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4 0-7 2-7 4.5V20h14v-1.5C19 16 16 14 12 14Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                </svg>
                            </span>
                            {{ item.label }}
                        </span>
                    </Link>
                </nav>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-20 border-b border-surface-muted/80 bg-surface/90 px-4 py-4 backdrop-blur sm:px-6 lg:px-8"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <button
                                type="button"
                                class="mb-3 inline-flex items-center rounded-full border border-surface-muted bg-white px-3 py-1.5 text-sm font-medium text-ink lg:hidden"
                                @click="mobileNavOpen = !mobileNavOpen"
                            >
                                Menu
                            </button>
                            <div class="sm-label">{{ todayLabel }}</div>
                            <h1 class="mt-1 truncate font-serif text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                                {{ greeting }}, {{ firstName }}
                            </h1>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-surface-muted bg-white text-ink shadow-soft"
                                aria-label="Notifications"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M6 9a6 6 0 1 1 12 0c0 4 1.5 5.5 1.5 5.5H4.5S6 13 6 9Zm4 8.5a2 2 0 0 0 4 0"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <span
                                    class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-gold"
                                />
                            </button>

                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-3 rounded-full border border-surface-muted bg-white py-1.5 pl-1.5 pr-3 shadow-soft"
                                    >
                                        <span
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-navy text-sm font-semibold text-white"
                                        >
                                            {{ initials }}
                                        </span>
                                        <span class="hidden text-left sm:block">
                                            <span
                                                class="block text-sm font-semibold text-ink"
                                                >{{ user.name }}</span
                                            >
                                            <span
                                                class="block text-xs text-ink-muted"
                                                >Coach</span
                                            >
                                        </span>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profile
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <nav
                        v-if="mobileNavOpen"
                        class="mt-4 grid gap-2 rounded-card bg-navy p-3 text-white lg:hidden"
                    >
                        <Link
                            v-for="item in navigation"
                            :key="`mobile-${item.label}`"
                            :href="item.href"
                            class="rounded-full px-4 py-2.5 text-sm font-medium"
                            :class="
                                isActive(item)
                                    ? 'bg-white text-navy'
                                    : 'text-white/85 hover:bg-white/10'
                            "
                            @click="mobileNavOpen = false"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
