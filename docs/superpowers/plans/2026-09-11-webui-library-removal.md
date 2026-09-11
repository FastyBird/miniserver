# WebUi Library Removal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Delete `src/FastyBird/Library/WebUi` (4,316 files, 52,810 lines) by consuming element-plus directly, porting the genuinely custom components into `Core/Application`, and replacing the generated Font Awesome package with `@iconify/vue`.

**Architecture:** The eight consuming extensions currently import 16 named exports from `@fastybird/web-ui-library` and 73 icons from `@fastybird/web-ui-icons`. Components with an element-plus equivalent are replaced at the call site; the rest move into `Core/Application/assets/components` using the flat `<name>.vue` + `.types.ts` + `.scss` pattern that directory already uses. `Core/Application` gains an `exports` entry so other extensions can import from it, which follows the documented layering (`docs/architecture.md:18`: "Modules depend on `application`, ...").

**Tech Stack:** Vue 3 `<script setup>`, TypeScript, element-plus 2.8, `@iconify/vue` + `@iconify/json`, SCSS via element-plus's `theme-chalk` mixins, Vite 5, yarn 1 workspaces, Node 20.

**Spec:** `docs/superpowers/specs/2026-09-11-webui-library-removal-design.md`

## Global Constraints

- **Node 20, yarn 1.** Every `yarn` command runs in the Node 20 container. Host results do not count. See `docs/baseline.md`.
- **`yarn install` always needs `--ignore-engines`** (transitive `@intlify/shared` wants Node >= 22).
- **No PHP changes.** This plan touches no `.php` file, no `.neon`, no `composer.json`.
- **There is no frontend test suite and this plan does not add one.** There is no vitest, no `@vue/test-utils`, no test script and zero `.spec.ts` files under any `assets/`. Classic TDD is therefore not available. Each task's test cycle is instead: the five real gates (`yarn build`, `yarn types`, `yarn lint:js`, `yarn lint:styles`, `yarn pretty:check`), plus a targeted `grep` assertion proving the old symbol is gone. Adding vitest is explicitly out of scope; see "Deferred" at the end.
- **Namespace.** These components call element-plus's public `useNamespace('x')`, which emits `el-x` classes with element-plus's default `el` prefix. The `fb-` in the stylesheet filenames is a filename convention only; no class is `fb-`-prefixed. Keep `useNamespace` — it is public API and needs no fork.
- **Brand colours** live in `packages/theme-chalk/src/index.scss` (light primary `#d9230f`) and `dark.scss` (dark primary `#a91b0c`). Both must survive Task 10.
- **Conventional commits**, scope from `commitlint.config.cjs`. `ui`, `library`, `core`, `module`, `ci`, `deps`, `deps-dev`, `cross` are the scopes used here.
- **`yarn build` dirties a tracked generated file** (`packages/icons/src/components/index.ts`) until Task 11 deletes it. `git checkout --` that path; never commit it.
- Do **not** run a long container attached; results only count from a completed run.

## File Structure

**Created**

| File | Responsibility |
|---|---|
| `src/FastyBird/Core/Application/assets/entry.ts` | Public surface of `@fastybird/application` for other extensions: re-exports the ported components and their types. Must not import `main.ts`. |
| `Core/Application/assets/components/app-icon-with-child.{vue,types.ts,scss}` | Icon with a secondary badge icon |
| `Core/Application/assets/components/app-bar.{vue,types.ts,scss}` | Application bar shell |
| `Core/Application/assets/components/app-bar-button.{vue,types.ts,scss}` | Bar button wrapping `el-button` |
| `Core/Application/assets/components/app-bar-heading.{vue,types.ts,scss}` | Bar heading, teleportable |
| `Core/Application/assets/components/app-bar-content.{vue,types.ts}` | Bar content slot host |
| `Core/Application/assets/components/app-bar-icon.{vue,types.ts,scss}` | Bar icon |
| `Core/Application/assets/components/app-list.{vue,scss}` | List container |
| `Core/Application/assets/components/app-list-item.{vue,types.ts}` | List row |
| `Core/Application/assets/styles/element-plus.scss` | Brand variable overrides, light |
| `Core/Application/assets/styles/element-plus-dark.scss` | Brand variable overrides, dark |
| `Core/Application/assets/styles/variables.scss` | The custom SCSS maps the ported components need (`$list`, `$media-item`) |

**Modified**

| File | Change |
|---|---|
| `Core/Application/package.json` | Add `exports` (Task 1) |
| `Core/Application/assets/main.ts:13` | Theme import moves from the deleted package to the local stylesheet |
| `Core/Application/assets/components/index.ts` | Export the ported components |
| `Module/Devices`, `Module/Accounts`, `Module/Ui`, `Module/Triggers`, `Connector/HomeKit`, `Core/Tools` manifests | Drop `@fastybird/web-ui-*`, add `@fastybird/application` where imported |
| ~55 `.vue`/`.ts` call sites | Rewrite imports; replace substituted components |
| root `package.json` | Drop the six workspace entries' deps, add `@iconify/vue` + `@iconify/json` |
| every manifest whose `assets/` imports `@iconify/vue` | Declare it rather than relying on hoisting (the Track C manifest-honesty rule) |
| `.github/workflows/ci-tests.yaml` | Remove the now-dead gate steps and the `Docs Build` job |
| `docs/superpowers/plans/2026-09-11-phase-6-modernization.md` | Retarget Tasks 8, 11, 36, 39 and Tracks C/E |

**Deleted**

`src/FastyBird/Library/WebUi/` in its entirety — `packages/{components,utils,icons,theme-chalk}`, `web-ui-library`, `docs`, and the four package-root files.

---

## Task 1: Make `Core/Application` importable by other extensions

`Core/Application` is named `@fastybird/application` but has **no `exports` field**, unlike every other UI extension (`Core/Tools`, `Module/Devices`, `Library/Metadata` all use `{".": "./assets/entry.ts"}`). Nothing can import from it today. Every later task depends on this.

**Files:**
- Create: `src/FastyBird/Core/Application/assets/entry.ts`
- Modify: `src/FastyBird/Core/Application/package.json`

**Interfaces:**
- Consumes: nothing.
- Produces: the import specifier `@fastybird/application`, resolving to `assets/entry.ts`. Tasks 3, 4 and 5 add their components to this file; Tasks 6 through 9 rely on consumers being able to resolve it.

- [ ] **Step 1: Create the entry point**

`assets/entry.ts` must not import `main.ts` — that is the application bootstrap and importing it from a library entry would run `createApp` in every consumer.

```ts
// Public surface of @fastybird/application for other extensions.
// Deliberately does NOT re-export main.ts: that is the application bootstrap.
export * from './components';
```

- [ ] **Step 2: Add the exports field**

In `src/FastyBird/Core/Application/package.json`, add a sibling to `"name"`, matching the shape `Core/Tools` already uses:

```json
	"exports": {
		".": "./assets/entry.ts"
	},
```

- [ ] **Step 3: Confirm the existing components still export cleanly**

`assets/components/index.ts` already exists and exports `app-gravatar`, `app-navigation`, `app-sidebar`, `app-topbar`. Read it and confirm it is a plain re-export barrel; if it exports anything that pulls in router or store singletons, note it in the task report rather than changing it.

- [ ] **Step 4: Verify resolution**

Run in the Node 20 container:

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --frozen-lockfile --ignore-engines && yarn types'
```

Expected: exit 0. `yarn types` proves the new `exports` entry resolves and type-checks.

- [ ] **Step 5: Commit**

```bash
git add src/FastyBird/Core/Application/assets/entry.ts src/FastyBird/Core/Application/package.json
git commit -m "feat(core): expose @fastybird/application for cross-extension imports"
```

---

## Task 2: Replace the icon package with `@iconify/vue`

`packages/icons` is 4,086 files and 41,148 lines — 78% of the library — generating 2,040 Vue components from Font Awesome SVGs. The application uses **73**.

All 73 were resolved against the Iconify API before this plan was written: 61 `fa6-solid`, 8 `fa6-regular`, 4 `fa6-brands`, **zero not found**. (An earlier count of 46 came from a single-line regex over import statements; 7 of the 52 import statements are prettier-wrapped across multiple lines, so 27 icons were missed.) No Pro icon, so no local SVG fallback is needed.

**Files:**
- Modify: root `package.json`, `Core/Application/package.json`, and every manifest declaring `@fastybird/web-ui-icons`
- Modify: ~52 call sites across `Module/*`, `Core/*`, `Connector/HomeKit`

**Interfaces:**
- Consumes: nothing.
- Produces: the `<Icon icon="fa6-solid:..." />` usage pattern. No later task depends on this; it is independent of the component work and may be done in parallel.

- [ ] **Step 1: Add the dependencies**

To root `package.json` `devDependencies` (matching smart-panel's versions):

```json
    "@iconify/json": "^2.2.449",
```

and to `dependencies`:

```json
    "@iconify/vue": "^5",
```

- [ ] **Step 2: Rewrite one call site by hand first, and render it**

Do **one** file before the bulk rewrite, to confirm sizing and colour behave. The old component took `size` via element-plus's `el-icon` wrapper; `@iconify/vue`'s `Icon` takes `width`/`height` or inherits `font-size`.

Before:

```vue
import { FasGaugeHigh } from '@fastybird/web-ui-icons';
...
<el-icon><fas-gauge-high /></el-icon>
```

After:

```vue
import { Icon } from '@iconify/vue';
...
<el-icon><Icon icon="fa6-solid:gauge-high" /></el-icon>
```

Keeping the surrounding `el-icon` preserves element-plus's sizing and colour inheritance, so the visual result stays closest to today.

- [ ] **Step 3: Apply the mapping to every remaining call site**

The complete mapping, verified against the Iconify API:

| Old component | Iconify identifier |
|---|---|
| `FabFacebook` | `fa6-brands:facebook` |
| `FabGithub` | `fa6-brands:github` |
| `FabXTwitter` | `fa6-brands:x-twitter` |
| `FarCircleCheck` | `fa6-regular:circle-check` |
| `FarCircleXmark` | `fa6-regular:circle-xmark` |
| `FarFaceSmile` | `fa6-regular:face-smile` |
| `FasAngleLeft` | `fa6-solid:angle-left` |
| `FasArrowsRotate` | `fa6-solid:arrows-rotate` |
| `FasBan` | `fa6-solid:ban` |
| `FasBars` | `fa6-solid:bars` |
| `FasBell` | `fa6-solid:bell` |
| `FasBox` | `fa6-solid:box` |
| `FasCheck` | `fa6-solid:check` |
| `FasCircleInfo` | `fa6-solid:circle-info` |
| `FasEllipsisVertical` | `fa6-solid:ellipsis-vertical` |
| `FasEnvelope` | `fa6-solid:envelope` |
| `FasEthernet` | `fa6-solid:ethernet` |
| `FasExclamation` | `fa6-solid:exclamation` |
| `FasFilter` | `fa6-solid:filter` |
| `FasFilterCircleXmark` | `fa6-solid:filter-circle-xmark` |
| `FasGaugeHigh` | `fa6-solid:gauge-high` |
| `FasGear` | `fa6-solid:gear` |
| `FasGears` | `fa6-solid:gears` |
| `FasHeart` | `fa6-solid:heart` |
| `FasInfo` | `fa6-solid:info` |
| `FasKey` | `fa6-solid:key` |
| `FasLayerGroup` | `fa6-solid:layer-group` |
| `FasLightbulb` | `fa6-solid:lightbulb` |
| `FasLock` | `fa6-solid:lock` |
| `FasMagnifyingGlass` | `fa6-solid:magnifying-glass` |
| `FasMoon` | `fa6-solid:moon` |
| `FasPenToSquare` | `fa6-solid:pen-to-square` |
| `FasPencil` | `fa6-solid:pencil` |
| `FasPlay` | `fa6-solid:play` |
| `FasPlug` | `fa6-solid:plug` |
| `FasPlugCircleBolt` | `fa6-solid:plug-circle-bolt` |
| `FasPlus` | `fa6-solid:plus` |
| `FasRightFromBracket` | `fa6-solid:right-from-bracket` |
| `FasSliders` | `fa6-solid:sliders` |
| `FasStop` | `fa6-solid:stop` |
| `FasStore` | `fa6-solid:store` |
| `FasSun` | `fa6-solid:sun` |
| `FasTrash` | `fa6-solid:trash` |
| `FasUser` | `fa6-solid:user` |
| `FasWandMagicSparkles` | `fa6-solid:wand-magic-sparkles` |
| `FasXmark` | `fa6-solid:xmark` |

- [ ] **Step 4: Prove no icon import survives**

```bash
grep -rn "@fastybird/web-ui-icons" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: no output. (`Library/WebUi` itself is excluded; Task 11 deletes it.)

- [ ] **Step 5: Run the gates**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn pretty:check'
```

Expected: all exit 0. Then `git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts` if the build dirtied it.

- [ ] **Step 6: Commit**

```bash
git add -u
git add package.json yarn.lock
git commit -m "refactor(ui): replace the generated icon package with @iconify/vue"
```

---

## Task 3: Port `FbIconWithChild`

The most-used export (13 sites) and the smallest, so it establishes the transformation every later port follows.

**Files:**
- Create: `Core/Application/assets/components/app-icon-with-child.vue`, `.types.ts`, `.scss`
- Modify: `Core/Application/assets/components/index.ts`
- Modify: the 13 call sites

**Interfaces:**
- Consumes: Task 1's `@fastybird/application` entry.
- Produces: `AppIconWithChild` (component) and `AppIconWithChildProps` (type), exported from `@fastybird/application`.

**The transformation, stated once and reused by Tasks 4 and 5.** Six files collapse to three:

| Old | New |
|---|---|
| `index.ts` (`withInstall`) | gone — components are imported directly, not installed as a plugin |
| `src/<name>.ts` (`buildProps`) | `<name>.types.ts` |
| `src/<name>.vue` | `<name>.vue` (template and style bodies move verbatim) |
| `src/instance.ts` | gone — `InstanceType<typeof X>` at the use site if ever needed |
| `style/index.ts`, `style/css.ts` | gone — the `.scss` is imported by the `.vue` |
| `theme-chalk/src/fb-<name>.scss` | `<name>.scss`, co-located |

- [ ] **Step 1: Write the types file**

`app-icon-with-child.types.ts`:

```ts
export type AppIconWithChildType = 'primary' | 'default' | 'info' | 'success' | 'waring' | 'danger';

export interface AppIconWithChildProps {
	/** child icon variant */
	type?: AppIconWithChildType;
	/** main icon size */
	size?: number | string;
	/** main icon color */
	color?: string;
}
```

Note `'waring'` is a typo in the original enum. Preserve it: the `.scss` iterates `(primary, default, info, success, warning, danger)` so `waring` never had a matching style, but changing the accepted value is a behaviour change and belongs in the UI polish work, not here. Record it in the task report.

- [ ] **Step 2: Write the component**

`app-icon-with-child.vue`. The template is the original verbatim; only the script block changes.

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { ElIcon, useNamespace } from 'element-plus';

import type { AppIconWithChildProps } from './app-icon-with-child.types';

import './app-icon-with-child.scss';

defineOptions({
	name: 'AppIconWithChild',
});

const props = withDefaults(defineProps<AppIconWithChildProps>(), {
	type: 'primary',
	size: 20,
	color: undefined,
});

const ns = useNamespace('icon-with-child');

const childSize = computed<number>((): number => {
	if (Number.isInteger(props.size)) {
		const size = (props.size as number) * 0.3;

		return size > 20 ? 20 : size;
	}

	return 20;
});
</script>
```

- [ ] **Step 3: Move the stylesheet**

Copy `packages/theme-chalk/src/fb-icon-with-child.scss` to `Core/Application/assets/components/app-icon-with-child.scss` **unchanged**. Its two `@use` lines already point at element-plus and keep working:

```scss
@use 'element-plus/theme-chalk/src/common/var' as *;
@use 'element-plus/theme-chalk/src/mixins/mixins' as *;
```

`@include b(icon-with-child)` emits `.el-icon-with-child`, matching `useNamespace('icon-with-child')`. Do not rename either.

- [ ] **Step 4: Export it**

Append to `Core/Application/assets/components/index.ts`:

```ts
export { default as AppIconWithChild } from './app-icon-with-child.vue';
export * from './app-icon-with-child.types';
```

- [ ] **Step 5: Rewrite the 13 call sites**

```
- import { FbIconWithChild } from '@fastybird/web-ui-library';
+ import { AppIconWithChild } from '@fastybird/application';
```

and `<fb-icon-with-child>` becomes `<app-icon-with-child>` in templates. Add `"@fastybird/application": "0.0.0"` to each consuming extension's `dependencies` (the internal version convention used by the other workspace packages).

- [ ] **Step 6: Gates**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
grep -rn "FbIconWithChild" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: gates exit 0; grep returns nothing.

- [ ] **Step 7: Commit**

```bash
git add -u && git add src/FastyBird/Core/Application/assets/components
git commit -m "refactor(ui): port FbIconWithChild to Core/Application"
```

---

## Task 4: Port the app-bar family

Five components (`app-bar`, `button`, `heading`, `content`, `icon`), 32 external usages. Follow the Task 3 transformation table.

**Files:**
- Create: `app-bar.{vue,types.ts,scss}`, `app-bar-button.{vue,types.ts,scss}`, `app-bar-heading.{vue,types.ts,scss}`, `app-bar-content.{vue,types.ts}`, `app-bar-icon.{vue,types.ts,scss}` in `Core/Application/assets/components`
- Modify: `index.ts`, and the call sites

**Interfaces:**
- Consumes: Task 1's entry; the Task 3 transformation.
- Produces: `AppBar`, `AppBarButton`, `AppBarHeading`, `AppBarContent`, `AppBarIcon`, plus `AppBarButtonAlignTypes` and `AppBarHeadingAlignTypes` enums, exported from `@fastybird/application`.

**The rename map for this task.** Every old symbol and its replacement, so no call site is left guessing:

| Old export | Old tag | New export | New tag |
|---|---|---|---|
| `FbAppBar` | `<fb-app-bar>` | `AppBar` | `<app-bar>` |
| `FbAppBarButton` | `<fb-app-bar-button>` | `AppBarButton` | `<app-bar-button>` |
| `FbAppBarHeading` | `<fb-app-bar-heading>` | `AppBarHeading` | `<app-bar-heading>` |
| `FbAppBarContent` | `<fb-app-bar-content>` | `AppBarContent` | `<app-bar-content>` |
| `FbAppBarIcon` | `<fb-app-bar-icon>` | `AppBarIcon` | `<app-bar-icon>` |
| `AppBarButtonAlignTypes` | — | `AppBarButtonAlignTypes` | — (unchanged) |
| `AppBarHeadingAlignTypes` | — | `AppBarHeadingAlignTypes` | — (unchanged) |

- [ ] **Step 1: `app-bar.types.ts`**

```ts
export interface AppBarProps {
	menuButtonHidden?: boolean;
	menuCollapsed?: boolean;
}

export interface AppBarEmits {
	(e: 'toggleMenu', evt: UIEvent): void;
}
```

The original used a runtime emits validator (`toggleMenu: (evt) => evt instanceof UIEvent`). The type-only form above is the `<script setup>` equivalent and drops a dev-time assertion that never fired in production.

- [ ] **Step 2: `app-bar-heading.types.ts`**

Keep both enums — they are part of the consumed surface (`AppBarButtonAlignTypes` has 9 usages).

```ts
import type { Component } from 'vue';

export enum AppBarHeadingAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	CENTER = 'center',
}

export type AppBarHeadingAlign = `${AppBarHeadingAlignTypes}`;

export interface AppBarHeadingProps {
	align?: AppBarHeadingAlign;
	/** icon of the heading; can also be passed with a named slot */
	icon?: string | Component;
	teleport?: boolean;
}
```

- [ ] **Step 3: `app-bar-button.types.ts` — the one exception to the `defineProps<Props>()` rule**

`appBarButtonProps` spreads element-plus's **public** `buttonProps`, and a type-only `defineProps<Props>()` cannot spread a runtime props object. This component therefore keeps runtime props, with `buildProps`/`definePropType` (the fork) replaced by plain Vue constructs. `definePropType<T>(C)` is exactly `C as PropType<T>`, and `buildProps`'s `values` key only drives a dev-time validator, so dropping it changes no production behaviour.

```ts
import { buttonEmits, buttonProps } from 'element-plus';

import type { PropType } from 'vue';

export enum AppBarButtonAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	BACK = 'back',
	NONE = 'none',
}

export type AppBarButtonAlign = `${AppBarButtonAlignTypes}`;

export const appBarButtonProps = {
	...buttonProps,
	align: { type: String as PropType<AppBarButtonAlign>, default: AppBarButtonAlignTypes.NONE },
	small: { type: Boolean, default: false },
	teleport: { type: Boolean, default: false },
	disabled: { type: Boolean, default: false },
	classes: { type: Array as PropType<string[]>, default: (): string[] => [] },
};

export const appBarButtonEmits = { ...buttonEmits };
```

`classes` must become a factory (`default: () => []`); an array literal as a Vue prop default is shared across instances. The original had this bug via `buildProps`; fix it here and note it in the report.

- [ ] **Step 4: Port the five `.vue` files**

For each, the template moves verbatim. The script block changes exactly as in Task 3 Step 2: drop the `@fastybird/web-ui-utils` import, rename via `defineOptions({ name: 'AppBar...' })`, and use `withDefaults(defineProps<...>(), {...})` — except `app-bar-button.vue`, which keeps `defineProps(appBarButtonProps)` and `defineEmits(appBarButtonEmits)` from Step 3.

- [ ] **Step 5: Move the four stylesheets**

`fb-app-bar.scss` carries all the app-bar styling including `@include css-var-from-global(('app-bar', 'heading', 'bg-color'), ('color', 'primary'))`, which is what binds the bar to the brand colour. Copy it unchanged to `app-bar.scss`. `fb-breadcrumb-item.scss` belongs to Task 8, not here.

- [ ] **Step 6: Export, rewrite call sites, gates**

Export all five plus both enums from `index.ts`. Rewrite the 32 usages from `@fastybird/web-ui-library` to `@fastybird/application`, renaming `Fb*` to `App*` and `<fb-app-bar*>` to `<app-bar*>`.

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
grep -rnE "FbAppBar|AppBarButtonAlignTypes.*web-ui" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: gates exit 0; grep returns nothing.

- [ ] **Step 7: Commit**

```bash
git add -u && git add src/FastyBird/Core/Application/assets/components
git commit -m "refactor(ui): port the app bar family to Core/Application"
```

---

## Task 5: Port `FbList` and `FbListItem`

19 usages across the two components and `ListItemVariantTypes`. element-plus has no List component, so these are genuine ports.

**Files:**
- Create: `app-list.{vue,scss}`, `app-list-item.{vue,types.ts}`
- Modify: `index.ts`, call sites, and `Core/Application/assets/styles/variables.scss` (Task 10 creates it; if Task 10 has not run, create it here with only the `$list` map and let Task 10 merge)

**Interfaces:**
- Consumes: Task 1's entry; the Task 3 transformation.
- Produces: `AppList`, `AppListItem`, `ListItemVariantTypes`, exported from `@fastybird/application`.

- [ ] **Step 1: `app-list-item.types.ts`**

```ts
export enum ListItemVariantTypes {
	DEFAULT = 'default',
	LIST = 'list',
}

export type ListItemVariant = `${ListItemVariantTypes}`;

export interface AppListItemProps {
	/** list item variant */
	variant?: ListItemVariant;
}

export interface AppListItemEmits {
	(e: 'click', evt: UIEvent): void;
}
```

- [ ] **Step 2: Port both `.vue` files**

`list.vue` has no props at all — its script block reduces to:

```vue
<script setup lang="ts">
import { useNamespace } from 'element-plus';

import './app-list.scss';

defineOptions({
	name: 'AppList',
});

const ns = useNamespace('list');
</script>
```

Template verbatim. `app-list-item.vue` takes `withDefaults(defineProps<AppListItemProps>(), { variant: ListItemVariantTypes.DEFAULT })`.

- [ ] **Step 3: Carry the `$list` SCSS map across**

`fb-list.scss` depends on the `$list` map defined in `packages/theme-chalk/src/common/var.scss`. Copy that map verbatim into `Core/Application/assets/styles/variables.scss` and have `app-list.scss` `@use` it. Omitting this is a silent failure: SCSS maps have `!default`, so a missing map yields empty values rather than an error.

- [ ] **Step 4: Export, rewrite the call sites, gates**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
grep -rnE "FbList|ListItemVariantTypes" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: gates exit 0; grep returns nothing.

- [ ] **Step 5: Commit**

```bash
git add -u && git add src/FastyBird/Core/Application/assets
git commit -m "refactor(ui): port the list components to Core/Application"
```

---

## Task 6: Replace `FbDialogHeader` and `FbDialogFooter` with `el-dialog` slots

8 usages. element-plus's `el-dialog` has `#header` and `#footer` slots that cover both.

**Files:**
- Modify: the 8 call sites

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: nothing. Purely subtractive.

- [ ] **Step 1: Read each call site before changing it**

`packages/theme-chalk/src/fb-dialog.scss` styles `.el-dialog` directly — it is an override of element-plus's dialog, not a separate component. Any styling worth keeping moves to `Core/Application/assets/styles/element-plus.scss` in Task 10. Read the stylesheet and list, in the task report, which rules are load-bearing before deleting it.

- [ ] **Step 2: Rewrite each site**

```vue
<el-dialog v-model="open">
	<template #header>
		<!-- the markup that was inside <fb-dialog-header> -->
	</template>

	<!-- body -->

	<template #footer>
		<!-- the markup that was inside <fb-dialog-footer> -->
	</template>
</el-dialog>
```

If a site passes props to `FbDialogHeader` (for example an icon), move them into the slot markup explicitly. Do not invent a replacement component — the point of this task is that there is no component.

- [ ] **Step 3: Gates and grep**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
grep -rnE "FbDialogHeader|FbDialogFooter" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: gates exit 0; grep returns nothing.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "refactor(ui): use el-dialog header and footer slots directly"
```

---

## Task 7: Replace `FbExpandableBox` with `el-collapse`

3 usages.

**Files:**
- Modify: the 3 call sites

**Interfaces:** Consumes nothing; produces nothing.

- [ ] **Step 1: Read `expandable-box` and each call site**

Read `packages/components/src/components/expandable-box` first and record its prop surface in the task report. `el-collapse` is an accordion of `el-collapse-item`s; if a call site uses the box as a single always-present toggle, a single `el-collapse-item` with `v-model` on the parent is the shape.

- [ ] **Step 2: Rewrite**

```vue
<el-collapse v-model="activeNames">
	<el-collapse-item name="1">
		<template #title><!-- the header markup --></template>
		<!-- body -->
	</el-collapse-item>
</el-collapse>
```

- [ ] **Step 3: Gates and grep**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
grep -rn "FbExpandableBox" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: gates exit 0; grep returns nothing.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "refactor(ui): replace the expandable box with el-collapse"
```

---

## Task 8: Replace `FbBreadcrumbs` with `el-breadcrumb`, keeping the teleport target

`FbBreadcrumbs` has 1 usage; `FB_BREADCRUMBS_TARGET` has 2. The target is an injection key for a teleport destination — an application concern, not a component — so it survives, relocated.

**Files:**
- Create: `Core/Application/assets/components/app-breadcrumbs.constants.ts`
- Modify: the 3 call sites, `index.ts`

**Interfaces:**
- Consumes: Task 1's entry.
- Produces: `FB_BREADCRUMBS_TARGET`, exported from `@fastybird/application`, with the same value it has today.

- [ ] **Step 1: Find the current definition and copy the value verbatim**

```bash
grep -rn "FB_BREADCRUMBS_TARGET" src/FastyBird/Library/WebUi
```

Copy the literal into `app-breadcrumbs.constants.ts` unchanged — the value is a DOM id or injection key that both the teleport source and destination must agree on, so changing it silently breaks the teleport at runtime with no build error.

- [ ] **Step 2: Rewrite the single breadcrumb usage**

```vue
<el-breadcrumb separator="/">
	<el-breadcrumb-item v-for="item in items" :key="item.key" :to="item.to">
		{{ item.label }}
	</el-breadcrumb-item>
</el-breadcrumb>
```

`packages/theme-chalk/src/fb-breadcrumb-item.scss` styles `.el-breadcrumb-item`; if any of it is load-bearing, move it to `Core/Application/assets/styles/element-plus.scss` in Task 10 and say so in the report.

- [ ] **Step 3: Gates and grep**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
grep -rn "FbBreadcrumbs" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
grep -rn "FB_BREADCRUMBS_TARGET" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: gates exit 0; the first grep returns nothing; the second returns the 3 sites now importing from `@fastybird/application`.

- [ ] **Step 4: Commit**

```bash
git add -u && git add src/FastyBird/Core/Application/assets/components
git commit -m "refactor(ui): replace breadcrumbs with el-breadcrumb"
```

---

## Task 9: Inline the three single-use components

`FbSpinner`, `FbSwipe` and `FbMediaItem` have one usage each. None earns a shared component.

**Files:**
- Modify: 3 call sites

**Interfaces:** Consumes nothing; produces nothing.

- [ ] **Step 1: `FbSpinner` becomes `v-loading`**

element-plus's loading directive covers it:

```vue
<div v-loading="isLoading"><!-- content --></div>
```

If the spinner is standalone rather than an overlay, `<el-icon class="is-loading"><Icon icon="fa6-solid:spinner" /></el-icon>` is the equivalent.

- [ ] **Step 2: `FbSwipe` and `FbMediaItem` move to their call site**

Read each component, then copy its template and style into the single consuming file as local markup. If either turns out to be more than roughly 80 lines or to carry real logic worth naming, stop and report — promoting it to `Core/Application/assets/components` instead is the correct call, and this plan prefers that over an unreadable inline block.

- [ ] **Step 3: Gates and grep**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
grep -rnE "FbSpinner|FbSwipe|FbMediaItem" --include='*.ts' --include='*.vue' src/FastyBird | grep -v 'Library/WebUi'
```

Expected: gates exit 0; grep returns nothing.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "refactor(ui): inline the three single-use components"
```

---

## Task 10: Move the theme into `Core/Application`

`main.ts:13` imports `@fastybird/web-ui-theme-chalk/src/index.scss` — the single entry that carries every brand colour. Until this moves, the library cannot be deleted.

**Files:**
- Create: `Core/Application/assets/styles/element-plus.scss`, `element-plus-dark.scss`, `variables.scss`
- Modify: `Core/Application/assets/main.ts:13`

**Interfaces:**
- Consumes: Tasks 3 through 9, which relocate the per-component stylesheets.
- Produces: the brand theme at its new path, consumed by `main.ts`. Task 11 cannot delete `theme-chalk` until this lands.

- [ ] **Step 1: Move the light theme**

Copy `packages/theme-chalk/src/index.scss` to `Core/Application/assets/styles/element-plus.scss`. It is a `@forward 'element-plus/theme-chalk/src/common/var' with (...)` carrying `$--colors` (primary `#d9230f`, success `#469408`, warning `#d9831f`, danger/error `#db2828`, info `#029acf`), the button padding override and the four breakpoints. Copy it **verbatim** — every value here is a brand decision.

Strip only the `@use` lines that pull in the deleted per-component `fb-*.scss` files, since those now live beside their components.

- [ ] **Step 2: Move the dark theme**

Copy `packages/theme-chalk/src/dark.scss` to `element-plus-dark.scss`. It forwards `element-plus/theme-chalk/src/dark/var.scss` with dark primary `#a91b0c`.

- [ ] **Step 3: Merge the remaining SCSS maps**

`packages/theme-chalk/src/common/var.scss` defines `$component-loading`, `$component-loading-error`, `$list`, `$media-item`, `$spinner-size` and `$spinner-border`. Only the maps still referenced by surviving stylesheets belong in `variables.scss` — `$list` for Task 5, and `$media-item` only if Task 9 kept the media item as a component. Drop the rest with the components they styled.

- [ ] **Step 4: Repoint `main.ts`**

```
- import '@fastybird/web-ui-theme-chalk/src/index.scss';
+ import './styles/element-plus.scss';
```

Keep it in the same position in the import order: it must load before `./styles/base.scss`, which depends on the element-plus variables it defines.

- [ ] **Step 5: Verify the brand colour actually survived**

A build succeeding proves nothing here — a missing `@forward` yields element-plus's default blue, silently. Assert on the built CSS:

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c 'yarn install --ignore-engines && yarn build'
grep -ric "d9230f" public/assets/*.css | head
```

Expected: at least one match. If zero, the theme did not take effect regardless of the exit code.

- [ ] **Step 6: Commit**

```bash
git add -u && git add src/FastyBird/Core/Application/assets/styles
git commit -m "refactor(ui): move the brand theme into Core/Application"
```

---

## Task 11: Delete the library and clean the manifests

**Files:**
- Delete: `src/FastyBird/Library/WebUi/` entirely
- Modify: root `package.json` (workspaces), every manifest declaring a `@fastybird/web-ui-*` dependency, `yarn.lock`

**Interfaces:**
- Consumes: Tasks 2 through 10. This task is blocked until every one of their greps returns empty.
- Produces: a tree with no `Library/WebUi`.

- [ ] **Step 1: Prove nothing references the library**

Do this **before** deleting, not after:

```bash
grep -rn "@fastybird/web-ui" --include='*.ts' --include='*.vue' --include='*.json' --include='*.scss' \
  src/FastyBird config package.json vite.config.ts | grep -v 'src/FastyBird/Library/WebUi'
```

Expected: no output. Any hit is a task above that did not finish; fix it there rather than here.

- [ ] **Step 2: Delete**

```bash
git rm -r --quiet src/FastyBird/Library/WebUi
```

- [ ] **Step 3: Remove the workspace globs**

Root `package.json` `workspaces` currently has four entries. Two exist only for this library and must go:

```
- "src/FastyBird/Library/WebUi/packages/*",
- "src/FastyBird/Library/WebUi/web-ui-library",
- "src/FastyBird/Library/WebUi/docs"
```

`"src/FastyBird/*/*"` stays — it matched `Library/WebUi` itself, which no longer exists.

- [ ] **Step 4: Drop the dependency declarations**

Remove every `@fastybird/web-ui-components`, `-utils`, `-icons`, `-library`, `-theme-chalk` entry from every remaining manifest. Also remove the devDependencies that existed only for the deleted packages' builds — `gulp`, `@esbuild-kit/cjs-loader`, `svgo`, `fast-glob`, `camelcase`, `rimraf`, `unplugin-vue-define-options`, the Storybook set and `vue-component-meta` — but verify each is unreferenced before removing it:

```bash
for dep in gulp svgo fast-glob camelcase rimraf vue-component-meta; do
  printf '%-22s ' "$dep"
  grep -rln "$dep" --include='*.ts' --include='*.mjs' --include='*.cjs' --include='*.json' \
    . --exclude-dir=node_modules --exclude-dir=.git --exclude=yarn.lock | head -3 | tr '\n' ' '
  echo
done
```

Anything still referenced stays. Report the list either way.

- [ ] **Step 5: Regenerate the lockfile and run every gate**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 bash -c \
  'yarn install --ignore-engines && yarn build && yarn types && yarn lint:js && yarn lint:styles && yarn pretty:check'
```

Expected: all exit 0. Then verify in a clean room, because a stale `node_modules` can hide a missing dependency:

```bash
rm -rf /tmp/webui-check && mkdir -p /tmp/webui-check && git archive HEAD | tar -x -C /tmp/webui-check
docker run --rm -v /tmp/webui-check:/app -w /app node:20 bash -c \
  'yarn install --frozen-lockfile --ignore-engines && yarn build'
```

Expected: exit 0.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor(ui): delete the WebUi library"
```

---

## Task 12: Strip the dead CI gates and update the ruleset

Three jobs and several steps added in #355 and #357 now gate packages that no longer exist. Leaving them makes CI red on the very commit that deletes them, so this task lands **with** Task 11 or immediately after.

**Files:**
- Modify: `.github/workflows/ci-tests.yaml`
- Modify: ruleset `22836868` via the GitHub API

**Interfaces:**
- Consumes: Task 11's deletion.
- Produces: a CI configuration matching the tree.

- [ ] **Step 1: Remove the dead steps from `js-lint`**

Delete the `Lint the Library/WebUi packages`, `Lint the theme-chalk stylesheets` and `Check formatting in the Library/WebUi packages` steps. Keep `yarn lint:js`, `yarn lint:styles` and `yarn pretty:check` — those gate the root application and remain correct.

- [ ] **Step 2: Delete the `docs-build` job**

It builds the Storybook workspace, which is gone.

- [ ] **Step 3: Update the required checks BEFORE merging**

`Docs Build` is a **required** check on ruleset `22836868`. If the job disappears while the requirement stands, every future pull request blocks forever on a check that can never report. Remove it from the required list in the same change:

```bash
gh api repos/FastyBird/miniserver/rulesets/22836868 > /tmp/rs.json
python3 - <<'PY'
import json
d = json.load(open('/tmp/rs.json'))
for r in d['rules']:
    if r['type'] == 'required_status_checks':
        checks = r['parameters']['required_status_checks']
        r['parameters']['required_status_checks'] = [c for c in checks if c['context'] != 'Docs Build']
        print([c['context'] for c in r['parameters']['required_status_checks']])
json.dump({k: d[k] for k in ('name','target','enforcement','conditions','bypass_actors','rules')},
          open('/tmp/rs-payload.json','w'), indent=2)
PY
gh api -X PUT repos/FastyBird/miniserver/rulesets/22836868 --input /tmp/rs-payload.json
```

Expected: 11 required checks remain. Build the payload from the live ruleset as shown — hand-writing it drops rules by omission.

- [ ] **Step 4: Verify**

```bash
python3 -c "
import yaml
d = yaml.safe_load(open('.github/workflows/ci-tests.yaml'))
print('jobs:', [v['name'] for v in d['jobs'].values()])
"
```

Expected: `Docs Build` absent, 10 jobs.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/ci-tests.yaml
git commit -m "ci(ci): drop the gates for the deleted WebUi packages"
```

---

## Task 13: Retarget the Phase 6 plan

The Phase 6 plan still contains tasks operating on deleted packages. Leaving them is a trap for whoever executes it next.

**Files:**
- Modify: `docs/superpowers/plans/2026-09-11-phase-6-modernization.md`
- Modify: `docs/superpowers/plans/2026-09-11-phase-6-modernization-review.md`

**Interfaces:**
- Consumes: Task 11.
- Produces: a Phase 6 plan consistent with the tree.

- [ ] **Step 1: Mark the superseded tasks**

Do not delete them — replace each task body with a note naming this plan, so the numbering other tasks reference stays intact:

- **Task 36** (align the three lagging `Library/WebUi` build packages) — superseded
- **Task 39** (Storybook 8 to 10 and vue-component-meta 2 to 3) — superseded

- [ ] **Step 2: Reduce the tasks that shrank**

- **Task 8** (declare dependencies yarn supplies by hoisting): five fewer manifests
- **Task 11** (dead frontend devDependencies): overlaps Task 11 of this plan; reconcile so the work is described in one place
- **Track C** (manifest honesty): five fewer manifests
- **Track E** (pnpm): the workspace count drops from nine to about four, and `pnpm-workspace.yaml` in Task 20 needs only two globs

Task 37 (UnoCSS) is unaffected — the root application uses UnoCSS directly.

- [ ] **Step 3: Note the review items that no longer apply**

Review item #69 (`@storybook/theming` does not exist at 10.x) described the failure that blocked PR #350. With Storybook deleted it is moot; mark it resolved-by-deletion rather than removing it, so the reasoning stays findable.

- [ ] **Step 4: Close the moot pull requests**

```bash
gh pr close 350 --comment "Superseded by the WebUi library removal: the docs workspace this bumps is deleted. See docs/superpowers/specs/2026-09-11-webui-library-removal-design.md"
gh pr close 352 --comment "Superseded by the WebUi library removal: theme-chalk is deleted. See docs/superpowers/specs/2026-09-11-webui-library-removal-design.md"
```

- [ ] **Step 5: Commit**

```bash
git add docs/superpowers/plans
git commit -m "docs(cross): retarget the phase 6 plan around the WebUi removal"
```

---

## Deferred, deliberately

- **A frontend test suite.** There is none today, so nothing regresses by not adding one, but every task above is verified by build and lint gates rather than by behaviour. Adding vitest and `@vue/test-utils` — which smart-panel already has, with `.spec.ts` files beside its components — is the natural follow-up, and the ported components are the right first subjects.
- **UI polish.** This plan moves and deletes. `el-collapse` and `el-breadcrumb` will not look identical to what they replace, which is accepted: the UI is unfinished and gets polished separately.
- **The `'waring'` typo** in `AppIconWithChildType` (Task 3 Step 1) is preserved deliberately; correcting it is a behaviour change.
