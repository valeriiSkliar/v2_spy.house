import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // 'resources/css/app.css',
                "resources/scss/app.scss",
                "resources/js/app.js",
                "resources/img/telegram.svg",
                "resources/img/viber.svg",
                "resources/img/whatsapp.svg",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "@": "/resources/js",
            "@img": "/resources/img",
            "@pages": "/resources/js/pages",
            "@scss": "/resources/scss",
            jquery: "jquery/dist/jquery.js",
        },
    },
    optimizeDeps: {
        include: ["jquery"],
    },
});
