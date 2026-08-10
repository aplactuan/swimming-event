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
            class="inline-flex items-center justify-center rounded-2xl bg-gold text-navy shadow-soft"
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
                    d="M12 2c1.8 3.1.7 5.4-.7 7.1C9.5 11 8 12.6 8 15.2c0 2.6 2 4.8 4 4.8s4-2.2 4-4.8c0-1.7-.7-3-1.6-4.1C12.8 9.2 12 7.4 12 2Z"
                    fill="currentColor"
                />
                <path
                    d="M7.5 16.5c.8 1.5 2.1 2.5 4.5 2.5s3.7-1 4.5-2.5"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    opacity="0.55"
                />
            </svg>
        </span>

        <div v-if="showWordmark" class="leading-none">
            <div
                class="font-serif font-bold tracking-tight text-inherit"
                :class="compact ? 'text-lg' : 'text-xl'"
            >
                SwimMeet
            </div>
            <div
                v-if="!compact"
                class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-current opacity-60"
            >
                Lane Control
            </div>
        </div>
    </div>
</template>
