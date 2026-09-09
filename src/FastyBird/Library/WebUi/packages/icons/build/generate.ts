import path from 'path';
import { fileURLToPath } from 'url';
import { readFile, writeFile } from 'node:fs/promises';
import { emptyDir, ensureDir } from 'fs-extra';
import { consola } from 'consola';
import camelcase from 'camelcase';
import glob from 'fast-glob';
import { type BuiltInParserName, format } from 'prettier';
import chalk from 'chalk';

import { pathComponents } from './paths';

consola.info(chalk.blue('generating vue components'));
await ensureDir(pathComponents);
await emptyDir(pathComponents);
const files = await getSvgFiles();

consola.info(chalk.blue('generating vue files'));
await Promise.all(files.map((file) => transformToVueComponent(file)));

consola.info(chalk.blue('generating entry file'));
await generateEntry(files);

async function getSvgFiles(): Promise<string[]> {
	const __filename = fileURLToPath(import.meta.url);
	const __dirname = path.dirname(__filename);

	return glob('**/*.svg', { cwd: path.resolve(__dirname, '../svg'), absolute: true });
}

function getName(file: string): {
	filename: string;
	prefix: string | null;
	componentName: string;
} {
	const filename = path.basename(file).replace('.svg', '');

	let prefix = null;

	switch (path.basename(path.dirname(file))) {
		case 'solid':
			prefix = 'fas';
			break;
		case 'regular':
			prefix = 'far';
			break;
		case 'brands':
			prefix = 'fab';
			break;
	}

	const componentName = camelcase((prefix ? prefix + '-' : '') + filename, { pascalCase: true });
	return {
		filename,
		prefix,
		componentName,
	};
}

function formatCode(code: string, parser: BuiltInParserName = 'typescript'): Promise<string> {
	return format(code, {
		parser,
		semi: false,
		singleQuote: true,
	});
}

async function transformToVueComponent(file: string): Promise<void> {
	const content = await readFile(file, 'utf-8');
	const { filename, prefix, componentName } = getName(file);
	const vue = await formatCode(
		`
<template>
${content}
</template>

<script setup lang="ts">
defineOptions({
  name: ${JSON.stringify(componentName)}
})
</script>`,
		'vue'
	);
	writeFile(path.resolve(pathComponents, `${prefix ? prefix + '-' : ''}${filename}.vue`), vue, 'utf-8');
}

async function generateEntry(files: string[]): Promise<void> {
	const code = await formatCode(
		files
			.map((file) => {
				const { filename, prefix, componentName } = getName(file);
				return `export { default as ${componentName} } from './${prefix ? prefix + '-' : ''}${filename}.vue'`;
			})
			.join('\n')
	);

	await writeFile(path.resolve(pathComponents, 'index.ts'), code, 'utf-8');
}
