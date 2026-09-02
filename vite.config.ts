import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        // Bind inside the container so the docker-compose port mapping
        // (${VITE_PORT:-5173}:5173) can forward to it.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Browser-facing URL — what laravel-vite-plugin writes to public/hot.
        // MUST match the host-side port mapping in docker-compose.yml, not the
        // container bind address: browsers refuse to fetch from 0.0.0.0.
        origin: 'http://localhost:5173',
        hmr: {
            host: 'localhost',
            protocol: 'ws',
        },
        cors: {
            // Nginx serves the app from the host's APP_PORT (8080 by default).
            origin: /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/,
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
            // Le build de production pré-génère les types Wayfinder dans une
            // étape disposant de PHP (Dockerfile.prod), puis neutralise cet
            // appel via WAYFINDER_COMMAND=true dans l'étape node, qui n'a pas
            // PHP. En développement la variable est absente : la commande par
            // défaut « php artisan wayfinder:generate » s'applique.
            command: process.env.WAYFINDER_COMMAND,
        }),
    ],
});
