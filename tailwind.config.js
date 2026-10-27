import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        // Classes définies côté PHP (ex. couleurs des statuts dans app/Enums).
        './app/Enums/**/*.php',
        './vendor/livewire/livewire/src/Features/SupportPagination/views/*.blade.php',
    ],

    theme: {
        extend: {
            // Charte JY Solutions (identique au prototype et au PDF), plus les teintes de l'interface.
            colors: {
                brand: { DEFAULT: '#0057D0', light: '#2F7BEA', dark: '#0046A8', soft: '#EEF4FD' },
                accent: { DEFAULT: '#E30613', dark: '#B8050F' },
                navy: { DEFAULT: '#0B1F45', deep: '#071531', light: '#14336E' },
                ink: '#1F2937',
                muted: '#64748B',
                line: '#DDE3EC',
                desk: '#F3F6FB',
            },
            // Interface en Inter ; les documents (feuille A4, PDF) gardent Calibri / Carlito.
            fontFamily: {
                sans: ['"Inter Variable"', 'Inter', '"Segoe UI"', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: '0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px -12px rgba(15, 23, 42, 0.12)',
                lift: '0 2px 4px rgba(15, 23, 42, 0.04), 0 18px 40px -16px rgba(15, 23, 42, 0.22)',
                brand: '0 1px 0 rgba(255, 255, 255, 0.25) inset, 0 8px 20px -8px rgba(0, 87, 208, 0.6)',
                sheet: '0 1px 3px rgba(15, 23, 42, 0.06), 0 24px 60px -20px rgba(15, 23, 42, 0.28)',
            },
            backgroundImage: {
                'brand-gradient': 'linear-gradient(135deg, #0B1F45 0%, #0A3D91 55%, #0057D0 100%)',
                'sidebar-gradient': 'linear-gradient(180deg, #0B1F45 0%, #0A2A5E 60%, #0B1F45 100%)',
            },
        },
    },

    plugins: [forms],
};
