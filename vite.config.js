import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/spatial.css', 'resources/js/spatial.js', 'resources/css/peta.css'],
            refresh: true,
        }),
    ],
});
