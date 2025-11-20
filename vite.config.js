import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/platform.css',
                'resources/js/platform.js',
                'resources/js/three/app.jsx',
                'resources/js/three/roomScene.jsx',
            ],
            refresh: true,
        }),
    ],
});
