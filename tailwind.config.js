import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
const formKitTailwind = require('@formkit/themes/tailwindcss');

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                'carolina': '#7BAFD4',
                'navy': '#13294B'
            }
        },
    },

    plugins: [
        forms,
        formKitTailwind
    ],
};
