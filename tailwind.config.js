import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            // Charte JY Solutions (identique au prototype et au PDF).
            colors: {
                brand: { DEFAULT: '#0057D0', dark: '#0046A8', soft: '#F3F6FB' },
                accent: { DEFAULT: '#E30613', dark: '#B8050F' },
                ink: '#1F2937',
                muted: '#6B7280',
                line: '#D9DEE7',
                desk: '#E9EDF3',
            },
            fontFamily: {
                sans: ['Calibri', 'Carlito', '"Segoe UI"', 'Arial', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
