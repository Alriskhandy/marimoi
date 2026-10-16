import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                manrope: ['Manrope', ...defaultTheme.fontFamily.sans],
                grotesk: ['"Space Grotesk"', 'Manrope', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                deep: '#061522',
                navy: '#071A2D',
                ocean: '#0A84FF',
                aqua: '#20D9FF',
                mint: '#4DE1C1',
                mist: '#F7FAFC',
            },
            keyframes: {
                cue: {
                    '0%': { transform: 'scaleY(0)', transformOrigin: 'top' },
                    '50%': { transform: 'scaleY(1)', transformOrigin: 'top' },
                    '51%': { transformOrigin: 'bottom' },
                    '100%': { transform: 'scaleY(0)', transformOrigin: 'bottom' },
                },
                ring: {
                    from: { transform: 'scale(.3)', opacity: '1' },
                    to: { transform: 'scale(1.6)', opacity: '0' },
                },
                draw: {
                    from: { strokeDashoffset: '1' },
                    to: { strokeDashoffset: '0' },
                },
                drop: {
                    '0%': { transform: 'translateY(-26px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                rise: {
                    from: { transform: 'scaleY(0)' },
                    to: { transform: 'scaleY(1)' },
                },
                pop: {
                    from: { transform: 'scale(0)', opacity: '0' },
                    to: { transform: 'scale(1)', opacity: '1' },
                },
                scan: {
                    from: { transform: 'translateY(-120%)' },
                    to: { transform: 'translateY(420%)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                blink: {
                    '0%, 100%': { opacity: '.25' },
                    '50%': { opacity: '1' },
                },
            },
            animation: {
                cue: 'cue 2.2s ease-in-out infinite',
                ring: 'ring 1.8s ease-out infinite',
                draw: 'draw 1.4s ease-out both',
                'draw-loop': 'draw 2.6s ease-in-out infinite',
                drop: 'drop .8s cubic-bezier(.34,1.56,.64,1) both',
                rise: 'rise .9s ease-out both',
                pop: 'pop .5s ease-out both',
                blink: 'blink 1.6s ease-in-out infinite',
                scan: 'scan 4.5s linear infinite',
                float: 'float 6s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
