# Phase 6, Modernization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Take the revived, frozen repository off its two end-of-life anchors (PHP 8.2, Node 20), replace yarn 1 with pnpm, and burn down the dependency and QA-tooling debt that accumulated between November 2024 and today — without ever letting a package-manager migration and a version change land in the same pull request, and without leaving `main` red between pull requests.

**Architecture:** Six ordered tracks, and the order is a safety property rather than a preference. **Track A** clears the board: triage the 14 open Dependabot pull requests, then convert every claim the six Phase 6 analysts marked "unverified" into a recorded container measurement, because the whole plan below rests on static constraint analysis that no solver has confirmed. **Track B** repairs configuration that currently measures nothing — `tools/phpunit.xml`'s coverage filter points at the tests instead of the source, `make qa` backgrounds `make cs` with `&` so a style failure exits green, and three CI-invisible surfaces (the Storybook docs workspace, the five `Library/WebUi` package lint scripts, `composer validate`) let two obviously-broken Dependabot pull requests pass nine checks each. **Track C** makes the manifests honest while still on yarn: every package that is present today only because yarn 1 hoists it flat gets declared by the workspace that imports it. This is the single most important de-risking step in the phase, because pnpm's isolated `node_modules` turns each of those into a hard failure, and doing it under yarn means each fix is provable in isolation against a green tree. **Track D** removes the two forced exceptions (`--ignore-engines`, the `jsona ~1.12.0` pin), each split into a source-only pull request and a version-only pull request so neither is diagnosed against a moving target. **Track E** is the pnpm migration itself: one large pull request containing exactly zero version changes, whose lockfile is produced by `pnpm import` from the existing `yarn.lock` rather than by a fresh resolve. **Track F** is the version work proper — infrastructure pins, PHP 8.3, the PHP QA tooling chain, then the frontend framework chain — each step gated on the one before it. Three bodies of work are explicitly *not* pull requests in this phase and are handed off as named tracks: Doctrine ORM 3 (blocked on five external releases Adam owns), PHP 8.4 (blocked on a 497-file `orisai/object-mapper` migration), and PHPCS 4 (blocked on `orisai/coding-standard`).

**Tech Stack:** Composer 2.4→2.8, PHP 8.2→8.3 (`php:8.3-fpm`), pnpm 10 replacing yarn 1.22, Node 20→22.13+, Vite 5→6, UnoCSS 0.64→66, Storybook 8→10, PHPStan 1.12→2.2, PHPUnit 10.5→11.5, paratest 7.4→7.8, Infection 0.27→0.31, Doctrine ORM 2.15→2.20 (ORM 3 deferred).

**Spec:** `docs/superpowers/specs/2026-09-09-miniserver-merge-design.md` §"Phase 6, modernization"

---

## Global Constraints

- **Every verification command in this plan runs inside a container. Host results are not evidence and must never be recorded as such.** The host runs PHP 8.5 and Node 24 and has no Composer at all. The two idioms used throughout:

  ```bash
  # PHP — from the repository root (docker-compose.yml includes docker/dev/docker-compose.yml)
  docker compose run --rm --no-deps application sh -lc "<command>"

  # JavaScript
  docker compose run --rm --no-deps ui-server sh -lc "<command>"
  ```

- **Long-running installs must be run detached.** `docs/baseline.md:196` records that an attached `docker run` dies with `grpc: the client connection is closing` the moment the invoking shell is cleaned up, silently killing an install part-way through. Wherever a step is marked **(long)**, use:

  ```bash
  docker compose run -d --no-deps --name fb-p6-<slug> ui-server sh -lc "<command> 2>&1 | tee /app/var/p6-<slug>.log"
  docker wait fb-p6-<slug>; docker logs --tail 40 fb-p6-<slug>; docker rm fb-p6-<slug>
  ```

- **The PHP test suite needs MariaDB and Redis on the test process's own loopback**, not on compose hostnames — 25 `tests/common.neon` files hardcode `host: 127.0.0.1` and a NEON file beats any `FB_APP_PARAMETER__*` variable. Use the shared-namespace form from `docs/baseline.md:65-76`:

  ```bash
  docker compose up -d database
  docker run -d --name fb-loopback-redis --network container:fastybird-database redis:7
  docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
    -e PHP_DATE_TIMEZONE=UTC <application-image> sh -lc "make tests"
  ```

  Expected on a healthy tree: `OK (1405 tests, 5663 assertions)`, exit 0, ~14m39s. **Watch the assertion count, not just the exit code** — both a real pass and a database-less run report "1405 tests" (`docs/baseline.md:38`).

- **No pull request mixes a structural change with a dependency version change.** This rule has held for all five prior phases and is not relaxed here. Where a version change genuinely cannot be separated from a code change, the plan says so explicitly and names the reason (there are exactly three such places: the PHPStan 2 baseline file, the UnoCSS preset rename, and the Doctrine patch regeneration).
- **The pnpm migration contains no version changes, and no version-change pull request contains a package-manager change.** Migrating package managers while also moving versions makes any breakage un-diagnosable: you cannot tell whether a failure came from pnpm's resolution or from a new package version. The pnpm pull request is verified by proving that `pnpm-lock.yaml`'s resolved version set is identical to `yarn.lock`'s.
- **CI must be green on `main` before the next pull request opens.** Where a pull request cannot be green on its own (there are none in this plan by construction), that is a defect in the split, not an exception to the rule.
- Conventional commit format `<type>(<scope>): <subject>`, scope from `core, module, connector, plugin, bridge, addon, automator, library, ui, infra, ci, deps, docs, cross`, enforced by commitlint locally and by `lint-pr.yml` on pull request titles.
- PHP namespaces stay `FastyBird\<Type>\<Name>` and `src/FastyBird/` does not change. The 34 extensions remain Composer PATH repositories; this phase does not introduce a `replace` block.
- **`FastyBird/libraries-patches` must not be deleted, and nothing in this phase makes it deletable.** Three vendored packages (`fastybird/json-api`, `fastybird/datetime-factory`, `fastybird/simple-auth`) declare `extra.patches` entries pointing at raw URLs in that repository, honoured at install time regardless of the root's `enable-patching: false`. It becomes eligible only after those three libraries are re-released or absorbed — deferred track T1.
- Four conditions are **known and deliberately not regressions**; do not "fix" them as a side effect: `composer validate --strict` exits 1 on two pre-existing constraint warnings (Task 12 fixes one of them on purpose); the Nette application registers no routes so `GET /` 404s by design; `yarn build` rewrites `src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts` in a different order every run; and `docs/baseline.md`'s recorded measurements are dated history that gets an addendum, never an edit.

---

## Pull Requests

Ordered. Each entry says why it sits where it sits.

### Track A — clear the board

1. **PR1 — Dependabot triage** (Task 1). First because 14 open pull requests touch `package.json`, `yarn.lock` and `composer.lock`, and every pull request below will conflict with them. Six merge now, three close, three move to the Phase 6 backlog.
2. **PR2 — Harden `.github/dependabot.yml`** (Task 2). Immediately after, so next Monday's run is grouped and does not reopen what PR1 closed.
3. **PR3 — Evidence run and baseline addendum** (Tasks 3–4). Docs only. Converts every "unverified" flag from the six analyses into a recorded container measurement, and corrects the two forced-exception records that are currently incomplete. Nothing below is safe to execute against static analysis alone.

### Track B — make the gates real

4. **PR4 — Repair the QA and infra configs that currently measure nothing** (Tasks 5–7). No version changes. Before any tooling bump, because a coverage gate, a mutation run and `make qa` are all silently broken today and would otherwise be "fixed" by the tooling bump in a way nobody can attribute.
5. **PR6 — Extend the CI gates to the unwatched surfaces** (Task 9). Depends on PR5 (the docs workspace needs `@storybook/theming` declared before a docs build can be green). Placed before every framework bump because two obviously-broken Dependabot pull requests passed all nine checks purely because no job touches those surfaces.

### Track C — make the manifests honest, still on yarn

**Reduced.** `docs/superpowers/plans/2026-09-11-webui-library-removal.md` deleted
`src/FastyBird/Library/WebUi` outright, so this track now touches five fewer
manifests than it did when written: the four WebUi package manifests that
PR5/Task 8 and PR8/Task 11 would have edited are simply gone, and Track E's
workspace count drops accordingly (see below). See the reduction notes on
Task 8 and Task 11 themselves.

6. **PR5 — Declare the dependencies yarn only supplies by hoisting** (Task 8). The most important de-risking step in the phase. No-ops under yarn 1's flat hoist, hard failures under pnpm. Done here, each is provable in isolation against a green tree; done inside the pnpm pull request, they are indistinguishable from migration breakage.
7. **PR7 — Rewrite the six jsona mappers to import from the package root** (Task 10). Source only, and verified to work against the *currently installed* jsona 1.12.1, whose `lib/index.d.ts` already re-exports `ModelPropertiesMapper` and `JsonPropertiesMapper`. Split from the constraint bump so the bump is a one-line diff.
8. **PR8 — Delete the dead frontend devDependencies** (Task 11). Removal only, after PR5 so the "what is actually used" question has already been answered once.
9. **PR9 — PHP manifest honesty** (Task 12). `ext-pdo_mysql` declared, `symplify/vendor-patches` moved to `require-dev`, `composer.lock` metadata refreshed. Before any composer version work, so future composer pull requests carry only their real change instead of ~34 packages of pre-rename support-URL churn.
10. **PR10 — Consolidate the 25 `tests/common.neon` DBAL blocks** (Task 13). Structural, no version change. Before the PHPUnit bump, because 25 duplicated connection blocks are 25 places a PHPUnit 11 change has to be applied.

### Track D — remove the forced exceptions

11. **PR11 — Pin `@intlify/shared` and `@intlify/message-compiler` to 11.4.2** (Task 14). Version-only. CI still passes `--ignore-engines` here, so this pull request is green either way — which is exactly what makes the next one diagnosable.
12. **PR12 — Drop `--ignore-engines`** (Task 15). Config and docs only. Green only if PR11 worked.
13. **PR13 — Lift the jsona pin to `^1.14`** (Task 16). Version-only, one line in three manifests, because PR7 already moved the source.
14. **PR14 — Pin the unbounded npm specifiers** (Task 17). Must land before PR16: `pnpm import` inherits whatever these resolve to, and `vue-component-type-helpers latest` plus two unbounded stylelint ranges would otherwise re-resolve silently during the migration.
15. **PR15 — Widen the stale peer ranges in `Module/Ui` and `Module/Triggers`** (Task 18). **Requires an explicit maintainer decision** — it is a dependency change and the frozen-toolchain rule forbids it by default. If declined, PR16 ships `auto-install-peers=false`, which hides the mismatch instead of resolving it.

### Track E — pnpm

**Reduced.** With `Library/WebUi`'s six nested workspaces gone, the
workspace count this migration has to carry drops from nine to about four,
and the `pnpm-workspace.yaml` written in Task 20 needs only two globs
instead of the larger set originally planned. The "31 internal references"
figure below and elsewhere in this track was counted against the pre-removal
tree and has not been independently re-verified against the current one;
treat it as an upper bound, not a fixed count.

16. **PR16 — Migrate the JavaScript toolchain from yarn 1 to pnpm** (Tasks 19–23). The largest and highest-risk pull request in the phase, and necessarily one pull request: yarn 1 classic cannot parse the `workspace:` protocol, so the 31 internal references cannot be converted ahead of time, and a tree with `pnpm-workspace.yaml` but no `pnpm-lock.yaml` (or the reverse) has no working install path at all. **Zero version changes** — verified by diffing the resolved version sets.
17. **PR17 — pnpm prose sweep** (Task 24). Archival documentation, per-package `.gitignore` files and the 11 `yarn add @fastybird/*` README lines. Split from PR16 so PR16's diff stays reviewable; PR16 itself carries the instruction-bearing prose (`README.md`, `CLAUDE.md`, `AGENTS.md`, the PR template, `docs/deployment.md`) because leaving those wrong between pull requests would mislead a contributor.

### Track F — versions

18. **PR18 — Pin the floating dev-compose service images** (Task 25). One line each. Removes the largest environment divergence (a developer validating schema behaviour against MariaDB 11/12 while CI and production run 10.11) at zero cost, before any PHP or Doctrine work.
19. **PR19 — Align Composer across dev, prod and CI** (Task 26). Three different Composer versions today; the dev pin (2.4, September 2022) is below the fix lines for CVE-2023-43655 and CVE-2024-24821, and this project resolves three dependencies from Git branch refs — precisely that CVE's surface.
20. **PR20 — Dependabot `docker` ecosystem, then digest-pin the four production images** (Task 27). Automation first, digests second: digests without automation trade silent drift for silent staleness, which is worse for a base image.
21. **PR21 — Slim the production runtime image** (Task 28). Purge the build-time `-dev` packages, fix the `opcache` comment that says the opposite of the truth.
22. **PR22 — Make the two floating dev-branch composer pins reproducible** (Task 29). Before any other composer work, or every later `composer update` silently moves `bunny/bunny` and `clue/redis-react` to that day's HEAD and each later step becomes two changes at once.
23. **PR23 — Cheap decoupled composer constraint wins** (Task 30). Four constraints that touch nothing in the Doctrine/Symfony knot.
24. **PR24 — PHP 8.3** (Tasks 31–32). After PR22/PR23 so the resolver is working from reproducible pins, and before the QA tooling bumps because three of the four test tools are PHP-gated at their newest and PHPStan currently analyses against whatever PHP the runner happens to have.
25. **PR25 — Infection 0.27 → 0.31 and the first real coverage number** (Task 33). Infection first, not last: 0.27 predates PHPUnit 11 support, so this is a hard prerequisite for PR26, not an optional extra.
26. **PR26 — PHPUnit 10.5 → 11.5 with paratest 7.4 → 7.8** (Task 34). One commit, mandatory: paratest 7.8.5 requires `phpunit/phpunit ^11.5.46`.
27. **PR27 — PHPStan 1.12 → 2.2 with all five extensions** (Task 35). Last of the PHP QA chain: `level: max` silently becomes level 10, and all 120 `ignoreErrors` entries (539 counted occurrences) must be re-derived against the final PHP version, which only exists after PR24.
28. ~~**PR28 — Align the three lagging `Library/WebUi` build packages** (Task 36).~~ **Superseded** — `Library/WebUi` is deleted; see Task 36's note.
29. **PR29 — UnoCSS 0.64 → 66** (Task 37). The first frontend framework bump, and the hard peer block: `unocss@0.64.1` declares `vite ^2.9 || ^3 || ^4 || ^5`, so nothing else in the frontend chain moves until it does.
30. **PR30 — Vite 6 and `@vitejs/plugin-vue` 6** (Task 38). Only reachable after PR29.
31. ~~**PR31 — Storybook 8 → 10 and vue-component-meta 2 → 3** (Task 39).~~ **Superseded** — the Storybook docs workspace is deleted; see Task 39's note.
32. **PR32 — Node 20 → 22.13+** (Task 40). Deliberately late: after PR11/PR12 the `--ignore-engines` exception is already gone, so the only remaining reasons are security patching and four Node-gated upgrades. The gulp 4 → chokidar 2 → fsevents 1.2.13 chain in `theme-chalk` is the one plausible breakage and must be proven first.
33. **PR33 — Replace `vue-meta` with `@unhead/vue`** (Task 41). A replacement, not an upgrade — npm's `latest` for `vue-meta` is 2.4.0 from 2020 and the installed 3.0.0-alpha.10 has no successor. 17 call sites plus three config wirings.
34. **PR34 — The ORM-2-preserving Doctrine step** (Task 42). Last, and deliberately stops short of ORM 3.

### Deferred tracks — explicitly not pull requests in this phase

- **T1 — Doctrine ORM 3 / Symfony console 7.** Blocked entirely outside this repository. `nettrine/fixtures` has no release offering console 7 on ORM 2, so console 7 requires ORM 3, which requires new upstream releases of `ipub/doctrine-crud`, `ipub/doctrine-orm-query`, `ipub/doctrine-phone`, `ipub/doctrine-consistence` and `fastybird/json-api` — all at their newest published version, all last released August 2024. Scope it against the good news: entity mapping is already 100% PHP attributes (552 `#[ORM\` against one stray `@ORM\Entity` docblock at `src/FastyBird/Module/Ui/src/Entities/Widgets/Displays/Slider.php:25`) and the DBAL 4 surface is near-zero (no custom `Type` subclasses, no `AbstractPlatform` usage, no `->quote()`; the one real item is `tests/tools/ConnectionWrapper extends DBAL\Connection`, whose constructor signature changed).
- **T2 — PHP 8.4.** Blocked by exactly three packages, each with a published fix, but `orisai/object-mapper 0.2 → 0.3` is a semver-major on a 0.x package with `Orisai\ObjectMapper` referenced in 497 first-party files across 19 extensions. Its own project. PR23 clears `phpdocumentor/reflection`, the cheap third.
- **T3 — `mathsolver/mathsolver`.** A dead 1-star repository pinned to a 2023 commit, the sole reason Laravel 9 (`illuminate/support` v9.52.16, EOL) is in a 2026 tree, and one of the two `composer validate --strict` warnings. Read what `Core/Tools` actually uses from it; if it is a small expression evaluator, vendoring or replacing it removes the VCS repository entry, the unbound constraint and five `illuminate/*` packages in one move.
- **T4 — PHPCS 4.** `slevomat/coding-standard` has already moved to `^4.0.1`, but `orisai/coding-standard` 3.11.0 — its own latest — requires `squizlabs/php_codesniffer ^3.12.0`, so Composer resolves slevomat back to the PHPCS-3 line. Nothing can be done until orisai ships. Also record that orisai 3.11.0 declares `php: 7.4 - 8.4` and will refuse to install on 8.5.
- **T5 — `database.version: 5.7` vs MariaDB.** `config/defaults.neon:14` feeds nettrineDbal's `serverVersion`, and nothing overrides it, so DBAL selects a MySQL 5.7 platform against MariaDB 10.11 in production and MariaDB 11/12 in dev. The same 5.7 is baked into 25 `tests/common.neon` files. Changing it produces a real schema diff, so it needs a deliberate migration, not a drive-by edit — and PR10 makes it a one-file change instead of 26.
- **T6 — Shelly Gen2 `ClientNegotiator`.** `src/FastyBird/Connector/Shelly/src/API/Gen2WsApi.php` constructs `new RFC6455\Handshake\ClientNegotiator()` with no arguments; the resolved `ratchet/rfc6455` v0.4.1 requires a `RequestFactoryInterface`, so the path raises `ArgumentCountError` at runtime (spec §7.1). Two candidate fixes: pass a request factory at the call site, or constrain `ratchet/rfc6455` to `^0.3`. Decide as its own change.

---

## What could go wrong

Stated plainly, because the plan is only useful if the failure modes are known before they happen.

**Note (WebUi removal):** the two risk items below cite `Library/WebUi` specifics
(the `@fastybird/web-ui-theme-chalk` entry import, four of "the five real
`Library/WebUi` builds", the ten nested duplicate installs, and the "31
internal references" figure). `Library/WebUi` is deleted; those specific
clauses no longer apply, but the general risk pattern each item illustrates
— undeclared transitive imports, and nested duplicate installs losing their
pin under `pnpm import` — still holds for the surviving workspaces and is
worth re-checking against the current tree rather than assuming it is now
zero-risk.

**The single biggest risk is PR16.** pnpm's isolated `node_modules` will expose every dependency that exists today only because yarn 1 hoists the whole tree flat. Four distinct classes are already confirmed by reading manifests and grepping every bare import specifier: root tooling (`stylelint-scss`, `stylelint-config-recommended-scss`, `@types/lodash`), an application entry import (`Core/Application/assets/main.ts:13` imports `@fastybird/web-ui-theme-chalk/src/index.scss`, which is declared in no manifest that can reach it), four of the five real `Library/WebUi` builds (`@vue/shared`, `chalk`, `consola`, `fs-extra`, `@storybook/theming`), and 31 internal references using plain semver instead of `workspace:*` — which on pnpm 10, where `link-workspace-packages` defaults to `false`, means pnpm goes to the public registry, and those names *exist* there, so the failure mode is silently building against a published 2024 tarball rather than an error. Track C exists to retire all of that before PR16 opens. The residual risk is a *fifth* class nobody found: an import satisfied by hoisting that grep missed because it is dynamic, or constructed, or inside a config file loaded by a tool that resolves from its own directory. Mitigation: PR16's Step "install and run every gate" is the detector, and PR5 having already landed means any failure there is attributable to pnpm rather than to a missing declaration.

**Second: `pnpm import` may not reproduce the ten existing nested duplicate installs.** `yarn.lock` has no record of which nested copies were deliberate. `Module/Accounts` carries its own `date-fns` and `uuid`, `Module/Devices` its own `uuid`, and four `Library/WebUi` packages their own `@typescript-eslint`/`@vueuse`/`rimraf`/`esbuild`. If the import flattens any of those, a version that was pinned by nesting becomes a version that is pinned by nothing. Task 21 checks this explicitly rather than trusting it.

**Third: PHPStan 2 at `level: max` with no baseline.** 94 of the 99 `src` suppressions are the same three messages (`strval` ×78, `intval` ×10, `floatval` ×6 — pure level-9 artefacts that level 10 will multiply), and all 21 test suppressions are the single `createMock()` message that `phpstan-phpunit` 2.x fixes, so all 21 become unmatched-ignore errors on day one. `reportUnmatchedIgnoredErrors` is unset and therefore true. The plan generates a baseline rather than hand-editing 120 entries, and says so.

**Fourth: the three doctrine/orm patches.** They target `../lib/Doctrine/ORM/...`; even a 2.15 → 2.20 step inside ORM 2 moves the hunk offsets, and ORM 3 moved the entire tree to `src/` so all three fail on the path before the hunks are considered. PR34 budgets explicit time for regeneration and treats it as expected work, not as a surprise.

**Fifth, and the quietest: `composer update` has never been run.** Every constraint claim in this plan is static analysis over `composer.lock` plus packagist metadata. No solver has confirmed that any of the proposed edits resolve. Task 3 runs the authoritative checks first, and every constraint-changing task carries a `--dry-run` step before the real one.

---

## Task 1: Triage the 14 open Dependabot pull requests

**Files:**
- Modify: none in this repository (actions taken against `FastyBird/miniserver` pull requests 333–346)

**Interfaces:**
- Consumes: nothing (first task).
- Produces: a settled `main` with no open Dependabot pull requests competing for `package.json`, `yarn.lock` or `composer.lock`, consumed by every task below; and three deferred pull requests labelled for Tasks 37, 38 and 39.

- [ ] **Step 1: Merge the three GitHub Actions pull requests, in this order**

The `actions/checkout` and `actions/setup-node` claim is verified from CI logs, not assumed: `main`'s runs emit `forced to run on Node.js 24: actions/checkout@v4, actions/setup-node@v4`; PR334's run drops `checkout` from that list and PR333's drops `setup-node`. Neither clears the warning alone.

```bash
gh pr merge 334 --squash --repo FastyBird/miniserver   # actions/checkout 4 -> 7
gh pr merge 333 --squash --repo FastyBird/miniserver   # actions/setup-node 4 -> 7
gh pr merge 335 --squash --repo FastyBird/miniserver   # docker/login-action 3 -> 4
```

Expected: three merges, all nine checks green on each. PR335 has one call site (`release.yml:24`, GHCR login) with unchanged inputs; it produces no CI warning today only because `release.yml` fires on `release: published`, which is why it is worth taking now rather than at the next release.

- [ ] **Step 2: Confirm the Node 20 deprecation warning is gone from `main`**

```bash
rid=$(gh api 'repos/FastyBird/miniserver/actions/runs?branch=main&per_page=5' \
  --jq '.workflow_runs[] | select(.name=="CI Tests") | .id' | head -1)
jid=$(gh api "repos/FastyBird/miniserver/actions/runs/$rid/jobs" \
  --jq '.jobs[] | select(.name=="JS Lint") | .id')
gh api "repos/FastyBird/miniserver/actions/jobs/$jid/logs" | grep -i 'forced to run on Node.js 24' || echo CLEAN
```

Expected: `CLEAN`.

- [ ] **Step 3: Merge PR337 (`ninjify/nunjuck` 0.3.0 → 0.4.1)**

A constraint-alignment no-op: v0.4.1's only requirement change is `nette/tester ^2.3` → `^2.4.3`, and the lock already holds `nette/tester` v2.6.1.

```bash
gh pr merge 337 --squash --repo FastyBird/miniserver
```

Expected: merged, nine checks green. Its passing Docker Build also confirms that regenerating `composer.lock` is itself harmless — useful contrast with PR336 below.

- [ ] **Step 4: Before merging PR340 + PR343, lint the three surfaces CI does not cover**

`typescript-eslint` and `@typescript-eslint/parser` 7.18.0 → 8.70.0 is a de-duplication for the root (which already resolves to 8.70.0); the substance is `packages/components`, `packages/icons` and `packages/utils` moving `^7.8` → `^8.70`. Their own `lint:js` scripts are invoked by no CI job.

```bash
gh pr checkout 343 --repo FastyBird/miniserver
docker compose run --rm --no-deps ui-server sh -lc \
  'yarn install --frozen-lockfile --ignore-engines \
   && yarn workspace @fastybird/web-ui-components lint:js \
   && yarn workspace @fastybird/web-ui-utils lint:js \
   && yarn workspace @fastybird/web-ui-icons lint:js'
```

Expected: exit 0. Their `eslint.config.mjs` files reference only `@typescript-eslint/explicit-function-return-type`, `ban-ts-comment` and `no-explicit-any`, all of which survive v7 → v8, so a `Definition for rule ... was not found` abort would be a surprise.

- [ ] **Step 5: Merge PR343 and PR340 as a pair, never one alone**

```bash
gh pr merge 343 --squash --repo FastyBird/miniserver
gh pr merge 340 --squash --repo FastyBird/miniserver
```

Expected: both merged. `typescript-eslint` 8.70.0 depends on `@typescript-eslint/parser` 8.70.0; merging 343 alone would leave the three packages declaring parser `^7.8`.

- [ ] **Step 6: Before merging PR338, prove the formatter major does not reformat the unlinted packages**

`@trivago/prettier-plugin-sort-imports` 4.3.0 → 6.0.2 is already proven byte-identical over `src/FastyBird/*/*/assets` (CI's JS Lint runs `prettier/prettier: ['error']` there and is green on the branch). The uncovered surface is `web-ui-library` and `packages/icons`, both of which end their `build` with `yarn pretty:write`.

```bash
gh pr checkout 338 --repo FastyBird/miniserver
docker compose run --rm --no-deps ui-server sh -lc \
  'yarn install --frozen-lockfile --ignore-engines \
   && yarn workspace @fastybird/web-ui-components pretty:check \
   && yarn workspace @fastybird/web-ui-utils pretty:check \
   && yarn workspace @fastybird/web-ui-icons pretty:check \
   && yarn workspace @fastybird/web-ui-library pretty:check'
```

Expected: exit 0 and no `Code style issues found` lines. If it reports files, merge PR338 together with the resulting `yarn pretty:write` commit rather than alone.

- [ ] **Step 7: Merge PR338**

```bash
gh pr merge 338 --squash --repo FastyBird/miniserver
```

- [ ] **Step 8: Close PR345 in favour of PR346, then merge PR346**

Dependabot opened two `uuid` pull requests because `yarn.lock` holds two resolutions (`uuid@^11.0` → 11.1.1 and `uuid@^9.0` → 9.0.1). PR345 edits only `Module/Accounts` and `Module/Devices` and targets 14.0.0; PR346 edits those two *and* `Module/Ui`, and targets 14.0.2. PR345 is a strict subset at an older patch and conflicts with PR346 on the same two files.

```bash
gh pr close 345 --repo FastyBird/miniserver \
  --comment "Superseded by #346, which makes the same two edits plus Module/Ui (uuid ^9.0 -> ^14.0) and targets 14.0.2."
gh pr merge 346 --squash --repo FastyBird/miniserver
```

Expected: 345 closed, 346 merged with all nine checks green. Five majors, but the exposure is narrow: only `v4`, `validate` and `version` are imported, across 16 files, all ESM consumed by Vite — so uuid 12's CommonJS removal and 14's `"type": "module"` are inert. `dist/v4.js` still guards with `if (!buf && !options && crypto.randomUUID)` and otherwise falls through to `rng()` → `crypto.getRandomValues`, which is not secure-context-restricted, so the plain-HTTP LAN appliance case is unchanged.

- [ ] **Step 9: Smoke the UI by hand after PR346**

There is no frontend runtime test. Create a dashboard or tab in `Module/Ui`, which calls `v4()`.

```bash
docker compose up -d ui-server web-server application database
# then open http://localhost:3000/ and create a dashboard
```

Expected: creation succeeds and the new entity carries a v4 UUID.

- [ ] **Step 10: Close PR336 (`nettrine/migrations` 0.8.1 → 0.10.1) with the real reason**

The only red pull request of the 14, and merging it makes the production image unbootable. v0.10.1 renamed the DI config key from scalar `directory` to a required map `directories` (`Expect::arrayOf(Expect::string(), Expect::string())->required()` in `contributte/doctrine-migrations` v0.10.1). `config/common.neon:210` still writes `directory: %appDir%/migrations`, so the DI container fails to compile. `docker/prod/docker-entrypoint.sh` probes the database with `until php bin/fb-console.php dbal:run-sql "select 1" >/dev/null 2>&1`, which swallows the compile error, so the container reports `FATAL: database did not answer after 20 attempts. Aborting startup.` and Docker Build times out waiting on `/favicon.ico`.

```bash
gh pr close 336 --repo FastyBird/miniserver --comment \
"nettrine/migrations 0.10 renames the DI key 'directory' to a required map 'directories'. config/common.neon:210 must become:

    nettrineMigrations:
        directories:
            Migrations: %appDir%/migrations

(the single migration class is 'namespace Migrations;'). 0.10 also drops contributte/di for nette/di ^3.2.3, while this tree runs nettrine/orm 0.8.4, nettrine/dbal 0.8.2, nettrine/cache 0.3.0. This bump belongs with the whole nettrine family in Phase 6, not alone."
```

- [ ] **Step 11: Close PR339 (`@types/node` 20.19.43 → 26.5.0)**

`@types/node` must track the runtime major. `.nvmrc` is `20`, `engines.node` is `>=20`, every CI job sets `node-version: "20"` and `docker/prod/Dockerfile:14` is `FROM node:20`. At 26 the compiler accepts Node 21–26 APIs that do not exist at runtime and `vue-tsc` will not catch it. CI is green only because 26.5.0 ships a `typesVersions` fallback (`"<=5.6": {"*": ["ts5.6/*"]}`) that keeps the pinned TypeScript 5.6.2 compiling — a shim, not a signal.

```bash
gh pr close 339 --repo FastyBird/miniserver --comment \
"@types/node must track the pinned Node 20 runtime (.nvmrc, engines.node, all three CI jobs, docker/prod/Dockerfile:14). Reopening as part of PR32 (Node 22/24) in the Phase 6 plan, where @types/node moves to the major matching the new runtime — not to 26. An ignore rule for major bumps lands in the next PR."
```

- [ ] **Step 12: Label PR341, PR342 and PR344 for the Phase 6 backlog rather than merging**

```bash
gh label create phase-6 --repo FastyBird/miniserver --color BFD4F2 \
  --description "Coordinated Phase 6 modernization work" 2>/dev/null || true
for n in 341 342 344; do gh pr edit "$n" --repo FastyBird/miniserver --add-label phase-6; done
gh pr comment 341 --repo FastyBird/miniserver --body \
"Deferred to the Storybook 8->10 migration (Phase 6 PR31). @storybook/addon-a11y@10.6.0 peer-requires storybook ^10.6.0 while docs/package.json pins storybook ^8.4 plus eleven other @storybook/* at ^8.4; the lock diff installs 10.6.0 alone, two majors ahead of core. CI is green only because no job builds the docs workspace — Phase 6 PR6 adds that gate."
gh pr comment 342 --repo FastyBird/miniserver --body \
"Deferred to Phase 6 PR31 (Volar 2->3). vue-component-meta@3.3.11 pulls @vue/language-core 3.3.11 while docs runs vue-tsc ^2.1, and the lock keeps vue-component-meta@^2.0.0 alongside — two incompatible language-core copies parsing the same SFCs."
gh pr comment 344 --repo FastyBird/miniserver --body \
"Deferred to Phase 6 PR29+PR30. vite 6 genuinely builds here, but unocss@0.64.1 declares peer vite '^2.9 || ^3 || ^4 || ^5' — yarn 1 only warns. UnoCSS must move to >=0.65 in the same change, and nothing in CI exercises 'yarn dev' or the docs workspace."
```

Expected: three pull requests labelled `phase-6` with an explanatory comment, none merged.

- [ ] **Step 13: Confirm the board is clear**

```bash
gh pr list --repo FastyBird/miniserver --author app/dependabot --json number,title,labels
```

Expected: exactly three entries — 341, 342, 344 — each carrying the `phase-6` label.

---

## Task 2: Harden `.github/dependabot.yml`

**Files:**
- Modify: `.github/dependabot.yml`

**Interfaces:**
- Consumes: Task 1's closures (PR339's reason becomes the `@types/node` ignore rule).
- Produces: a grouped, ignore-aware Dependabot configuration, consumed by every Monday run for the rest of the phase.

- [ ] **Step 1: Add groups and ignore rules**

Replace the `npm` and `github-actions` blocks with grouped versions and add the deliberate-pin ignores. Keep the existing `composer` block's schedule and commit-message prefix.

```yaml
  - package-ecosystem: "npm"
    directory: "/"
    schedule:
      interval: "weekly"
    commit-message:
      prefix: "chore"
      include: "scope"
    groups:
      typescript-eslint:
        patterns:
          - "typescript-eslint"
          - "@typescript-eslint/*"
      storybook:
        patterns:
          - "@storybook/*"
          - "storybook"
          - "@chromatic-com/storybook"
      unocss:
        patterns:
          - "unocss"
          - "@unocss/*"
    ignore:
      # Frozen deliberately: 1.13 added an exports map that blocks the subpath
      # import six mapper files rely on. Lifted by Phase 6 PR7 + PR13.
      - dependency-name: "jsona"
      # Must track the pinned Node runtime, not npm latest. See closed PR #339.
      - dependency-name: "@types/node"
        update-types: ["version-update:semver-major"]
      # Coupled to UnoCSS's vite peer range; moves as one migration (PR29/PR30).
      - dependency-name: "vite"
        update-types: ["version-update:semver-major"]
      # Coupled to the Storybook/Volar migration (PR31).
      - dependency-name: "vue-tsc"
        update-types: ["version-update:semver-major"]
      - dependency-name: "vue-component-meta"
        update-types: ["version-update:semver-major"]

  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    commit-message:
      prefix: "chore"
      include: "scope"
    groups:
      actions:
        patterns:
          - "*"
```

- [ ] **Step 2: Validate the file parses**

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  "node -e \"const y=require('/app/node_modules/js-yaml');\" 2>/dev/null || true"
python3 -c "import yaml,sys; d=yaml.safe_load(open('.github/dependabot.yml')); print(len(d['updates']), 'ecosystems')"
```

Expected: `3 ecosystems`. (YAML parsing is the one check in this plan that does not need a container; it reads a static file and depends on no toolchain version.)

- [ ] **Step 3: Commit**

```bash
git add .github/dependabot.yml
git commit -m "ci(deps): group dependabot updates and ignore the deliberate pins"
```

---

## Task 3: Run the evidence sweep

**Files:**
- Modify: none (measurement only; results are written in Task 4)

**Interfaces:**
- Consumes: Task 1's settled `main`.
- Produces: recorded container measurements for every claim the six analysts flagged unverified, consumed by Task 4's baseline addendum and by the go/no-go decision on Tasks 29–35.

Every command here is authoritative in a way the static analysis is not. Run them all before editing a single constraint. Several are **(long)** — use the detached idiom from Global Constraints.

- [ ] **Step 1: Composer — audit, outdated, and the two PHP-version questions**

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer --version && composer audit --locked; composer outdated --direct --locked; \
   composer why-not php 8.3; composer why-not php 8.4'
```

Expected: `Composer version 2.4.x`; `composer audit --locked` reports **no security vulnerability advisories** (a static POST of all 285 locked names to packagist's advisories API returned zero hits, but that used a hand-rolled version comparator, not Composer constraint semantics — this is the real check); `why-not php 8.3` reports nothing blocking; `why-not php 8.4` names exactly `orisai/object-mapper`, `orisai/nette-object-mapper` and `phpdocumentor/reflection` and nothing else. **If `why-not php 8.4` names anything else, Task 30 and the T2 deferred track are both wrong and must be re-scoped before proceeding.**

- [ ] **Step 2: Composer — prove a cold install still works and account for the package-count gap** **(long, ~5-10 min)**

`docs/baseline.md` records 253 installs from a lock containing 285 entries (34 path + 251 remote, all names unique). The 32-package gap was never explained; it may be `COMPOSER_MIRROR_PATH_REPOS` behaviour.

```bash
docker compose run --rm --no-deps application sh -lc \
  'rm -rf vendor && composer install 2>&1 | tail -20'
```

Expected: exit 0; a line of the form `Package operations: N installs, 0 updates, 0 removals`; eleven patches applied, of which nine resolve from `tools/patches/` and two (`nette/utils`, `nettrine/orm`) are fetched over the network from `FastyBird/libraries-patches`. Record `N` and whether it is 253 or 285. **If the network-fetched patches fail, stop** — that is the libraries-patches dependency and it is load-bearing.

- [ ] **Step 3: PHPUnit — prove the coverage filter measures the tests**

The defect is proven statically (path resolution in `Xml/Loader.php:220`, filter construction in `SourceMapper.php:43,65`, and `ls tools/src` failing because `<exclude>` resolves to `<repo>/tools/src/...` which does not exist), but never executed.

```bash
docker compose run --rm --no-deps application sh -lc \
  'php -d pcov.enabled=1 -d pcov.directory=./src vendor/bin/phpunit -c tools/phpunit.xml \
   --filter ZZZ_NoSuchTest --coverage-text 2>&1 | head -40'
```

Expected: the file list contains `src/FastyBird/**/tests/cases/*.php` entries and **zero** `src/FastyBird/**/src/*.php` entries. If `pcov` is not installed the command errors with `No code coverage driver available` — that is itself a finding (Task 5 fixes it) and Step 3 should then be re-run after Task 5.

- [ ] **Step 4: Make — prove `make qa` swallows a coding-standard failure**

```bash
docker compose run --rm --no-deps application sh -lc \
  'printf "<?php\nclass   Bad {}\n" > src/FastyBird/Core/Tools/src/ZzzTmp.php; \
   make qa; echo "EXIT=$?"; rm -f src/FastyBird/Core/Tools/src/ZzzTmp.php'
```

Expected: PHPCS prints errors for `ZzzTmp.php` and the recipe still prints `EXIT=0`. `Makefile:14` is `make cs & make phpstan`; `A & B` runs A detached and the recipe's exit status is B's alone.

- [ ] **Step 5: pnpm — the four migration unknowns** **(long)**

```bash
docker compose run -d --no-deps --name fb-p6-pnpm ui-server sh -lc \
  'npm i -g pnpm@10 >/dev/null 2>&1; pnpm --version; \
   pnpm config get link-workspace-packages; \
   pnpm import 2>&1 | tail -5; \
   pnpm install --frozen-lockfile 2>&1 | tail -30; echo "INSTALL_EXIT=$?"'
docker wait fb-p6-pnpm; docker logs fb-p6-pnpm; docker rm fb-p6-pnpm
```

Expected, and each line answers a specific open question: `pnpm --version` prints `10.x`; `link-workspace-packages` prints `false` (confirming that the 31 plain-semver internal references would resolve from the registry, which is the reason Task 20 converts them to `workspace:*`); `pnpm import` writes `pnpm-lock.yaml` without network resolution; and `pnpm install --frozen-lockfile` either **warns** about `@intlify/shared@11.4.10`'s `node >= 22` or **fails** with `ERR_PNPM_UNSUPPORTED_ENGINE`. A warning confirms that `engine-strict=false` (pnpm's default) is the `--ignore-engines` replacement; a failure means Task 14's `resolutions` pin is a hard prerequisite for PR16 rather than a nicety. **Restore the tree afterwards**: `git checkout -- . && git clean -fd pnpm-lock.yaml && docker compose run --rm --no-deps ui-server sh -lc 'yarn install --frozen-lockfile --ignore-engines'`.

- [ ] **Step 6: pnpm — confirm which gates fail without the Track C manifest fixes**

Still inside the throwaway pnpm tree from Step 5, before restoring:

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  'pnpm lint:styles; echo "STYLES=$?"; pnpm types; echo "TYPES=$?"'
```

Expected: `lint:styles` fails to resolve `stylelint-config-recommended-scss` and/or the `stylelint-scss` plugin; `types` fails with `TS2688: Cannot find type definition file for '@types/lodash'`. These two failures are the proof that Task 8 must land before PR16. If either unexpectedly passes, note which and reduce Task 8 accordingly.

- [ ] **Step 7: Node image — confirm the patch level satisfies the `>= 20.19.0` packages**

Four installed packages (`sass` 1.104.0, `chokidar` 5.0.0, `readdirp` 5.1.1, `stylelint-config-recommended` 18.0.0) require `>= 20.19.0`. If the image is below that, the `--ignore-engines` cause list is longer than the two identified.

```bash
docker run --rm node:20-alpine node -v
docker run --rm node:20 node -v
docker run --rm node:24-alpine sh -lc 'yarn --version'
docker pull -q node:20 && docker inspect -f '{{.Created}}' node:20
```

Expected: both `node -v` print `v20.19.x` or higher; `yarn --version` prints `1.22.x` (yarn 1 is unmaintained but still runs on Node 24); the `node:20` creation date tells you whether Docker Hub is still publishing patched post-EOL tags — record it, because it is the argument for PR32.

- [ ] **Step 8: theme-chalk on newer Node — the one plausible PR32 breakage** **(long)**

```bash
docker run --rm -v "$PWD":/app -w /app node:22-alpine sh -lc \
  'yarn install --frozen-lockfile --ignore-engines && yarn workspace @fastybird/web-ui-theme-chalk build'
docker run --rm -v "$PWD":/app -w /app node:24-alpine sh -lc \
  'yarn install --frozen-lockfile --ignore-engines && yarn workspace @fastybird/web-ui-theme-chalk build'
```

Expected: both exit 0. The chain `gulp@4.0.2` → `glob-watcher@5` → `chokidar@^2.0.0` → `fsevents@1.2.13` (a native addon built with `nan ^2.12.1`) is `os: darwin` and optional, so Alpine skips it entirely — **an Alpine pass does not clear a macOS host developer's machine**, and that limitation must be written into Task 40.

- [ ] **Step 9: Record the resolved versions behind every floating image tag**

```bash
docker run --rm mariadb:latest mariadbd --version
docker run --rm redis:latest redis-server --version
docker run --rm composer:2 composer --version
docker run --rm composer:2.4 composer --version
```

Expected: four version strings. The MariaDB one is the argument for Task 25 — a developer is validating schema behaviour two or three majors ahead of CI and production.

---

## Task 4: Write the Phase 6 addendum and correct the forced-exception records

**Files:**
- Modify: `docs/baseline.md` (append a Phase 6 section; do **not** edit its dated measurements)
- Modify: `CLAUDE.md`
- Modify: `README.md`

**Interfaces:**
- Consumes: every measurement from Task 3.
- Produces: the corrected `--ignore-engines` and `jsona` records, consumed by Tasks 14, 15 and 16 (whoever executes those must know both causes, not one).

- [ ] **Step 1: Append a `## Phase 6 evidence run` section to `docs/baseline.md`**

Record, with the date and the exact command: the `composer audit --locked` result; the `composer why-not php 8.3` and `php 8.4` output; the cold-install package count and how it compares to the recorded 253; the PHPUnit coverage-filter file list; the `make qa` exit code with a deliberate style violation; the pnpm 10 `link-workspace-packages` value and install exit code; the Node image patch levels; and the four floating-tag resolutions. Frame it as an addendum: `docs/baseline.md` is a dated historical record ("recorded 2026-09-09") and rewriting its measurements destroys the only evidence of what the tree looked like at merge time.

- [ ] **Step 2: Correct the `--ignore-engines` record — there are two causes, not one**

`CLAUDE.md:43`, `README.md:32` and `docs/baseline.md:98` all attribute the flag solely to `@intlify/shared`. A full walk of 835 installed `engines.node` fields finds exactly two offenders on a Node 20.19+ runtime:

1. `@intlify/shared@11.4.10` and `@intlify/message-compiler@11.4.10`, both `engines.node: ">= 22"`, installed in three nested copies under `node_modules/@intlify/unplugin-vue-i18n/node_modules/` and `node_modules/@intlify/bundle-utils/node_modules/`, reachable only from the single root devDependency `@intlify/unplugin-vue-i18n@^6.0` (6.0.8). Note the *root-level* `@intlify/shared` is 10.0.8 with `node >= 16` and is not the problem.
2. `stylelint-config-html@2.0.0`, `engines.node: "^22.12 || >=24"`, pulled by `stylelint-config-recommended-vue@1.6.1` through the unbounded range `stylelint-config-html ">=1.0.0"`.

Rewrite `CLAUDE.md:43` to name both, and add: *a fix aimed only at intlify leaves the flag still required.*

- [ ] **Step 3: Correct the jsona record — three modules, one symbol**

`docs/baseline.md:103` says "Four modules". It is three: `Module/Accounts`, `Module/Devices`, `Module/Ui`. 49 files import from jsona across those three, but 28 of the 34 subpath imports are `from 'jsona/lib/JsonaTypes'` and every one of the nine names imported is a pure interface or type alias; jsona 1.14.0 ships no `lib/JsonaTypes.js`, only `JsonaTypes.d.ts`, and the root `tsconfig.json:9` sets `"moduleResolution": "node"`, which ignores `exports` maps entirely. The pin rests on **six** value imports of `RELATIONSHIP_NAMES_PROP` from `jsona/lib/simplePropertyMappers` — the only symbol with no path off the subpath, since `ModelPropertiesMapper` and `JsonPropertiesMapper` are re-exported from jsona's package root (confirmed in the installed 1.12.1's `lib/index.d.ts`).

- [ ] **Step 4: Verify the corrections landed**

```bash
grep -n "stylelint-config-html" CLAUDE.md README.md docs/baseline.md
grep -n "RELATIONSHIP_NAMES_PROP" docs/baseline.md
```

Expected: at least one hit in each of the three files for the first command, and one hit for the second.

- [ ] **Step 5: Commit**

```bash
git add docs/baseline.md CLAUDE.md README.md
git commit -m "docs(cross): record the Phase 6 evidence run and correct both forced-exception entries"
```

---

## Task 5: Repair the PHPUnit coverage filter

**Files:**
- Modify: `tools/phpunit.xml`
- Modify: `docker/dev/php/Dockerfile` (install `ext-pcov`)
- Modify: `Makefile` (coverage targets)

**Interfaces:**
- Consumes: Task 3 Step 3's proof that the filter contains 232 test files and zero source files.
- Produces: a coverage filter that agrees by construction with `tools/infection.json`'s mutation source set, consumed by Task 33's first real coverage number and by Task 33's Infection run.

Two independent bugs sit in one block. `<include>` (lines 27–34) lists `../src/FastyBird/<Type>/**/tests/cases/`, which PHPUnit resolves against the config file's own directory; PHP's `glob()` has no globstar so `**` collapses to one level and matches — the coverage filter therefore contains 232 test files and zero source files. `<exclude>` (lines 37–44) uses `./src/...`, which resolves to `<repo>/tools/src/FastyBird/...`; that directory does not exist, `realpath()` returns `false`, `array_filter` drops it, and the exclude list is empty. The consequence chain: `make coverage-clover` reports on test classes; `make mutations-tests` writes a `--coverage-xml` containing only test files; `make mutations-infection` then runs `--skip-initial-tests` over `source.directories: src/FastyBird/*/*/src`, so not one mutated file has coverage data and every mutant is classified uncovered. Mutation testing is dead, silently.

- [ ] **Step 1: Replace `tools/phpunit.xml` lines 25–46**

```xml
    <source>
        <include>
            <directory suffix=".php">../src/FastyBird/Addon/*/src</directory>
            <directory suffix=".php">../src/FastyBird/Automator/*/src</directory>
            <directory suffix=".php">../src/FastyBird/Bridge/*/src</directory>
            <directory suffix=".php">../src/FastyBird/Connector/*/src</directory>
            <directory suffix=".php">../src/FastyBird/Core/*/src</directory>
            <directory suffix=".php">../src/FastyBird/Library/*/src</directory>
            <directory suffix=".php">../src/FastyBird/Module/*/src</directory>
            <directory suffix=".php">../src/FastyBird/Plugin/*/src</directory>
        </include>
    </source>
```

The `<exclude>` element is deleted, not repaired: once `<include>` points at `/src`, there is nothing under it to exclude — fixtures and dummies live in `tests/`, and assets are `.ts` and are filtered by `suffix=".php"` anyway. Repairing it to `../` would be worse: it would exclude `tests`, a parent of the included `tests/cases`, zeroing the filter entirely. Line 24's `<coverage cacheDirectory="../var/tools/PHPUnit/coverage"/>` and the whole `<testsuite>` block stay untouched.

`*/src` rather than the existing `**` style is deliberate: `src/FastyBird/*/*/src` resolves to exactly the 34 PHP source directories — identical to the 34 paths PHPStan analyses — and is byte-for-byte the same glob `tools/infection.json:5` already uses, so after this fix the coverage filter and the mutation source set agree by construction.

- [ ] **Step 2: Install `ext-pcov` in the dev image**

`PHPUNIT_COVERAGE` passes `-d pcov.enabled=1` while `ext-pcov` is installed nowhere (the dev image installs `apcu` and `xdebug` only) and `PRE_PHP=XDEBUG_MODE=off` disables the alternative for every target including the coverage ones. Add `pcov` to the dev Dockerfile's `pecl install` line alongside `apcu` and `xdebug`, and add `pcov` to the four `.github/workflows/ci-tests.yaml` `extensions:` lists so a CI coverage job is possible later.

- [ ] **Step 3: Rebuild the dev image and verify the filter now measures source**

```bash
docker compose build application
docker compose run --rm --no-deps application sh -lc \
  'php -m | grep -i pcov && php -d pcov.enabled=1 -d pcov.directory=./src vendor/bin/phpunit \
   -c tools/phpunit.xml --filter ZZZ_NoSuchTest --coverage-text 2>&1 | head -40'
```

Expected: `pcov` in the module list; the file list now shows `src/FastyBird/**/src/*.php` entries and **no** `tests/cases` entries. This is the same command as Task 3 Step 3, with the opposite expected result.

- [ ] **Step 4: Prove the change does not turn the suite red** **(long, ~15 min)**

In PHPUnit 10.5 the `<source>` block also drives deprecation/notice attribution, but `restrictDeprecations`, `restrictNotices` and `restrictWarnings` all default to `false` and none is set here, so `<source>` currently affects the coverage filter only. Prove it rather than reasoning about it, using the loopback idiom from Global Constraints:

```bash
docker compose up -d database
docker run -d --name fb-loopback-redis --network container:fastybird-database redis:7
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make tests"
```

Expected: `OK (1405 tests, 5663 assertions)`, exit 0. **Watch the assertion count** — a database-less run also reports 1405 tests.

- [ ] **Step 5: Commit**

```bash
git add tools/phpunit.xml docker/dev/php/Dockerfile .github/workflows/ci-tests.yaml
git commit -m "fix(infra): point the phpunit coverage filter at src instead of the tests"
```

---

## Task 6: Fix the QA configs that fail open

**Files:**
- Modify: `Makefile`
- Modify: `tools/phpstan.neon`
- Modify: `tools/infection.json`

**Interfaces:**
- Consumes: Task 3 Step 4's proof that `make qa` exits 0 with a style violation present.
- Produces: a `make qa` that actually gates, a PHPStan config with no dead `excludePaths` entry and a pinned `phpVersion`, consumed by Task 31 (the PHP 8.3 bump reads the pin) and Task 35 (PHPStan 2 would hard-fail on the dead path).

- [ ] **Step 1: Make `make qa` sequential**

`Makefile:13-14` currently runs `make cs & make phpstan`, so the recipe's exit status is PHPStan's alone and a failing `make cs` leaves `make qa` green — with its output interleaved so the failure is easy to miss visually too. Replace with two recipe lines (make stops on the first non-zero):

```make
qa: ## Check code quality - coding style and static analysis
	make cs
	make phpstan
```

Blast radius of the bug was local only — CI runs `php-cs` and `php-phpstan` as separate jobs and never invokes `make qa` — but `make qa` is the target a maintainer runs before pushing, so it is precisely the pre-push gate that does not gate.

- [ ] **Step 2: Delete the dead `excludePaths` entry and pin `phpVersion`**

`tools/phpstan.neon:71` lists `../src/FastyBird/Core/Tools/src/Helpers/LoopWrapper.php`, which does not exist anywhere in the tree — the original was `src/FastyBird/Library/Bootstrap/src/Helpers/LoopWrapper.php`, deleted upstream in `e5d68011`, and commit `4aba64ca` carried the rename into the merged config without noticing the file was gone. PHPStan 1.x tolerates it; PHPStan 2.0's UPGRADING.md is explicit that paths in `excludePaths` must be a valid path or an fnmatch pattern, so under 2.x it is a hard failure. Delete line 71 and **keep line 70** (`Core/Application/src/EventLoop/Wrapper.php` is real, 158 lines).

In the same file, and in `tools/phpstan.tests.neon`, add under `parameters:`:

```neon
    phpVersion: 80200
```

This is a no-op today and that is the point: neither config sets `phpVersion`, so static analysis silently follows whatever PHP the runner has, and PHPStan's result would shift under Task 31's runtime bump with no way to attribute the delta. Pin it now at the current version; Task 31 changes it to `80300` as one reviewable line.

- [ ] **Step 3: Fix the Infection log paths**

Infection resolves log paths relative to the config file's directory (`ConfigurationFactory::pathToAbsolute`), so `tools/infection.json:9-10`'s `var/tools/Coverage/mutations/...` writes into `tools/var/tools/Coverage/mutations/` — a path `git check-ignore` confirms is *not* ignored (`var/tools/.gitignore` only covers the repository-root `var/`). Line 15's `tmpDir` correctly uses `../var/tools/Infection`. Prefix both log lines with `../`.

- [ ] **Step 4: Switch the Makefile docker targets to Compose v2**

`Makefile:77-87` invokes `docker-compose` (hyphenated). Compose v1 is retired and absent from current Docker Desktop and Engine installs, while every other reference in the repository — `docs/deployment.md`, the compose file headers — uses `docker compose`. These are the first commands a new contributor runs. Replace all four.

- [ ] **Step 5: Verify all four fixes**

```bash
docker compose run --rm --no-deps application sh -lc \
  'printf "<?php\nclass   Bad {}\n" > src/FastyBird/Core/Tools/src/ZzzTmp.php; \
   make qa; echo "EXIT=$?"; rm -f src/FastyBird/Core/Tools/src/ZzzTmp.php'
docker compose run --rm --no-deps application sh -lc 'make phpstan'
grep -n "LoopWrapper" tools/phpstan.neon || echo NO_LOOPWRAPPER
grep -n '"../var/tools/Coverage/mutations' tools/infection.json
grep -c "docker-compose" Makefile || echo NO_V1
```

Expected, in order: `EXIT=2` (or any non-zero) with PHPCS errors visible; `make phpstan` exits 0 with `[OK] No errors` for both configs; `NO_LOOPWRAPPER`; two matching lines with the `../` prefix; `NO_V1`.

- [ ] **Step 6: Commit**

```bash
git add Makefile tools/phpstan.neon tools/infection.json
git commit -m "fix(infra): make qa fail on a coding-standard error and drop the dead phpstan path"
```

---

## Task 7: Fix the Docker context and the misleading opcache comment

**Files:**
- Modify: `.dockerignore`
- Modify: `docker/prod/Dockerfile` (comment only)

**Interfaces:**
- Consumes: nothing.
- Produces: a build context that excludes nested `node_modules`, consumed by Task 22 (pnpm creates a `node_modules` in every one of the 15 workspace packages by design, as symlink farms pointing into `<root>/node_modules/.pnpm`).

- [ ] **Step 1: Exclude nested `node_modules` and the pnpm store**

`.dockerignore:2` is `node_modules`, which in Docker matches only the context root. The ten nested `node_modules` under `src/FastyBird/**` are already being copied into both the production and every dev build context today. pnpm makes it strictly worse — 15 symlink farms — and copying a symlink farm into a context and then running an install on top of it is a good way to get a partially-broken tree or a very slow build.

```
**/node_modules
.pnpm-store
```

- [ ] **Step 2: Fix the opcache comment, which says the opposite of the truth**

`docker/prod/Dockerfile:53-59` claims opcache is "already active by default, kept here only to make the dependency explicit". That contradicts the sentence directly above it, which correctly explains that asking `docker-php-ext-install` to rebuild a built-in extension fails with "Cannot find config.m4". Since the Docker Build job is green, `docker-php-ext-install opcache` succeeded — which means opcache is **not** built into `php:8.2-fpm` and that line is load-bearing. `docker/prod/php/opcache.ini` (`opcache.enable=1`, `jit=1255`, a 256M JIT buffer) does nothing without it. Rewrite the comment to say: opcache is not built in, the install line is required, and `opcache.ini` is inert without it — the current wording invites a future reader to delete the line as redundant and silently lose opcache and JIT on an embedded target.

- [ ] **Step 3: Verify the production image still builds and still has opcache**

```bash
docker build -t fb-p6-ctx -f docker/prod/Dockerfile .
docker run --rm --entrypoint php fb-p6-ctx -m | grep -i -E 'zend opcache|opcache'
docker run --rm --entrypoint composer fb-p6-ctx check-platform-reqs
```

Expected: the build succeeds and stage 1 still produces `public/`; `php -m` lists `Zend OPcache`; `check-platform-reqs` prints every requirement as `success`.

- [ ] **Step 4: Commit**

```bash
git add .dockerignore docker/prod/Dockerfile
git commit -m "fix(infra): exclude nested node_modules from the build context"
```

---

## Task 8: Declare the dependencies yarn only supplies by hoisting

**Reduced.** `docs/superpowers/plans/2026-09-11-webui-library-removal.md` (Task 11
of that plan) deleted `src/FastyBird/Library/WebUi` entirely, so the four
WebUi-specific manifests below no longer exist and Steps 2–4 (the
`@fastybird/web-ui-theme-chalk` entry import and the four WebUi packages'
undeclared dependencies) are moot — `Core/Application/assets/main.ts` no
longer imports `@fastybird/web-ui-theme-chalk/src/index.scss` either. Only
Step 1 (root tooling: `@types/lodash`, `stylelint-config-recommended-scss`,
`stylelint-scss`) still applies. This is part of the same reduction noted
under Track C and Track E below (nine workspaces to about four).

**Files:**
- Modify: `package.json`
- ~~Modify: `src/FastyBird/Library/WebUi/packages/utils/package.json`~~ (deleted)
- ~~Modify: `src/FastyBird/Library/WebUi/packages/theme-chalk/package.json`~~ (deleted)
- ~~Modify: `src/FastyBird/Library/WebUi/web-ui-library/package.json`~~ (deleted)
- ~~Modify: `src/FastyBird/Library/WebUi/docs/package.json`~~ (deleted)
- Modify: `yarn.lock` (regenerated)

**Interfaces:**
- Consumes: Task 3 Step 6's proof of which gates fail under pnpm without these declarations.
- Produces: a manifest set where every imported package is declared by the workspace that imports it, consumed by PR16 — this is the single largest de-risking step for the migration.

Four distinct classes, all confirmed by reading manifests and grepping every bare import specifier in every workspace package. Under yarn 1's flat hoist every one of these is a no-op; under pnpm's isolated linker every one is a hard failure with an error that looks like a config bug rather than a missing dependency.

- [ ] **Step 1: Root tooling — three packages the root config names but never declares**

`stylelint.config.mjs:2` extends `stylelint-config-recommended-scss` and `:4` loads plugin `stylelint-scss`. Neither is in root `package.json` — the first is hoisted from `packages/theme-chalk`'s devDependencies, the second from `stylelint-config-recommended-scss`'s own tree. stylelint resolves `extends`/`plugins` relative to the config file, i.e. the repository root. `tsconfig.json:33` lists `"@types/lodash"` in `types`; the root declares `@types/lodash.capitalize`, `.defaultsdeep`, `.get`, `.isequal`, `.omit` and `@types/md5`, but not `@types/lodash` — it is hoisted from `packages/utils`, `packages/components` and `web-ui-library`.

Add to root `devDependencies`, using the versions already resolved so the lockfile does not move:

```json
    "@types/lodash": "^4.17",
    "stylelint-config-recommended-scss": "^14.0",
    "stylelint-scss": "^6.4",
```

Do **not** blindly add `postcss-html` (the peer of `stylelint-config-recommended-vue`): it is genuinely absent from `node_modules` today, which is evidence that config path is unused — and Task 11 removes `stylelint-config-recommended-vue` entirely.

- [ ] **Step 2: ~~The application entry import that resolves nowhere under pnpm~~ — no longer applicable**

`Core/Application/assets/main.ts` no longer imports `@fastybird/web-ui-theme-chalk/src/index.scss`; the WebUi removal replaced it. See the note under this task's heading.

- [ ] **Step 3: ~~The four `Library/WebUi` builds that import what they never declared~~ — no longer applicable**

`packages/utils`, `packages/theme-chalk`, `web-ui-library` and `docs` no longer exist. See the note under this task's heading.

- [ ] **Step 4: ~~Remove the duplicate declaration in `web-ui-library`~~ — no longer applicable**

`web-ui-library/package.json` no longer exists. See the note under this task's heading.

- [ ] **Step 5: Reinstall and prove no resolved version moved** **(long)**

This is the check that keeps this pull request structural rather than a version change.

```bash
cp yarn.lock /tmp/yarn.lock.before
docker compose run -d --no-deps --name fb-p6-decl ui-server sh -lc \
  'yarn install --ignore-engines 2>&1 | tail -20'
docker wait fb-p6-decl; docker logs --tail 20 fb-p6-decl; docker rm fb-p6-decl
diff <(grep -E '^  version ' /tmp/yarn.lock.before | sort | uniq -c | sort -rn) \
     <(grep -E '^  version ' yarn.lock | sort | uniq -c | sort -rn) && echo NO_VERSION_DRIFT
```

Expected: `NO_VERSION_DRIFT`. `yarn.lock` gains new *keys* (the newly declared specifiers now have their own entries) but no `version` line for a pre-existing package changes. If a version did move, back out that one declaration and pin it to the already-installed version instead.

- [ ] **Step 6: Run every gate**

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  'yarn lint:js && yarn lint:styles && yarn types && yarn build && yarn pretty:check'
```

Expected: exit 0 for all five. (`yarn build:ui` and its `Library/WebUi/packages/icons` index-file churn no longer apply — that script and the directory it touched are gone.)

- [ ] **Step 7: Commit**

```bash
git add package.json yarn.lock
git commit -m "fix(ui): declare the packages that only yarn hoisting supplies"
```

---

## Task 9: Extend the CI gates to the three unwatched surfaces

**Files:**
- Modify: `.github/workflows/ci-tests.yaml`

**Interfaces:**
- Consumes: Task 8's `@storybook/theming` declaration (without it a docs build cannot be green).
- Produces: CI coverage of the docs workspace, the five `Library/WebUi` package lint scripts, `composer validate` and `composer audit`, consumed by Tasks 36, 37 and 39 — every one of which changes code that is invisible to CI today.

Dependabot pull requests 341 and 342 each passed all nine checks while being clearly broken, because no CI job touches the docs workspace. Task 1 had to lint three packages by hand for PR340/343 and check formatting by hand for PR338, for the same reason. Close all three holes before any framework bump.

- [ ] **Step 1: Add a `docs-build` job**

Modelled on the existing `js-build` job (checkout, setup-node with `node-version: "20"` and `cache: "yarn"`, `yarn install --frozen-lockfile --ignore-engines`), then:

```yaml
      - name: "Build the Library/WebUi packages (Storybook consumes their dist)"
        run: "yarn build:ui"

      - name: "yarn workspace @fastybird/web-ui-docs build"
        run: "yarn workspace @fastybird/web-ui-docs build"
```

- [ ] **Step 2: Extend `js-lint` to the five build packages**

Root `lint:js` globs only `src/FastyBird/*/*/assets` and root `tsconfig.json`'s `include` uses the same glob, leaving all of `src/FastyBird/Library/WebUi/packages/*/src` unlinted and untype-checked. Add to the `js-lint` job, after `yarn lint:js`:

```yaml
      - name: "Lint the Library/WebUi packages"
        run: |
          yarn workspace @fastybird/web-ui-utils lint:js
          yarn workspace @fastybird/web-ui-icons lint:js
          yarn workspace @fastybird/web-ui-components lint:js
          yarn workspace @fastybird/web-ui-library lint:js
```

- [ ] **Step 3: Add a `composer-validate` job and a non-blocking `composer audit` job**

`make composer-validate` exists in the `Makefile` with a careful explanation of why it drops `--strict`, and nothing calls it. Add a job that runs it. Add a second job running `composer audit --locked` with `continue-on-error: true` — Composer 2.4 has no `--ignore-severity` (2.8.0) and no `audit.ignore` config (2.9.2), so the command is all-or-nothing with no allowlist and cannot be a hard gate until Task 26 raises the Composer version. It also needs network access to the packagist advisories API.

Skip `yarn audit` for now: yarn 1's exit code is a severity bitmask (1/2/4/8/16), so a naive `run:` fails on informational advisories and would be red permanently.

- [ ] **Step 4: Verify the new jobs pass on a branch before merging**

```bash
git push -u origin ci/phase6-gates
gh pr create --fill --repo FastyBird/miniserver
gh pr checks --watch --repo FastyBird/miniserver
```

Expected: eleven checks, all green except `composer audit` which may be yellow (`continue-on-error`). **If `docs-build` fails, do not weaken the job** — the docs workspace is genuinely broken and that is the finding; fix it in this pull request.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/ci-tests.yaml
git commit -m "ci(ui): gate the storybook docs workspace and the WebUi package lint scripts"
```

---

## Task 10: Rewrite the six jsona mappers to import from the package root

**Files:**
- Modify: `src/FastyBird/Module/Accounts/assets/jsonapi/JsonApiModelPropertiesMapper.ts`
- Modify: `src/FastyBird/Module/Accounts/assets/jsonapi/JsonApiJsonPropertiesMapper.ts`
- Modify: `src/FastyBird/Module/Devices/assets/jsonapi/JsonApiModelPropertiesMapper.ts`
- Modify: `src/FastyBird/Module/Devices/assets/jsonapi/JsonApiJsonPropertiesMapper.ts`
- Modify: `src/FastyBird/Module/Ui/assets/jsonapi/JsonApiModelPropertiesMapper.ts`
- Modify: `src/FastyBird/Module/Ui/assets/jsonapi/JsonApiJsonPropertiesMapper.ts`

**Interfaces:**
- Consumes: nothing.
- Produces: a source tree with no value import from `jsona/lib/*`, consumed by Task 16's one-line constraint bump.

Verified against the **installed** jsona 1.12.1: its `lib/index.d.ts` re-exports `ModelPropertiesMapper` and `JsonPropertiesMapper` from the package root already, so this change works on the current pin and is a pure structural refactor. Only `RELATIONSHIP_NAMES_PROP` — `export declare const RELATIONSHIP_NAMES_PROP = "relationshipNames"` — has no path off the subpath, and it is a string literal.

- [ ] **Step 1: Rewrite the six import lines**

In each of the six files, replace:

```ts
import { ModelPropertiesMapper, RELATIONSHIP_NAMES_PROP } from 'jsona/lib/simplePropertyMappers';
```

(or the `JsonPropertiesMapper` variant) with:

```ts
import { ModelPropertiesMapper } from 'jsona';
```

and add, once per module, a shared local constant. Put it in a new `src/FastyBird/Module/<Name>/assets/jsonapi/constants.ts`:

```ts
// jsona does not export this from its package root, only from the
// jsona/lib/simplePropertyMappers subpath, which 1.13's exports map blocks.
// It is a stable string literal, so declaring it locally removes the only
// reason this repository could not move off jsona ~1.12.
export const RELATIONSHIP_NAMES_PROP = 'relationshipNames';
```

and import it in the two mapper files of that module.

Leave the 28 `from 'jsona/lib/JsonaTypes'` type imports untouched: every one of the nine names is a pure interface or type alias, jsona 1.14 ships `JsonaTypes.d.ts` with no runtime module, and `tsconfig.json:9`'s `"moduleResolution": "node"` ignores `exports` maps entirely.

- [ ] **Step 2: Verify no value import from a jsona subpath remains**

```bash
grep -rn "jsona/lib/simplePropertyMappers" src/FastyBird --include="*.ts" || echo NO_SUBPATH_VALUE_IMPORTS
grep -rc "jsona/lib/JsonaTypes" src/FastyBird --include="*.ts" | awk -F: '{s+=$2} END {print s" type imports remain"}'
```

Expected: `NO_SUBPATH_VALUE_IMPORTS`, then `28 type imports remain`.

- [ ] **Step 3: Build and type-check against the still-pinned 1.12.1**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'yarn build:ui && yarn types && yarn build'
```

Expected: exit 0 for all three. This proves the refactor is version-neutral.

- [ ] **Step 4: Commit**

```bash
git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts
git add src/FastyBird/Module
git commit -m "module(cross): import the jsona mappers from the package root"
```

---

## Task 11: Delete the dead frontend devDependencies

**Reduced, and reconciled with `docs/superpowers/plans/2026-09-11-webui-library-removal.md`'s
own Task 11**, which deleted `src/FastyBird/Library/WebUi` entirely — including
the four package manifests this task would have edited — as part of removing
the library outright rather than pruning its dead devDependencies in place.
That plan's Task 11 is the one that actually executed; the work described
below now applies to the root manifests only. Do not redo the WebUi-scoped
half of Steps 1 and 2.

**Files:**
- Modify: `package.json`
- Modify: `tsconfig.json`
- ~~Modify: `src/FastyBird/Library/WebUi/packages/theme-chalk/package.json`~~ (deleted)
- ~~Modify: `src/FastyBird/Library/WebUi/packages/utils/package.json`~~ (deleted)
- ~~Modify: `src/FastyBird/Library/WebUi/packages/components/package.json`~~ (deleted)
- ~~Modify: `src/FastyBird/Library/WebUi/web-ui-library/package.json`~~ (deleted)
- Modify: `yarn.lock`

**Interfaces:**
- Consumes: Task 8 (the "what is actually used" question has already been answered once).
- Produces: a smaller dependency surface for every upgrade sweep below, and — via `stylelint-config-recommended-vue` — the removal of one of the two `--ignore-engines` causes.

- [ ] **Step 1: Remove the eleven unreferenced root devDependencies**

Each verified by grepping source, configs and scripts for zero hits: `babel-loader` (9.2.1, no webpack in the tree), `vue-loader`, `sass-loader`, `vite-plugin-eslint` (1.8.1, last published 2022 — `vite.config.ts` uses `@nabla/vite-plugin-eslint` instead), `@iconify/iconify` (3.1.1, DEPRECATED on npm), `@iconify/vue`, `minimist`, `cross-env`, `dotenv`, `stylelint-config-prettier` (obsolete since stylelint 15 removed stylistic rules, and not extended by `stylelint.config.mjs`), and `vite-plugin-vue-type-imports` (0.2.5, 2023, not in the plugins array, declares peer `vite ^3.0.0 || ^4.0.0` which Vite 5 already violates).

`vite-plugin-vue-type-imports` must be removed **together with** its entry in `tsconfig.json`'s `types` array — it is the only one of the eleven that would surface as a compiler error if the package were simply deleted.

~~Also remove `vue-loader` from `packages/utils`, `packages/components` and `web-ui-library`, and `stylelint-config-prettier` and `sass-loader` from `theme-chalk`.~~ No longer applicable — those manifests are deleted.

`stylelint-config-standard`, `stylelint-config-standard-scss`, `stylelint-order` and `postcss-scss` are also not extended by `stylelint.config.mjs` — remove them too, but verify with a `lint:styles` output diff in Step 3 rather than on inspection alone.

- [ ] **Step 2: Remove `stylelint-config-recommended-vue` from the root** (the `theme-chalk` half is gone with the deleted manifest)

This is the stylelint half of the `--ignore-engines` problem. It is declared in root `package.json`, extended by no config (`stylelint.config.mjs` is the only stylelint config in the repository and extends only `stylelint-config-recommended-scss` and `stylelint-prettier/recommended`), and `lint:styles` globs `'src/FastyBird/*/*/assets/**/*.scss'`, so the Vue/HTML-embedded-CSS config it provides could not be exercised even if it were extended. Deleting the declaration removes `stylelint-config-html@2.0.0` (`engines.node: "^22.12 || >=24"`) from the tree entirely.

- [ ] **Step 3: Diff `lint:styles` output before and after**

```bash
git stash push -u -m "p6-deadweight-$(date +%s)"
docker compose run --rm --no-deps ui-server sh -lc 'yarn lint:styles' > /tmp/styles.before 2>&1
STASH=$(git stash list --format='%H %gs' | grep p6-deadweight | head -1 | cut -d' ' -f1)
git stash apply "$STASH"
docker compose run -d --no-deps --name fb-p6-dead ui-server sh -lc 'yarn install --ignore-engines'
docker wait fb-p6-dead; docker rm fb-p6-dead
docker compose run --rm --no-deps ui-server sh -lc 'yarn lint:styles' > /tmp/styles.after 2>&1
diff /tmp/styles.before /tmp/styles.after && echo IDENTICAL
```

Expected: `IDENTICAL`. (Per the environment rules, use `git stash push -u -m <tag>` and `git stash apply <sha>`, never bare `git stash`/`git stash pop` — the stash stack is shared across worktrees. Drop the entry afterwards by re-finding it by tag.)

- [ ] **Step 4: Confirm `stylelint-config-html` left the tree**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'ls node_modules/stylelint-config-html 2>&1' || echo GONE
grep -c "stylelint-config-html" yarn.lock || echo NOT_IN_LOCK
```

Expected: `GONE` and `NOT_IN_LOCK`. That leaves `@intlify/shared`/`@intlify/message-compiler` as the sole remaining `--ignore-engines` cause, which Task 14 addresses.

- [ ] **Step 5: Run every gate, then commit**

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  'yarn lint:js && yarn lint:styles && yarn types && yarn build'
git add package.json tsconfig.json yarn.lock
git commit -m "chore(deps): drop the frontend devDependencies nothing references"
```

(`yarn build:ui` no longer exists — it orchestrated the now-deleted `Library/WebUi` workspaces.)

---

## Task 12: Make the PHP manifests honest

**Files:**
- Modify: `composer.json`
- Modify: `src/FastyBird/*/*/composer.json` (the ones declaring `symplify/vendor-patches` in `require`)
- Modify: `.github/workflows/ci-tests.yaml`
- Modify: `composer.lock`

**Interfaces:**
- Consumes: Task 3 Step 1's audit and outdated output.
- Produces: a `composer.lock` whose metadata matches the post-rename tree, consumed by every composer pull request below — without this, each one drags ~34 packages of support-URL churn into its diff.

- [ ] **Step 1: Declare `ext-pdo_mysql`**

The root declares 18 `ext-*` requirements and the production image installs exactly the right set — with one gap. `pdo_mysql` is the driver hardcoded in `config/defaults.neon:17` and in all 25 `tests/common.neon` files, it is installed in both Dockerfiles, and it is required by **zero** `composer.json` files. Consequence: `composer check-platform-reqs` — the CI step added specifically to catch this class of defect — cannot detect its removal, and CI's `setup-php` `extensions:` lists omit it too, so the PHP Tests job passes only because setup-php's default set happens to include it. Add `"ext-pdo_mysql": "*"` to the root `require`, and add `pdo_mysql` to the four `setup-php` `extensions:` lists.

- [ ] **Step 2: Move `symplify/vendor-patches` from `require` to `require-dev`**

`composer.json:83` declares it in `require`, and it is repeated in `require` across many path manifests. It is a development helper for generating vendor patches, has no runtime role, and ships into the production image. Its latest (12.1.2) requires `php >=8.3`, so it will also independently block a `composer update` on 8.2 the moment its constraint is widened.

- [ ] **Step 3: Fix one of the two `composer validate --strict` warnings**

`src/FastyBird/Connector/HomeKit/composer.json:57` pins `endroid/qr-code` to the exact version `"4.5"`. Change it to `"^4.5"` — this is the smaller of the two warnings and a one-line fix that does not move the resolved version (latest 4.x still satisfies; latest overall is 6.1.3, four majors ahead, and that migration is not in scope). The second warning, `mathsolver/mathsolver: "@dev"` at `src/FastyBird/Core/Tools/composer.json:40`, is left alone deliberately and handed to deferred track T3, because fixing it means deciding the package's fate.

- [ ] **Step 4: Refresh the lock metadata**

`main`'s `composer.lock` still carries pre-rename path-package metadata — support URLs pointing at `github.com/FastyBird/fastybird/issues` and the old per-package repositories — while every `src/FastyBird/*/*/composer.json` already says `miniserver`.

```bash
docker compose run --rm --no-deps application sh -lc 'composer update --lock'
git diff --stat composer.lock
grep -c 'FastyBird/fastybird' composer.lock || echo NO_STALE_URLS
```

Expected: a large `composer.lock` diff consisting only of `support` URLs and `reference` fields for the 34 path packages, and `NO_STALE_URLS`. **No `"version"` field for a remote package may change** — verify:

```bash
docker compose run --rm --no-deps application sh -lc \
  "php -r '\$a=json_decode(file_get_contents(\"composer.lock\"),true); foreach(array_merge(\$a[\"packages\"],\$a[\"packages-dev\"]) as \$p){echo \$p[\"name\"],\" \",\$p[\"version\"],\"\n\";}'" | sort > /tmp/lock.after
git show HEAD:composer.lock > /tmp/lock.before.json
diff <(sort /tmp/lock.after) <(sort /tmp/lock.after) && echo VERSIONS_UNCHANGED
```

- [ ] **Step 5: Verify**

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer validate && make composer-validate && composer check-platform-reqs'
docker build -t fb-p6-manifest -f docker/prod/Dockerfile . \
  && docker run --rm --entrypoint composer fb-p6-manifest check-platform-reqs | grep -i pdo_mysql
```

Expected: `composer validate` prints `./composer.json is valid` (one `--strict` warning remains, the `mathsolver` one); `check-platform-reqs` now lists `ext-pdo_mysql ... success` — proving the guardrail covers the database driver instead of relying on setup-php defaults.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock src/FastyBird .github/workflows/ci-tests.yaml
git commit -m "fix(deps): declare ext-pdo_mysql and move symplify/vendor-patches to require-dev"
```

---

## Task 13: Consolidate the 25 `tests/common.neon` DBAL connection blocks

**Files:**
- Create: `tests/config/dbal-test-connection.neon`
- Modify: 25 × `src/FastyBird/*/*/tests/common.neon`
- Modify: `.github/workflows/ci-tests.yaml` (delete the 20-line apology comment above `php-tests`)

**Interfaces:**
- Consumes: nothing.
- Produces: one shared connection definition, consumed by deferred track T5 (`database.version: 5.7`), which becomes a one-file change instead of a 26-file change.

The premise is verified. All 25 `tests/tools/ConnectionWrapper.php` files are byte-identical once the `namespace` line is normalised (25 identical md5s). The class does `unset($params['dbname'])` before `parent::__construct()`, then in `connect()` issues DROP/CREATE/USE for `fb_test_<pid><md5(time)>` (plus a `TEST_TOKEN` suffix under paratest) and registers a shutdown DROP — so `dbname: testdb` is dead weight in all 25 files. `host`, `port`, `user` and `password` are **not** inert: creating and dropping databases needs them, which is why CI must keep MariaDB root credentials on the job's own loopback. 24 of the 25 `nettrineDbal.connection` blocks are byte-identical apart from `wrapperClass`; the 25th, `src/FastyBird/Module/Accounts/tests/common.neon:16-22`, already uses the better pattern (a local `parameters: database: {...}` block referenced as `%database.host%`).

- [ ] **Step 1: Create the shared file — at this exact path, not `tests/config/common.neon`**

`tools/phpunit-bootstrap.php` defines `FB_CONFIG_DIR` as `<repo>/tests/config`, which does not exist today and is skipped harmlessly by `Bootstrap::resolveConfigFiles()`. Creating `tests/config/common.neon` under that exact name would suddenly make it load for every test. `tests/config/dbal-test-connection.neon` is safe.

Give it the Accounts-style `parameters: database:` block plus the shared `nettrineDbal.connection` keys, omitting `dbname` (inert) and omitting `wrapperClass` (per-package).

- [ ] **Step 2: Replace the block in all 25 files**

Nette resolves `includes:` relative to the including file, and every package sits at exactly `src/FastyBird/<Type>/<Name>/tests/`, so one uniform path reaches the repository `tests/` directory from all 25:

```neon
includes:
    - ../../../../../tests/config/dbal-test-connection.neon

nettrineDbal:
    connection:
        wrapperClass: FastyBird\Module\Devices\Tests\Tools\ConnectionWrapper
```

Nette merges and the local value wins, so each file keeps only its own `wrapperClass:` line after the include.

**Leave the 25 `ConnectionWrapper` classes alone.** Each is bound to its package's `FastyBird\<...>\Tests\Tools\` PSR-4 prefix in both the root and the per-package `composer.json`; a single shared wrapper needs a new `autoload-dev` entry in the root manifest, which is a larger change than this task's value justifies.

Also note: the repository `tests/` directory is `export-ignore` in `.gitattributes`, so anything placed there vanishes from source archives. Harmless for tests — do not put anything a runtime path needs there.

- [ ] **Step 3: Run the suite** **(long, ~15 min)**

```bash
docker compose up -d database
docker run -d --name fb-loopback-redis --network container:fastybird-database redis:7
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make tests"
```

Expected: `OK (1405 tests, 5663 assertions)`, exit 0.

- [ ] **Step 4: Prove the CI env block is now live, then delete the apology comment**

`Bootstrap::loadEnvParameters` maps `FB_APP_PARAMETER__DATABASE_HOST` to parameter `database.host`, and `Configurator::generateContainer` appends explicit static parameters **last** (`vendor/nette/bootstrap/src/Bootstrap/Configurator.php:328-329`), so an environment variable beats a NEON `parameters:` block. Adopting the Accounts pattern in the shared file should therefore make the previously-inert CI env block take effect:

```bash
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC -e FB_APP_PARAMETER__DATABASE_HOST=nonexistent.invalid \
  fastybird-application sh -lc "vendor/bin/phpunit -c tools/phpunit.xml --filter AccountsModule" 2>&1 | tail -5
```

Expected: the run now **fails** to resolve `nonexistent.invalid`, rather than silently succeeding against 127.0.0.1. That is the proof. Only then delete the 20-line comment block above the `php-tests` job (`ci-tests.yaml:84-102`) and replace it with a two-line note that the connection is defined once in `tests/config/dbal-test-connection.neon` and is overridable by `FB_APP_PARAMETER__DATABASE_*`.

- [ ] **Step 5: Commit**

```bash
git add tests/config src/FastyBird .github/workflows/ci-tests.yaml
git commit -m "cross(tests): define the DBAL test connection once instead of 25 times"
```

---

## Task 14: Pin `@intlify/shared` and `@intlify/message-compiler` to 11.4.2

**Files:**
- Modify: `package.json` (new `resolutions` block)
- Modify: `yarn.lock`

**Interfaces:**
- Consumes: Task 11 (the stylelint half of the problem is already gone).
- Produces: an install tree with no `engines.node >= 22` package, consumed by Task 15's flag removal.

Both packages bumped their declared floor from `>= 16` to `>= 22` at exactly 11.4.3. 11.4.2 still declares `>= 16` and still satisfies `@intlify/unplugin-vue-i18n@6.0.8`'s `^11.1.2` and `@intlify/bundle-utils@10.0.1`'s `^11.1.2`. This is a same-minor downgrade of a transitive dependency, not a functional change, and `resolutions` is a supported yarn 1 mechanism. The root `package.json` currently has no `resolutions` field at all.

- [ ] **Step 1: Add the resolutions block**

```json
  "resolutions": {
    "@intlify/shared": "11.4.2",
    "@intlify/message-compiler": "11.4.2"
  },
```

- [ ] **Step 2: Reinstall with no flag and confirm the install succeeds** **(long)**

The absence of `--ignore-engines` is the test.

```bash
docker compose run -d --no-deps --name fb-p6-intl ui-server sh -lc \
  'rm -rf node_modules && yarn install 2>&1 | tail -30; echo "EXIT=$?"'
docker wait fb-p6-intl; docker logs fb-p6-intl; docker rm fb-p6-intl
```

Expected: `EXIT=0` and **no** `The engine "node" is incompatible with this module` error. If the install still fails, read the offending package name from the error — the two-cause list from Task 4 was wrong and the record must be corrected again before proceeding.

- [ ] **Step 3: Confirm no `>= 22` package remains**

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  "node -e \"const fs=require('fs'),p=require('path');function walk(d){for(const e of fs.readdirSync(d,{withFileTypes:true})){const f=p.join(d,e.name);if(e.isDirectory()){walk(f)}else if(e.name==='package.json'){try{const j=JSON.parse(fs.readFileSync(f));const n=j.engines&&j.engines.node;if(n&&/2[2-9]/.test(n)&&!/\\^?20/.test(n))console.log(j.name,j.version,n)}catch(_){}}}};walk('/app/node_modules')\"" | sort -u
```

Expected: no output.

- [ ] **Step 4: Build and type-check**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'yarn build:ui && yarn types && yarn build'
```

Expected: exit 0. vue-i18n behaviour is unchanged — the pin is a same-minor step on a shared utility package.

- [ ] **Step 5: Commit**

```bash
git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts
git add package.json yarn.lock
git commit -m "fix(deps): pin @intlify/shared and @intlify/message-compiler to 11.4.2"
```

CI still passes `--ignore-engines` at this point, so this pull request is green either way — which is exactly what makes the next one diagnosable.

---

## Task 15: Drop `--ignore-engines` everywhere

**Files:**
- Modify: `docker/prod/Dockerfile:20`
- Modify: `.github/workflows/ci-tests.yaml` (lines 171, 191, 221, plus the new jobs from Task 9)
- Modify: `build/debian/make_deb.sh:25`
- Modify: `README.md:29,32`, `CLAUDE.md:43`, `docs/baseline.md` (Phase 6 addendum only)

**Interfaces:**
- Consumes: Task 14's proof that a flagless install succeeds.
- Produces: the retirement of the first of the two forced exceptions recorded in `docs/baseline.md`.

- [ ] **Step 1: Remove the flag from every install call site**

```bash
grep -rn -- "--ignore-engines" --include="*.yaml" --include="*.yml" --include="Dockerfile" \
  --include="*.sh" --include="*.md" . --exclude-dir=node_modules --exclude-dir=vendor \
  --exclude-dir=docs/superpowers
```

Expected before the edit: `docker/prod/Dockerfile:20`, `build/debian/make_deb.sh:25`, the `ci-tests.yaml` install steps (three original plus any added in Task 9), and the prose references. Remove the flag from every one; in `docs/baseline.md` leave the historical text alone and note the retirement in the Phase 6 addendum instead.

- [ ] **Step 2: Rewrite `CLAUDE.md:43`**

Replace the "`yarn install` always needs `--ignore-engines`" paragraph with a one-line note that the exception was retired in Phase 6 by pinning `@intlify/shared`/`@intlify/message-compiler` to 11.4.2 and removing the unused `stylelint-config-recommended-vue`, and that the `resolutions` block must not be removed without re-checking `engines.node` across the tree.

- [ ] **Step 3: Verify the flag is gone and everything still installs**

```bash
grep -rn -- "--ignore-engines" . --exclude-dir=node_modules --exclude-dir=vendor \
  --exclude-dir=.git --exclude-dir=docs/superpowers | grep -v "docs/baseline.md" || echo FLAG_GONE
docker build -t fb-p6-noflag -f docker/prod/Dockerfile . && echo PROD_BUILD_OK
```

Expected: `FLAG_GONE`, then `PROD_BUILD_OK` with `public/` produced by stage 1.

- [ ] **Step 4: Commit**

```bash
git add docker/prod/Dockerfile .github/workflows/ci-tests.yaml build/debian/make_deb.sh \
  README.md CLAUDE.md docs/baseline.md
git commit -m "ci(ui): drop --ignore-engines now that no installed package requires node 22"
```

---

## Task 16: Lift the jsona pin to `^1.14`

**Files:**
- Modify: `src/FastyBird/Module/Accounts/package.json`
- Modify: `src/FastyBird/Module/Devices/package.json`
- Modify: `src/FastyBird/Module/Ui/package.json`
- Modify: `yarn.lock`

**Interfaces:**
- Consumes: Task 10's source rewrite.
- Produces: the retirement of the second forced exception.

- [ ] **Step 1: Change three constraints**

`"jsona": "~1.12.0"` → `"jsona": "^1.14"` in all three manifests.

- [ ] **Step 2: Reinstall, type-check and build** **(long)**

```bash
docker compose run -d --no-deps --name fb-p6-jsona ui-server sh -lc \
  'yarn install 2>&1 | tail -10 && yarn build:ui && yarn types && yarn build; echo "EXIT=$?"'
docker wait fb-p6-jsona; docker logs --tail 40 fb-p6-jsona; docker rm fb-p6-jsona
```

Expected: `EXIT=0`. The 28 `jsona/lib/JsonaTypes` type imports still resolve because `tsconfig.json`'s `"moduleResolution": "node"` ignores `exports` maps and jsona 1.14 ships `JsonaTypes.d.ts` with no runtime module. **If `yarn types` fails on those imports, the exports-map analysis was wrong** and the correct fallback is to keep `^1.14` and rewrite the 28 imports to `from 'jsona'`, which requires checking that all nine type names are re-exported from the package root.

- [ ] **Step 3: Confirm the resolved version and remove the ignore rule**

```bash
grep -A1 '^jsona@' yarn.lock
```

Expected: `version "1.14.x"`. Then remove the `jsona` entry from `.github/dependabot.yml`'s npm `ignore` list (added in Task 2), since the pin no longer exists.

- [ ] **Step 4: Note the retirement and commit**

```bash
git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts
git add src/FastyBird/Module yarn.lock .github/dependabot.yml docs/baseline.md
git commit -m "fix(deps): lift the jsona pin to ^1.14"
```

---

## Task 17: Pin the unbounded npm specifiers

**Files:**
- Modify: `package.json` (`resolutions`)
- Modify: `yarn.lock`

**Interfaces:**
- Consumes: Task 14's `resolutions` block (this task extends it).
- Produces: a lockfile with no floating specifier, consumed by Task 21 — `pnpm import` inherits whatever these resolve to at import time.

Installs are deterministic today because `yarn.lock` pins everything, and all three CI jobs plus both Dockerfiles use `--frozen-lockfile`. The exposure is **regeneration**: any lockfile rewrite re-resolves these silently, with no range to constrain them and no diff-review signal that a major moved.

- [ ] **Step 1: Pin the three floating entries**

- `vue-component-type-helpers latest` (`yarn.lock:2209`) comes from `@storybook/vue3@8.6.18`'s own published manifest, so it cannot be fixed locally except by upgrading Storybook or adding a resolution. The lock key at `:10752` merges it with element-plus's `^3.3.9` and pins both to 3.3.11. It would de-dedupe the moment a v4 is published.
- `stylelint-config-recommended ">=6.0.0"` from `stylelint-config-recommended-vue@1.6.1` already produces two copies (14.0.1 and 18.0.0). Note: Task 11 removed `stylelint-config-recommended-vue`, so **re-check whether this entry still exists** before adding a resolution for it.

```json
  "resolutions": {
    "@intlify/shared": "11.4.2",
    "@intlify/message-compiler": "11.4.2",
    "vue-component-type-helpers": "3.3.11"
  },
```

- [ ] **Step 2: Confirm no floating specifier remains**

```bash
grep -cE '^\s+\S+ latest$' yarn.lock || echo NO_LATEST_TAGS
grep -nE '^\S+ ">=[0-9]' yarn.lock | head
```

Expected: `NO_LATEST_TAGS`, and no unbounded `>=` range keys (the two stylelint ones should have left with `stylelint-config-recommended-vue`). Eleven `@types/*@*` wildcards remain and are acceptable — they are `@types` packages resolved through their own peer graph.

- [ ] **Step 3: Reinstall, build, commit**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'yarn install && yarn build'
git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts
git add package.json yarn.lock
git commit -m "chore(deps): pin the specifiers that would re-resolve on any lockfile rewrite"
```

---

## Task 18: Widen the stale peer ranges in `Module/Ui` and `Module/Triggers`

> **MAINTAINER DECISION REQUIRED before this task runs.** This is a dependency change and the frozen-toolchain rule forbids it by default. If declined, skip this task; Task 22 then ships `auto-install-peers=false`, which suppresses the symptom rather than resolving it, and this task moves to the deferred list.

**Files:**
- Modify: `src/FastyBird/Module/Ui/package.json`
- Modify: `src/FastyBird/Module/Triggers/package.json`

**Interfaces:**
- Consumes: nothing.
- Produces: peer ranges that match what the root actually installs, consumed by Task 22 — under pnpm's default `auto-install-peers=true`, a mismatched peer materialises a *second* copy of the package inside that workspace's `node_modules`.

`Module/Triggers` has **no** `dependencies` block at all, only `peerDependencies`, pinning `vue-i18n ^9.9`, `pinia ^2.1`, `vue ^3.4`, `vue-router ^4.3`. `Module/Ui` pins `element-plus ^2.7`, `pinia ^2.1`, `vue-i18n ^9.13`, `vue ^3.4`, `vue-router ^4.3`. The root installs `vue-i18n ^10.0` (resolving 10.0.8), `element-plus ^2.8` (2.14.5), `pinia ^2.2`, `vue ^3.5` (3.5.42), `vue-router ^4.4` (4.6.4). Under yarn 1 these are unmet-peer warnings and everything shares the single hoisted copy; the code evidently works against vue-i18n 10 (one app-wide instance created in `Core/Application/assets/locales/index.ts` with `legacy: false`). This is manifest rot, but it makes the manifests unusable as a source of truth for scoping a vue-i18n bump.

- [ ] **Step 1: Widen both peer blocks to match the root**

`vue ^3.5`, `vue-i18n ^10.0`, `pinia ^2.2`, `element-plus ^2.8`, `vue-router ^4.4`.

- [ ] **Step 2: Confirm yarn stops warning**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'yarn install 2>&1' | grep -i "unmet peer" | grep -E "triggers|ui-module" || echo NO_PEER_WARNINGS
```

Expected: `NO_PEER_WARNINGS` for those two packages.

- [ ] **Step 3: Build, then commit**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'yarn build:ui && yarn types && yarn build'
git add src/FastyBird/Module/Ui/package.json src/FastyBird/Module/Triggers/package.json yarn.lock
git commit -m "module(cross): align the Ui and Triggers peer ranges with the installed set"
```

---

## Task 19: Decide and record the pnpm major

**Files:**
- Modify: `package.json` (`packageManager`)
- Modify: `CLAUDE.md`, `AGENTS.md`

**Interfaces:**
- Consumes: Task 3 Step 5's measured `pnpm config get link-workspace-packages` value.
- Produces: the pinned pnpm version, consumed by Tasks 20–23 and by both Dockerfiles.

- [ ] **Step 1: Pin pnpm 10**

pnpm 10 is the right target — Node 20 is supported — but it changes two defaults that both matter here, and each has a correct fix rather than a workaround:

- `link-workspace-packages` defaults to **false** in pnpm 10 (it was true through pnpm 9), so a workspace member is linked only when the dependency uses the `workspace:` protocol. Task 20 converts all 31 references, which makes the setting irrelevant on both majors.
- Dependency lifecycle scripts are **blocked** by default. Task 20 adds the allow-list.

Pinning pnpm 9 instead would mask both problems rather than fix them, and would have to be undone later.

```json
  "packageManager": "pnpm@10.x.y+sha512.<hash>"
```

Obtain the exact string:

```bash
docker run --rm node:20 sh -lc 'npm i -g pnpm@10 >/dev/null 2>&1 && pnpm --version'
docker run --rm node:20 sh -lc 'npm view pnpm@10 dist.integrity'
```

- [ ] **Step 2: Record it as a frozen-toolchain line**

Add pnpm to the `## Requirements` block of `CLAUDE.md` alongside PHP 8.2 and Node 20, replacing line 9's "pnpm arrives in Phase 6 of the merge — do not document or use pnpm before then". Do the same in `AGENTS.md`.

- [ ] **Step 3: Verify the string is accepted**

```bash
docker run --rm -v "$PWD":/app -w /app node:20 sh -lc \
  'npm i -g corepack@latest >/dev/null 2>&1 && corepack enable && pnpm --version'
```

Expected: the pinned version prints. If it fails with `Error: Cannot find matching keyid`, that is the known Node 20 corepack registry-signing bug — Task 23 avoids corepack in containers for exactly this reason.

---

## Task 20: Convert the workspace manifests

**Reduced.** `src/FastyBird/Library/WebUi` (three of the nine manifests and 10
of the 31 references below) is gone, and independent dependency cleanup that
landed alongside its removal has also changed some of the surviving
manifests' internal `@fastybird/*` references. **The file/line inventory and
counts in this task were computed against the pre-removal tree and are
stale — re-run Step 1's own verification grep against the current tree
before trusting any number here**, including the "9 × package.json" and "31
internal references" figures immediately below and the `pnpm-workspace.yaml`
glob list in Step 2. As of this note, `src/FastyBird/*/*` alone matches
every remaining workspace package with a `package.json`
(`Connector/HomeKit`, `Core/Application`, `Core/Tools`, `Library/Metadata`,
`Module/Accounts`, `Module/Devices`, `Module/Triggers`, `Module/Ui`) — none
of the other three globs below resolve to anything anymore.

**Files:**
- Modify: 9 × `package.json` (31 internal references) — **stale, see note above**
- Create: `pnpm-workspace.yaml`
- Create: `.npmrc`
- Modify: `package.json` (delete `workspaces`, add `pnpm.onlyBuiltDependencies`)
- Modify: `.gitignore`

**Interfaces:**
- Consumes: Task 19's pinned major.
- Produces: a manifest set pnpm can resolve without the registry, consumed by Task 21's lockfile import.

- [ ] **Step 1: Convert all 31 internal references to `workspace:*`**

All internal references use exact versions (`"0.0.0"` for the near-empty extensions, `"1.0.0-dev.24"` for the WebUi packages), and yarn 1 links a workspace member whenever its version satisfies the range — which is why this works today and why it cannot be done ahead of the migration (yarn 1 classic does not understand the `workspace:` protocol). Every version currently matches its workspace member exactly, so `workspace:*` is a safe drop-in.

Sites, by file and line (verified against the tree **before** the WebUi removal —
stale; re-run the Step 1 verify command below to regenerate this list against
the current tree rather than trusting these line numbers):

```
package.json:47,48,50,51                                              (4)
src/FastyBird/Connector/HomeKit/package.json:42,43,44,46,47            (5)
src/FastyBird/Core/Tools/package.json:33                               (1)
src/FastyBird/Module/Accounts/package.json:41,42,43,44                 (4)
src/FastyBird/Module/Devices/package.json:41,42,44,45                  (4)
src/FastyBird/Module/Ui/package.json:42,44,45                          (3)
~~src/FastyBird/Library/WebUi/packages/components/package.json:45,46,47~~  (deleted)
~~src/FastyBird/Library/WebUi/web-ui-library/package.json:43,54,55~~       (deleted)
~~src/FastyBird/Library/WebUi/docs/package.json:28,29,30~~                 (deleted)
```

~~Plus the `@fastybird/web-ui-theme-chalk` entry Task 8 added to root `dependencies` (1) = 31.~~
Task 8's Step 2 (which added that entry) no longer applies — see the note on Task 8.

**Do NOT convert `@fastybird/vue-wamp-v1`** (root:49, `Module/Ui`:43, `Module/Devices`:43, `Connector/HomeKit`:45). It is a genuine external npm package with no workspace member.

Verify:

```bash
grep -rn '"@fastybird/' --include=package.json package.json src/FastyBird --exclude-dir=node_modules \
  | grep -v '"name"' | grep -v 'workspace:\*' | grep -v 'vue-wamp-v1' || echo ALL_CONVERTED
```

Expected: `ALL_CONVERTED`.

- [ ] **Step 2: Create `pnpm-workspace.yaml` and delete the `workspaces` array**

**Reduced.** Three of the original four globs pointed into `src/FastyBird/Library/WebUi`,
which is deleted. Only the first glob still resolves to anything:

```yaml
packages:
  - "src/FastyBird/*/*"
```

If a second glob still proves necessary once Step 1 is re-verified against the
current tree (for example if something under `Library/` should be excluded or
re-included), add it then — do not carry the three dead `Library/WebUi` globs
forward. `src/FastyBird/*/*` matches every remaining workspace package with a
`package.json` (`Connector/HomeKit`, `Core/Application`, `Core/Tools`,
`Library/Metadata`, `Module/Accounts`, `Module/Devices`, `Module/Triggers`,
`Module/Ui`) plus a larger number of inert matches (extension directories and
`README.md` files with no `package.json`, which pnpm silently skips) — do not
narrow the glob, since that is what lets a future extension gain a UI without
touching the workspace file.

**Delete `package.json:26-31`'s `workspaces` array in the same commit.** Leaving it means a stray `npm install` or `yarn install` still half-works and produces a conflicting tree, and pnpm 9.12 already warns about it.

- [ ] **Step 3: Create `.npmrc`**

```
# pnpm's default; pinned explicitly so nobody "tidies it up" by turning it on.
# This is the --ignore-engines replacement: pnpm warns rather than errors on a
# dependency engines mismatch unless engine-strict is true.
engine-strict=false

# Prevents pnpm materialising a second vue-i18n / element-plus / vue inside
# Module/Ui and Module/Triggers from their peerDependencies ranges.
auto-install-peers=false

# The dev container shares /app with the macOS host over a bind mount. pnpm's
# default store lives outside the project, so hard-linking would be a
# cross-device link and pnpm silently degrades to copying.
store-dir=.pnpm-store
```

Add `.pnpm-store` to `.gitignore` (`.dockerignore` already has it from Task 7).

- [ ] **Step 4: Add the build allow-list**

pnpm 10 does not run dependency `install`/`postinstall` scripts unless the package is allow-listed. The installed tree has six such packages, two of which matter:

- `esbuild` at 0.18.20, 0.20.2, 0.21.5, 0.23.1, 0.25.12 and 0.28.2 — `postinstall: node install.js` validates and places the platform binary. Skipping it is the well-known cause of `You installed esbuild for another platform` **at build time**, not install time.
- `vue-demi@0.14.10` — `postinstall` switches its entry between the Vue 2 and Vue 3 shims. `@vueuse/core` (used by `Core/Tools`, `Module/Ui`, `packages/utils`, `packages/components`, `web-ui-library`) depends on it.
- `@parcel/watcher@2.6.0`, `core-js@3.50.0`, `es5-ext@0.10.64` — lower stakes, included for completeness.

```json
  "pnpm": {
    "onlyBuiltDependencies": ["esbuild", "vue-demi", "@parcel/watcher", "core-js", "es5-ext"]
  }
```

- [ ] **Step 5: Verify the manifests parse and the workspace enumerates**

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  'npm i -g pnpm@10 >/dev/null 2>&1 && pnpm -r list --depth -1 2>&1 | head -25'
```

Expected: 15 workspace packages listed, no `The "workspaces" field in package.json is not supported by pnpm` warning.

---

## Task 21: Generate `pnpm-lock.yaml` by import, never by resolve

**Files:**
- Create: `pnpm-lock.yaml`
- Delete: `yarn.lock`

**Interfaces:**
- Consumes: Task 20's manifests, Task 17's pinned specifiers.
- Produces: a lockfile whose resolved version set is identical to `yarn.lock`'s — the single property that keeps PR16 free of version changes.

Nearly every dependency here is a caret range, and `yarn.lock` is the only thing pinning the 900+ package transitive tree that Phases 1–5 verified. Deleting it and running a bare `pnpm install` re-resolves every one of those ranges against today's registry — that is exactly the mechanism that produced both forced exceptions in the first place, and it would produce new ones.

- [ ] **Step 1: Import, then remove, then install frozen — in this order** **(long)**

```bash
docker compose run -d --no-deps --name fb-p6-import ui-server sh -lc \
  'npm i -g pnpm@10 >/dev/null 2>&1 && pnpm import 2>&1 | tail -10 && echo IMPORT_OK'
docker wait fb-p6-import; docker logs fb-p6-import; docker rm fb-p6-import
git rm yarn.lock
docker compose run -d --no-deps --name fb-p6-inst ui-server sh -lc \
  'rm -rf node_modules && pnpm install --frozen-lockfile 2>&1 | tail -40; echo "EXIT=$?"'
docker wait fb-p6-inst; docker logs fb-p6-inst; docker rm fb-p6-inst
```

Expected: `IMPORT_OK`, then `EXIT=0`. **Never run a bare `pnpm install` for the first install.**

- [ ] **Step 2: Prove no version moved**

```bash
git show HEAD:yarn.lock > /tmp/yarn.lock.orig
python3 - <<'PY'
import re, yaml
yarn = {}
name = None
for line in open('/tmp/yarn.lock.orig'):
    m = re.match(r'^"?([^@\s"][^@]*)@', line)
    if m and not line.startswith(' '):
        name = m.group(1)
    m2 = re.match(r'^  version "(.+)"', line)
    if m2 and name:
        yarn.setdefault(name, set()).add(m2.group(1))
lock = yaml.safe_load(open('pnpm-lock.yaml'))
pnpm = {}
for k in lock.get('packages', {}):
    n, _, v = k.rpartition('@')
    pnpm.setdefault(n.lstrip('/'), set()).add(v)
only_yarn = {k: v for k, v in yarn.items() if k in pnpm and yarn[k] != pnpm[k]}
print("DRIFTED:", len(only_yarn))
for k, v in list(only_yarn.items())[:20]:
    print(" ", k, "yarn", sorted(v), "pnpm", sorted(pnpm[k]))
PY
```

Expected: `DRIFTED: 0`. Any non-zero result means `pnpm import` did not preserve the tree and PR16 has become a version change — stop and investigate before proceeding.

- [ ] **Step 3: Check whether the ten deliberate nested duplicates survived**

`yarn.lock` has no record of which nested copies were deliberate. `Module/Accounts` carries its own `date-fns` and `uuid`, `Module/Devices` its own `uuid`, and four WebUi packages their own `@typescript-eslint`/`@vueuse`/`rimraf`/`esbuild`.

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  'pnpm why date-fns; pnpm why uuid; pnpm list --depth 0 --filter @fastybird/accounts-module'
```

Expected: both `date-fns` majors (3.6.0 and 4.4.0) and both `uuid` majors present, with `Module/Accounts` resolving its own. If the import flattened any of them, a version that was pinned by nesting is now pinned by nothing — record which, and add an explicit constraint rather than accepting the flatten.

- [ ] **Step 4: Expect an unreviewable lockfile diff, and say so in the pull request body**

`pnpm-lock.yaml` is a wholly new file replacing a wholly deleted one. The review signal is Step 2's `DRIFTED: 0`, not the diff.

---

## Task 22: Rewrite the 30 hardcoded `yarn` call sites

**Files:**
- Modify: `package.json` (6 scripts)
- Modify: 6 × `src/FastyBird/Library/WebUi/**/package.json` (13 intra-package script lines)
- Modify: `.husky/commit-msg`
- Modify: `.npmignore:9`
- Modify: `config/supervisor/system/application.conf`

**Interfaces:**
- Consumes: Task 20's workspace file.
- Produces: an executable script graph, consumed by Task 23's containers and CI.

- [ ] **Step 1: Rewrite the root scripts**

```json
    "build:ui": "pnpm --filter @fastybird/web-ui-utils run build && pnpm --filter @fastybird/web-ui-icons run build && pnpm --filter @fastybird/web-ui-theme-chalk run build && pnpm --filter @fastybird/web-ui-components run build && pnpm --filter @fastybird/web-ui-library run build",
    "build": "pnpm run build:ui && vue-tsc --noEmit && vite build",
    "pretty": "pnpm run pretty:write && pnpm run pretty:check",
    "storybook": "pnpm --filter @fastybird/web-ui-docs run dev",
```

**Keep `build:ui`'s explicit five-step chain** rather than switching to `pnpm --filter "@fastybird/web-ui-library..." run build`. The topological order pnpm would infer depends on `icons`/`components`/`utils` being devDependencies of `web-ui-library`, which is fragile and would silently reorder if a manifest changed.

- [ ] **Step 2: Rewrite the 13 intra-package `yarn <script>` calls to `pnpm run <script>`**

`packages/utils:32,34,38`; `packages/icons:56,59`; `packages/theme-chalk:32,35`; `packages/components:32,34,38`; `web-ui-library:32,37`; `docs:23`.

- [ ] **Step 3: Fix the husky hook — `pnpm exec`, not `pnpm`**

`.husky/commit-msg:1` becomes:

```sh
pnpm exec commitlint --edit "$1"
```

`pnpm commitlint` will **not** work: unlike yarn 1, pnpm does not fall back from an unknown command to `node_modules/.bin`.

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  'echo "bad message" | pnpm exec commitlint; echo "BAD=$?"; \
   echo "docs(cross): x" | pnpm exec commitlint; echo "GOOD=$?"'
```

Expected: `BAD=1` and `GOOD=0`.

- [ ] **Step 4: `.npmignore:9` — `yarn.lock` → `pnpm-lock.yaml`**

- [ ] **Step 5: Deal with the already-dead supervisor program**

`config/supervisor/system/application.conf:4` is `command = yarn workspace @fastybird/application dev`. This program cannot start today either — `@fastybird/application`'s `package.json` has no `scripts` block at all. Either delete the program or repoint line 4 at `pnpm dev` from the repository root. Deleting is the honest option; `config/supervisor/` is documented in `CLAUDE.md:16` as "a legacy, currently-unused layout".

- [ ] **Step 6: Confirm no executable `yarn` call site remains**

```bash
grep -rn '\byarn\b' --include="package.json" --include="*.sh" --include="*.yaml" --include="*.yml" \
  --include="Dockerfile" --include="commit-msg" . \
  --exclude-dir=node_modules --exclude-dir=vendor --exclude-dir=docs \
  | grep -v 'FabYarn' || echo NO_EXECUTABLE_YARN
```

Expected: `NO_EXECUTABLE_YARN`. (`src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts:643` exports `FabYarn`, a FontAwesome icon component — a false positive, do not touch it.)

---

## Task 23: Move the containers and CI onto pnpm

**Files:**
- Modify: `docker/prod/Dockerfile:20-21`
- Modify: `docker/dev/node/Dockerfile`
- Modify: `docker/dev/docker-compose.yml` (ui-server volumes)
- Modify: `build/debian/make_deb.sh:25-26`
- Modify: `.github/workflows/ci-tests.yaml` (three original JS jobs plus Task 9's additions)
- Modify: `README.md`, `CLAUDE.md`, `AGENTS.md`, `.github/PULL_REQUEST_TEMPLATE.md`, `docs/deployment.md`, `docker/prod/supervisor/supervisord.conf`

**Interfaces:**
- Consumes: Tasks 19–22.
- Produces: a green CI and a production image built with pnpm — the deliverable of PR16.

- [ ] **Step 1: Production Dockerfile**

```dockerfile
FROM node:20 AS ui
WORKDIR /app
RUN npm i -g pnpm@<pinned>
COPY . .
RUN pnpm install --frozen-lockfile && pnpm build
```

Prefer `npm i -g pnpm@<pinned>` over corepack in containers, to sidestep the Node 20 `Cannot find matching keyid` registry-signing bug (corepack versions bundled with Node 20 before ~20.19 fail when fetching a package manager).

- [ ] **Step 2: Dev node image and compose**

`docker/dev/node/Dockerfile` installs nothing today (Phase 1 removed the broken `RUN yarn install` on the reasoning that dependencies come from the mounted volume) and its CMD is `yarn dev`. yarn 1 happens to be preinstalled in the node images; pnpm is not. Add `RUN npm i -g pnpm@<pinned>` and change the CMD to `["pnpm", "dev"]`.

While here, fix the related dev-only defect: the compose service bind-mounts the whole repository with no anonymous volume shadowing `/app/node_modules`, so the container executes whatever native binaries the host installed and vice versa. This worktree's `node_modules` currently contains `@esbuild/linux-arm64` and `@rollup/rollup-linux-arm64-{gnu,musl}` — installed inside Linux — which means a host-side `yarn build` would fail on a missing platform binary right now. Add `- /app/node_modules` as an anonymous volume to the `ui-server` service.

- [ ] **Step 3: Debian packaging**

`build/debian/make_deb.sh:25-26` uses `sudo yarn install ...`. `sudo` plus a corepack shim means pnpm is not on root's PATH — either install pnpm globally as root or drop the `sudo`.

- [ ] **Step 4: CI — step order matters**

In each of the JS jobs (`js-lint`, `js-types`, `js-build`, plus Task 9's `docs-build`), insert `pnpm/action-setup@v4` **before** `actions/setup-node`, change `cache: "yarn"` to `cache: "pnpm"`, and swap the run commands. Getting the order wrong fails with `Unable to locate executable file: pnpm`.

- [ ] **Step 5: Rewrite the instruction-bearing prose in this same pull request**

`README.md:13,29,32`; `CLAUDE.md:9,35-40,43`; `AGENTS.md:7,13,16`; `.github/PULL_REQUEST_TEMPLATE.md:25,35,44`; `docs/deployment.md:63`; `docker/prod/supervisor/supervisord.conf:33-34`. Leaving these saying `yarn` between pull requests would actively mislead a contributor, which is why they belong here rather than in Task 24. `CONTRIBUTING.md` does not mention yarn at all — nothing to change there.

- [ ] **Step 6: Full verification**

```bash
docker compose build ui-server
docker compose run --rm --no-deps ui-server sh -lc \
  'pnpm install --frozen-lockfile && pnpm lint:js && pnpm lint:styles && pnpm build:ui \
   && pnpm types && pnpm build && pnpm pretty:check'
docker build -t fb-p6-pnpm-prod -f docker/prod/Dockerfile . && echo PROD_OK
```

Expected: every command exits 0; `PROD_OK`; stage 1 produces `public/index.html` and hashed assets.

- [ ] **Step 7: Prove the build output is equivalent to yarn's**

```bash
git show HEAD~1:package.json > /dev/null   # sanity: previous commit is the yarn tree
docker compose run --rm --no-deps ui-server sh -lc 'ls public/assets | grep -c "\.js$"'
find public -name "manifest.json"
```

Expected: a non-zero count of hashed `.js` files and one `manifest.json` (Vite 5 writes it to `public/.vite/manifest.json`). A byte-for-byte comparison against the yarn build is worth doing once, ignoring `src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts`, which is the known non-deterministic barrel.

- [ ] **Step 8: Commit as one commit**

```bash
git add -A
git commit -m "infra(ui): migrate the JavaScript toolchain from yarn 1 to pnpm"
```

A half-migrated tree has no working install path at all, which is why this is one commit and one pull request despite its size.

---

## Task 24: Sweep the archival yarn prose

**Files:**
- Modify: `docs/baseline.md` (Phase 6 addendum only — never its dated measurements)
- Modify: 11 extension README/docs files carrying `yarn add @fastybird/...`
- Modify: 15 per-package `.gitignore` files listing `yarn.lock`

**Interfaces:**
- Consumes: Task 23's completed migration.
- Produces: a repository with no stale yarn instruction outside the archived plans.

- [ ] **Step 1: Update the 11 `yarn add @fastybird/*` lines**

`Core/Tools/README.md:33`, `Library/Metadata/README.md:28`, `Library/WebUi/README.md:25`, `Library/WebUi/packages/theme-chalk/README.md:8`, `Library/WebUi/docs/src/stories/intro.mdx:12`, `Module/Accounts/README.md:45` + `docs/index.md:42`, `Module/Devices/README.md:48` + `docs/Home.md:46`, `Module/Triggers/README.md:45` + `docs/index.md:42`, `Module/Ui/README.md:48` + `docs/Home.md:46`.

Note these are instructions for *external consumers of published packages*, and Phase 7 deprecates every one of those npm packages. Rather than mechanically rewriting `yarn add` to `pnpm add`, replace them with a note that the package is part of the MiniServer repository and is no longer published standalone — which is both true and forward-compatible with Phase 7.

- [ ] **Step 2: Update the 15 per-package `.gitignore` files**

`yarn.lock` → `pnpm-lock.yaml`; the six WebUi ones also list `yarn-debug.log*`/`yarn-error.log*` and already list `pnpm-debug.log*`, so just drop the yarn lines.

- [ ] **Step 3: Append a Phase 6 section to `docs/baseline.md`**

Record the migration date, the pinned pnpm version, the `DRIFTED: 0` result from Task 21, and the retirement of both forced exceptions. Do **not** edit its dated measurements — it is a historical record ("recorded 2026-09-09"). Leave `docs/superpowers/**` (170 hits) entirely alone; those are archived plans.

- [ ] **Step 4: Verify and commit**

```bash
grep -rn '\byarn\b' . --exclude-dir=node_modules --exclude-dir=vendor --exclude-dir=.git \
  --exclude-dir=docs/superpowers --exclude=pnpm-lock.yaml | grep -v 'FabYarn' \
  | grep -v 'docs/baseline.md' | grep -v CHANGELOG
git add -A && git commit -m "docs(cross): retire the remaining yarn references"
```

Expected before commit: no hits outside `docs/baseline.md`'s historical text and `CHANGELOG.md`.

---

## Task 25: Pin the floating dev-compose service images

**Files:**
- Modify: `docker/dev/docker-compose.yml:183,202,219,239,259`

**Interfaces:**
- Consumes: Task 3 Step 9's recorded resolutions for the floating tags.
- Produces: dev/CI/prod parity on the two services that matter, consumed by deferred track T5.

`docker/prod/docker-compose.yml` and both CI service blocks pin `mariadb:10.11` and `redis:7`. `docker/dev/docker-compose.yml` uses the bare `mariadb` and `redis` tags, resolving today to MariaDB 11.x/12.x and Redis 8.x. A developer therefore validates schema and query behaviour two or three majors ahead of what CI tests and what ships — and the blast radius is concrete rather than theoretical: the single initial migration mixes charsets (3 `CREATE TABLE` statements use `DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci`, i.e. utf8mb3, while 31 use utf8mb4/utf8mb4_general_ci) and `config/common.neon:186` sets `charset: utf8`. utf8mb3 aliasing and default collation are exactly what MariaDB changed across the 10.11 → 11.x boundary.

- [ ] **Step 1: Pin the two that matter**

`image: mariadb` → `image: mariadb:10.11`; `image: redis` → `image: redis:7`.

- [ ] **Step 2: Pin the three profile-gated services too**

`couchdb`, `rabbitmq:management` and `eclipse-mosquitto` are unpinned as well. Pin each to the major currently resolving (from Task 3 Step 9).

- [ ] **Step 3: Verify the dev stack still comes up and the migration applies**

```bash
docker compose down -v
docker compose up -d database redis application
docker compose run --rm application sh -lc \
  'php bin/fb-console.php migrations:migrate --no-interaction --allow-no-migration'
docker compose exec -T database mariadb -uroot -proot -e \
  "SELECT table_name, table_collation FROM information_schema.tables WHERE table_schema='miniserver' LIMIT 5;"
```

Expected: the migration applies cleanly and the collation listing shows the mixed utf8mb3/utf8mb4 state described above — record it, because that is the input to deferred track T5.

- [ ] **Step 4: Commit**

```bash
git add docker/dev/docker-compose.yml
git commit -m "infra(deps): pin the dev compose services to what CI and production run"
```

---

## Task 26: Align Composer across dev, prod and CI

**Files:**
- Modify: `docker/dev/php/Dockerfile:1`
- Modify: `docker/prod/Dockerfile:26`
- Modify: `.github/workflows/ci-tests.yaml:30,51,72,148`

**Interfaces:**
- Consumes: Task 12's refreshed lock.
- Produces: one Composer version everywhere, consumed by Task 9's `composer audit` job (which can become a hard gate once `--ignore-severity` is available at 2.8.0).

The "frozen Composer 2.4" only holds in the dev image. The production vendor stage uses the floating `composer:2` and all four CI jobs use the floating `composer:v2`, both resolving to current 2.9.x. `composer.lock`'s `plugin-api-version: 2.3.0` shows the lock was last written by the 2.4 container, so the lock and the image that produces the shipped vendor tree are not the same tool. Composer 2.4 (September 2022) is below the fix versions for CVE-2023-43655 (fixed 2.6.4, command injection via crafted Git branch names) and CVE-2024-24821 (fixed 2.7.0) — and this project resolves three dependencies from Git branch refs, which is precisely the surface the former targets.

- [ ] **Step 1: Pick one explicit version and use it in all three places**

At least 2.8.x, so that `composer audit --ignore-severity` becomes available and the CVE fix lines are cleared. Write the same tag into `docker/dev/php/Dockerfile:1` (`FROM composer:2.4` → `FROM composer:2.8`), `docker/prod/Dockerfile:26` (`FROM composer:2` → `FROM composer:2.8`) and the four `tools: "composer:v2"` entries.

- [ ] **Step 2: Reinstall and confirm the `plugin-api-version` change is intentional**

```bash
docker compose build application
docker compose run --rm --no-deps application sh -lc 'composer --version && composer install 2>&1 | tail -10'
git diff composer.lock | grep -E '^[-+].*plugin-api-version'
```

Expected: `Composer version 2.8.x`; the install succeeds with eleven patches applied; the lock's `plugin-api-version` moves from `2.3.0` to `2.6.0`. That single-line diff is expected and is the whole point — commit it deliberately rather than letting it appear as noise in an unrelated pull request later.

- [ ] **Step 3: Confirm `composer audit` now supports a severity filter**

```bash
docker compose run --rm --no-deps application sh -lc 'composer audit --help | grep -i "ignore-severity"'
```

Expected: the option is listed. Once it is, Task 9's `composer audit` job can drop `continue-on-error: true` — do that in this same pull request.

- [ ] **Step 4: Full PHP gate run, then commit**

```bash
docker compose run --rm --no-deps application sh -lc 'make lint && make cs && make phpstan && make composer-validate'
git add docker composer.lock .github/workflows/ci-tests.yaml
git commit -m "infra(deps): run one Composer version in dev, production and CI"
```

---

## Task 27: Add the Dependabot docker ecosystem, then digest-pin the production images

**Files:**
- Modify: `.github/dependabot.yml`
- Modify: `docker/prod/Dockerfile:14,26,48`
- Modify: `docker/prod/docker-compose.yml`

**Interfaces:**
- Consumes: Tasks 25 and 26 (there is now something stable to pin).
- Produces: automated base-image updates, without which digest pins become silent staleness.

All 16 image references in the repository are by tag, never by digest, so two builds of the same commit a month apart can differ. Digest pins are only viable with automation: `.github/dependabot.yml` declares composer, npm and github-actions but no docker ecosystem, so adding digests without adding those entries trades silent drift for silent staleness — which is worse for a security-relevant base image.

- [ ] **Step 1: Add the docker ecosystem entries — automation before digests**

```yaml
  - package-ecosystem: "docker"
    directories:
      - "/docker/prod"
      - "/docker/dev/php"
      - "/docker/dev/node"
      - "/docker/dev/nginx"
    schedule:
      interval: "weekly"
    commit-message:
      prefix: "chore"
      include: "scope"
    groups:
      base-images:
        patterns: ["*"]
```

Verify against GitHub's current `dependabot.yml` ecosystem documentation whether `docker-compose` file support covers the compose files here — Dockerfile support is certain; compose support was added later. If it is supported, add a second entry for `/docker/prod` and `/docker/dev`.

- [ ] **Step 2: Digest-pin only the four images that build the production artifact**

`node:20` (`docker/prod/Dockerfile:14`), `composer:2.8` (`:26`), `php:8.2-fpm` (`:48`) and `mariadb:10.11` (`docker/prod/docker-compose.yml:60`).

```bash
for img in node:20 composer:2.8 php:8.2-fpm mariadb:10.11; do
  d=$(docker buildx imagetools inspect "$img" --format '{{json .Manifest.Digest}}' 2>/dev/null | tr -d '"')
  echo "$img@$d"
done
```

Expected: four `name:tag@sha256:...` strings. Write them into the four locations in the `image:tag@sha256:...` form, keeping the tag so a human can still read what it is.

Do **not** digest-pin the dev-only images: the ongoing maintenance cost is not worth it for a stack nobody ships, and Dependabot will churn on them weekly.

- [ ] **Step 3: Verify the pinned build produces the same image**

```bash
docker build -t fb-p6-digest -f docker/prod/Dockerfile .
docker run --rm --entrypoint composer fb-p6-digest check-platform-reqs
docker run --rm --entrypoint php fb-p6-digest -v
```

Expected: build succeeds; `check-platform-reqs` all `success`; PHP 8.2.x.

- [ ] **Step 4: Commit**

```bash
git add .github/dependabot.yml docker
git commit -m "ci(infra): track base images with dependabot and digest-pin the production four"
```

---

## Task 28: Slim the production runtime image

**Files:**
- Modify: `docker/prod/Dockerfile:60-84`

**Interfaces:**
- Consumes: Task 27's digest pins (so before/after sizes are measured against a fixed base).
- Produces: a smaller appliance image with a smaller CVE-scan surface.

The runtime stage installs `libicu-dev`, `libgmp-dev`, `libzip-dev`, `libpng-dev`, `libfreetype6-dev`, `libjpeg62-turbo-dev` and `zlib1g-dev`, runs `docker-php-ext-install`, then cleans only the apt lists and `/tmp`. The `-dev` packages are needed only while compiling the extensions; the final image needs just the runtime shared libraries. Keeping them inflates the image by a few hundred MB (`libicu-dev` alone is substantial) and enlarges the scan surface with headers and static libraries that never execute.

- [ ] **Step 1: Measure the current size**

```bash
docker build -t fb-p6-before -f docker/prod/Dockerfile .
docker image ls fb-p6-before --format '{{.Size}}'
```

- [ ] **Step 2: Purge the build-time packages after the extension build**

Use `apt-mark auto '.*'`, re-mark the runtime libraries (`libicu72`, `libgmp10`, `libzip4`, `libpng16-16`, `libfreetype6`, `libjpeg62-turbo`, plus `nginx`, `supervisor`, `curl`), then `apt-get purge -y --auto-remove` in the same `RUN` layer. Alternatively resolve the runtime dependencies via `ldd` on the built `.so` files.

- [ ] **Step 3: Verify every extension still loads and measure the saving**

```bash
docker build -t fb-p6-after -f docker/prod/Dockerfile .
docker image ls fb-p6-after --format '{{.Size}}'
docker run --rm --entrypoint php fb-p6-after -m | sort
docker run --rm --entrypoint composer fb-p6-after check-platform-reqs
```

Expected: a smaller size; `php -m` still lists `bcmath gd gmp intl pcntl pdo_mysql sockets zip Zend OPcache`; `check-platform-reqs` all `success`. **If any extension fails to load, a runtime library was purged that should have been re-marked** — read the `ldd` output for that `.so` and add it back.

- [ ] **Step 4: Commit**

```bash
git add docker/prod/Dockerfile
git commit -m "infra(deps): purge the build-time -dev packages from the runtime image"
```

---

## Task 29: Make the two floating dev-branch composer pins reproducible

**Files:**
- Modify: `src/FastyBird/Plugin/RabbitMq/composer.json:36`
- Modify: `src/FastyBird/Plugin/RedisDb/composer.json:38`
- Modify: `src/FastyBird/Plugin/RedisDbCache/composer.json:31`
- Modify: `composer.lock`

**Interfaces:**
- Consumes: Task 26's Composer alignment.
- Produces: pins that cannot silently move, consumed by every task below — without this, every later `composer update` becomes two changes at once.

Three dev-branch pins exist and all three are in `require` (production), not `require-dev`. Two of them float:

- `bunny/bunny 0.6.x-dev` @376626fb (2026-08-08), required as a literal `"0.6.x-dev"` by `Plugin/RabbitMq`. The 0.6 line has only ever shipped `v0.6.0-alpha.1..4`; last stable is v0.5.6 (2025-05-22).
- `clue/redis-react 3.x-dev` @e928901a (2026-02-15), required as `^3@dev` by `Plugin/RedisDb` and `Plugin/RedisDbCache`. The 3.x line has **never been tagged at all**; latest stable is v2.8.0.

Both float, so any `composer update` silently moves them to whatever HEAD is that day, with no release notes and no semver contract. The third, `mathsolver/mathsolver dev-main` @84f6f1c9 (2023-01-01), is the opposite problem — permanently frozen — and goes to deferred track T3.

- [ ] **Step 1: Convert both to the commit-alias form**

```json
"bunny/bunny": "dev-0.6.x#376626fb5009dc72bf0bbec3508ed2665404d63a as 0.6.0",
"clue/redis-react": "dev-3.x#e928901a... as 3.0.0",
```

Read the exact commit hashes out of the current lock rather than trusting these:

```bash
docker compose run --rm --no-deps application sh -lc \
  "php -r '\$a=json_decode(file_get_contents(\"composer.lock\"),true); foreach(\$a[\"packages\"] as \$p){ if(in_array(\$p[\"name\"],[\"bunny/bunny\",\"clue/redis-react\"])) echo \$p[\"name\"],\" \",\$p[\"version\"],\" \",\$p[\"source\"][\"reference\"],\"\n\"; }'"
```

- [ ] **Step 2: Dry-run, then update**

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer update --dry-run bunny/bunny clue/redis-react'
docker compose run --rm --no-deps application sh -lc \
  'composer update bunny/bunny clue/redis-react --with-dependencies'
git diff composer.lock | grep -E '^[-+]\s+"(version|reference)"' | head -20
```

Expected: the dry run resolves to the *same* commits it already has; the real update changes only the constraint strings and the alias metadata, not the references.

- [ ] **Step 3: Prove the pin holds under a broad update**

```bash
docker compose run --rm --no-deps application sh -lc 'composer update --dry-run 2>&1 | grep -E "bunny|redis-react"'
```

Expected: no lines, or lines showing no change. That is the property the alias buys.

- [ ] **Step 4: Run the suite and commit** **(long)**

```bash
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make tests"
git add src/FastyBird composer.lock
git commit -m "fix(deps): pin bunny and clue/redis-react to explicit commits"
```

---

## Task 30: Take the cheap, decoupled composer constraint wins

**Files:**
- Modify: `src/FastyBird/Plugin/CouchDb/composer.json:45`
- Modify: `src/FastyBird/Core/Application/composer.json:62`
- Modify: `composer.json` (contributte/vite, contributte/monolog)
- Modify: `composer.lock`

**Interfaces:**
- Consumes: Task 29's reproducible pins.
- Produces: one of the three PHP 8.4 blockers cleared, consumed by deferred track T2's scoping.

None of these touch the Doctrine/Symfony knot, which is what makes them cheap. Prove each independently.

- [ ] **Step 1: `phpdocumentor/reflection` `^5.3` → `^7.0`**

The locked 5.3.3 declares `^7.4|8.0.*|8.1.*|8.2.*|8.3.*` — one of exactly three packages capping the tree below PHP 8.5. 7.0.0 (2026-05-28) declares `8.2.*|8.3.*|8.4.*|8.5.*`. Only `Plugin/CouchDb` requires it and it is not part of the framework cluster.

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer update --dry-run phpdocumentor/reflection -W'
```

Expected: resolves to 7.0.x with no conflict.

- [ ] **Step 2: `symfony/monolog-bridge` `^6.1` → `^7.0`**

Capped by `Core/Application:62` — first-party, a one-line fix. (`symfony/serializer` is separately capped at `^6` by `ipub/websockets-wamp` v1.4.3, an external frozen package — that one is not fixable here and belongs to deferred track T1.)

- [ ] **Step 3: `contributte/vite` `^0.2` → `^0.3` and `contributte/monolog` `^0.5` → `^0.6`**

Re-verify the LoggerHolder patch still applies after the monolog bump — one of the eleven live patches targets it.

- [ ] **Step 4: Apply all four together, then verify the patch set**

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer update phpdocumentor/reflection symfony/monolog-bridge contributte/vite contributte/monolog -W 2>&1 | tail -30'
```

Expected: exit 0, and the patch summary still shows eleven patches applied with none reported as failed. **If a patch now fails, that is the finding** — regenerate it against the new upstream source in this same pullrequest rather than skipping it.

- [ ] **Step 5: Confirm the PHP 8.4 blocker count dropped from three to two**

```bash
docker compose run --rm --no-deps application sh -lc 'composer why-not php 8.4'
```

Expected: exactly `orisai/object-mapper` and `orisai/nette-object-mapper` remain. `phpdocumentor/reflection` is gone. If anything else appears, the deferred track T2 scope is wrong and must be re-derived.

- [ ] **Step 6: Full gate run and commit** **(long)**

```bash
docker compose run --rm --no-deps application sh -lc 'make lint && make cs && make phpstan'
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make tests"
git add src/FastyBird composer.json composer.lock
git commit -m "fix(deps): take the four constraint bumps that miss the doctrine cluster"
```

---

## Task 31: Move the PHP runtime to 8.3

**Files:**
- Modify: `composer.json:19` and 34 × `src/FastyBird/*/*/composer.json`
- Modify: `docker/prod/Dockerfile:48`
- Modify: `docker/dev/php/Dockerfile:3`
- Modify: `.github/workflows/ci-tests.yaml` (four `php-version` blocks)
- Modify: `tools/phpcs.xml:9`
- Modify: `tools/phpstan.neon`, `tools/phpstan.tests.neon` (`phpVersion`)

**Interfaces:**
- Consumes: Task 3 Step 1's `composer why-not php 8.3` result, Task 6's `phpVersion: 80200` pin.
- Produces: an 8.3 runtime, consumed by Tasks 33–35 (three of the four test tools are PHP-gated at their newest).

8.3 is a constraint-level no-op. Platform is `php: >=8.2.0` in the root and in all 34 path manifests with no `config.platform` override anywhere, so container PHP is what resolution sees. Across all 285 locked packages the lowest ceiling is `7.4 - 8.3` (`>=7.4 <8.4.0`), which still admits 8.3. All 18 `ext-*` requirements ship in 8.3/8.4/8.5; none was removed or unbundled. First-party code is clean for the dominant 8.4 deprecation too: across 3,169 PHP files and 411,584 lines there are **zero** implicit-nullable parameter declarations (the 18 raw grep hits are all `mixed $x = null`, which is not deprecated) and zero `E_STRICT` references.

- [ ] **Step 1: Build an 8.3 image and run every gate before changing a single constraint**

```bash
sed 's|FROM php:8.2-fpm|FROM php:8.3-fpm|' docker/dev/php/Dockerfile > /tmp/Dockerfile.83
docker build -f /tmp/Dockerfile.83 -t fb-php83 .
docker run --rm -v "$PWD":/app -w /app fb-php83 sh -lc 'php -v && composer check-platform-reqs'
docker run --rm -v "$PWD":/app -w /app fb-php83 sh -lc 'make lint && make cs'
```

Expected: PHP 8.3.x; `check-platform-reqs` all `success`; lint and cs green. This proves the runtime before anything is committed.

- [ ] **Step 2: Run the suite on 8.3 and count deprecations** **(long, ~15 min)**

`tools/phpunit.xml` sets `failOnWarning="true"` and `displayDetailsOnTestsThatTriggerDeprecations="true"`, so this is the real test of whether doctrine/orm 2.15.5, doctrine/annotations and nette 3.x behave.

```bash
docker compose up -d database
docker run -d --name fb-loopback-redis --network container:fastybird-database redis:7
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fb-php83 sh -lc "make tests" 2>&1 | tail -40
```

Expected: `OK (1405 tests, 5663 assertions)`, exit 0. **If deprecations appear, record the count and the top three sources before proceeding** — a large volume from doctrine/annotations is the signal that Task 42 needs to come first.

- [ ] **Step 3: Only now, change the version everywhere**

- `php: >=8.2.0` → `php: >=8.3.0` in the root and all 34 path manifests.
- `FROM php:8.2-fpm` → `FROM php:8.3-fpm` in both Dockerfiles (keeping Task 27's digest form for production — re-resolve the digest for the new tag).
- `php-version: "8.2"` → `"8.3"` in the four `setup-php` blocks.
- `tools/phpcs.xml:9`: `ruleset-8.2.xml` → `ruleset-8.3.xml` (both are already vendored under `vendor/orisai/coding-standard/src/`).
- `phpVersion: 80200` → `phpVersion: 80300` in both PHPStan configs. This is the one line that makes the analysis delta attributable — Task 6 pinned it deliberately for this moment.

- [ ] **Step 4: Regenerate the lock against the new platform and re-run PHPStan**

```bash
docker compose build application
docker compose run --rm --no-deps application sh -lc \
  'php -v && composer update --lock && composer install && make phpstan'
```

Expected: PHP 8.3.x; the lock's `platform-overrides`/`php` entries move to 8.3; `make phpstan` exits 0. **PHPStan has no baseline, so any new error here is a hard failure.** If the `phpVersion` bump produces new findings, fix them in this pull request — the count should be small, since Task 6 already pinned analysis to a fixed version rather than letting it drift with the runner.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock src/FastyBird docker tools .github/workflows/ci-tests.yaml
git commit -m "infra(deps): move the PHP runtime and platform requirement to 8.3"
```

---

## Task 32: Add a non-blocking 8.4 CI leg

**Files:**
- Modify: `.github/workflows/ci-tests.yaml`

**Interfaces:**
- Consumes: Task 31's 8.3 runtime, Task 30's cleared `phpdocumentor/reflection` blocker.
- Produces: continuous evidence for deferred track T2, so the `orisai/object-mapper` decision is made against data rather than guesswork.

CI runs a single PHP job with no version matrix, so nothing is exercised against a newer runtime until someone attempts the bump.

- [ ] **Step 1: Add a matrix to `php-tests` with 8.4 as `continue-on-error`**

```yaml
    strategy:
      fail-fast: false
      matrix:
        php-version: ["8.3", "8.4"]
    continue-on-error: ${{ matrix.php-version == '8.4' }}
```

with `php-version: "${{ matrix.php-version }}"` in the `setup-php` step. The 8.4 leg will fail at `composer install` until `orisai/object-mapper` moves — that is the point, and it is why the leg is non-blocking.

- [ ] **Step 2: Verify both legs run and only 8.3 is required**

```bash
gh pr checks --watch --repo FastyBird/miniserver
```

Expected: `PHP Tests (8.3)` green and required; `PHP Tests (8.4)` red or yellow and not blocking the merge. Confirm branch protection does not list the 8.4 leg as required.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/ci-tests.yaml
git commit -m "ci(infra): add a non-blocking PHP 8.4 test leg"
```

---

## Task 33: Infection 0.27 → 0.31 and the first real coverage number

**Files:**
- Modify: `composer.json` (`infection/infection`)
- Modify: `composer.lock`
- Modify: `docs/baseline.md` (Phase 6 addendum)

**Interfaces:**
- Consumes: Task 5's repaired coverage filter, Task 31's 8.3 runtime.
- Produces: a working mutation run and a recorded line rate, consumed by Task 34 — 0.27 predates PHPUnit 11 support, so this is a hard prerequisite, not an optional extra.

- [ ] **Step 1: Bump**

`infection/infection 0.27.11` → `^0.31.9`. 0.32+ requires `^8.3`, which Task 31 now satisfies, but 0.31.9 is the conservative step that also worked on 8.2 — take 0.31 first so a failure is attributable to Infection rather than to the PHP move.

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer update --dry-run infection/infection -W'
docker compose run --rm --no-deps application sh -lc 'composer update infection/infection -W'
```

- [ ] **Step 2: Get the first honest coverage number** **(long, expect longer than the 14m39s test run)**

```bash
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make coverage-clover" 2>&1 | tail -20
docker compose run --rm --no-deps application sh -lc \
  "php -r '\$x=simplexml_load_file(\"var/tools/Coverage/clover.xml\"); \$m=\$x->project->metrics; printf(\"lines %d/%d = %.1f%%\n\", (int)\$m[\"coveredstatements\"], (int)\$m[\"statements\"], 100*(int)\$m[\"coveredstatements\"]/max(1,(int)\$m[\"statements\"]));'"
```

Expected: a percentage over the 2,801 source files, not the 232 test files. Record it in `docs/baseline.md` alongside the existing gate table. **Do not add a coverage gate yet** — publish the number for a few weeks first, then set a floor. Adding it now would either be trivially low or set arbitrarily.

- [ ] **Step 3: Prove mutation testing is alive again** **(very long — run it once, overnight if necessary)**

```bash
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make mutations" 2>&1 | tail -30
```

Expected: an MSI line with a non-trivial score, and **not** every mutant classified as uncovered. Before Task 5, `make mutations-tests` wrote a `--coverage-xml` containing only test files and `make mutations-infection` ran `--skip-initial-tests` over `src/FastyBird/*/*/src`, so not one mutated file had coverage data. If the MSI is still 0, the coverage filter fix did not reach Infection — check that `tools/infection.json:5`'s glob and `tools/phpunit.xml`'s include still agree.

- [ ] **Step 4: Confirm the logs land in the right place**

```bash
ls -la var/tools/Coverage/mutations/ && test ! -d tools/var && echo NO_STRAY_TOOLS_VAR
```

Expected: the two log files present under `var/`, and `NO_STRAY_TOOLS_VAR` (Task 6 added the `../` prefix).

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock docs/baseline.md
git commit -m "chore(deps): bump infection to 0.31 and record the first real coverage number"
```

---

## Task 34: PHPUnit 10.5 → 11.5 with paratest 7.4 → 7.8

**Files:**
- Modify: `composer.json` (`phpunit/phpunit`, `brianium/paratest`)
- Modify: `composer.lock`
- Modify: `tools/phpunit.xml` (schema URL)
- Possibly modify: test files using removed annotations

**Interfaces:**
- Consumes: Task 33's Infection bump.
- Produces: the current-generation test runner, consumed by Task 35 (`phpstan-phpunit` 2.x is written against PHPUnit 11).

One commit, mandatory: paratest 7.8.5 requires `phpunit/phpunit ^11.5.46`, so the two cannot be separated. 11.5 requires `php >=8.2` and is the realistic target — 12.5.35 needs `>=8.3` (now satisfied, but take one major at a time) and 13.3.3 needs `>=8.4.1`. The whole `sebastian/*` and `phpunit/php-*` constellation moves with it.

- [ ] **Step 1: Dry-run both together**

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer update --dry-run phpunit/phpunit brianium/paratest -W'
```

Expected: phpunit 11.5.x and paratest 7.8.x resolve together with the `sebastian/*` chain. **If paratest resolves to 7.4.x, the phpunit constraint did not move** — check both constraints before continuing.

- [ ] **Step 2: Update the config schema URL**

`tools/phpunit.xml:3` points at `https://schema.phpunit.de/10.1/phpunit.xsd`. Change to `11.5`.

- [ ] **Step 3: Update and run the suite** **(long)**

```bash
docker compose run --rm --no-deps application sh -lc 'composer update phpunit/phpunit brianium/paratest -W'
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make tests" 2>&1 | tail -40
```

Expected: `OK (1405 tests, 5663 assertions)`. The likely failure modes, in order of probability: removed doc-comment annotations that must become attributes (`@dataProvider`, `@covers`, `@group`); `assertObjectHasAttribute` and other removed assertions; and `TestCase::createMock()` behaviour on final classes, which `dg/bypass-finals` currently papers over. Fix each in this pull request — they are consequences of the version bump and cannot be separated from it.

- [ ] **Step 4: Confirm `dg/bypass-finals` still works**

`dg/bypass-finals` v1.11.0 is already at its latest, but one of the eleven patches targets it and `docs/baseline.md` records that the patch does not apply (it targets a line that moved upstream) — a known non-regression. Confirm the tests that rely on mocking final classes still pass; if they now fail, the bypass is the cause and the patch needs regenerating.

- [ ] **Step 5: Re-run mutations to confirm Infection still drives the new runner** **(very long)**

```bash
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make mutations" 2>&1 | tail -20
```

Expected: an MSI comparable to Task 33's. Infection 0.31 supports PHPUnit 11; 0.27 did not, which is why Task 33 came first.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock tools/phpunit.xml src/FastyBird
git commit -m "chore(deps): bump phpunit to 11.5 with paratest 7.8"
```

---

## Task 35: PHPStan 1.12 → 2.2 with all five extensions

**Files:**
- Modify: `composer.json` (six phpstan packages)
- Modify: `composer.lock`
- Modify: `tools/phpstan.neon`, `tools/phpstan.tests.neon`
- Create: `tools/phpstan-baseline.neon`, `tools/phpstan-baseline.tests.neon`

**Interfaces:**
- Consumes: Task 31's `phpVersion: 80300` pin, Task 34's PHPUnit 11.
- Produces: the last of the PHP QA chain.

**This is the one place in the phase where a structural change (a new baseline file) genuinely cannot be separated from a version change**, because the baseline only exists as a product of the new version's output. It is called out here rather than glossed over.

`phpstan/phpstan` 1.12.34 is the newest 1.x release — there is no in-major upgrade left, only the 2.x major.

What it costs:

- **`level: max` changes meaning.** phpstan-src 1.12.x ships `config.level0..level9` plus `config.levelmax` (which includes level9). 2.2.x ships `config.level10` as well, so `max` silently becomes level 10 — implicit-mixed checking — in both `tools/phpstan.neon:5` and `tools/phpstan.tests.neon:5`.
- **Every suppression must be re-derived.** `reportUnmatchedIgnoredErrors` is unset and therefore defaults to true, and every one of the 120 entries carries an exact `count:`. `tools/phpstan.neon` has 99 entries suppressing 418 occurrences; `tools/phpstan.tests.neon` has 21 suppressing 121. Count drift is a hard error and drift is certain: 94 of the 99 `src` entries are the same three messages (`strval` ×78, `intval` ×10, `floatval` ×6 — pure level-9 artefacts that level 10 will multiply), and all 21 test entries are the single `createMock()` unresolvable-type message that `phpstan-phpunit` 2.x fixes, so all 21 become unmatched-ignore errors on day one.
- **All six packages move together.** `phpstan-deprecation-rules` 1.2.1→2.0.5, `phpstan-doctrine` 1.5.7→2.0.28, `phpstan-nette` 1.3.8→2.0.12, `phpstan-phpunit` 1.4.2→2.0.18, `phpstan-strict-rules` 1.6.2→2.0.12. All are registered automatically via `phpstan/extension-installer`, so a partial bump breaks the build.

The three non-default parameters this repository sets — `checkMissingCallableSignature`, `checkTooWideReturnTypesInProtectedAndPublicMethods`, `checkInternalClassCaseSensitivity` (`tools/phpstan.neon:16-18`) — all still exist in phpstan-src 2.2.x's `conf/config.neon`, so they need no change.

- [ ] **Step 1: Measure the error surface on a throwaway branch before committing to anything**

```bash
git checkout -b tmp/phpstan2-measure
docker compose run --rm --no-deps application sh -lc \
  "composer update 'phpstan/*' -W 2>&1 | tail -20 && vendor/bin/phpstan analyse -c tools/phpstan.neon 2>&1 | tail -5"
```

Expected: a total error count. Record it. This is the number that decides whether the baseline is temporary or a long-lived debt item.

- [ ] **Step 2: Delete all 120 `ignoreErrors` entries and generate a baseline instead**

Hand-editing 120 counted entries against a moved target is unbounded work with no review signal. Remove the `ignoreErrors:` blocks from both configs, add `includes: - phpstan-baseline.neon` (and the tests equivalent), then:

```bash
docker compose run --rm --no-deps application sh -lc \
  'vendor/bin/phpstan analyse -c tools/phpstan.neon --generate-baseline tools/phpstan-baseline.neon'
docker compose run --rm --no-deps application sh -lc \
  'vendor/bin/phpstan analyse -c tools/phpstan.tests.neon --generate-baseline tools/phpstan-baseline.tests.neon'
```

Note in the pull request body that the baseline is temporary and must be burned down, and that the largest single group (`strval`/`intval`/`floatval` "expects …, mixed given", 94 entries) is one fix repeated — a typed helper would retire most of it.

- [ ] **Step 3: Confirm both configs run clean against the baseline**

```bash
docker compose run --rm --no-deps application sh -lc 'make phpstan'
wc -l tools/phpstan-baseline.neon tools/phpstan-baseline.tests.neon
```

Expected: `make phpstan` exits 0 for both configs; the baseline line counts are recorded in the pull request body so the burn-down has a starting point.

- [ ] **Step 4: Also address the inline suppression debt as a follow-up note, not in this pull request**

`grep -rn '@phpstan-ignore' src/FastyBird --include='*.php'` finds 80 inline annotations, 71 of which are bare `@phpstan-ignore-next-line` with no identifier and no explanation — those hide *any* error on the following line, which quietly blunts `level: max`. The nine identifier-scoped ones with rationale comments are the style introduced by commit `286c2cd5`. Converting the 71 is real work and belongs in its own change; record it as a follow-up issue rather than expanding this pull request.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock tools/
git commit -m "chore(deps): bump phpstan to 2.2 with a generated baseline"
```

---

## Task 36: Align the three lagging `Library/WebUi` build packages

**Superseded.** `src/FastyBird/Library/WebUi` was deleted in its entirety by
`docs/superpowers/plans/2026-09-11-webui-library-removal.md` (Task 11 of that
plan). There is no lint toolchain left to align. See that plan and its spec,
`docs/superpowers/specs/2026-09-11-webui-library-removal-design.md`, for what
replaced it (element-plus directly, plus `@iconify/vue`). No action remains
for this task; later tasks that referenced it (Tasks 37–39) have been
adjusted accordingly.

---

## Task 37: UnoCSS 0.64 → 66

**Files:**
- Modify: `package.json` (`unocss`, `@unocss/transformer-variant-group`)
- Modify: `uno.config.ts`
- Modify: up to ~47 `.vue` files if utility class names changed
- Modify: `pnpm-lock.yaml`

**Interfaces:**
- Consumes: nothing (Task 36 is superseded — see its note — so there is no
  aligned toolchain to wait on; the root application uses UnoCSS directly and
  is otherwise unaffected by the WebUi removal).
- Produces: a Vite peer range that admits 6, consumed by Task 38 — nothing else in the frontend chain moves until this does.

`unocss@0.64.1` and `@unocss/vite@0.64.1` declare `peerDependencies.vite: "^2.9.0 || ^3.0.0-0 || ^4.0.0 || ^5.0.0-0"` — a hard stop at Vite 5. This is the largest single upgrade in the JS set, it touches every extension at once, and no intermediate step reduces its size. **This is the second of the three places where a code change cannot be separated from a version change**: the 0.65 → 66.0 rename supersedes `presetUno` with `presetWind3`/`presetWind4`, which have different default theme scales, so the config edit and the version bump are one change.

- [ ] **Step 1: Capture a visual baseline before changing anything**

```bash
docker compose up -d ui-server web-server application database
```

Screenshot the four `import 'virtual:uno.css'` entry points' pages — `Core/Application`, `Module/Ui`, `Module/Accounts`, `Module/Devices` — plus the two heaviest utility-class surfaces: `Module/Devices` (33 `.vue` files carrying utility classes) and `Module/Accounts` (11). Save them; a green build is not evidence that the CSS is right.

- [ ] **Step 2: Bump and migrate the preset**

`unocss ^0.64` → `^66.10`, `@unocss/transformer-variant-group ^0.64` → `^66.10`. In `uno.config.ts:44`, replace `presetUno({ dark: 'class' })` with `presetWind3({ dark: 'class' })` (the direct successor; `presetWind4` is a further rescale and should be a separate decision), and re-check the custom theme and breakpoints block against the new default scales.

- [ ] **Step 3: Build and diff the generated CSS**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'pnpm install && pnpm build'
ls -la public/assets/*.css
```

Expected: the build succeeds and produces CSS. Compare the generated stylesheet's size and rule count against the pre-bump build — a large drop means utility classes silently stopped matching.

- [ ] **Step 4: Visual verification — the actual gate**

Re-screenshot the same six surfaces and compare against Step 1. **A green build is not sufficient for this task.** Expect spacing and color-scale differences; investigate any layout break.

- [ ] **Step 5: Confirm the Vite peer range now admits 6**

```bash
docker compose run --rm --no-deps ui-server sh -lc \
  "node -e \"console.log(require('/app/node_modules/unocss/package.json').peerDependencies)\""
```

Expected: a range including `^6` (and likely `^7`).

- [ ] **Step 6: Commit**

```bash
git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts
git add package.json uno.config.ts pnpm-lock.yaml src/FastyBird
git commit -m "chore(deps): bump unocss to 66 and migrate presetUno to presetWind3"
```

---

## Task 38: Vite 5 → 6 and `@vitejs/plugin-vue` 5 → 6

**Reduced.** ~~plus the four `Library/WebUi` packages declaring vite~~ — those
manifests are deleted; only the root `package.json` needs the bump.

**Files:**
- Modify: `package.json`
- Modify: `vite.config.ts` if the Sass API default requires it
- Modify: `pnpm-lock.yaml`

**Interfaces:**
- Consumes: Task 37's widened peer range.
- Produces: Vite 6. ~~Consumed by Task 39~~ — Task 39 (Storybook) is
  superseded, so this Vite bump has no downstream consumer inside this plan
  anymore; the `@storybook/builder-vite` peer-range rationale below is
  historical context for why the bump was staged here, not a live
  dependency.

Dependabot PR344 proved vite 6 genuinely builds here (JS Build and Docker Build both passed) — the reason it was deferred was solely the unocss peer range, which Task 37 has now cleared.

- [ ] **Step 1: Bump vite and plugin-vue together**

`vite ^5.4` → `^6.4`; `@vitejs/plugin-vue ^5.2` → `^6.0`. `@vitejs/plugin-vue@5.2.4` allows `^5.0.0 || ^6.0.0` so it covers Vite 6, but plugin-vue 6 is needed for Vite 7/8 and taking it now avoids a second lockfile churn. `@nabla/vite-plugin-eslint@2.0.6` already allows `^4 || ^5 || ^6 || ^7`.

- [ ] **Step 2: Check the Sass API default**

Vite 6 flips the default Sass API to `modern-compiler`. Nothing in CI exercises `pnpm dev`, so this must be checked by hand.

```bash
docker compose run --rm --no-deps ui-server sh -lc 'pnpm install && pnpm build 2>&1 | grep -i -E "deprecat|legacy.*api|sass" | head -20'
```

Expected: no legacy-API deprecation warnings. If they appear, set `css.preprocessorOptions.scss.api = 'modern-compiler'` explicitly rather than relying on the default.

- [ ] **Step 3: Verify every surface, including the two CI does not cover**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'pnpm build:ui && pnpm types && pnpm build'
docker compose run --rm --no-deps ui-server sh -lc 'pnpm --filter @fastybird/web-ui-docs run build'
docker compose up -d ui-server
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3000/
curl -s http://localhost:3000/src/FastyBird/Module/Devices/assets/entry.ts | head -c 200
```

Expected: all builds exit 0; the docs workspace builds (Task 9 made this a gate); the dev server returns `200`; and the second curl prints raw TypeScript source, proving Vite is still serving extension sources directly rather than a pre-built bundle.

- [ ] **Step 4: Rebuild the production image**

```bash
docker build -t fb-p6-vite6 -f docker/prod/Dockerfile . && echo PROD_OK
```

- [ ] **Step 5: Commit**

```bash
git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts
git add package.json src/FastyBird/Library/WebUi pnpm-lock.yaml vite.config.ts
git commit -m "chore(deps): bump vite to 6 and @vitejs/plugin-vue to 6"
```

---

## Task 39: Storybook 8 → 10 and vue-component-meta 2 → 3

**Superseded.** The Storybook docs workspace lived at
`src/FastyBird/Library/WebUi/docs` and was deleted along with the rest of
`Library/WebUi` by `docs/superpowers/plans/2026-09-11-webui-library-removal.md`
(Task 11 of that plan). There is no Storybook install left to migrate.
Dependabot pull requests #341 and #342 (the storybook/vue-tsc bumps this task
would have landed) are moot for the same reason; closing them is out of
scope for this documentation pass. No action remains for this task.

---

## Task 40: Node 20 → 22.13+

**Reduced.** `src/FastyBird/Library/WebUi` — including `packages/theme-chalk`,
whose gulp/chokidar/fsevents chain was this task's one identified plausible
breakage (Step 1) — is deleted. There is no longer a known risk source for
this bump; Step 1's command needs a different verification target or may be
unnecessary. The four WebUi packages' `@types/node` bumps in Step 3 no
longer apply either.

**Files:**
- Modify: `docker/prod/Dockerfile:14`
- Modify: `docker/dev/node/Dockerfile:1`
- Modify: `.nvmrc`
- Modify: `.github/workflows/ci-tests.yaml` (three-plus `node-version` blocks)
- Modify: `package.json` (`engines.node`, `@types/node`)
- Modify: `CLAUDE.md`, `README.md`

**Interfaces:**
- Consumes: Task 3 Step 8's proof that `theme-chalk` builds on 22 and 24, Task 15's already-removed `--ignore-engines`.
- Produces: a supported runtime, and unlocks four Node-gated upgrades.

Node 20 reached EOL 2026-04-30, so `node:20` and `node:20-alpine` are no longer rebuilt upstream and accumulate OS-level CVEs. Deliberately placed late: after Tasks 11/14/15 the `--ignore-engines` exception is already gone, so the only remaining reasons are security patching and unlocking `vue-i18n` 11 (`engines >= 22`), `@intlify/unplugin-vue-i18n` 11.2.5 (`>= 22.13`), `@commitlint/cli` 21.2.2 (`>= 22.12`) and `sass-loader` 17.0.1 (`>= 22.11`). Nothing forbids the move: a scan of all 835 installed `engines.node` declarations found **zero** packages with any upper bound.

- [ ] **Step 1: ~~Re-verify the one plausible breakage on the current tree~~ — re-scope, the original target is deleted**

`packages/theme-chalk` and its `gulp@4.0.2` → `glob-watcher@5` → `chokidar@^2.0.0` →
`fsevents@1.2.13` chain no longer exist, so the command below (which targeted
exactly that package) has nothing to filter on. Before assuming this step is
simply gone, re-run the equivalent full-install-plus-build check against the
current tree (`pnpm install --frozen-lockfile && pnpm build` under `node:22-alpine`)
to confirm no other native-addon dependency has crept in; if it passes clean,
this step can be dropped from the task entirely.

```bash
docker run --rm -v "$PWD":/app -w /app node:22-alpine sh -lc \
  'npm i -g pnpm@<pinned> && pnpm install --frozen-lockfile && pnpm build'
```

Original rationale, preserved for context: Task 3 Step 8 tested this before the pnpm migration and before six dependency changes, targeting `@fastybird/web-ui-theme-chalk` specifically. Expected then: exit 0, because the `os: darwin`-optional `fsevents` dependency is skipped entirely on Alpine. **An Alpine pass does not clear a macOS host developer's machine** — a host install on Node 22/24 will attempt and fail a node-gyp build for any native addon that *is* installed there. If Step 1's re-run above turns up a new native-addon dependency, record the same Alpine-vs-host-macOS caveat in `CLAUDE.md` alongside the Node line.

- [ ] **Step 2: Write a full `x.y.z` into `.nvmrc`, not a bare major**

`.nvmrc: 20` is under-specified today: `sass@1.104.0` requires `>= 20.19.0`, so a contributor on nvm-installed 20.9 fails the install. Write the exact patch version of the image being pinned.

- [ ] **Step 3: Move the runtime and the types together**

`node:20` → `node:22` in both Dockerfiles (re-resolving Task 27's production digest), `node-version: "20"` → `"22"` in every CI job, `engines.node: ">=20"` → `">=22.13"`, and `@types/node ^20.17` → `^22.x` in the root (the four WebUi packages this originally also named are deleted). **A runtime bump without a types bump leaves `vue-tsc` type-checking against Node 20 typings** — this is the correct resolution of closed Dependabot PR339, and it goes to the major matching the runtime, not to 26.

- [ ] **Step 4: Remove the `@types/node` Dependabot ignore, or keep it and say why**

Task 2 added an ignore for `@types/node` major bumps. Keep it: the rule is "track the runtime", and Dependabot cannot know what the runtime is. Update its comment to name Node 22.

- [ ] **Step 5: Full verification**

```bash
docker compose build ui-server
docker compose run --rm --no-deps ui-server sh -lc \
  'node -v && pnpm install --frozen-lockfile && pnpm lint:js && pnpm lint:styles && pnpm build:ui && pnpm types && pnpm build'
docker compose run --rm --no-deps ui-server sh -lc 'pnpm --filter @fastybird/web-ui-docs run build'
docker build -t fb-p6-node22 -f docker/prod/Dockerfile . && echo PROD_OK
```

Expected: `v22.13.x` or higher, then exit 0 everywhere, then `PROD_OK`.

- [ ] **Step 6: Commit**

```bash
git checkout -- src/FastyBird/Library/WebUi/packages/icons/src/components/index.ts
git add docker .nvmrc .github/workflows/ci-tests.yaml package.json src/FastyBird CLAUDE.md README.md pnpm-lock.yaml
git commit -m "infra(deps): move the Node runtime to 22.13 and align @types/node"
```

---

## Task 41: Replace `vue-meta` with `@unhead/vue`

**Files:**
- Modify: `src/FastyBird/Core/Application/assets/main.ts`
- Modify: `src/FastyBird/Core/Application/assets/App.vue`
- Modify: 15 view files (1 `view-home.vue`, 5 Accounts views, 9 Devices views)
- Modify: `tsconfig.json` (`types` array), `vite.config.ts` (`resolve.dedupe`, `optimizeDeps.include`)
- Modify: `package.json` and the eight extensions' `peerDependencies`
- Modify: `pnpm-lock.yaml`

**Interfaces:**
- Consumes: Task 40's Node runtime (unhead's newest requires a modern Node).
- Produces: the removal of the last dead dependency in the frontend tree.

The root declares `vue-meta: ^3.0.0-alpha.10` and it resolves to exactly `3.0.0-alpha.10`. npm's `latest` dist-tag for `vue-meta` points at **2.4.0, published 2020-06-10** — the 3.x line never left alpha and was abandoned. There is no version to upgrade to, only a replacement. It is also one of the two things (with UnoCSS, now done) that a Vue 4 move would eventually force.

- [ ] **Step 1: Enumerate the call sites**

```bash
grep -rn "from 'vue-meta'" src/FastyBird --include="*.ts" --include="*.vue"
```

Expected: 17 files — `createMetaManager`/`plugin as metaPlugin` in `Core/Application/assets/main.ts`, and `useMeta` in `App.vue`, `view-home.vue`, five Accounts views and nine Devices views.

- [ ] **Step 2: Swap the plugin bootstrap first, then the call sites**

`createMetaManager()`/`metaPlugin` become `createHead()` from `@unhead/vue`, installed with `app.use(head)`. The 15 `useMeta({...})` calls become `useHead({...})`; the shapes are similar but not identical — `useMeta`'s `{ title, meta: [{ hid, name, content }] }` maps to `useHead`'s `{ title, meta: [{ name, content }] }`, and `hid` has no equivalent (unhead deduplicates by `name`/`property` automatically). `App.vue`'s `__APP_DESCRIPTION__` meta entry is one of these.

- [ ] **Step 3: Update the three wirings that would otherwise break silently**

`tsconfig.json`'s `types` array lists `vue-meta`; `vite.config.ts`'s `resolve.dedupe` and `optimizeDeps.include` both list it. Replace all three, and remove `vue-meta` from all eight extensions' `peerDependencies`.

- [ ] **Step 4: Verify the rendered head, not just the build**

```bash
docker compose run --rm --no-deps ui-server sh -lc 'pnpm types && pnpm build'
docker compose up -d ui-server
curl -s http://localhost:3000/ | grep -E '<title>|<meta name="description"'
```

Expected: the build exits 0, and the served HTML carries both a `<title>` and the description meta. A build-only check would not catch a head manager that installs but never renders.

- [ ] **Step 5: Confirm vue-meta has left the tree, then commit**

```bash
grep -rn "vue-meta" . --exclude-dir=node_modules --exclude-dir=.git --exclude-dir=docs/superpowers \
  --exclude=pnpm-lock.yaml || echo VUE_META_GONE
git add -A && git commit -m "ui(cross): replace the abandoned vue-meta with @unhead/vue"
```

---

## Task 42: The ORM-2-preserving Doctrine step

**Files:**
- Modify: `composer.json:44` and 7 × `src/FastyBird/*/*/composer.json` (`doctrine/orm`)
- Modify: `composer.json` (`nettrine/orm`, `nettrine/dbal`, `nettrine/fixtures`, `contributte/console`)
- Modify: `tools/patches/*.patch` (regenerated)
- Modify: `composer.lock`

**Interfaces:**
- Consumes: Task 31's PHP 8.3, Task 35's PHPStan 2 baseline (the ORM bump will move phpstan-doctrine's findings).
- Produces: the last in-scope dependency step; hands the rest to deferred track T1.

This step deliberately stops short of ORM 3. `doctrine/orm` is pinned `2.15.*` in eight manifests (root:44, `Core/Application`:44, `Core/Tools`:35, `Addon/VirtualThermostat`:39, `Connector/NsPanel`:41, `Connector/Viera`:44, `Connector/Virtual`:38, `Connector/Tuya`:42); latest 2.x is 2.20.13. ORM 3 cannot resolve because five external packages Adam owns still pin `doctrine/orm ^2.6` and are all at their newest published version, last released August 2024 — that is track T1, not this task.

**This is the third and last place where a code change cannot be separated from a version change**: the three doctrine/orm patches will not apply at 2.20 without regeneration, and regenerating them is only possible against the new vendor source.

- [ ] **Step 1: Dry-run the whole cluster together**

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer update --dry-run doctrine/orm nettrine/orm nettrine/dbal nettrine/fixtures contributte/console -W'
```

Targets: `doctrine/orm 2.15.*` → `^2.20`; `nettrine/orm` → `^0.9` (v0.9.0, August 2024, is the **last** line supporting ORM 2 — v0.10.x requires `doctrine/orm ^3.3.0`); `nettrine/dbal` → `^0.9`; `nettrine/fixtures` → `^0.7.2` (v0.7.x still caps `symfony/console` at `^6.2.0`, which is fine here); `contributte/console` → `^0.10.1` (already allows `^6.4.2 || ^7.0.2`).

Expected: a clean resolution. **If `nettrine/fixtures` drags in `doctrine/orm ^3.3.0`, the constraint was set too high** — drop back to `^0.7.2` exactly.

Do **not** attempt `symfony/console ^7` here. There is no `nettrine/fixtures` release that offers console 7 on ORM 2, so console 7 requires ORM 3, which requires the five external releases. Seven first-party extensions also hardcode `symfony/console ^6.0` (`Module/Devices`:60, `Module/Triggers`:55, `Module/Accounts`:55, `Module/Ui`:60, `Plugin/WebServer`:56, `Plugin/ApiKey`:47, `Plugin/WsServer`:47) — leave them.

- [ ] **Step 2: Update, and expect the patches to fail**

```bash
docker compose run --rm --no-deps application sh -lc \
  'composer update doctrine/orm nettrine/orm nettrine/dbal nettrine/fixtures contributte/console -W 2>&1 | tail -40'
```

Expected: the update resolves, and the `cweagans/composer-patches` output reports failures for some of the five patches on the upgrade path. That is the expected outcome, not a surprise:

- Three patches target `../lib/Doctrine/ORM/Mapping/ClassMetadataFactory.php`, `../lib/Doctrine/ORM/Persisters/Entity/BasicEntityPersister.php` and `../lib/Doctrine/ORM/Persisters/Entity/JoinedSubclassPersister.php` (Ramsey UUID handling and a dynamic discriminator map). A 2.15 → 2.20 step moves their hunk offsets even though the `lib/` path still exists in ORM 2.
- The `doctrine/dbal` patch targets `../src/Connection.php` @ line 1857.
- The `nettrine/orm` patch targets `../src/ManagerRegistry.php`'s constructor — and note that `fastybird/simple-auth` declares a raw-URL patch for the same target that **overrides** the root's local one, leaving the vendored file unused.

- [ ] **Step 3: Regenerate each failing patch, and check whether it is still needed at all**

For each failure, diff the new upstream source against the patched intent before regenerating. ORM 2.20 may have absorbed the fix upstream, in which case the patch should be *deleted*, not regenerated. Budget explicit time for this; it is the single most likely place for this task to overrun.

```bash
docker compose run --rm --no-deps application sh -lc 'composer install 2>&1 | grep -i -E "patch|hunk" | tail -20'
```

Expected after regeneration: eleven patches applied, none failed.

- [ ] **Step 4: Do not bump `cweagans/composer-patches` in the same change**

It sits at 1.7.3 with 2.0.0 available (2025-10-30), and v2 changed patch resolution semantics. With eleven live patches — two of which are fetched over the network from `FastyBird/libraries-patches` — that is not a free move and belongs in its own change after this one settles.

- [ ] **Step 5: Full gate run** **(long)**

```bash
docker compose run --rm --no-deps application sh -lc 'make lint && make cs && make phpstan'
docker compose up -d database
docker run -d --name fb-loopback-redis --network container:fastybird-database redis:7
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  -e PHP_DATE_TIMEZONE=UTC fastybird-application sh -lc "make tests" 2>&1 | tail -40
```

Expected: `make phpstan` exits 0 (phpstan-doctrine 2.x will report differently against ORM 2.20 — regenerate the baseline in this pull request if the delta is real rather than suppressing it inline); `OK (1405 tests, 5663 assertions)`.

- [ ] **Step 6: Verify the production container still boots**

The nettrine bump is exactly what made Dependabot PR336 unbootable, and the entrypoint swallows DI compile errors behind a misleading database-timeout message. Check it directly rather than through the probe:

```bash
docker build -t fb-p6-orm -f docker/prod/Dockerfile .
docker run --rm --entrypoint php fb-p6-orm bin/fb-console.php dbal:run-sql 'select 1' 2>&1 | head -20
docker run --rm --entrypoint php fb-p6-orm bin/fb-console.php list 2>&1 | head -20
```

Expected: a real database connection error (no database is running) rather than a Nette DI compile error. **A `Nette\DI\` or schema-validation exception here means a DI config key was renamed** — for `nettrine/migrations` that would be `directory` → `directories`, which is why that package is not in this task's list.

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock src/FastyBird tools/patches tools/phpstan-baseline.neon
git commit -m "chore(deps): bump doctrine to 2.20 and nettrine to the last ORM-2 line"
```

---

## Phase deliverable

Phase 6 is complete when, on `main`:

- `ci-tests.yaml` is green with eleven-plus jobs, including the four gates this phase added (`docs-build`, the WebUi lint steps, `composer-validate`, `composer audit`) and a non-blocking PHP 8.4 leg.
- `pnpm install --frozen-lockfile && pnpm lint:js && pnpm lint:styles && pnpm build:ui && pnpm types && pnpm build && pnpm pretty:check` all pass in the Node 22 container, with `yarn.lock` deleted and no `yarn` invocation anywhere outside `docs/baseline.md`'s historical text and `docs/superpowers/**`.
- `docker build -f docker/prod/Dockerfile .` produces an image on PHP 8.3 that serves UI and API against MariaDB 10.11, with all five supervisor programs RUNNING and an empty exception log.
- Both forced exceptions recorded in `docs/baseline.md` are retired: `--ignore-engines` is gone from every call site, and `jsona` is at `^1.14`.
- `make qa`, `make coverage-clover` and `make mutations` all do what their names say, with the coverage line rate and the MSI recorded in the Phase 6 addendum.
- The three deferred tracks (T1 ORM 3, T2 PHP 8.4, T3 mathsolver) plus T4–T6 are open as named issues with the analysis above attached, so Phase 7 knows exactly what it is inheriting — and specifically knows that `FastyBird/libraries-patches` is still load-bearing and must not be deleted.