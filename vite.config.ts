import nette from '@nette/vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [
		nette({
			entry: 'app.js',
		}),
		tailwindcss(),
	],

	build: {
		emptyOutDir: true,
	},

	css: {
		devSourcemap: true,
	},
});
