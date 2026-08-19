import react from '@vitejs/plugin-react';
import path from 'path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./tests/Frontend/setup.ts'],
        include: [
            'tests/Frontend/Unit/**/*.{test,spec}.{ts,tsx}',
            'tests/Frontend/Components/**/*.{test,spec}.{ts,tsx}',
        ],
        exclude: [
            'tests/Frontend/E2E/**',
            'node_modules/**',
            'vendor/**',
        ],
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
