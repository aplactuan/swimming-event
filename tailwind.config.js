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
                navy: {
                    DEFAULT: '#0A1F33',
                    soft: '#123048',
                    muted: '#1A3A52',
                    deep: '#061525',
                },
                gold: {
                    DEFAULT: '#F5B941',
                    soft: '#F8D58A',
                    deep: '#E0A22C',
                },
                surface: {
                    DEFAULT: '#F1F8F9',
                    muted: '#E5F0F2',
                    card: '#FFFFFF',
                },
                ink: {
                    DEFAULT: '#0A1F33',
                    muted: '#5B6B76',
                    faint: '#8A9AA3',
                },
                mint: {
                    DEFAULT: '#D8EDE8',
                    soft: '#EAF5F2',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                card: '0 10px 30px -18px rgba(10, 31, 51, 0.35)',
                soft: '0 8px 24px -16px rgba(10, 31, 51, 0.25)',
            },
            borderRadius: {
                card: '1.25rem',
            },
        },
    },

    plugins: [forms],
};
