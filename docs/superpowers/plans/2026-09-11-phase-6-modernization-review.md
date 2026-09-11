# Phase 6 plan — completeness review

An adversarial completeness pass over `2026-09-11-phase-6-modernization.md`, run as the final
stage of the analysis that produced it. **The plan has not yet been corrected against this
review.** Applying these is the first work of Phase 6, before any task is executed.

The three that matter most:

1. **#7 — phantom dependencies are named as the biggest risk but never actually handled**, and
   the reviewer found two concrete ones that break on day one of the pnpm cut-over. Track C
   exists precisely to prevent this; it needs those two written into it.
2. **#2 — `fastybird-application` is a container name, not an image name.** Every long-running
   verification command in Tasks 30, 33, 34 and 42 fails instantly as written.
3. **#6 — Task 32's PHP version matrix renames the required status check.** `PHP Tests` becomes
   `PHP Tests (8.3)`. Since `PHP Tests` is now a *required* check in the repository ruleset,
   that rename would block every pull request permanently until the ruleset is updated in the
   same change.

---

# Phase 6 plan critique — prioritized

Verified against the tree at `/Users/akadlec/.t3/worktrees/fastybird/t3code-6ace9350`. Everything below was checked against real files unless marked *(unverified)*.

---

### 1. Tasks 33, 34, 35 — `composer update <pkg> -W` before editing the constraint is a no-op, so three "measurement" steps measure the old version

Root `composer.json` `require-dev` pins `infection/infection: ^0.27`, `phpunit/phpunit: ^10.0`, `brianium/paratest: ^7.3`, `phpstan/phpstan: ^1.10` and the five `phpstan-*` at `^1.x`. Every dry-run/measure step runs `composer update` *before* any manifest edit:

- **Task 35 Step 1** is the worst. It creates `tmp/phpstan2-measure`, runs `composer update 'phpstan/*' -W`, and expects "a total error count" for PHPStan 2.2. Under `^1.10` it resolves to the already-installed 1.12.34 and prints `[OK] No errors` (`docs/baseline.md:28-29` records exactly that). The decision "temporary baseline vs. long-lived debt" would be made on a fabricated zero.
- **Task 34 Step 1** expects "phpunit 11.5.x and paratest 7.8.x resolve together"; under `^10.0`/`^7.3` it returns 10.5.64/7.4.9. Its own failure hint ("If paratest resolves to 7.4.x, the phpunit constraint did not move") describes the guaranteed outcome.
- **Task 33 Step 1** has the same shape (`^0.27` → `--dry-run infection/infection -W`).

**Fix:** each of these must edit the constraint first, e.g. `composer require --dev --no-update 'phpstan/phpstan:^2.2' 'phpstan/phpstan-deprecation-rules:^2.0' … && composer update 'phpstan/*' -W`. State the edited constraint in the step body, not only in the prose above it.

---

### 2. Tasks 30, 33, 34, 42 — `fastybird-application` is a **container name**, not an image name; every long command fails instantly

`docker/dev/docker-compose.yml` sets `container_name: fastybird-application` but no `image:`. Compose derives the image name from the project directory — in this worktree `t3code-6ace9350-application`. `docs/baseline.md:71-74` says so explicitly: *"Substitute your own image name; it is derived from the directory name."*

Affected: Task 30 Step 6, Task 33 Step 2, Task 34 Steps 3 and 5, Task 42 Step 5 — i.e. every `make tests` / `make coverage-clover` / `make mutations` invocation in the phase. They all exit with `Unable to find image 'fastybird-application:latest'`.

**Fix:** `IMG=$(docker compose images -q application)` (or `"$(basename "$PWD")-application"`) and substitute.

---

### 3. Task 33 (and Task 34 Step 5) — `make coverage-clover` and `make mutations` cannot produce coverage: **pcov is not installed anywhere**

`Makefile:102-103` are `php -d pcov.enabled=1 -d pcov.directory=./src …`, but:
- `docker/dev/php/Dockerfile` pecl-installs only `apcu` and `xdebug` — no pcov.
- `Makefile:98` sets `PRE_PHP=XDEBUG_MODE=off`, disabling the one driver that *is* present.
- `.github/workflows/ci-tests.yaml:147` extension list has no pcov, and there is no coverage job.

So `make coverage-clover` dies with *"No code coverage driver is available"* regardless of the Task 5 filter repair. Task 33 Step 2 ("the first honest coverage number"), Step 3 ("prove mutation testing is alive") and Task 34 Step 5 are all blocked, and the Phase deliverable's *"`make coverage-clover` and `make mutations` all do what their names say, with the coverage line rate and the MSI recorded"* is unreachable as written.

**Fix:** add a task before 33 that adds `pecl install pcov && docker-php-ext-enable pcov` to `/Users/akadlec/.t3/worktrees/fastybird/t3code-6ace9350/docker/dev/php/Dockerfile` (structural, no version change — good PR hygiene), or change `PHPUNIT_COVERAGE`/`PHPUNIT_MUTATIONS` to `XDEBUG_MODE=coverage`. Note pcov must also exist in whatever image Task 33 actually runs in.

---

### 4. Task 39 Step 1 — most of the twelve `@storybook/*` entries have no `^10.6` to move to

Actual contents of `src/FastyBird/Library/WebUi/docs/package.json`:

- `@storybook/addon-actions`, `addon-essentials`, `addon-interactions`, `addon-links`, `blocks`, `test`, `manager-api` — folded into the `storybook` package in SB9/10 and no longer published as separate 10.x packages. Instructing "all twelve … to `^10.6`" produces an unresolvable install.
- `@storybook/storybook-deployer` `^2.8` is an unrelated community tool (installed 2.8.16, no peers, no 10.x line). It must stay where it is.
- **`storybook-dark-mode: ^4.0`** is declared *and* listed in `.storybook/main.ts` addons. It has no SB9/10 release and Task 39 does not mention it at all — this is the concrete blocker for the migration.
- `@storybook/theming` (which Task 8 apparently adds for `.storybook/manager.js:2`) does not exist at 10.x; the import must become `storybook/theming/create` and the dependency removed again.

Step 2's "several addons folded into core" acknowledges the shape but Step 1's instruction contradicts it.

**Fix:** replace Step 1 with an explicit keep / fold-into-`storybook` / drop-and-replace table, resolved against `npm view <pkg> versions` in the container, and decide `storybook-dark-mode`'s fate (drop, or replace with `@storybook/addon-themes`) before anything else in the task.

---

### 5. Task 42 Steps 2–3 — regenerating the local `nettrine/orm` patch is inert; the live one is fetched from a repo this plan cannot change, and failure is **silent**

Confirmed in `composer.lock`: `fastybird/simple-auth v0.14.3` declares `nettrine/orm` → `"Enable connection overrides"` as `https://raw.githubusercontent.com/FastyBird/libraries-patches/master/nettrine-orm-src-managerregistry-php.patch`, and `docs/baseline.md:139-146` records that the dependency's URL **overrides** the root's local entry, leaving `tools/patches/nettrine-orm-src-managerregistry-php.patch` unused. Task 42 Step 2 states this correctly and then Step 3 tells you to regenerate the dead file.

Worse: `extra.composer-exit-on-patch-failure` is **not set** in `composer.json` (I checked the whole `extra` block), so cweagans 1.7.3 warns and continues on a failed patch — which is exactly why `dg/bypass-finals` fails today without breaking anything. Bumping `nettrine/orm v0.8.4 → ^0.9` moves `src/ManagerRegistry.php` upstream; the network patch will then fail *quietly* and connection-override behaviour disappears at runtime rather than at install time.

**Fix:** (a) set `extra.composer-exit-on-patch-failure: true` for the duration of this task so failures are loud; (b) add an explicit verification of the override behaviour that does not depend on patch output; (c) add a decision point — the network patch can only be retired by pushing to `FastyBird/libraries-patches` or releasing a new `fastybird/simple-auth`, which is deferred track T1. Task 42 may be blocked on T1 after all, and the plan should say so instead of claiming T1 is untouched.

---

### 6. Task 32 — the matrix renames the required status check and can block every PR

`.github/workflows/ci-tests.yaml:81` sets `name: "PHP Tests"` literally. Adding `strategy.matrix.php-version` does **not** append the matrix value when `name:` is explicitly set, so both legs render as `PHP Tests` — Step 2's expected `PHP Tests (8.3)` / `PHP Tests (8.4)` never appear, and branch protection cannot distinguish them. Whatever is currently listed as required (`PHP Tests`) will now match ambiguously or not at all, leaving PRs stuck between Task 32 and whatever fixes it.

**Fix:** `name: "PHP Tests (PHP ${{ matrix.php-version }})"`, and make updating the branch-protection required-checks list an explicit, *pre-merge* step of the same task. Same care applies to Task 9's new jobs and Task 31's `php-version` change.

---

### 7. The phantom-dependency risk is only mentioned, never handled — and I found two that break on day one of the pnpm cut-over

Tasks 36–41 all assume a working pnpm tree; the only mention is Task 36's "under pnpm the nesting is by design". Two concrete failures exist right now:

- `src/FastyBird/Core/Application/package.json` declares **no dependencies at all** (no `dependencies`/`devDependencies`/`peerDependencies` keys), yet `src/FastyBird/Core/Application/assets/` imports 15 external packages. Fourteen happen to be in the root manifest, but `src/FastyBird/Core/Application/assets/main.ts:13` is `import '@fastybird/web-ui-theme-chalk/src/index.scss';` and **`@fastybird/web-ui-theme-chalk` is in neither the root nor Core/Application**. It resolves today purely via yarn 1 hoisting of the workspace symlink. Under pnpm's isolated layout it will not be linked → `pnpm dev` and `pnpm build` fail.
- `tsconfig.json:33` lists `"@types/lodash"` in `compilerOptions.types`, but no manifest declares it — it is a hoisted transitive of `element-plus`. Under pnpm it lives under `.pnpm/` and not in `node_modules/@types/` → `pnpm types` (`vue-tsc --noEmit`) fails.

**Fix:** add a structural, version-free task before the pnpm cut-over that runs a declared-vs-imported audit over all nine workspace manifests plus `tsconfig.json`'s `types` array and `vite.config.ts`'s `dedupe`/`optimizeDeps.include`, and adds the missing declarations. Then gate the migration on `pnpm install --frozen-lockfile && pnpm build && pnpm types` passing with pnpm's default (non-hoisted) `node-linker`.

---

### 8. Task 37 — the file list misses seven manifests that also declare `unocss`

`unocss: "^0.64"` is a `peerDependency` in `Core/Tools:42`, `Library/Metadata:37`, `Module/Accounts:62`, `Connector/HomeKit:57`, `Module/Devices:63`, `Module/Triggers:42`, `Module/Ui:60`. Task 37 lists only the root `package.json`. Leaving them at `^0.64` against a root `^66.10` produces seven unmet-peer warnings and, under pnpm's isolated layout, a second UnoCSS generation resolved inside each extension.

Same class of miss in **Task 41 Step 3**: "remove `vue-meta` from all eight extensions' `peerDependencies`" — only **seven** declare it (`Core/Application` does not; it is the one extension with an empty manifest, see item 7).

Also, Task 37 Step 1's visual-baseline list omits `Connector/HomeKit`, which carries utility classes in 4 `.vue` files. (Its Devices=33 / Accounts=11 counts are exactly right.)

---

### 9. Task 42 Step 3 — "eleven patches applied, none failed" contradicts the plan's own Task 34 Step 4

`docs/baseline.md:186` records the verified cold-install result as *"9 applied from `tools/patches/`, 2 fetched over the network, 1 known failure"* — `dg/bypass-finals` permanently fails and Task 34 Step 4 explicitly treats it as a known non-regression. Task 42 cannot produce zero failures.

**Fix:** expected output becomes "ten patch operations attempted from `tools/patches/` + two from the network; one known `dg/bypass-finals` failure; **zero new** failures."

---

### 10. Task 41 Step 4 — the head verification cannot detect the failure it is designed to detect

`index.html` already contains a static `<title>FastyBird IoT</title>` and **no** `<meta name="description">`. `@unhead/vue` renders client-side; the Vite dev server returns the (transformed) `index.html`, not a rendered head. So `curl … | grep -E '<title>|<meta name="description"'` matches `<title>` even if unhead never installs (false pass) and can never match the description meta (false fail). The step's own rationale — "a build-only check would not catch a head manager that installs but never renders" — applies to itself.

**Fix:** assert in a real DOM — headless browser evaluating `document.title` and `document.querySelector('meta[name="description"]')?.content` after mount, or a jsdom/vitest test.

---

### 11. The `jsona` forced exception is in the Phase deliverable but in **no task**, and it is not a pure version bump

The deliverable requires *"`jsona` is at `^1.14`"*. Nothing in Tasks 30–42 touches it. `~1.12.0` is declared in `Module/Devices:52`, `Module/Accounts:51`, `Module/Ui:51`, and **28 files** across those three modules import `jsona/lib/JsonaTypes` / `jsona/lib/simplePropertyMappers`, which 1.13's `"exports": {".": …}` map blocks (`docs/baseline.md:103-108`). Retiring it means rewriting 28 imports or vendoring the types — a **fourth** code-change-inseparable-from-a-version-change, contradicting the plan's own claim that there are exactly three (Tasks 35, 37, 42).

**Fix:** add the task (with the import-rewrite strategy and a `pnpm types` gate) or remove the claim from the deliverable.

---

### 12. Tasks 31 and 40 — the toolchain stays documented as PHP 8.2 / Node 20 in five tracked files

Neither file list includes:
- `AGENTS.md` — "PHP 8.2, Node 20, yarn 1" and the `--ignore-engines` paragraph. `CLAUDE.md` states these two files must be kept in sync; Task 40 updates `CLAUDE.md` and `README.md` but not `AGENTS.md`.
- `.github/PULL_REQUEST_TEMPLATE.md:31` — "The toolchain is PHP 8.2 / Node 20 and results…" plus a yarn command list at `:35`.
- `docs/deployment.md:5` (`node:20`, `yarn install --frozen-lockfile --ignore-engines`, "the frozen 8.2 runtime", `php:8.2-fpm`), `:63` (`ui-server` runs `yarn dev`), `:67` ("not validated against the current PHP 8.2 … stack").
- `docs/baseline.md` — Task 33 amends it, but nothing reconciles its PHP 8.2 / Node 20 reproduction commands.
- `CLAUDE.md` also carries "**Package manager**: yarn 1 (pnpm arrives in Phase 6 … do not document or use pnpm before then)" — that line must flip in the pnpm task, not later.

These are the agent contract for every future session; leaving them stale is a real regression.

---

### 13. Task 39 Step 3 — nothing is listening on port 6006

`docker/dev/node/Dockerfile:12` is `CMD ["yarn", "dev"]` (Vite on 3000). Compose publishes 6006 but nothing binds it. `storybook build` writes a static bundle; it does not serve. So `docker compose up -d ui-server` followed by `curl http://localhost:6006/` returns connection-refused.

**Fix:** `docker compose run --rm --service-ports ui-server sh -lc 'pnpm --filter @fastybird/web-ui-docs dev --ci'` (`docs` scripts: `"dev": "storybook dev -p 6006"`), or serve `storybook-static/` and curl that.

---

### 14. Task 37 Step 3 — the CSS size/rule-count comparison is not measurable as written

`vite.config.ts` sets `outDir: resolve(__dirname, './public')` with `emptyOutDir: false` (deliberate — `public/` is the PHP web root). `public/.gitignore` ignores `assets`, `index.html`, `.vite`, so hashed outputs from every prior build accumulate. `ls -la public/assets/*.css` lists old and new together with no way to tell which is current.

**Fix:** `rm -rf public/assets public/.vite public/index.html` before each build, and identify the current stylesheet from `public/.vite/manifest.json` rather than a glob.

---

### 15. Task 38 Step 3 — "raw TypeScript source" is the wrong expected output

Vite's dev server transpiles `.ts` on request and rewrites import specifiers. `curl /src/FastyBird/Module/Devices/assets/entry.ts` returns compiled ESM JavaScript, not TypeScript. The check still proves source-serving, but the stated expectation will read as a failure to whoever runs it.

**Fix:** expect ESM JS with rewritten specifiers (`/@fs/`, `/node_modules/.vite/deps/`) and grep for a known identifier from `entry.ts` instead.

---

### 16. Task 31 Step 4 — the expected lock delta is half wrong, and `--lock` needs a guard

`composer.lock` has **no** `platform-overrides` key (correct — there is no `config.platform`, as the task itself argues). It has `platform`, currently `{"php": ">=8.2.0", …18 ext-*}`. Only `platform.php` moves.

**Fix:** state "`composer.lock`'s `platform.php` moves to `>=8.3.0`; there is no `platform-overrides` key and none should appear", and add `git diff --stat composer.lock` proving only `content-hash` and `platform` changed — otherwise `composer update --lock` has silently re-resolved packages inside a PR that is supposed to be a platform bump only.

---

### 17. Task 36 — `theme-chalk` is left behind, and Step 1's "Expected" does not match the tree

- `theme-chalk/package.json:52` declares `rimraf: "^5.0"` — the exact bump Task 36 applies to the other three — and `sass-loader: "^14.2"` against root/docs `^16.0`. `theme-chalk` is not in the file list, so "one lint toolchain generation across the workspace" is not achieved.
- Step 1 expects `typescript-eslint`/`@typescript-eslint/*` "already at `^8.70`". On the current tree all three are `^7.8`. Even if PR340/343 land, root is `^8.15` and `web-ui-library` `^8.16`, so the workspace still is not on one declared line.

**Fix:** add `theme-chalk` to the file list, give Step 1 an explicit fallback ("if still `^7.8`, bump here"), and either align root/`web-ui-library` in the same PR or soften the stated outcome.

---

### 18. `make qa` is broken and the deliverable claims otherwise

`Makefile:14` is `make cs & make phpstan`. The `&` backgrounds `make cs`, so its exit status is discarded and `make qa` reports PHPStan's only — a coding-standard failure passes silently. No task fixes it, yet the deliverable says *"`make qa` … do what their names say"*.

**Fix:** `make cs && make phpstan`, in a structural (version-free) PR.

---

### 19. Smaller, but each will cost a re-run

- **Task 40 Step 1** contains the literal placeholder `npm i -g pnpm@<pinned>` — not runnable as written; resolve it against whatever Task 23 pins (`packageManager` field / `corepack`).
- **Task 41 Step 5** uses `git add -A` while Tasks 36, 37, 38 and 40 all correctly `git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts` first. Task 41 Step 4 runs `pnpm build`, which runs `build:ui` → the icons generator → the known 163-line barrel churn (`docs/baseline.md:170-179`), which `git add -A` then commits. Use the same guard and an explicit path list.
- **Task 31 Step 2 and Task 42 Step 5** both run `docker run -d --name fb-loopback-redis …` with a fixed name and neither removes it; the second invocation exits `Conflict. The container name … is already in use`. Prefix each with `docker rm -f fb-loopback-redis 2>/dev/null || true`.
- **Task 35 Step 4** — the inline-suppression counts are 80 total with **72** bare `@phpstan-ignore-next-line` (so 8 identifier-scoped), not 71/9.
- **Task 38** — the "four `Library/WebUi` packages declaring vite" is exactly right (`components`, `utils`, `web-ui-library`, `docs`), but three of them pin `vite-plugin-dts` to the **exact** version `4.1.1` (peer `vite: "*"`, so no resolution error, but a 2024 pin carried across a Vite major with no fallback stated). Name it in the file list.
- **Task 33 Step 2** — the inline PHP one-liner's `printf("… %.1f%%\n", …)` is inside single quotes, so `\n` prints literally. Cosmetic, but it makes the "expected output" not match.

---

## Sections that are genuinely sound — no action

Verified accurate against the tree, do not re-litigate:

- **Task 35's cost analysis.** `tools/phpstan.neon` = 99 entries suppressing 418 occurrences; `tools/phpstan.tests.neon` = 21 / 121 — both exact. `level: max` at `:5` in both. The three non-default parameters at `tools/phpstan.neon:16-18` are correctly identified. `reportUnmatchedIgnoredErrors` is indeed unset.
- **Task 42's manifest inventory.** All eight `doctrine/orm: "2.15.*"` locations and all seven `symfony/console: "^6.0"` line numbers are exact. `nettrine/orm v0.8.4`, `nettrine/dbal v0.8.2`, `nettrine/fixtures v0.6.4`, `contributte/console v0.9.4` confirmed in the lock.
- **Task 31's platform reasoning.** `orisai/object-mapper 0.2.0` and `orisai/nette-object-mapper 0.1.1` both declare `php: 7.4 - 8.3` — genuinely the binding ceiling, and it admits 8.3. `phpdocumentor/reflection 5.3.3` caps at `8.3.*`, consistent with Task 30's third-blocker claim. 285 locked packages (225 + 60) is exact. `vendor/orisai/coding-standard/src/ruleset-8.3.xml` exists.
- **Task 36's `@vueuse/core` arithmetic.** Three majors are genuinely resident (10.11.1, 11.3.0, 14.4.0); `element-plus` resolves to 2.14.5 and does hard-pin `@vueuse/core@14.4.0`; the bump reduces three to two, not one.
- **File-existence claims** `uno.config.ts:44` (`presetUno({ dark: 'class' })`), `.storybook/manager.js:2` (`@storybook/theming/create`), `tools/infection.json:5`, `tools/phpcs.xml:9`, `tools/phpunit.xml:3` (schema 10.1), `composer.json:19` and `:44`, `docker/prod/Dockerfile:14`/`:48`, `docker/dev/php/Dockerfile:3`, `docker/dev/node/Dockerfile:1`, four `php-version` blocks, three `node-version` blocks, 34 path manifests, 17 `vue-meta` call sites, 4 `virtual:uno.css` entry points — all verified correct.
- **The `tools/infection.json` `../` diagnosis is right, for the right reason.** `Infection\Configuration\ConfigurationFactory` resolves `logs`, `tmpDir`, `phpUnit.configDir` and `--coverage` against `dirname($schema->getFile())`, but passes `source.directories` straight to `sourceFileCollector->collectFiles()` (CWD-relative). That asymmetry is exactly why the logs land in `tools/var/` while the source glob works — Task 6's fix and Task 33 Step 4's check are both correct.