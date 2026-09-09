import path from 'path';
import fs from 'fs-extra';
import { consola } from 'consola';
import chalk from 'chalk';

import { packages, pathSrc } from './paths';

try {
	consola.info(chalk.blue('generating web ui library'));

	for (const name of Object.keys(packages)) {
		await fs.ensureDir(packages[name].dest);
		await fs.emptyDir(packages[name].dest);
	}

	consola.info(chalk.blue('generating web ui packages'));
	await Promise.all(Object.keys(packages).map((name) => copyPackage(packages[name].dest, packages[name].src)));

	consola.info(chalk.blue('updating packages import'));

	for (const name of Object.keys(packages)) {
		await replaceImports(pathSrc, packages[name].name, packages[name].relativeDest); // Replace imports in the top-level directory
	}

	async function copyPackage(destination: string, source: string): Promise<void> {
		await fs.ensureDir(destination);

		const files = await fs.readdir(source);

		for (const file of files) {
			const sourcePath = path.join(source, file);
			const destPath = path.join(destination, file);

			const info = await fs.lstat(sourcePath);

			if (info.isDirectory()) {
				await copyPackage(destPath, sourcePath);
			} else {
				await fs.copy(sourcePath, destPath);
			}
		}
	}

	async function replaceImports(directory: string, packageName: string, relativePath: string): Promise<void> {
		const files = await fs.readdir(directory);

		for (const file of files) {
			const filePath = path.join(directory, file);

			const info = await fs.lstat(filePath);

			if (info.isDirectory()) {
				const newRelativePath = './.' + relativePath;

				await replaceImports(filePath, packageName, newRelativePath);
			} else if (filePath.endsWith('.ts') || filePath.endsWith('.vue')) {
				let content = await fs.readFile(filePath, 'utf8');

				content = content.replace(new RegExp(`from '${packageName}`, 'g'), `from '${relativePath}`);

				await fs.writeFile(filePath, content);
			}
		}
	}
} catch (e) {
	console.log(e);
}
