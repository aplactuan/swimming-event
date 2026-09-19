<script setup lang="ts">
/**
 * Stylised illustration of a swimmer mid-freestyle in a marked lane.
 * Drawn inline so it scales cleanly and needs no asset request.
 */
withDefaults(
    defineProps<{
        title?: string;
    }>(),
    {
        title: 'A swimmer powering through a freestyle stroke in her lane',
    },
);
</script>

<template>
    <svg
        viewBox="0 0 420 400"
        fill="none"
        role="img"
        :aria-label="title"
        xmlns="http://www.w3.org/2000/svg"
    >
        <title>{{ title }}</title>

        <defs>
            <linearGradient id="swim-water" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#0E6A92" />
                <stop offset="45%" stop-color="#0B5375" />
                <stop offset="100%" stop-color="#04263A" />
            </linearGradient>

            <linearGradient id="swim-surface" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#A5E9F1" stop-opacity="0.8" />
                <stop offset="100%" stop-color="#57D1E3" stop-opacity="0" />
            </linearGradient>

            <pattern
                id="swim-rope"
                width="33"
                height="14"
                patternUnits="userSpaceOnUse"
            >
                <rect width="11" height="14" fill="#F2C14E" />
                <rect x="11" width="11" height="14" fill="#EFF9FC" />
                <rect x="22" width="11" height="14" fill="#57D1E3" />
            </pattern>

            <radialGradient id="swim-foam" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.55" />
                <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0" />
            </radialGradient>
        </defs>

        <rect width="420" height="400" rx="28" fill="url(#swim-water)" />

        <!-- Light caustics raking across the water -->
        <g opacity="0.14" stroke="#FFFFFF" stroke-width="1.5">
            <path d="M-40 60 L200 -60" />
            <path d="M-40 140 L260 -40" />
            <path d="M-20 260 L320 -20" />
            <path d="M40 380 L420 30" />
            <path d="M120 420 L440 140" />
        </g>

        <!-- Lane ropes -->
        <rect x="-10" y="64" width="440" height="14" rx="7" fill="url(#swim-rope)" opacity="0.9" />
        <rect x="-10" y="318" width="440" height="14" rx="7" fill="url(#swim-rope)" opacity="0.7" />

        <!-- Pool floor tile lines -->
        <g opacity="0.12" stroke="#A5E9F1" stroke-width="2">
            <path d="M0 356 H420" />
            <path d="M0 378 H420" />
        </g>

        <!-- Water surface -->
        <path
            d="M0 192 C 60 178 96 202 150 194 C 212 184 250 208 306 198 C 356 189 386 174 420 182 L420 216 L0 216 Z"
            fill="url(#swim-surface)"
        />
        <path
            d="M0 192 C 60 178 96 202 150 194 C 212 184 250 208 306 198 C 356 189 386 174 420 182"
            stroke="#DFF6FA"
            stroke-width="2.5"
            stroke-opacity="0.6"
            stroke-linecap="round"
        />

        <g transform="translate(6 -96) rotate(-7 210 310)">
            <!-- Kick splash -->
            <ellipse cx="80" cy="338" rx="44" ry="26" fill="url(#swim-foam)" />

            <!-- Ponytail, drawn behind the body -->
            <path
                d="M264 284 C 246 278 228 280 214 290 C 224 292 230 296 232 302"
                stroke="#3A2A24"
                stroke-width="10"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

            <!-- Trailing legs -->
            <g stroke="#F0C6A4" stroke-width="17" stroke-linecap="round">
                <path d="M158 318 C 130 322 106 334 84 328" />
                <path d="M158 326 C 128 338 108 350 86 356" />
            </g>

            <!-- Torso and swimsuit -->
            <path
                d="M150 306 C 186 296 224 292 254 294 C 262 302 262 316 254 324 C 224 328 186 328 150 332 Z"
                fill="#F0C6A4"
            />
            <path
                d="M150 306 C 168 302 186 299 202 297 L202 331 C 186 331 168 331 150 332 Z"
                fill="#2AB4CC"
            />
            <path
                d="M202 297 C 214 295 226 294 236 293 L236 327 C 226 328 214 329 202 331 Z"
                fill="#57D1E3"
            />

            <!-- Lead arm reaching forward under the surface -->
            <path
                d="M252 306 C 282 310 308 316 334 320"
                stroke="#F0C6A4"
                stroke-width="15"
                stroke-linecap="round"
            />
            <ellipse cx="342" cy="321" rx="12" ry="8.5" fill="#F0C6A4" />

            <!-- Recovery arm arcing above the water -->
            <path
                d="M246 300 C 246 262 268 240 298 246"
                stroke="#F5D2B4"
                stroke-width="15"
                stroke-linecap="round"
            />
            <ellipse
                cx="306"
                cy="248"
                rx="12"
                ry="8.5"
                fill="#F5D2B4"
                transform="rotate(18 306 248)"
            />

            <!-- Head, cap and goggles -->
            <circle cx="276" cy="292" r="21" fill="#F5D2B4" />
            <path
                d="M256 288 A 21 21 0 0 1 294 280 L 296 288 C 284 296 268 297 256 292 Z"
                fill="#57D1E3"
            />
            <path
                d="M255 293 C 264 299 276 300 286 296"
                stroke="#04263A"
                stroke-width="5"
                stroke-linecap="round"
                stroke-opacity="0.85"
            />
            <circle
                cx="285"
                cy="296"
                r="4.5"
                fill="#A5E9F1"
                stroke="#04263A"
                stroke-width="1.6"
            />

            <!-- Bow wave at the head -->
            <ellipse cx="296" cy="280" rx="28" ry="15" fill="url(#swim-foam)" />
        </g>

        <!-- Bubbles -->
        <g fill="#DFF6FA">
            <circle cx="128" cy="262" r="6" opacity="0.4" />
            <circle cx="96" cy="286" r="4" opacity="0.32" />
            <circle cx="158" cy="292" r="3" opacity="0.36" />
            <circle cx="206" cy="268" r="5" opacity="0.28" />
            <circle cx="248" cy="290" r="3.5" opacity="0.3" />
            <circle cx="62" cy="244" r="3" opacity="0.28" />
            <circle cx="310" cy="276" r="4" opacity="0.22" />
        </g>
    </svg>
</template>
