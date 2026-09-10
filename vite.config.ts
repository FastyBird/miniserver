import { resolve } from 'path';
import UnoCSS from 'unocss/vite';
import { defineConfig } from 'vite';
import { viteVConsole } from 'vite-plugin-vconsole';
import svgLoader from 'vite-svg-loader';

import vueI18n from '@intlify/unplugin-vue-i18n/vite';
import eslint from '@nabla/vite-plugin-eslint';
import vue from '@vitejs/plugin-vue';

import pkg from './package.json';

// https://vitejs.dev/config/
export default defineConfig({
	envPrefix: 'FB_APP_PARAMETER__',
	publicDir: false,
	define: {
		__APP_VERSION__: JSON.stringify(pkg.version),
		__APP_DESCRIPTION__: JSON.stringify(pkg.description),
	},
	plugins: [
		vue(),
		vueI18n({
			include: [resolve(__dirname, './src/FastyBird/Core/Application/assets/locales/**.json')],
		}),
		eslint(),
		viteVConsole({
			entry: resolve(__dirname, './src/FastyBird/Core/Application/assets/main.ts'), // entry file
			localEnabled: true, // dev environment
			enabled: false, // build production
			config: {
				theme: 'dark',
			},
		}),
		svgLoader(),
		UnoCSS(),
	],
	resolve: {
		dedupe: ['pinia', 'vue', 'vue-router', 'vue-i18n', 'vue-meta', 'nprogress', 'element-plus'],
		alias: {
			'@config': resolve(__dirname, './config'),
		},
	},
	css: {
		modules: {
			localsConvention: 'camelCaseOnly',
		},
	},
	optimizeDeps: {
		include: ['pinia', 'vue', 'vue-router', 'vue-i18n', 'vue-meta', 'nprogress', 'element-plus'],
	},
	build: {
		manifest: true,
		outDir: resolve(__dirname, './public'),
	},
	server: {
		watch: {
			usePolling: true,
		},
		hmr: {
			host: 'localhost',
		},
		proxy: {
			'/api': {
				target: process.env.FB_APP_PARAMETER__APPLICATION_TARGET || 'http://localhost',
				secure: false,
				changeOrigin: true,
				timeout: 60000,
			},
			'/ws-exchange': {
				target: process.env.FB_APP_PARAMETER__WEBSOCKETS_TARGET || 'ws://localhost:8888',
				rewrite: (path: string): string => {
					return path.replace(new RegExp(`^/ws-exchange`, 'g'), ''); // Remove base path
				},
				secure: true,
				changeOrigin: true,
				ws: true,
			},
		},
		port: 3000,
	},
	preview: {
		port: 3000,
	},
});
