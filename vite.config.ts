import { defineConfig } from 'vite';
import { resolve } from 'path';
import { viteVConsole } from 'vite-plugin-vconsole';
import eslintPlugin from 'vite-plugin-eslint';
import svgLoader from 'vite-svg-loader';
import vueTypeImports from 'vite-plugin-vue-type-imports';
import vue from '@vitejs/plugin-vue';
import vueI18n from '@intlify/vite-plugin-vue-i18n';

// https://vitejs.dev/config/
export default defineConfig({
	envPrefix: 'FB_APP_PARAMETER__',
	publicDir: false,
	plugins: [
		vue(),
		vueTypeImports(),
		vueI18n({
			include: resolve(__dirname, './assets/locales/**.json'),
		}),
		viteVConsole({
			entry: resolve('assets/main.ts'), // entry file
			localEnabled: true, // dev environment
			enabled: false, // build production
			config: {
				maxLogNumber: 1000,
				theme: 'dark',
			},
		}),
		eslintPlugin(),
		svgLoader(),
	],
	resolve: {
		alias: {
			'@fastybird': resolve(__dirname, './node_modules/@fastybird'),
			'@': resolve(__dirname, './assets'),
		},
	},
	css: {
		modules: {
			localsConvention: 'camelCaseOnly',
		},
	},
	optimizeDeps: {
		include: ['vue', 'pinia', 'vue-router', 'nprogress', '@vueuse/core', 'vue-i18n'],
	},
	build: {
		outDir: 'public/dist'
	},
	server: {
		proxy: {
			'/api': {
				target: 'http://10.10.10.116',
				rewrite: (path): string => {
					const apiPrefix: string = '/api';

					return path.replace(new RegExp(`^${apiPrefix}`, 'g'), ''); // Remove base path
				},
				secure: true,
				changeOrigin: true,
			},
			'/ws-exchange': {
				target: 'ws://10.10.10.116:9000',
				rewrite: (path: string): string => {
					const wsPrefix: string = '/ws-exchange';

					return path.replace(new RegExp(`^${wsPrefix}`, 'g'), ''); // Remove base path
				},
				secure: true,
				changeOrigin: true,
				ws: true,
				configure: (proxy) => {
					console.log('CONFIGURE');
					proxy.on('proxyReq', function (): void {
						console.log('EVENT');
					});
				},
			},
		},
		port: 3000,
	},
	preview: {
		port: 3000
	}
});
