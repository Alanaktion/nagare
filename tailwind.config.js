const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require('tailwindcss/colors');

module.exports = {
    darkMode: 'class',

    purge: [
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        colors: {
            transparent: 'transparent',
            current: 'currentColor',

            black: colors.black,
            white: colors.white,
            gray: colors.coolGray,
            trueGray: colors.trueGray,
            red: colors.red,
            yellow: colors.amber,
            green: colors.emerald,
            blue: colors.blue,
            indigo: colors.indigo,
            purple: colors.violet,
        },
        extend: {
            borderWidth: {
                '3': '3px',
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
    ],

    variants: {
        extend: {
            opacity: ['disabled'],
            backgroundOpacity: ['dark'],
        },
    },
};
