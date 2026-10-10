#!/usr/bin/env node
/**
 * Prints the public surface of `@fastybird/miniserver-core`: one `name<TAB>kind` line for every
 * name that src/FastyBird/Core/Core/assets/entry.ts exports, sorted.
 *
 * Why: the package has a single `"."` export, `./assets/entry.ts`, and four other packages
 * consume it through the bare specifier. Moving or splitting the files behind that entry must
 * not add, drop, rename or re-kind one exported name. There are no JS tests to say so, so this
 * tool says it: run it on the base and on the head and diff the two outputs. An empty diff
 * proves the surface is the same; a non-empty one is the finding.
 *
 * How: the TypeScript compiler API, with the compiler options of the root tsconfig.json, creates
 * a program whose only root is entry.ts and asks the type checker for the module's exports.
 * Aliases (`export { x } from`, `export * from`) are followed to the declaration, so a name's
 * kind is the kind of what it finally is, not of the re-export that carries it.
 *
 * `.vue` imports are opaque. TypeScript cannot read a single-file component, so every module
 * specifier ending in `.vue` is resolved to a virtual declaration that exports a default
 * constant, and a name that re-exports a component is reported as a `value`. What is inside a
 * component is outside the surface. The path of the `.vue` file is not read, so moving one
 * cannot change the output, and a `.vue` file that does not exist is not noticed.
 *
 * Kind is one of:
 *   class      a class declaration
 *   enum       an enum, const or not
 *   function   a function declaration
 *   value      any other runtime binding: const, let, var, a default-exported expression, or a
 *              component default
 *   interface  an interface
 *   type       a type alias
 * A name that is both a type and a value, such as an interface and a const of the same name, is
 * printed once per kind.
 *
 * Usage:
 *   node scripts/js-api-surface.mjs            # the surface to stdout, one line per name
 *
 * The only dependency is `typescript`, already installed for `vue-tsc`. Exit codes:
 *   0  surface printed
 *   1  the surface could not be determined (an unresolved import, an unresolved export, or an
 *      entry that does not export anything), so the output would be incomplete
 */
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import ts from 'typescript';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const ENTRY = join(ROOT, 'src', 'FastyBird', 'Core', 'Core', 'assets', 'entry.ts');
const TSCONFIG = join(ROOT, 'tsconfig.json');

// What every `.vue` import resolves to. A real component is a default export of unknown shape.
const VUE_STUB = 'declare const component: unknown;\nexport default component;\n';
const VUE_STUB_SUFFIX = '.opaque-vue.d.ts';

// "Cannot find module" and its relatives. Anything else the checker reports (a missing global
// type, an implicit any) says nothing about which names the entry exports and is not our business.
const UNRESOLVED_MODULE = new Set([2307, 2792, 2834, 2835]);

function fail(message) {
	process.stderr.write(`js-api-surface: ${message}\n`);

	process.exit(1);
}

function readCompilerOptions() {
	const raw = ts.readConfigFile(TSCONFIG, ts.sys.readFile);

	if (raw.error) {
		fail(ts.flattenDiagnosticMessageText(raw.error.messageText, '\n'));
	}

	const parsed = ts.parseJsonConfigFileContent(raw.config, ts.sys, ROOT);
	const unrelated = new Set([
		// Global type packages are irrelevant to which names a module exports, and loading them
		// would make the answer depend on whatever happens to be installed.
		'types',
		// This tool reads; it never writes.
		'declaration',
		'sourceMap',
		'noEmit',
	]);

	const options = Object.fromEntries(Object.entries(parsed.options).filter(([key]) => !unrelated.has(key)));

	return { ...options, types: [], noEmit: true };
}

function createHost(options) {
	const host = ts.createCompilerHost(options, true);
	const { fileExists, readFile, getSourceFile } = host;
	const isStub = (fileName) => fileName.endsWith(VUE_STUB_SUFFIX);

	host.fileExists = (fileName) => isStub(fileName) || fileExists.call(host, fileName);
	host.readFile = (fileName) => (isStub(fileName) ? VUE_STUB : readFile.call(host, fileName));
	host.getSourceFile = (fileName, languageVersionOrOptions, onError, shouldCreate) =>
		isStub(fileName)
			? ts.createSourceFile(fileName, VUE_STUB, languageVersionOrOptions, true, ts.ScriptKind.TS)
			: getSourceFile.call(host, fileName, languageVersionOrOptions, onError, shouldCreate);

	host.resolveModuleNameLiterals = (literals, containingFile, redirected, compilerOptions) =>
		literals.map((literal) => {
			if (literal.text.endsWith('.vue')) {
				// One stub per importing directory is enough; the path only has to be unique and
				// absolute. It is derived from the specifier so two components stay two modules.
				const stub = join(dirname(containingFile), literal.text) + VUE_STUB_SUFFIX;

				return {
					resolvedModule: { resolvedFileName: stub, extension: ts.Extension.Dts, isExternalLibraryImport: false },
				};
			}

			return ts.resolveModuleName(literal.text, containingFile, compilerOptions, host, undefined, redirected);
		});

	return host;
}

function kindsOf(symbol) {
	const flags = symbol.flags;
	const kinds = [];

	if (flags & ts.SymbolFlags.Class) {
		kinds.push('class');
	}

	if (flags & ts.SymbolFlags.Enum) {
		kinds.push('enum');
	}

	if (flags & ts.SymbolFlags.Function) {
		kinds.push('function');
	}

	// `export default <expression>` declares a Property symbol named "default".
	if (flags & (ts.SymbolFlags.Variable | ts.SymbolFlags.Property | ts.SymbolFlags.ValueModule)) {
		kinds.push('value');
	}

	if (flags & ts.SymbolFlags.Interface) {
		kinds.push('interface');
	}

	if (flags & ts.SymbolFlags.TypeAlias) {
		kinds.push('type');
	}

	return kinds;
}

const options = readCompilerOptions();
const program = ts.createProgram({ rootNames: [ENTRY], options, host: createHost(options) });
const checker = program.getTypeChecker();

const unresolved = ts
	.sortAndDeduplicateDiagnostics([...program.getSyntacticDiagnostics(), ...program.getSemanticDiagnostics()])
	.filter((diagnostic) => UNRESOLVED_MODULE.has(diagnostic.code));

if (unresolved.length > 0) {
	fail(
		'the program has an unresolved import, so the surface would be incomplete:\n' +
			unresolved.map((diagnostic) => `  ${ts.flattenDiagnosticMessageText(diagnostic.messageText, '\n')}`).join('\n'),
	);
}

const entry = program.getSourceFile(ENTRY);
const moduleSymbol = entry && checker.getSymbolAtLocation(entry);

if (!moduleSymbol) {
	fail(`${ENTRY} is not a module`);
}

const lines = new Set();

for (const exported of checker.getExportsOfModule(moduleSymbol)) {
	const target = exported.flags & ts.SymbolFlags.Alias ? checker.getAliasedSymbol(exported) : exported;
	const kinds = kindsOf(target);

	if (kinds.length === 0) {
		fail(`cannot classify the export "${exported.name}" (symbol flags ${target.flags})`);
	}

	for (const kind of kinds) {
		lines.add(`${exported.name}\t${kind}`);
	}
}

if (lines.size === 0) {
	fail(`${ENTRY} exports nothing`);
}

process.stdout.write([...lines].sort().join('\n') + '\n');
