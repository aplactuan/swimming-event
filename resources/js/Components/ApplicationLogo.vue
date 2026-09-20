<script setup lang="ts">
withDefaults(
    defineProps<{
        showWordmark?: boolean;
        compact?: boolean;
    }>(),
    {
        showWordmark: true,
        compact: false,
    },
);
</script>

<template>
    <div class="flex items-center gap-3" :class="{ 'gap-2': compact }">
        <span
            class="inline-flex items-center justify-center rounded-lg bg-aqua text-pool-deep"
            :class="compact ? 'h-9 w-9' : 'h-11 w-11'"
            aria-hidden="true"
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-6 w-6"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M5 7.5c2.1-1.5 4.2-1.5 6.3 0s4.2 1.5 7.7 0M5 12c2.1-1.5 4.2-1.5 6.3 0s4.2 1.5 7.7 0M5 16.5c2.1-1.5 4.2-1.5 6.3 0s4.2 1.5 7.7 0"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                />
            </svg>
        </span>

        <div v-if="showWordmark" class="leading-none">
            <div
                class="font-bold tracking-tight text-inherit"
                :class="compact ? 'text-lg' : 'text-xl'"
            >
                SwimMeet
            </div>
            <div
                v-if="!compact"
                class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-current opacity-60"
            >
                Meet operations
            </div>
        </div>
    </div>
</template>
