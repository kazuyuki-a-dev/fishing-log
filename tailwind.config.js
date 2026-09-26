import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"BIZ UDPGothic"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                sea: {
                    50: "#E8F0F2",
                    100: "#CFE0E4",
                    400: "#3F7486",
                    500: "#1C5569",
                    600: "#12465A",
                    700: "#0D3747",
                    800: "#092A36",
                    DEFAULT: "#12465A",
                },
                tide: "#EEF3F2",
                float: {
                    DEFAULT: "#E0572B",
                    dark: "#C2461F",
                },
                ink: "#1D2A30",
                sand: "#8A9A9F",
            },
        },
    },

    plugins: [forms],
};
