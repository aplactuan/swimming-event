import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                /** Deep end water. Used for dark chrome and primary text. */
                pool: {
                    DEFAULT: '#123B46',
                    soft: '#1B5360',
                    muted: '#2A6875',
                    deep: '#082A33',
                },
                /** Sunlit pool surface. The primary accent. */
                aqua: {
                    DEFAULT: '#5DD6C0',
                    soft: '#BFEDE4',
                    deep: '#2EAE98',
                },
                /** Lane rope floats. A warm accent against all the blue. */
                lane: {
                    DEFAULT: '#E7A84B',
                    soft: '#F7E3BE',
                },
                surface: {
                    DEFAULT: '#F4F7F6',
                    muted: '#DDE7E5',
                    card: '#FFFFFF',
                },
                ink: {
                    DEFAULT: '#17343B',
                    muted: '#60767B',
                    faint: '#8A9B9F',
                },
                mint: {
                    DEFAULT: '#D3EEE8',
                    soft: '#EAF6F3',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 12px 32px -24px rgba(8, 42, 51, 0.35)',
                soft: '0 8px 22px -18px rgba(8, 42, 51, 0.28)',
            },
            borderRadius: {
                card: '0.875rem',
            },
            keyframes: {
                drift: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
                ripple: {
                    '0%': { backgroundPosition: '0 0' },
                    '100%': { backgroundPosition: '120px 0' },
                },
            },
            animation: {
                drift: 'drift 6s ease-in-out infinite',
                ripple: 'ripple 14s linear infinite',
            },
        },
    },

    plugins: [forms],
};
