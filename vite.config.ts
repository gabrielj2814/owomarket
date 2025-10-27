import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
             refresh: {
                // Configuración para detectar cambios en subdominios
                paths: [
                    'resources/views/**',
                    'resources/js/**',
                    'routes/**',
                    'lang/**',
                ],
            },
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'owomarket.local',
            // Configuración específica para CORS
            protocol: 'ws',
            // Agrega esto para permitir todos los orígenes en desarrollo
        },
        // Configuración CORS crítica
        cors: {
            origin: true, // Permite todos los orígenes
            credentials: true,
        },
        // Agregar headers CORS manualmente
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers': 'X-Requested-With, content-type, Authorization',
        },
    },
    // Configuración adicional para el build
    build: {
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },

});
