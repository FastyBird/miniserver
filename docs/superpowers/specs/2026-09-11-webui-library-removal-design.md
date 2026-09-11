# Removing the WebUi library in favour of element-plus

**Date:** 2026-09-11
**Status:** approved, not yet planned
**Supersedes:** Phase 6 Tasks 36 and 39; reduces Tasks 8, 11 and Tracks C and E

## Why

`src/FastyBird/Library/WebUi` is 4,316 tracked files and 52,810 lines across six
packages, four build pipelines, six lint configurations and a Storybook. What the rest
of the application actually consumes from it is **16 named exports across 39 import
sites**, plus an icon set.

Three facts settle the question.

**element-plus is already a direct dependency in ten manifests**, including the
consuming extensions (`Module/Devices`, `Module/Accounts`, `Module/Triggers`,
`Module/Ui`, `Core/Tools`). The application is not choosing between the library and
element-plus; it already runs both.

**The library is not the fork it is remembered as.** `packages/theme-chalk` does not
port element-plus's theme -- it `@use`s element-plus's own mixins and `@forward`s its
dark variables with brand colours, a ~1,500-line layer on top. The genuine fork is
`packages/utils`: element-plus's internal `@element-plus/utils` copied wholesale with
`epPropKey` renamed to `fbPropKey`, which exists only because element-plus does not
export those helpers publicly.

**The target state already exists and is maintained by the same author.**
`FastyBird/smart-panel`'s `apps/admin` uses `element-plus ^2.13.5` directly,
`@iconify/vue` + `@iconify/json` for icons, UnoCSS 66, no wrapper library and no
Storybook. Its `src/common/components/` holds `app-bar.vue`, `app-bar-button.vue`,
`app-bar-content.vue`, `app-bar-heading.vue`, `app-bar-icon.vue` and
`app-breadcrumbs.vue` -- the direct analogues of this repository's `Fb*` components.

## Decisions taken

1. **Decide now and retarget Phase 6**, rather than finishing Phase 6 first. Phase 6
   Tasks 36 and 39 are work on packages this design deletes.
2. **Replace what element-plus already covers**; port only the genuinely custom
   components. Single-use components are inlined at their call site.

## Target structure

Everything consumed moves into `Core/Application/assets/`, which already follows the
smart-panel pattern: flat, co-located `<name>.vue` + `<name>.types.ts` + `<name>.scss`,
no `index.ts` or `src/` nesting. The existing `app-navigation`, `app-sidebar`,
`app-topbar` and `app-gravatar` components are already in exactly this shape, so no new
convention is introduced.

```
Core/Application/assets/
  components/   app-bar.vue/.types.ts/.scss      app-bar-button.*
                app-bar-heading.*                app-icon-with-child.*
                app-list.*                       app-list-item.*
                (alongside the existing app-navigation / app-sidebar /
                 app-topbar / app-gravatar)
  styles/       element-plus variable overrides, dark mode, brand primary #a91b0c
```

Ported components use idiomatic Vue 3 `<script setup>` with `defineProps<Props>()` and a
sibling `.types.ts`, as smart-panel does. This is what allows `packages/utils` to be
deleted rather than relocated: nothing needs `buildProps` or `definePropType` any more.

## Component disposition

The complete externally-consumed surface, with its fate. "Uses" counts occurrences of
that named export outside `Library/WebUi`; a single import statement usually names
several, which is why these total 81 across the 39 import statements.

### Ported to `Core/Application/assets/components`

| Export | Uses | Notes |
|---|---|---|
| `FbIconWithChild` | 13 | No element-plus equivalent |
| `FbAppBarHeading` | 11 | Port from smart-panel's `app-bar-heading.vue` |
| `FbAppBarButton` | 9 | Port from smart-panel's `app-bar-button.vue` |
| `AppBarButtonAlignTypes` | 9 | Moves with the button |
| `FbList` | 7 | element-plus has no List component |
| `FbListItem` | 6 | Moves with the list |
| `ListItemVariantTypes` | 6 | Moves with the list |
| `FbAppBar` | 3 | Port from smart-panel's `app-bar.vue` |

`FbAppBarContent` and `FbAppBarIcon` are not consumed externally but are used by
`app-bar` internally; port whatever the ported components require.

### Replaced with element-plus

| Export | Uses | Replacement |
|---|---|---|
| `FbDialogHeader` | 4 | `el-dialog` `#header` slot |
| `FbDialogFooter` | 4 | `el-dialog` `#footer` slot |
| `FbExpandableBox` | 3 | `el-collapse` |
| `FbBreadcrumbs` | 1 | `el-breadcrumb` |
| `FB_BREADCRUMBS_TARGET` | 2 | Keep the teleport-target pattern; it is an application concern, not a component |

### Inlined at their single call site

| Export | Uses | Notes |
|---|---|---|
| `FbSpinner` | 1 | Becomes element-plus `v-loading` |
| `FbSwipe` | 1 | Inline at the call site |
| `FbMediaItem` | 1 | Inline at the call site |

## Icons

`packages/icons` generates 2,040 Vue components from Font Awesome SVGs
(`solid`/`regular`/`brands` to `fas`/`far`/`fab`). It is 4,086 tracked files and 41,148
lines -- 78% of the whole library -- and the application uses **73 distinct icons**.

Replacement is `@iconify/vue` with `@iconify/json`, as in smart-panel. Component names
map mechanically onto Iconify identifiers:

```
FasAngleLeft   -> fa6-solid:angle-left
FarCircleCheck -> fa6-regular:circle-check
FabGithub      -> fa6-brands:github
```

**Verified before writing this spec:** all 73 icons were resolved against the Iconify
API -- 61 `fa6-solid`, 8 `fa6-regular`, 4 `fa6-brands`, **zero not found**. (An earlier
count of 46 came from a single-line regex over import statements; 7 of the 52 import
statements are prettier-wrapped across multiple lines, so 27 icons were missed. The
figure above is the corrected, complete count.) The risk that some icon was Font
Awesome Pro (which Iconify does not ship) does not materialise, so no local SVG
fallback collection is needed.

## Styles and branding

`packages/theme-chalk` splits in two:

- The element-plus customisation -- variable overrides and the dark-mode
  `@forward ... with ($colors: ...)` carrying brand primary `#a91b0c` -- moves to
  `Core/Application/assets/styles/`.
- The per-component `fb-*.scss` files follow their components as co-located `.scss`
  siblings, or are deleted along with the components they styled.

The brand colour must survive the move; it is the one piece of the theme package with no
element-plus equivalent.

## What is deleted

| Package | Files | Lines |
|---|---|---|
| `packages/icons` | 4,086 | 41,148 |
| `packages/components` | 118 | 5,534 |
| `docs` (Storybook) | 40 | 2,718 |
| `packages/theme-chalk` | 23 | 1,508 |
| `packages/utils` | 35 | 1,388 |
| `web-ui-library` | 10 | 341 |
| package root (`package.json`, `README.md`, `.gitignore`, `.markdownlint.json`) | 4 | 173 |
| **Total** | **4,316** | **52,810** |

`web-ui-library` is build machinery only: `build/generate.ts` copies
`packages/components/src` and `packages/utils/src` into a git-ignored `src/` with
rewritten imports, then bundles. Only `entry.ts` is tracked. Nothing is lost by deleting
it.

## Impact on Phase 6

**Dropped outright**

- Task 36, align the three lagging `Library/WebUi` build packages
- Task 39, Storybook 8 to 10 and vue-component-meta 2 to 3

**Reduced**

- Task 8, declaring dependencies yarn supplies by hoisting -- fewer packages
- Task 11, dead frontend devDependencies -- overlaps this removal
- Track C, manifest honesty -- five fewer manifests
- Track E, pnpm -- the workspace count drops from nine to about four

**Unaffected**

- Task 37, UnoCSS 0.64 to 66: the root application uses UnoCSS directly

**Open pull requests that become moot**

- #350, the storybook group -- currently the only PR failing `Docs Build`
- #352, stylelint-config-recommended-scss for `theme-chalk`

**Already-merged work this partially obsoletes**

The CI gates added in #355 and #357 cover packages this design deletes
(`theme-chalk lint:styles`, six `pretty:check` scripts, four `lint:js` scripts, the
`Docs Build` job). They are correct today and guard the interim, which spans several
pull requests; they are removed alongside the packages, and the required-checks list in
ruleset 22836868 must be updated in the same change that removes `Docs Build`.

## Verification strategy

Each step is provable, and the existing gates make most of it automatic.

1. **Icon parity.** Every replaced icon renders. The 73-icon list is fixed and known;
   a build plus a grep for residual `@fastybird/web-ui-icons` imports proves completion.
2. **No residual imports.** `grep -rn '@fastybird/web-ui-' --include='*.ts'
   --include='*.vue' src/FastyBird` returns nothing.
3. **Gates stay green.** `yarn build`, `yarn types`, `yarn lint:js`, `yarn lint:styles`
   and `yarn pretty:check` all run today and must continue to exit 0.
4. **Production image.** `docker/prod/Dockerfile` copies `public/` wholesale and the
   Vite build writes there; the existing Docker Build smoke test covers it.
5. **Visual check.** The UI is unfinished and lightly used, so a rendered smoke test of
   the app shell -- app bar, navigation, one device list -- is sufficient. There are no
   frontend unit tests to preserve.

## Risks

**The UI is unfinished, so "correct" is weakly defined.** There is no visual baseline
and no frontend test suite. Mitigation: port the app-bar family from smart-panel, which
is a working implementation of the same components, rather than reinterpreting them.

**element-plus substitutions change markup.** `el-collapse` and `el-breadcrumb` will not
be pixel-identical to the components they replace. This is accepted: the design
explicitly chooses a smaller long-term surface over pixel fidelity in an unfinished UI.

**Scope creep into a UI redesign.** This design moves and deletes; it does not redesign.
Improving the unfinished UI is separate work and out of scope here.

## Out of scope

- Redesigning or completing the web UI
- Any PHP change
- The pnpm migration itself (Track E), which this only makes smaller
- Replacing UnoCSS or Vite
