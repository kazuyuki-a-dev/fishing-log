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
                // 見出しだけに使う手書きの字（Google Fonts の Yomogi、SIL OFL）
                hand: ['"Yomogi"', '"BIZ UDPGothic"', ...defaultTheme.fontFamily.sans],
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
                // メモ帳の紙の色と、クレヨンの黄色（塗りは次の Issue で使う）
                paper: "#FFFDF6",
                crayon: "#FFE58A",
                float: {
                    DEFAULT: "#E0572B",
                    dark: "#C2461F",
                },
                ink: "#1D2A30",
                // 補足の文字。白と紙の色の上でコントラスト 4.5 以上（5.5）にするため濃くした
                sand: "#5B6B70",
            },
        },
    },

    plugins: [forms],
};
