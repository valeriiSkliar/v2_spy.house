import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // 'resources/css/app.css',
                "resources/scss/app.scss",
                "resources/js/app.js",
                "resources/js/pages/landings.js",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "@": "/resources/js",
            "@scss": "/resources/scss",
            jquery: "jquery/dist/jquery.js",
        },
    },
    optimizeDeps: {
        include: ["jquery"],
    },
});
