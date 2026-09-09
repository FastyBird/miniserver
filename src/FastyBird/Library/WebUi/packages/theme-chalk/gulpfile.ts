import fs from 'fs';
import path, { resolve } from 'path';
import chalk from 'chalk';
import { dest, parallel, series, src } from 'gulp';
import gulpSass from 'gulp-sass';
import dartSass from 'sass';
import autoprefixer from 'gulp-autoprefixer';
import cleanCSS from 'gulp-clean-css';
import { consola } from 'consola';

const webUiLibraryOutput = resolve(__dirname, '..', '..', 'web-ui-library', 'dist');

const distFolder = path.resolve(__dirname, 'dist');
const distBundle = path.resolve(webUiLibraryOutput, 'theme-chalk');

/**
 * Resolve the path to node_modules dynamically
 * @returns {string[]} array of potential node_modules paths
 */
function resolveNodeModulesPaths() {
	const possiblePaths = [
		resolve(__dirname, 'node_modules'),
		resolve(__dirname, '..', '..', '..', 'node_modules'),
		resolve(__dirname, '..', '..', '..', '..', '..', '..', 'node_modules'),
	];

	// Filter out paths that do not exist
	return possiblePaths.filter((p) => fs.existsSync(p));
}

/**
 * compile theme-chalk scss & minify
 * not use sass.sync().on('error', sass.logError) to throw exception
 * @returns
 */
function buildThemeChalk() {
	const sass = gulpSass(dartSass);
	const includePaths = resolveNodeModulesPaths();

	return src(path.resolve(__dirname, 'src/*.scss'))
		.pipe(sass.sync({ includePaths }).on('error', sass.logError))
		.pipe(autoprefixer({ cascade: false }))
		.pipe(
			cleanCSS({}, (details) => {
				consola.success(
					`${chalk.cyan(details.name)}: ${chalk.yellow(details.stats.originalSize / 1000)} KB -> ${chalk.green(details.stats.minifiedSize / 1000)} KB`
				);
			})
		)
		.pipe(dest(distFolder));
}

/**
 * copy from packages/theme-chalk/dist to dist/web-ui-library/theme-chalk
 */
export function copyThemeChalkBundle() {
	return src(`${distFolder}/**`).pipe(dest(distBundle));
}

/**
 * copy source file to packages
 */

export function copyThemeChalkSource() {
	return src(path.resolve(__dirname, 'src/**')).pipe(dest(path.resolve(distBundle, 'src')));
}

export const build = parallel(copyThemeChalkSource, series(buildThemeChalk, copyThemeChalkBundle));

export default build;
