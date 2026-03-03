import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

const __dirname = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
	plugins: [svelte()],
	root: __dirname,
	resolve: {
		alias: {
			'@utilities': resolve(__dirname, '../_utilities'),
		},
	},
	build: {
		outDir: resolve(__dirname, '../../plugin-src/builder-dist'),
		emptyOutDir: true,
		manifest: true,
		rollupOptions: {
			input: resolve(__dirname, 'src/main.ts'),
		},
	},
	server: {
		port: 5179,
		strictPort: true,
		cors: true,
		hmr: {
			protocol: 'ws',
		},
	},
});
