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
                    DEFAULT: '#063B55',
                    soft: '#0B5375',
                    muted: '#116A91',
                    deep: '#04263A',
                },
                /** Sunlit pool surface. The primary accent. */
                aqua: {
                    DEFAULT: '#57D1E3',
                    soft: '#A5E9F1',
                    deep: '#2AB4CC',
                },
                /** Lane rope floats. A warm accent against all the blue. */
                lane: {
                    DEFAULT: '#F2C14E',
                    soft: '#FBE7B4',
                },
                surface: {
                    DEFAULT: '#EFF9FC',
                    muted: '#DCEFF5',
                    card: '#FFFFFF',
                },
                ink: {
                    DEFAULT: '#062A3D',
                    muted: '#4A6B7C',
                    faint: '#6E8C9C',
                },
                mint: {
                    DEFAULT: '#CDEDF3',
                    soft: '#E3F5F8',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 10px 30px -18px rgba(6, 59, 85, 0.35)',
                soft: '0 8px 24px -16px rgba(6, 59, 85, 0.25)',
            },
            borderRadius: {
                card: '1.25rem',
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
