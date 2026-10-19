import nette from '@nette/vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';
import {viteStaticCopy} from "vite-plugin-static-copy";

export default defineConfig({
	plugins: [
		nette({
			entry: 'app.js',
		}),
		tailwindcss(),
        viteStaticCopy({
            targets: [
                { src: 'img/*', dest: '' },
                { src: 'favicon/*', dest: '' },
            ]
        })
	],

	build: {
        outDir: '../www/assets',
		emptyOutDir: true,
	},

	css: {
		devSourcemap: true,
	},
});
