const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require('tailwindcss/colors');

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',

    content: [
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                gray: colors.slate,
                neutral: {
                    ... colors.neutral,
                    850: '#1d1d1d',
                },
                green: colors.emerald,
                yellow: colors.amber,
                purple: colors.violet,
            },
            borderWidth: {
                '3': '3px',
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
