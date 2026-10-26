import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

// Deliberately separate from vite.config.ts. That config loads the Laravel
// plugin, which refuses to run in CI because it assumes it is being asked to
// start the HMR dev server. Component tests need Vue and nothing else.
export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.ts', 'tests/electron/**/*.test.ts'],
    },
});
