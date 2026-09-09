# Phase 5, Frontend Consolidation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the nine separately built library packages plus the `Core/Application` shell into one root Vite application that consumes the eight UI-carrying extensions as source through yarn workspaces, still on yarn 1.

**Architecture:** Lift the shell's `index.html`, `vite.config.ts`, `tsconfig.json`, `uno.config.ts`, `eslint.config.mjs` and `prettier.config.mjs` from `src/FastyBird/Core/Application/` to the repository root, add a root `stylelint.config.mjs`, and rewrite every one of the eight UI extension `package.json` files (`Connector/HomeKit`, `Core/Application`, `Core/Tools`, `Library/Metadata`, `Module/Accounts`, `Module/Devices`, `Module/Triggers`, `Module/Ui`) so each becomes a plain yarn workspace member whose `exports` field points straight at its `assets/entry.ts` source, with no build step of its own. The root `vite.config.ts` drops the three `NODE_ENV`-conditional extension aliases because yarn's workspace symlinks plus each extension's new `exports` field now resolve `@fastybird/accounts-module`, `@fastybird/devices-module` and `@fastybird/homekit-connector` to source in every mode; a new `@config` alias replaces the five-level relative path from `assets/main.ts` to the shipped extension registry. `lerna.json` and the `lerna` devDependency are deleted everywhere (root and `Library/WebUi`) and replaced with plain `yarn workspace <name> <script>` commands sequenced by hand in the root `build:ui` script, because the five `Library/WebUi` packages (`utils`, `icons`, `theme-chalk`, `components`, `web-ui-library`) keep their own real Vite/gulp/esbuild builds and must run in that dependency order before the application build.

**Tech Stack:** yarn 1 classic workspaces, Vite 5, vue-tsc 2, TypeScript 5.6, ESLint 9 flat config, Prettier 3, Stylelint 16, UnoCSS 0.64, Vue 3.5, Pinia 2, vue-router 4.4, vue-i18n 10, vue-meta 3 alpha, Element Plus 2.8.

**Spec:** docs/superpowers/specs/2026-09-09-miniserver-merge-design.md

## Global Constraints

- PHP 8.2, Node >= 20, yarn 1 during this phase (D10). pnpm replaces yarn only in the first Phase 6 pull request; this plan does not touch `pnpm-workspace.yaml` or `pnpm-lock.yaml`.
- **Every `yarn` command in this plan is run inside the Node 20 container, never on the host** (spec 4.10). The development host runs Node 24, and this phase rewrites workspace resolution, `exports` fields and the lockfile, all of which resolve differently across major Node versions. A build that passes on Node 24 is not evidence the frozen toolchain still builds. Use the `ui-server` service that Phase 2 produced:

  ```bash
  docker compose -f docker/dev/docker-compose.yml exec -T ui-server sh -lc "yarn build"
  ```

  Wherever a step below reads `Run: yarn <script>`, execute it in that form and expect the stated output from the container. This applies only to commands *you* invoke. The `yarn` strings written *into* `package.json` script bodies stay exactly as shown, because they already execute inside the container.
- Confirm the container is really Node 20 before trusting any result in this phase: `docker compose -f docker/dev/docker-compose.yml exec -T ui-server node --version` must print `v20.x`. Phase 1 pinned this image to `node:20-alpine`; if it prints anything else, stop and fix the pin before continuing, because every verification in this plan depends on it.
- No external dependency version numbers change in this phase. Every `package.json` edit either relocates an already-declared version string to a different field (`dependencies` → `peerDependencies`, or the reverse) or introduces the internal workspace self-reference `0.0.0` for one of the eight packages this phase itself versions at `0.0.0` (D10, D11).
- No pull request in this phase mixes a structural change with a dependency version change (D11). CI must be green before the next pull request starts; this phase is a single pull request, so CI must be green before Phase 6 starts.
- Conventional commit format `<type>(<scope>): <subject>` with the scope list `core, module, connector, plugin, bridge, addon, automator, library, ui, infra, ci, deps, docs, cross` (4.8), even though commitlint enforcement is wired in Phase 4, which runs before this phase.
- PHP namespaces stay `FastyBird\<Type>\<Name>` (D4) and `src/FastyBird/` does not change (D3); this phase touches no PHP file.
- This plan assumes Phases 0 through 4 are already merged: `config/extensions.ts` exists at `config/extensions.ts` (moved from `var/config/` in Phase 3), the root `composer.json`/`package.json` identity fields already read `fastybird/miniserver` / `@fastybird/miniserver` (Phase 4, table 4.9), and repository/bugs URLs already read `https://github.com/FastyBird/miniserver` everywhere (4.9). Every path and content block below is written against that post-Phase-4 state.
- `Library/WebUi`'s five packages (`utils`, `icons`, `theme-chalk`, `components`, `web-ui-library`) and its `docs` (Storybook) package keep their existing `package.json`, `vite.config.ts`/build scripts untouched in this phase; only the orchestration layer above them (`Library/WebUi/package.json`'s own `workspaces` field and `lerna.json`) is removed.
- Registering `Module/Triggers` or `Module/Ui` in `config/extensions.ts` is out of scope (8, spec). Renaming `assets/old-entry.ts` to `assets/entry.ts` in `Module/Triggers` is a structural fix so the package can carry the uniform `exports` shape; it does not register the module.

## Pull Requests

1. **PR1 — Frontend consolidation to a single root Vite app (yarn).** All 16 tasks below. This phase is kept as one pull request: the root `vite.config.ts` can only drop its extension aliases once every extension `package.json` already exposes `exports: {".": "./assets/entry.ts"}`, and the root `package.json` build/lint/type scripts can only run once `lerna.json` is gone and every extension's own build/lint tooling is gone — splitting these across pull requests would leave an intermediate state where `yarn build`, `yarn dev` or `yarn lint:js` fails, violating D11's "CI green before the next pull request" rule. Tasks 1–14 are pure file moves and manifest edits; Tasks 15–16 install and verify the result end to end.

---

### Task 1: Lift the shell's build config to the repository root

**Files:**
- Move: `src/FastyBird/Core/Application/index.html` → `index.html`
- Move: `src/FastyBird/Core/Application/vite.config.ts` → `vite.config.ts`
- Move: `src/FastyBird/Core/Application/tsconfig.json` → `tsconfig.json`
- Move: `src/FastyBird/Core/Application/uno.config.ts` → `uno.config.ts`
- Move: `src/FastyBird/Core/Application/eslint.config.mjs` → `eslint.config.mjs`
- Move: `src/FastyBird/Core/Application/prettier.config.mjs` → `prettier.config.mjs`
- Create: `stylelint.config.mjs`

**Interfaces:**
- Consumes: nothing from this plan (first task).
- Produces: `vite.config.ts`'s `@config` alias and `define` block, consumed by Task 13 (`assets/main.ts`) and Task 14 (`assets/App.vue`); root `tsconfig.json`'s `include` globs, consumed by every extension `package.json` rewrite (Tasks 5–12) and by Task 16's `yarn types` check.

- [ ] **Step 1: Move the six files with `git mv`**

```bash
git mv src/FastyBird/Core/Application/index.html index.html
git mv src/FastyBird/Core/Application/vite.config.ts vite.config.ts
git mv src/FastyBird/Core/Application/tsconfig.json tsconfig.json
git mv src/FastyBird/Core/Application/uno.config.ts uno.config.ts
git mv src/FastyBird/Core/Application/eslint.config.mjs eslint.config.mjs
git mv src/FastyBird/Core/Application/prettier.config.mjs prettier.config.mjs
```

- [ ] **Step 2: Point `index.html`'s script tag at the shell's real path**

Edit `index.html`, replacing the closing `<script>` line:

```html
    <script type="module" src="./assets/main.ts"></script>
```

with:

```html
    <script type="module" src="/src/FastyBird/Core/Application/assets/main.ts"></script>
```

- [ ] **Step 3: Rewrite `vite.config.ts` for the root**

Replace the whole file with:

```ts
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
```

This drops the three `process.env.NODE_ENV` conditional aliases for `@fastybird/accounts-module`, `@fastybird/devices-module` and `@fastybird/homekit-connector` (Tasks 8, 9 and 5 give those packages `exports: {".": "./assets/entry.ts"}`, so yarn's workspace symlink resolves them to source in every mode without an alias) and changes `build.outDir` from `resolve(__dirname, './../../../../public')` to `resolve(__dirname, './public')` because `__dirname` is now the repository root.

- [ ] **Step 4: Rewrite `tsconfig.json` for the root**

Replace the whole file with:

```json
{
  "compilerOptions": {
    "target": "esnext",
    "module": "esnext",
    "strict": true,
    "declaration": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "importHelpers": true,
    "moduleResolution": "node",
    "experimentalDecorators": true,
    "esModuleInterop": true,
    "allowSyntheticDefaultImports": true,
    "sourceMap": true,
    "baseUrl": ".",
    "paths": {
      "@config/*": ["./config/*"]
    },
    "skipLibCheck": true,
    "resolveJsonModule": true,
    "types": [
      "@fastybird/tools",
      "@fastybird/metadata-library",
      "@fastybird/web-ui-library",
      "@intlify/unplugin-vue-i18n/messages",
      "@types/lodash",
      "@types/md5",
      "node",
      "vite/client",
      "vite-plugin-vue-type-imports",
      "vite-svg-loader",
      "vue-meta",
      "unocss"
    ],
    "lib": [
      "esnext",
      "dom",
      "dom.iterable",
      "scripthost"
    ]
  },
  "include": [
    "src/FastyBird/*/*/assets/**/*.ts",
    "src/FastyBird/*/*/assets/**/*.d.ts",
    "src/FastyBird/*/*/assets/**/*.vue",
    "src/FastyBird/*/*/assets/**/*.json",
    "config/**/*.ts",
    "package.json"
  ],
  "exclude": [
    "node_modules",
    "dist"
  ]
}
```

This drops `"composite": true` (each extension no longer builds its own `dist`/`.tsbuildinfo`, so there is nothing to compose) and replaces `"baseUrl": "assets"` with `"."`, adds a `"paths"` entry mapping `@config/*` to `./config/*` (matching the Vite `@config` alias from Step 3, so `vue-tsc` can resolve the `import { extensions } from '@config/extensions';` added in Task 13), and replaces the `assets/**/*` includes with the `src/FastyBird/*/*/assets/**/*` globs plus `config/**/*.ts` (4.4).

- [ ] **Step 5: Create the root `stylelint.config.mjs`**

```js
export default {
	extends: ['stylelint-config-recommended-scss', 'stylelint-prettier/recommended'],
	syntax: 'scss',
	plugins: ['stylelint-scss', 'stylelint-prettier'],
	rules: {
		'at-rule-no-unknown': null,
		'scss/at-rule-no-unknown': true,
		'no-empty-source': null,
		'prettier/prettier': [true, { singleQuote: true, tabWidth: 2 }],
		'selector-pseudo-class-no-unknown': [
			true,
			{
				ignorePseudoClasses: ['deep'],
			},
		],
	},
};
```

This is the ESM equivalent of `src/FastyBird/Module/Devices/.stylelintrc.json`, the fullest of the per-extension `.stylelintrc.json` files (Module/Accounts and Connector/HomeKit are byte-identical to it; Core/Application's copy is missing only the `selector-pseudo-class-no-unknown` rule).

- [ ] **Step 6: Verify the move**

Run:

```bash
test -f index.html && test -f vite.config.ts && test -f tsconfig.json && test -f uno.config.ts && test -f eslint.config.mjs && test -f prettier.config.mjs && test -f stylelint.config.mjs && echo ALL_PRESENT
test -f src/FastyBird/Core/Application/vite.config.ts && echo STILL_THERE || echo GONE
node -e "require('./vite.config.ts')" 2>&1 | head -1
```

Expected: `ALL_PRESENT`, then `GONE` (the six files no longer exist under `Core/Application`), and the last line fails only on `import`/TS syntax (Node cannot run `.ts` directly), not on a missing-file error — confirming the file parses as valid JS/TS text. Full syntactic validation happens in Task 16 when `vite build` actually loads this config.

- [ ] **Step 7: Commit**

```bash
git add index.html vite.config.ts tsconfig.json uno.config.ts eslint.config.mjs prettier.config.mjs stylelint.config.mjs src/FastyBird/Core/Application
git commit -m "infra(cross): lift application shell build config to the repository root"
```

---

### Task 2: Rewrite the root `package.json` — workspaces, scripts, dependencies

**Files:**
- Modify: `package.json` (workspaces glob, scripts block, add dependencies, replace devDependencies)

**Interfaces:**
- Consumes: Task 1's root `vite.config.ts`/`tsconfig.json` (the `build`/`types` scripts invoke `vite build`/`vue-tsc` against them); the five `Library/WebUi` package names for `build:ui`.
- Produces: `yarn dev`, `yarn build`, `yarn build:ui`, `yarn types`, `yarn lint:js`, `yarn lint:js:fix`, `yarn lint:styles`, `yarn pretty`, `yarn pretty:check`, `yarn pretty:write`, `yarn storybook`, consumed by Task 16's verification.

- [ ] **Step 1: Replace the `workspaces` array**

Edit `package.json`, replacing:

```json
  "workspaces": [
    "src/FastyBird/Addon/**/*",
    "src/FastyBird/Automator/**/*",
    "src/FastyBird/Bridge/**/*",
    "src/FastyBird/Connector/**/*",
    "src/FastyBird/Core/**/*",
    "src/FastyBird/Library/**/*",
    "src/FastyBird/Module/**/*",
    "src/FastyBird/Plugin/**/*"
  ],
```

with:

```json
  "workspaces": [
    "src/FastyBird/*/*",
    "src/FastyBird/Library/WebUi/packages/*",
    "src/FastyBird/Library/WebUi/web-ui-library",
    "src/FastyBird/Library/WebUi/docs"
  ],
```

- [ ] **Step 2: Replace the `scripts` block**

Replace:

```json
  "scripts": {
    "dev": "lerna run dev --stream --ignore '@fastybird/web-ui'",
    "build": "lerna run build --stream --ignore '@fastybird/web-ui' --ignore '@fastybird/application' && yarn workspace @fastybird/application build",
    "build:dev": "lerna run build:dev --stream --ignore '@fastybird/web-ui' --ignore '@fastybird/application' && yarn workspace @fastybird/application build:dev",
    "clean": "lerna clean && rm -rf node_modules",
    "fix": "lerna run fix",
    "graph": "nx graph",
    "types": "lerna run types --stream --ignore '@fastybird/web-ui'",
    "lint:js": "lerna run lint:js --stream --ignore '@fastybird/web-ui'",
    "lint:js:fix": "lerna run lint:js:fix --stream --ignore '@fastybird/web-ui'",
    "lint:styles": "lerna run lint:styles --stream --ignore '@fastybird/web-ui'",
    "pretty": "yarn pretty:write && yarn pretty:check --ignore '@fastybird/web-ui'",
    "pretty:check": "lerna run pretty:check --ignore '@fastybird/web-ui'",
    "pretty:write": "lerna run pretty:write --ignore '@fastybird/web-ui'",
    "test": "lerna run test --stream --ignore '@fastybird/web-ui'"
  },
```

with:

```json
  "scripts": {
    "dev": "vite --host",
    "build:ui": "yarn workspace @fastybird/web-ui-utils build && yarn workspace @fastybird/web-ui-icons build && yarn workspace @fastybird/web-ui-theme-chalk build && yarn workspace @fastybird/web-ui-components build && yarn workspace @fastybird/web-ui-library build",
    "build": "yarn build:ui && vue-tsc --noEmit && vite build",
    "types": "vue-tsc --noEmit",
    "lint:js": "eslint src/FastyBird/*/*/assets",
    "lint:js:fix": "eslint src/FastyBird/*/*/assets --fix",
    "lint:styles": "stylelint 'src/FastyBird/*/*/assets/**/*.scss'",
    "pretty": "yarn pretty:write && yarn pretty:check",
    "pretty:check": "prettier src/FastyBird/*/*/assets --check",
    "pretty:write": "prettier src/FastyBird/*/*/assets --write",
    "storybook": "yarn workspace @fastybird/web-ui-docs dev"
  },
```

The `build:ui` order (`utils`, `icons`, `theme-chalk`, `components`, `web-ui-library`) matches the dependency graph read from the five packages' own `package.json` files: `web-ui-components` depends on `web-ui-icons`, `web-ui-theme-chalk` and `web-ui-utils`; `web-ui-library` depends on `web-ui-theme-chalk` and dev-depends on `web-ui-components` and `web-ui-icons`.

- [ ] **Step 3: Add `dependencies` and replace `devDependencies`**

Insert a `dependencies` key and replace the `devDependencies` key (this is the superset described in 4.4, copied verbatim from `src/FastyBird/Core/Application/package.json`'s own `dependencies`/`devDependencies` before Task 12 empties that file):

```json
  "dependencies": {
    "@fastybird/metadata-library": "0.0.0",
    "@fastybird/tools": "0.0.0",
    "@fastybird/vue-wamp-v1": "^1.2",
    "@fastybird/web-ui-icons": "1.0.0-dev.24",
    "@fastybird/web-ui-library": "1.0.0-dev.24",
    "@iconify/iconify": "^3.1",
    "@iconify/vue": "^4.1",
    "element-plus": "^2.8",
    "lodash.get": "^4.4",
    "md5": "^2.3",
    "nprogress": "^0.2",
    "pinia": "^2.2",
    "unocss": "^0.64",
    "vue": "^3.5",
    "vue-i18n": "^10.0",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.4"
  },
  "devDependencies": {
    "@commitlint/cli": "^19.6",
    "@commitlint/config-conventional": "^19.6",
    "@eslint/js": "^9.15",
    "@intlify/unplugin-vue-i18n": "^6.0",
    "@nabla/vite-plugin-eslint": "^2.0",
    "@trivago/prettier-plugin-sort-imports": "^4.3",
    "@types/lodash.get": "^4.4",
    "@types/md5": "^2.3",
    "@types/node": "^20.17",
    "@types/nprogress": "^0.2",
    "@typescript-eslint/eslint-plugin": "^8.15",
    "@typescript-eslint/parser": "^8.15",
    "@unocss/transformer-variant-group": "^0.64",
    "@vitejs/plugin-vue": "^5.2",
    "@vue/eslint-config-prettier": "^10.1",
    "@vue/eslint-config-typescript": "^14.1",
    "babel-loader": "^9.2",
    "cross-env": "^7.0",
    "dotenv": "^16.4",
    "eslint": "^9.15",
    "eslint-config-prettier": "^9.1",
    "eslint-plugin-prettier": "^5.2",
    "eslint-plugin-vue": "^9.31",
    "minimist": "^1.2",
    "postcss": "^8.4",
    "postcss-scss": "^4.0",
    "prettier": "^3.3",
    "rimraf": "^6.0",
    "sass": "^1.81",
    "sass-loader": "^16.0",
    "stylelint": "^16.10",
    "stylelint-config-prettier": "^9.0",
    "stylelint-config-recommended-vue": "^1.5",
    "stylelint-config-standard": "^36.0",
    "stylelint-config-standard-scss": "^13.1",
    "stylelint-order": "^6.0",
    "stylelint-prettier": "^5.0",
    "typescript": "5.6.2",
    "typescript-eslint": "^8.15",
    "vconsole": "^3.15",
    "vite": "^5.4",
    "vite-plugin-eslint": "^1.8",
    "vite-plugin-vconsole": "^2.1",
    "vite-plugin-vue-type-imports": "^0.2",
    "vite-svg-loader": "^5.1",
    "vue-loader": "^17.4",
    "vue-tsc": "^2.1"
  },
```

No version number in either block is new: `@fastybird/metadata-library` and `@fastybird/tools` are the two of Core/Application's original `dependencies` entries that name one of the eight packages this phase re-versions at `0.0.0` (Tasks 6 and 7); every other line is copied unchanged from `src/FastyBird/Core/Application/package.json`. The `lerna` devDependency is not carried over (Task 3 deletes it).

- [ ] **Step 4: Verify the file is valid JSON and lerna is gone**

Run:

```bash
node -e "const p = require('./package.json'); console.log(p.workspaces.length, Object.keys(p.scripts).length, 'lerna' in (p.devDependencies||{}))"
```

Expected: `4 11 false` (four workspace globs, eleven scripts, no `lerna` key).

- [ ] **Step 5: Commit**

```bash
git add package.json
git commit -m "infra(cross): replace lerna orchestration with plain yarn workspace scripts in root package.json"
```

---

### Task 3: Delete `lerna.json` and the `lerna` dependency, trim `Library/WebUi`'s own workspace

**Files:**
- Delete: `lerna.json`
- Delete: `src/FastyBird/Library/WebUi/lerna.json`
- Modify: `src/FastyBird/Library/WebUi/package.json` (remove `workspaces` and lerna-based scripts/devDependency)

**Interfaces:**
- Consumes: Task 2's root `workspaces` array, which now lists `src/FastyBird/Library/WebUi/packages/*`, `.../web-ui-library` and `.../docs` directly, making `Library/WebUi`'s own nested `workspaces` field redundant.
- Produces: a `Library/WebUi/package.json` that is a normal (non-orchestrating) workspace member, consumed by Task 16's `yarn install`.

- [ ] **Step 1: Delete both `lerna.json` files**

```bash
git rm lerna.json src/FastyBird/Library/WebUi/lerna.json
```

- [ ] **Step 2: Trim `Library/WebUi/package.json`**

Replace:

```json
  "workspaces": [
    "docs",
    "packages/**/*",
    "web-ui-library"
  ],
  "scripts": {
    "dev": "lerna run dev --stream",
    "build": "lerna run build --stream",
    "build:dev": "lerna run build:dev --stream",
    "clean": "lerna clean && rm -rf node_modules",
    "bootstrap": "lerna bootstrap",
    "fix": "lerna run fix",
    "graph": "nx graph",
    "types": "lerna run types --stream",
    "lint:js": "lerna run lint:js --stream",
    "lint:js:fix": "lerna run lint:js:fix --stream",
    "lint:styles": "lerna run lint:styles --stream",
    "playground": "lerna run dev --scope=@fastybird/web-ui-docs",
    "pretty": "yarn pretty:write && yarn pretty:check",
    "pretty:check": "lerna run pretty:check",
    "pretty:write": "lerna run pretty:write",
    "test": "yarn jest --coverage"
  },
  "devDependencies": {
    "lerna": "^8.1"
  },
  "engines": {
    "node": ">=20"
  }
```

with:

```json
  "engines": {
    "node": ">=20"
  }
```

(the OLD block above includes the file's pre-existing trailing `"engines"` section immediately after `devDependencies`, so replacing it with the single NEW block leaves exactly one `"engines"` key in the file, as the last key before the closing brace).

- [ ] **Step 3: Verify no `lerna.json` and no `lerna` dependency remain anywhere in the tracked tree**

Run:

```bash
git ls-files | grep -i lerna
grep -rl '"lerna"' --include=package.json src/FastyBird package.json
```

Expected: both commands print nothing.

- [ ] **Step 4: Commit**

```bash
git add -u lerna.json src/FastyBird/Library/WebUi/lerna.json src/FastyBird/Library/WebUi/package.json
git commit -m "infra(library): remove lerna orchestration from the web-ui workspace"
```

---

### Task 4: Rename `Module/Triggers`'s stub entry file so it matches the uniform `exports` shape

**Files:**
- Move: `src/FastyBird/Module/Triggers/assets/old-entry.ts` → `src/FastyBird/Module/Triggers/assets/entry.ts`

**Interfaces:**
- Consumes: nothing (independent of Tasks 1–3).
- Produces: `src/FastyBird/Module/Triggers/assets/entry.ts`, consumed by Task 10's `package.json` `exports` field.

- [ ] **Step 1: Confirm no other file references the old name**

Run:

```bash
grep -rn "old-entry" src/FastyBird/Module/Triggers
```

Expected: no output (the file is only ever loaded as the package's `main`/`exports` target, never imported by relative path from a sibling file).

- [ ] **Step 2: Rename with `git mv`**

```bash
git mv src/FastyBird/Module/Triggers/assets/old-entry.ts src/FastyBird/Module/Triggers/assets/entry.ts
```

- [ ] **Step 3: Verify**

Run:

```bash
test -f src/FastyBird/Module/Triggers/assets/entry.ts && ! test -f src/FastyBird/Module/Triggers/assets/old-entry.ts && echo RENAMED
```

Expected: `RENAMED`.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Module/Triggers/assets
git commit -m "module(triggers): rename the stub entry file to entry.ts"
```

---

### Task 5: Rewrite `Connector/HomeKit/package.json` and delete its per-extension config

**Files:**
- Modify: `src/FastyBird/Connector/HomeKit/package.json`
- Delete: `src/FastyBird/Connector/HomeKit/vite.config.ts`
- Delete: `src/FastyBird/Connector/HomeKit/tsconfig.json`
- Delete: `src/FastyBird/Connector/HomeKit/eslint.config.mjs`
- Delete: `src/FastyBird/Connector/HomeKit/prettier.config.mjs`
- Delete: `src/FastyBird/Connector/HomeKit/.stylelintrc.json`
- Delete: `src/FastyBird/Connector/HomeKit/.browserslistrc`
- Delete: `src/FastyBird/Connector/HomeKit/jest.config.js`
- Delete: `src/FastyBird/Connector/HomeKit/commitlint.config.js`

**Interfaces:**
- Consumes: `src/FastyBird/Connector/HomeKit/assets/entry.ts` (confirmed present), Task 6/9's `@fastybird/tools`/`@fastybird/devices-module` re-versioning to `0.0.0`.
- Produces: `@fastybird/homekit-connector` resolving to `./assets/entry.ts` via yarn workspace symlink, consumed by Task 1's root `vite.config.ts` (no more alias needed) and by `config/extensions.ts`.

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/homekit-connector",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT connector for HomeKit Accessory Protocol",
  "keywords": [
    "fastybird",
    "fb",
    "api",
    "php",
    "iot",
    "vuejs",
    "typescript",
    "vue",
    "connector",
    "nette",
    "controls",
    "devices",
    "vue3",
    "pinia"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "sideEffects": true,
  "dependencies": {
    "@chenfengyuan/vue-qrcode": "^2.0",
    "@fastybird/devices-module": "0.0.0",
    "@fastybird/metadata-library": "0.0.0",
    "@fastybird/tools": "0.0.0",
    "@fastybird/vue-wamp-v1": "^1.2",
    "@fastybird/web-ui-icons": "1.0.0-dev.24",
    "@fastybird/web-ui-library": "1.0.0-dev.24",
    "lodash.defaultsdeep": "^4.6",
    "lodash.get": "^4.4",
    "lodash.omit": "^4.5",
    "natural-orderby": "^5.0",
    "qrcode": "^1.5"
  },
  "peerDependencies": {
    "element-plus": "^2.8",
    "pinia": "^2.2",
    "unocss": "^0.64",
    "vue": "^3.5",
    "vue-i18n": "^10.0",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.4"
  }
}
```

`pinia`, `vue` and `vue-i18n` were already declared in this file's own `peerDependencies` (as well as duplicated in `devDependencies`) at these exact versions; `vue-meta` and `unocss` are genuinely new to `peerDependencies`, added at the version this file's `devDependencies` already declared; `element-plus` moves from `dependencies` to `peerDependencies` at the same `^2.8`; `vue-router` is added at `^4.4`, declared nowhere in the original file.

- [ ] **Step 2: Delete the per-extension config files**

```bash
git rm src/FastyBird/Connector/HomeKit/vite.config.ts \
       src/FastyBird/Connector/HomeKit/tsconfig.json \
       src/FastyBird/Connector/HomeKit/eslint.config.mjs \
       src/FastyBird/Connector/HomeKit/prettier.config.mjs \
       src/FastyBird/Connector/HomeKit/.stylelintrc.json \
       src/FastyBird/Connector/HomeKit/.browserslistrc \
       src/FastyBird/Connector/HomeKit/jest.config.js \
       src/FastyBird/Connector/HomeKit/commitlint.config.js
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Connector/HomeKit/package.json'); console.log(p.version, p.exports['.'], 'vue' in p.peerDependencies, 'vue' in (p.dependencies||{}))"
find src/FastyBird/Connector/HomeKit -maxdepth 1 -name "vite.config.ts" -o -maxdepth 1 -name "tsconfig.json"
```

Expected: `0.0.0 ./assets/entry.ts true false`, and the `find` prints nothing.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Connector/HomeKit
git commit -m "connector(homekit): convert package.json to a source-only yarn workspace member"
```

---

### Task 6: Rewrite `Core/Tools/package.json` and delete its per-extension config

**Files:**
- Modify: `src/FastyBird/Core/Tools/package.json`
- Delete: `src/FastyBird/Core/Tools/vite.config.ts`
- Delete: `src/FastyBird/Core/Tools/tsconfig.json`
- Delete: `src/FastyBird/Core/Tools/eslint.config.mjs`
- Delete: `src/FastyBird/Core/Tools/prettier.config.mjs`
- Delete: `src/FastyBird/Core/Tools/commitlint.config.mjs`

**Interfaces:**
- Consumes: `src/FastyBird/Core/Tools/assets/entry.ts` (confirmed present).
- Produces: `@fastybird/tools` at version `0.0.0`, consumed by Task 5, 8, 9, 11's `dependencies` and by every extension that imports `@fastybird/tools` (93 import sites per spec 2.1).

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/tools",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT useful tools",
  "keywords": [
    "fastybird",
    "fb",
    "libs",
    "library",
    "tools",
    "composables"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "sideEffects": true,
  "dependencies": {
    "@fastybird/metadata-library": "0.0.0",
    "@vueuse/core": "^11.2",
    "axios": "^1.6",
    "lodash.get": "^4.4",
    "mitt": "^3.0"
  },
  "peerDependencies": {
    "element-plus": "^2.8",
    "pinia": "^2.2",
    "unocss": "^0.64",
    "vue": "^3.5",
    "vue-i18n": "^10.0",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.4"
  }
}
```

`pinia`, `vue`, `vue-i18n` and `vue-router` were already this file's own `peerDependencies` at these exact versions; `element-plus` moves here from `dependencies` at the same `^2.8`. `vue-meta` and `unocss` were declared nowhere in the original file, so both are added at the version the root `package.json` provides (`^3.0.0-alpha.10` and `^0.64`).

- [ ] **Step 2: Delete the per-extension config files**

```bash
git rm src/FastyBird/Core/Tools/vite.config.ts \
       src/FastyBird/Core/Tools/tsconfig.json \
       src/FastyBird/Core/Tools/eslint.config.mjs \
       src/FastyBird/Core/Tools/prettier.config.mjs \
       src/FastyBird/Core/Tools/commitlint.config.mjs
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Core/Tools/package.json'); console.log(p.version, p.exports['.'])"
find src/FastyBird/Core/Tools -maxdepth 1 -name "vite.config.ts" -o -maxdepth 1 -name "tsconfig.json"
```

Expected: `0.0.0 ./assets/entry.ts`, and `find` prints nothing.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Tools
git commit -m "core(tools): convert package.json to a source-only yarn workspace member"
```

---

### Task 7: Rewrite `Library/Metadata/package.json` and delete its per-extension config

**Files:**
- Modify: `src/FastyBird/Library/Metadata/package.json`
- Delete: `src/FastyBird/Library/Metadata/vite.config.ts`
- Delete: `src/FastyBird/Library/Metadata/tsconfig.json`
- Delete: `src/FastyBird/Library/Metadata/eslint.config.mjs`
- Delete: `src/FastyBird/Library/Metadata/prettier.config.mjs`
- Delete: `src/FastyBird/Library/Metadata/commitlint.config.mjs`

**Interfaces:**
- Consumes: `src/FastyBird/Library/Metadata/assets/entry.ts` (confirmed present; a pure type re-export, `export * from './types'`, with no runtime import).
- Produces: `@fastybird/metadata-library` at version `0.0.0`, consumed by Tasks 5, 6, 8, 9, 11's `dependencies` (43 import sites per spec 2.1).

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/metadata-library",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT metadata reader & validator",
  "keywords": [
    "fastybird",
    "fb",
    "libs",
    "library",
    "metadata",
    "modules",
    "plugins",
    "connectors"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "sideEffects": true,
  "peerDependencies": {
    "element-plus": "^2.8",
    "pinia": "^2.2",
    "unocss": "^0.64",
    "vue": "^3.5",
    "vue-i18n": "^10.0",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.4"
  }
}
```

There is no `dependencies` key: `assets/entry.ts` re-exports only its own local `./types` module, and nothing else in `Library/Metadata/assets` imports an external package (verified by `grep -rhoE "from '[^.'][^']*'"` across the whole `assets/` tree, which returned no matches). The uniform `peerDependencies` set is added at the root `package.json`'s versions, since none of the seven are declared anywhere in the original file.

- [ ] **Step 2: Delete the per-extension config files**

```bash
git rm src/FastyBird/Library/Metadata/vite.config.ts \
       src/FastyBird/Library/Metadata/tsconfig.json \
       src/FastyBird/Library/Metadata/eslint.config.mjs \
       src/FastyBird/Library/Metadata/prettier.config.mjs \
       src/FastyBird/Library/Metadata/commitlint.config.mjs
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Library/Metadata/package.json'); console.log(p.version, p.exports['.'], 'dependencies' in p)"
find src/FastyBird/Library/Metadata -maxdepth 1 -name "vite.config.ts" -o -maxdepth 1 -name "tsconfig.json"
```

Expected: `0.0.0 ./assets/entry.ts false`, and `find` prints nothing.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Library/Metadata
git commit -m "library(metadata): convert package.json to a source-only yarn workspace member"
```

---

### Task 8: Rewrite `Module/Accounts/package.json` and delete its per-extension config

**Files:**
- Modify: `src/FastyBird/Module/Accounts/package.json`
- Delete: `src/FastyBird/Module/Accounts/vite.config.ts`
- Delete: `src/FastyBird/Module/Accounts/tsconfig.json`
- Delete: `src/FastyBird/Module/Accounts/eslint.config.mjs`
- Delete: `src/FastyBird/Module/Accounts/prettier.config.mjs`
- Delete: `src/FastyBird/Module/Accounts/.stylelintrc.json`
- Delete: `src/FastyBird/Module/Accounts/.browserslistrc`
- Delete: `src/FastyBird/Module/Accounts/jest.config.js`
- Delete: `src/FastyBird/Module/Accounts/commitlint.config.js`

**Interfaces:**
- Consumes: `src/FastyBird/Module/Accounts/assets/entry.ts` (confirmed present, `export default {...}`); Tasks 6/7's `@fastybird/tools`/`@fastybird/metadata-library` re-versioning.
- Produces: `@fastybird/accounts-module` resolving to source, consumed by Task 1's root `vite.config.ts` (dropped alias) and `config/extensions.ts`.

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/accounts-module",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT accounts module for application accounts & access management",
  "keywords": [
    "fastybird",
    "fb",
    "api",
    "php",
    "iot",
    "security",
    "vuejs",
    "vue",
    "nette",
    "accounts",
    "roles",
    "access",
    "vue3",
    "pinia"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "sideEffects": true,
  "dependencies": {
    "@fastybird/metadata-library": "0.0.0",
    "@fastybird/tools": "0.0.0",
    "@fastybird/web-ui-icons": "1.0.0-dev.24",
    "@fastybird/web-ui-library": "1.0.0-dev.24",
    "@sentry/vue": "^8.39",
    "ajv": "^8.17",
    "ajv-formats": "^3.0",
    "async-validator": "^4.2",
    "axios": "^1.7",
    "date-fns": "^4.1",
    "jsona": "^1.12",
    "jwt-decode": "^4.0",
    "lodash.defaultsdeep": "^4.6",
    "lodash.get": "^4.4",
    "md5": "^2.3",
    "uuid": "^11.0",
    "vue3-cookies": "^1.0"
  },
  "peerDependencies": {
    "element-plus": "^2.8",
    "pinia": "^2.2",
    "unocss": "^0.64",
    "vue": "^3.5",
    "vue-i18n": "^10.0",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.4"
  }
}
```

`pinia`, `unocss`, `vue`, `vue-i18n`, `vue-meta` and `vue-router` were already this file's own `peerDependencies` at these exact versions; only `element-plus` (`^2.8`) moves in from `dependencies`, at the same version.

- [ ] **Step 2: Delete the per-extension config files**

```bash
git rm src/FastyBird/Module/Accounts/vite.config.ts \
       src/FastyBird/Module/Accounts/tsconfig.json \
       src/FastyBird/Module/Accounts/eslint.config.mjs \
       src/FastyBird/Module/Accounts/prettier.config.mjs \
       src/FastyBird/Module/Accounts/.stylelintrc.json \
       src/FastyBird/Module/Accounts/.browserslistrc \
       src/FastyBird/Module/Accounts/jest.config.js \
       src/FastyBird/Module/Accounts/commitlint.config.js
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Module/Accounts/package.json'); console.log(p.version, p.exports['.'], 'element-plus' in p.peerDependencies, 'element-plus' in (p.dependencies||{}))"
find src/FastyBird/Module/Accounts -maxdepth 1 -name "vite.config.ts" -o -maxdepth 1 -name "tsconfig.json"
```

Expected: `0.0.0 ./assets/entry.ts true false`, and `find` prints nothing.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Module/Accounts
git commit -m "module(accounts): convert package.json to a source-only yarn workspace member"
```

---

### Task 9: Rewrite `Module/Devices/package.json` and delete its per-extension config

**Files:**
- Modify: `src/FastyBird/Module/Devices/package.json`
- Delete: `src/FastyBird/Module/Devices/vite.config.ts`
- Delete: `src/FastyBird/Module/Devices/tsconfig.json`
- Delete: `src/FastyBird/Module/Devices/eslint.config.mjs`
- Delete: `src/FastyBird/Module/Devices/prettier.config.mjs`
- Delete: `src/FastyBird/Module/Devices/.stylelintrc.json`
- Delete: `src/FastyBird/Module/Devices/.browserslistrc`
- Delete: `src/FastyBird/Module/Devices/jest.config.js`
- Delete: `src/FastyBird/Module/Devices/commitlint.config.js`

**Interfaces:**
- Consumes: `src/FastyBird/Module/Devices/assets/entry.ts` (confirmed present, `export default {...}`); Tasks 6/7's re-versioning.
- Produces: `@fastybird/devices-module` resolving to source, consumed by Task 1's root `vite.config.ts` (dropped alias), `config/extensions.ts`, and Task 5's HomeKit connector dependency.

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/devices-module",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT devices module for connectors & devices management & basic control logic",
  "keywords": [
    "fastybird",
    "fb",
    "api",
    "php",
    "iot",
    "vuejs",
    "typescript",
    "vue",
    "connector",
    "nette",
    "controls",
    "devices",
    "vue3",
    "pinia"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "sideEffects": true,
  "dependencies": {
    "@fastybird/metadata-library": "0.0.0",
    "@fastybird/tools": "0.0.0",
    "@fastybird/vue-wamp-v1": "^1.2",
    "@fastybird/web-ui-icons": "1.0.0-dev.24",
    "@fastybird/web-ui-library": "1.0.0-dev.24",
    "ajv": "^8.17",
    "ajv-formats": "^3.0",
    "async-validator": "^4.2",
    "axios": "^1.7",
    "date-fns": "^3.6",
    "idb": "^8.0",
    "jsona": "^1.12",
    "lodash.capitalize": "^4.2",
    "lodash.defaultsdeep": "^4.6",
    "lodash.get": "^4.4",
    "lodash.isequal": "^4.5",
    "natural-orderby": "^5.0",
    "uuid": "^11.0"
  },
  "peerDependencies": {
    "element-plus": "^2.8",
    "pinia": "^2.2",
    "unocss": "^0.64",
    "vue": "^3.5",
    "vue-i18n": "^10.0",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.4"
  }
}
```

`pinia`, `unocss`, `vue`, `vue-i18n`, `vue-meta` and `vue-router` were already this file's own `peerDependencies` at these exact versions; only `element-plus` (`^2.8`) moves in from `dependencies`.

- [ ] **Step 2: Delete the per-extension config files**

```bash
git rm src/FastyBird/Module/Devices/vite.config.ts \
       src/FastyBird/Module/Devices/tsconfig.json \
       src/FastyBird/Module/Devices/eslint.config.mjs \
       src/FastyBird/Module/Devices/prettier.config.mjs \
       src/FastyBird/Module/Devices/.stylelintrc.json \
       src/FastyBird/Module/Devices/.browserslistrc \
       src/FastyBird/Module/Devices/jest.config.js \
       src/FastyBird/Module/Devices/commitlint.config.js
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Module/Devices/package.json'); console.log(p.version, p.exports['.'])"
find src/FastyBird/Module/Devices -maxdepth 1 -name "vite.config.ts" -o -maxdepth 1 -name "tsconfig.json"
```

Expected: `0.0.0 ./assets/entry.ts`, and `find` prints nothing.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Module/Devices
git commit -m "module(devices): convert package.json to a source-only yarn workspace member"
```

---

### Task 10: Rewrite `Module/Triggers/package.json` and delete its per-extension config

**Files:**
- Modify: `src/FastyBird/Module/Triggers/package.json`
- Delete: `src/FastyBird/Module/Triggers/vite.config.ts`
- Delete: `src/FastyBird/Module/Triggers/tsconfig.json`
- Delete: `src/FastyBird/Module/Triggers/.prettierrc`
- Delete: `src/FastyBird/Module/Triggers/.stylelintrc.json`
- Delete: `src/FastyBird/Module/Triggers/.browserslistrc`
- Delete: `src/FastyBird/Module/Triggers/jest.config.js`
- Delete: `src/FastyBird/Module/Triggers/commitlint.config.js`

**Interfaces:**
- Consumes: Task 4's renamed `src/FastyBird/Module/Triggers/assets/entry.ts`.
- Produces: `@fastybird/triggers-module` resolving to source, not registered in `config/extensions.ts` (out of scope, per spec 8).

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/triggers-module",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT module for triggers management & basic automation logic",
  "keywords": [
    "fastybird",
    "fb",
    "api",
    "php",
    "iot",
    "automation",
    "vuejs",
    "typescript",
    "vue",
    "nette",
    "scenes triggers",
    "vue3",
    "pinia"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "sideEffects": true,
  "peerDependencies": {
    "element-plus": "^2.8",
    "pinia": "^2.1",
    "unocss": "^0.64",
    "vue": "^3.4",
    "vue-i18n": "^9.9",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.3"
  }
}
```

There is no `dependencies` key: `assets/entry.ts`, `configuration.ts`, `router/index.ts` and `types/index.ts` import only `vue` and `vue-router` (verified by `grep -rn "^import" src/FastyBird/Module/Triggers/assets`); the previously declared `@fastybird/metadata-library` and `@fastybird/web-ui-library` dependencies are not imported anywhere in this package's `assets/` tree and are dropped. `pinia` (`^2.1`), `vue` (`^3.4`), `vue-i18n` (`^9.9`) and `vue-meta` (`^3.0.0-alpha.10`) move in from this file's own original `peerDependencies` at their existing versions (which also listed `vee-validate` and `vue-toastification`, neither imported anywhere in `assets/`, so both are dropped); `vue-router` keeps its own `^4.3`. `element-plus` and `unocss`, absent from the original file entirely, are added at the root `package.json`'s versions.

- [ ] **Step 2: Delete the per-extension config files**

```bash
git rm src/FastyBird/Module/Triggers/vite.config.ts \
       src/FastyBird/Module/Triggers/tsconfig.json \
       src/FastyBird/Module/Triggers/.prettierrc \
       src/FastyBird/Module/Triggers/.stylelintrc.json \
       src/FastyBird/Module/Triggers/.browserslistrc \
       src/FastyBird/Module/Triggers/jest.config.js \
       src/FastyBird/Module/Triggers/commitlint.config.js
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Module/Triggers/package.json'); console.log(p.version, p.exports['.'], 'dependencies' in p)"
find src/FastyBird/Module/Triggers -maxdepth 1 -name "vite.config.ts" -o -maxdepth 1 -name "tsconfig.json"
```

Expected: `0.0.0 ./assets/entry.ts false`, and `find` prints nothing.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Module/Triggers
git commit -m "module(triggers): convert package.json to a source-only yarn workspace member"
```

---

### Task 11: Rewrite `Module/Ui/package.json` and delete its per-extension config

**Files:**
- Modify: `src/FastyBird/Module/Ui/package.json`
- Delete: `src/FastyBird/Module/Ui/vite.config.ts`
- Delete: `src/FastyBird/Module/Ui/tsconfig.json`
- Delete: `src/FastyBird/Module/Ui/eslint.config.mjs`
- Delete: `src/FastyBird/Module/Ui/.prettierrc`
- Delete: `src/FastyBird/Module/Ui/.stylelintrc.json`
- Delete: `src/FastyBird/Module/Ui/.browserslistrc`
- Delete: `src/FastyBird/Module/Ui/jest.config.js`
- Delete: `src/FastyBird/Module/Ui/commitlint.config.js`

**Interfaces:**
- Consumes: `src/FastyBird/Module/Ui/assets/entry.ts` (confirmed present, `export default function createDevicesModule()`); Task 7's `@fastybird/metadata-library` re-versioning.
- Produces: `@fastybird/ui-module` resolving to source, not registered in `config/extensions.ts` (out of scope, per spec 8).

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/ui-module",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT module for managing visual components",
  "keywords": [
    "fastybird",
    "fb",
    "api",
    "php",
    "iot",
    "vuejs",
    "typescript",
    "vue",
    "connector",
    "nette",
    "controls",
    "ui",
    "interface",
    "vue3",
    "pinia"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "sideEffects": true,
  "dependencies": {
    "@fastybird/metadata-library": "0.0.0",
    "@fastybird/vue-wamp-v1": "^1.2",
    "@fastybird/web-ui-icons": "1.0.0-dev.24",
    "@fastybird/web-ui-library": "1.0.0-dev.24",
    "@vueuse/core": "^11.2",
    "ajv": "^8.12",
    "ajv-formats": "^3.0",
    "axios": "^1.6",
    "idb": "^8.0",
    "jsona": "^1.12",
    "lodash.defaultsdeep": "^4.6",
    "lodash.get": "^4.4",
    "lodash.isequal": "^4.5",
    "uuid": "^9.0"
  },
  "peerDependencies": {
    "element-plus": "^2.7",
    "pinia": "^2.1",
    "unocss": "^0.64",
    "vue": "^3.4",
    "vue-i18n": "^9.13",
    "vue-meta": "^3.0.0-alpha.10",
    "vue-router": "^4.3"
  }
}
```

`assets/composables/useBreakpoints.ts` imports `@vueuse/core`, which the original `dependencies` list never declared; it is added here at `^11.2`, the version `src/FastyBird/Core/Tools/package.json` (Task 6) already pins for the same package elsewhere in the workspace. `@sentry/vue`, `date-fns`, `jwt-decode`, `lodash.capitalize`, `lodash.has`, `mitt`, `natural-orderby` and `vue3-cookies` were declared in the original `dependencies` but are not imported anywhere under `assets/` (verified by `grep -rn` for each), so all eight are dropped. `pinia`, `vue`, `vue-i18n`, `vue-meta` and `vue-router` move in from this file's own original `peerDependencies` at their existing versions; `element-plus` (`^2.7`) moves in from `dependencies`; `unocss`, absent from the original file, is added at the root `package.json`'s `^0.64`.

- [ ] **Step 2: Delete the per-extension config files**

```bash
git rm src/FastyBird/Module/Ui/vite.config.ts \
       src/FastyBird/Module/Ui/tsconfig.json \
       src/FastyBird/Module/Ui/eslint.config.mjs \
       src/FastyBird/Module/Ui/.prettierrc \
       src/FastyBird/Module/Ui/.stylelintrc.json \
       src/FastyBird/Module/Ui/.browserslistrc \
       src/FastyBird/Module/Ui/jest.config.js \
       src/FastyBird/Module/Ui/commitlint.config.js
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Module/Ui/package.json'); console.log(p.version, p.exports['.'], '@vueuse/core' in p.dependencies, '@sentry/vue' in p.dependencies)"
find src/FastyBird/Module/Ui -maxdepth 1 -name "vite.config.ts" -o -maxdepth 1 -name "tsconfig.json"
```

Expected: `0.0.0 ./assets/entry.ts true false`, and `find` prints nothing.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Module/Ui
git commit -m "module(ui): convert package.json to a source-only yarn workspace member"
```

---

### Task 12: Trim `Core/Application/package.json` and delete its remaining per-extension config

**Files:**
- Modify: `src/FastyBird/Core/Application/package.json`
- Delete: `src/FastyBird/Core/Application/.browserslistrc`
- Delete: `src/FastyBird/Core/Application/.stylelintrc.json`
- Delete: `src/FastyBird/Core/Application/jest.config.mjs`
- Delete: `src/FastyBird/Core/Application/commitlint.config.mjs`

**Interfaces:**
- Consumes: Task 2's root `package.json`, which now carries this file's former `dependencies`/`devDependencies` as its own superset.
- Produces: `Core/Application` as a valid, near-empty yarn workspace member (needed only so the `src/FastyBird/*/*` glob in Task 2's `workspaces` array resolves to a real package), consumed by Task 16's `yarn install`.

Unlike the other seven, `Core/Application` is not consumed by any other package: `grep -rn "@fastybird/application"` across `src/FastyBird` (excluding this file itself) returns no import, and `var/config/extensions.ts` / `config/extensions.ts` never lists it. It therefore gets no `exports`, `types`, `dependencies` or `peerDependencies` — those all now live in the root `package.json` (Task 2) or are consumed directly by path from `vite.config.ts`/`index.html` (Task 1), not through an `@fastybird/application` import.

- [ ] **Step 1: Replace `package.json`**

```json
{
  "name": "@fastybird/application",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird IoT application user interface",
  "keywords": [
    "fastybird",
    "fb",
    "ui",
    "interface",
    "vue"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  }
}
```

- [ ] **Step 2: Delete the remaining per-extension config files**

```bash
git rm src/FastyBird/Core/Application/.browserslistrc \
       src/FastyBird/Core/Application/.stylelintrc.json \
       src/FastyBird/Core/Application/jest.config.mjs \
       src/FastyBird/Core/Application/commitlint.config.mjs
```

- [ ] **Step 3: Verify**

Run:

```bash
node -e "const p=require('./src/FastyBird/Core/Application/package.json'); console.log(p.version, 'exports' in p, 'dependencies' in p, 'scripts' in p)"
find src/FastyBird/Core/Application -maxdepth 1 \( -name "vite.config.ts" -o -name "tsconfig.json" -o -name "index.html" -o -name "uno.config.ts" -o -name "eslint.config.mjs" -o -name "prettier.config.mjs" -o -name ".browserslistrc" -o -name ".stylelintrc.json" -o -name "jest.config.mjs" -o -name "commitlint.config.mjs" \)
```

Expected: `0.0.0 false false false`, and the `find` prints nothing (every one of the ten files was already moved in Task 1 or deleted here).

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Application/package.json src/FastyBird/Core/Application/.browserslistrc src/FastyBird/Core/Application/.stylelintrc.json src/FastyBird/Core/Application/jest.config.mjs src/FastyBird/Core/Application/commitlint.config.mjs
git commit -m "core(application): trim package.json now that build config lives at the repository root"
```

---

### Task 13: Fix `assets/main.ts` — `@config` alias and `__APP_VERSION__`

**Files:**
- Modify: `src/FastyBird/Core/Application/assets/main.ts`
- Create: `src/FastyBird/Core/Application/assets/vite-env.d.ts`

**Interfaces:**
- Consumes: Task 1's `@config` alias and `__APP_VERSION__`/`__APP_DESCRIPTION__` `define` block in `vite.config.ts`.
- Produces: `assets/vite-env.d.ts`'s ambient `__APP_VERSION__`/`__APP_DESCRIPTION__` declarations, consumed by Task 14's `App.vue` edit and by Task 16's `yarn types` check.

- [ ] **Step 1: Replace the `var/config/extensions` import with the `@config` alias and drop the `package.json` import**

Edit `src/FastyBird/Core/Application/assets/main.ts`, replacing:

```ts
import { extensions } from '../../../../../var/config/extensions';

import { version } from './../package.json';
import App from './App.vue';
```

with:

```ts
import { extensions } from '@config/extensions';

import App from './App.vue';
```

- [ ] **Step 2: Replace the `version` reference with `__APP_VERSION__`**

Replace:

```ts
		meta: {
			author: 'FastyBird s.r.o.',
			website: 'https://www.fastybird.com',
			version: version,
		},
```

with:

```ts
		meta: {
			author: 'FastyBird s.r.o.',
			website: 'https://www.fastybird.com',
			version: __APP_VERSION__,
		},
```

- [ ] **Step 3: Create the ambient type declaration**

```ts
/// <reference types="vite/client" />

declare const __APP_VERSION__: string;
declare const __APP_DESCRIPTION__: string;
```

- [ ] **Step 4: Verify**

Run:

```bash
grep -n "var/config\|package.json" src/FastyBird/Core/Application/assets/main.ts
grep -n "@config/extensions\|__APP_VERSION__" src/FastyBird/Core/Application/assets/main.ts
```

Expected: the first command prints nothing (no relative `var/config` path and no `package.json` import remain); the second prints the two matching lines.

- [ ] **Step 5: Commit**

```bash
git add src/FastyBird/Core/Application/assets/main.ts src/FastyBird/Core/Application/assets/vite-env.d.ts
git commit -m "core(application): resolve the extension registry through the @config alias"
```

---

### Task 14: Fix `assets/App.vue` — `__APP_DESCRIPTION__`

**Files:**
- Modify: `src/FastyBird/Core/Application/assets/App.vue`

**Interfaces:**
- Consumes: Task 13's `assets/vite-env.d.ts` ambient `__APP_DESCRIPTION__` declaration.
- Produces: nothing further consumed within this phase; this is the last of the two `package.json`-reading call sites named in the spec (4.4).

- [ ] **Step 1: Drop the `package.json` import**

Edit `src/FastyBird/Core/Application/assets/App.vue`, replacing:

```ts
import { description } from './../package.json';
import Logo from './assets/images/fb_row.svg?component';
```

with:

```ts
import Logo from './assets/images/fb_row.svg?component';
```

- [ ] **Step 2: Use the global constant in the meta content**

Replace:

```ts
		{
			hid: 'description',
			name: 'description',
			content: description ?? '',
		},
```

with:

```ts
		{
			hid: 'description',
			name: 'description',
			content: __APP_DESCRIPTION__ ?? '',
		},
```

- [ ] **Step 3: Verify**

Run:

```bash
grep -n "package.json\|__APP_DESCRIPTION__" src/FastyBird/Core/Application/assets/App.vue
```

Expected: one line containing `__APP_DESCRIPTION__` and no line containing `package.json`.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Application/assets/App.vue
git commit -m "core(application): read the page description from __APP_DESCRIPTION__"
```

---

### Task 15: Reinstall and confirm the workspace graph resolves

**Files:**
- Modify: `yarn.lock` (regenerated by `yarn install`)

**Interfaces:**
- Consumes: every `package.json` change from Tasks 2–12.
- Produces: an installed `node_modules` tree with the eight rewritten packages symlinked, consumed by Task 16's build/dev/lint/type checks.

- [ ] **Step 1: Reinstall**

```bash
yarn install
```

Expected: exits `0`. Yarn 1 may print `warning ... has unmet peer dependency` lines for the `peerDependencies` entries Tasks 5–11 added or moved — those are warnings, not failures, and are expected because yarn 1 (unlike npm 7+) never auto-installs peers; the root `package.json` (Task 2) already carries the same packages as real `dependencies`, which is what actually satisfies them at runtime.

- [ ] **Step 2: Confirm the eight rewritten packages resolve to their own source directory through the workspace symlink**

```bash
for pkg in @fastybird/homekit-connector @fastybird/tools @fastybird/metadata-library @fastybird/accounts-module @fastybird/devices-module @fastybird/triggers-module @fastybird/ui-module @fastybird/application; do
  target=$(readlink "node_modules/$pkg")
  echo "$pkg -> $target"
done
```

Expected: eight lines, each resolving into the matching `src/FastyBird/...` directory (for example `@fastybird/homekit-connector -> ../../../src/FastyBird/Connector/HomeKit`).

- [ ] **Step 3: Commit the refreshed lockfile**

```bash
git add yarn.lock
git commit -m "infra(deps): refresh yarn.lock for the source-only workspace layout"
```

---

### Task 16: End-to-end verification — build, dev, types, lint, format

**Files:**
- Modify: none (verification only; commits only if a check below writes a file).

**Interfaces:**
- Consumes: every task in this plan.
- Produces: the phase's Deliverable, matching spec section 5, Phase 5: `yarn build` producing `public/index.html` and hashed assets, `yarn dev` serving extension sources with hot reload, `yarn types`/`yarn lint:js`/`yarn lint:styles`/`yarn pretty:check` passing, and no per-extension Vite or tsconfig files remaining.

- [ ] **Step 1: Build the `Library/WebUi` packages in dependency order, then the app**

```bash
yarn build
```

Expected: exits `0`. Internally this runs `yarn build:ui` (the five `yarn workspace ... build` commands in the order `utils`, `icons`, `theme-chalk`, `components`, `web-ui-library`), then `vue-tsc --noEmit`, then `vite build`.

- [ ] **Step 2: Confirm the build output**

```bash
test -f public/index.html && echo HTML_OK
find public -maxdepth 1 -type d
ls public/assets 2>/dev/null | grep -c '\.js$'
```

Expected: `HTML_OK`; a `public/assets` directory; a non-zero count of hashed `.js` files.

- [ ] **Step 3: Confirm `manifest.json` was written**

```bash
find public -name "manifest.json"
```

Expected: one path is printed (Vite 5 writes it to `public/.vite/manifest.json` by default since `build.manifest: true` is unchanged from the original `Core/Application/vite.config.ts`).

- [ ] **Step 4: Smoke-test `yarn dev`, confirm hot reload serves extension source directly**

```bash
yarn dev &
DEV_PID=$!
sleep 3
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3000/
curl -s http://localhost:3000/src/FastyBird/Module/Devices/assets/entry.ts | head -c 200
kill $DEV_PID
```

Expected: the first `curl` prints `200`; the second `curl` prints raw TypeScript source starting with the real content of `src/FastyBird/Module/Devices/assets/entry.ts` (proving Vite is serving the extension's `assets/` directly, not a pre-built bundle, since that package no longer has a `dist/`).

- [ ] **Step 5: Type-check the whole workspace**

```bash
yarn types
```

Expected: exits `0` with no reported errors.

- [ ] **Step 6: Lint JS/TS across every extension's `assets/`**

```bash
yarn lint:js
```

Expected: exits `0`.

- [ ] **Step 7: Lint SCSS across every extension's `assets/`**

```bash
yarn lint:styles
```

Expected: exits `0`.

- [ ] **Step 8: Check formatting**

```bash
yarn pretty:check
```

Expected: exits `0`. If it reports unformatted files (for example because a hand-written JSON block in Tasks 5–12 used different key ordering or spacing than Prettier would produce), run `yarn pretty:write` once, review the diff is formatting-only with `git diff --stat`, and commit it in this same task.

- [ ] **Step 9: Prove no per-extension Vite or TypeScript config remains**

```bash
find src/FastyBird -mindepth 3 -maxdepth 3 \( -name "vite.config.ts" -o -name "tsconfig.json" \)
```

Expected: no output. (`Library/WebUi`'s five packages keep their own `vite.config.ts`/`tsconfig.json` at `src/FastyBird/Library/WebUi/packages/*/vite.config.ts`, four levels deep, which this `-maxdepth 3` search correctly does not reach.)

- [ ] **Step 10: Confirm the working tree is clean**

```bash
git status --short
```

Expected: no output (everything from Steps 1–9 was either already committed in Tasks 1–15 or, if Step 8 ran `pretty:write`, committed just now).

- [ ] **Step 11: Commit any formatting fixes from Step 8, if not already committed**

```bash
git add -u
git commit -m "cross(ui): apply prettier formatting to the rewritten extension manifests" --allow-empty
```

If Step 8 required no `pretty:write` pass, this command's `--allow-empty` makes it a harmless no-op marker commit; otherwise it captures the formatting fix.
