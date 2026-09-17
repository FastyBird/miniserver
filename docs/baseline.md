# The frozen baseline

This repository was frozen in November 2024. Before the merge described in
`docs/superpowers/specs/2026-09-09-miniserver-merge-design.md` could begin, we needed
one state that provably builds, analyses and tests clean, so that every later change can
be judged against it. This document is that state: what passes, how to reproduce it, and
what is knowingly broken.

If you are starting a later phase, read this first. If a gate fails for you, compare
against the commands here before assuming you broke something.

**Established at:** the final commit of the baseline phase, on branch
`t3code/analyze-miniserver-repo-merge`.

**Toolchain, and it is not negotiable:** PHP 8.2, Node 20, yarn 1, Composer 2.4.

> **Update (2026-09-11):** the toolchain has since moved to **PHP 8.4 and Node 24**.
> Every measurement below was taken on PHP 8.2 / Node 20 and is left unchanged as a
> dated record. `--ignore-engines` is no longer required.

The project's constraints permit newer PHP, but a dependency set two years old does not
behave the same three minor versions ahead. Every command below runs in a container for
that reason. Results obtained on a host running PHP 8.5 or Node 24 are not evidence.

> **Update, since this baseline was taken:** the toolchain moved from Node 20 to Node 24
> in a later change. The `--ignore-engines` exception this document records below as
> forced (see "Forced exceptions") is retired repo-wide as of that change -- it is no
> longer present in any install command, Dockerfile or CI job. Everything else on this
> page, including the gate results, reproduction commands and cold-reinstall numbers, is
> left exactly as it was measured against the Node 20 baseline; it is not re-measured
> here, and the `node:20`/`node:20-alpine` references below describe that historical
> measurement, not the current toolchain.

## Gate results

All six were run in the PHP 8.2 container against the final tree.

| Gate | Result |
|---|---|
| `make lint` | 3240 files checked, no syntax error, exit 0 |
| `make cs` | exit 0, about 26 seconds |
| `make phpstan` (src config) | `[OK] No errors` |
| `make phpstan` (tests config) | `[OK] No errors` |
| `make tests` | `OK (1405 tests, 5663 assertions)`, exit 0, 14m39s |
| `yarn build` | exit 0, about 6m23s, artefacts emitted |

The build writes `public/index.html`, `public/.vite/manifest.json` and 24 files into
`public/assets/`. That output is git-ignored and is not committed.

Watch the assertion count on the test suite, not just the exit code. A mis-wired run
reported 2347 assertions because the database-backed cases erred before asserting
anything. Both runs are "1405 tests"; only the assertion count distinguishes a real pass
from an early collapse.

## Reproducing each gate

### PHP: lint, coding standards, static analysis

```bash
docker compose build application          # php:8.2-fpm, Composer 2.4, all required extensions
docker compose run --rm --no-deps application sh -lc "composer install"
docker compose run --rm --no-deps application sh -lc "make lint"
docker compose run --rm --no-deps application sh -lc "make cs"
docker compose run --rm --no-deps application sh -lc "make phpstan"
```

### PHP: the test suite

This one has a trap. Twenty-five per-package `tests/common.neon` files hardcode
`host: 127.0.0.1`, and the Redis client defaults to `tcp://127.0.0.1:6379`. Those
files take precedence over the `FB_APP_PARAMETER__*` environment variables that
`docker-compose.yml` sets, so a compose-based run reaches nothing and produces several
hundred "Connection refused" errors.

The suite needs both services on the **test process's own loopback**. Share one network
namespace:

```bash
docker compose up -d database
docker run -d --name fb-loopback-redis \
  --network container:fastybird-database redis:latest

docker run --rm --network container:fastybird-database \
  -v "$PWD":/app -w /app -e PHP_DATE_TIMEZONE=UTC \
  <application-image> sh -lc "make tests"
```

Substitute your own image name; it is derived from the directory name. Do not "fix" this
by pointing the tests at compose hostnames, which is what the environment variables
suggest and what does not work.

Also note that `docker-compose.yml` publishes Redis on a fixed host port, which fails
outright on a machine already running another Redis. The namespace approach above avoids
publishing any port. The deployment phase rewrites these compose files and should fold
this in.

### JavaScript: install and build

```bash
docker compose build ui-server            # node:20-alpine, yarn 1.22
docker compose run --rm --no-deps ui-server sh -lc "yarn install --ignore-engines"
docker compose run --rm --no-deps ui-server sh -lc "yarn build"
```

`--ignore-engines` is required. See below.

## Forced exceptions

The baseline phase forbade dependency changes. Two were made anyway, deliberately, and
both are recorded here because an undocumented exception is a trap for whoever hits it next.

**`yarn install` requires `--ignore-engines`.** A transitive dependency,
`@intlify/shared@11.4.10`, declares `node >= 22` while this project is frozen at Node 20.
The flag is metadata-only and changes no resolved version. It must be carried into every
install instruction, Dockerfile and CI job until the toolchain moves past Node 20.

**`jsona` is pinned to `~1.12.0`.** Three modules -- `Module/Accounts`, `Module/Devices`,
`Module/Ui` -- import a symbol from `jsona/lib/simplePropertyMappers`; specifically, six
value imports of `RELATIONSHIP_NAMES_PROP`, the one symbol with no path off the subpath
(corrected from an original "Four modules" miscount -- see the Phase 6 evidence run
below). Version 1.13 introduced an `exports` map listing only `"."`, which blocks subpath
imports even though the files are still present, so three packages fail to build and the
application shell never builds at all. The declared range `^1.12` permitted 1.14. This is
not an upgrade: 1.12 is what the code was authored against, and pinning restores that.

> **Update, since this baseline was taken:** Phase 6 rewrote the six `RELATIONSHIP_NAMES_PROP`
> imports to come from the package root instead of the `jsona/lib/simplePropertyMappers`
> subpath, which is what the exports map in 1.13+ was blocking. The pin is retired:
> all three manifests now declare `jsona: ^1.14`, resolving to `1.14.0`.

Both exceptions share one root cause worth understanding, because it will recur. Neither
lock file had ever been committed. "Frozen" therefore never applied to transitive
dependencies, and the set now committed is a 2026 resolution of 2024 constraints. Where
a range allowed drift, we got drift.

## Known conditions, deliberately not fixed

None of these are regressions. All predate the merge, and all were left alone because the
baseline phase must not change behaviour.

**The `dg/bypass-finals` patch does not apply.** It targets a line that moved upstream.
It is accepted and non-blocking; the test suite passes without it.

**`composer validate --strict` exits 1.** Two pre-existing warnings remain:
`endroid/qr-code` uses an exact version constraint, and `mathsolver/mathsolver` is
unbound. Fixing either is a dependency change. Any later gate running that command must
drop `--strict` or tolerate warnings, or it will be red from birth.

**Two runtime defects are recorded in the spec, section 7.1.** The Shelly Gen2 WebSocket
client raises an argument-count error if executed, and a presenter returns an empty array
where its parent declares a non-empty list. Both are suppressed with truthful comments at
the call site and must be resolved before the affected features are relied upon.

**`FastyBird/libraries-patches` cannot be deleted, and vendoring did not fully take
effect.** A cold install on 2026-09-10 showed exactly which patches come from where.
Nine of the eleven applied patches resolve from `tools/patches/`. Two are still fetched
over the network from that repository:

- `nette/utils` — the root never declared it. `fastybird/json-api`,
  `fastybird/datetime-factory` and `fastybird/simple-auth` each declare it by raw URL.
- `nettrine/orm` — **the root does declare this one locally, and is overridden.**
  `fastybird/simple-auth` declares the same patch by raw URL, and a dependency's
  declaration wins over a root entry sharing its description key. The vendored file sits
  unused.

So the project still needs that repository reachable at install time, and one vendored
file is dead weight until the external libraries stop declaring it. Deleting the
repository would break `composer install` on every future checkout. It becomes
removable only once Phase 6 updates or absorbs those three libraries. Verify with
`grep -rl libraries-patches vendor/*/*/composer.json` returning nothing.

> **Update (2026-09-12), after a full audit of all eleven patches.** Three were deleted:
> `dg/bypass-finals` (never applied here at all -- `patches_applied: []` -- and it never
> fixed a library bug), `react/event-loop` (its last consumer disappeared in 2024) and the
> `nettrine/orm` root entry (the dead duplicate described just above; the dependency's copy
> is still the live one and is unaffected).
>
> Four more went in the two changes that followed. `doctrine/dbal` cast null to `''` for
> every parameter bound with an explicit string type -- not only in `quote()`, which was its
> stated purpose and has no caller in `src/`, but in `bindParameters()`, which is how
> Doctrine ORM binds every UPDATE. The two `doctrine/orm` "Ramsey uuid" patches pre-converted
> UUID identifiers to raw bytes before a delete, which upstream made unnecessary in 2.7.0 and
> 2.8.2 by passing `$types` through to `Connection::delete()` and `deleteJoinTableRecords()`;
> and the `ramsey/uuid-doctrine` patch existed only to make `UuidBinaryType` tolerate the raw
> bytes those two produced. All three had to go in one commit: with the ORM patches applied
> and the uuid-doctrine one removed, every delete throws `ConversionException`.
>
> Four patch files and four root entries across four packages remain, and five packages are
> patched at install time -- the fifth being `nettrine/orm` through `fastybird/simple-auth`.
>
> Two `extra` flags changed with them. `enable-patching` is now an explicit `true` rather
> than relying on `Patches::isPatchingEnabled()` returning true as a side effect of a
> non-empty `patches` block: emptying that block would otherwise disable **all** patching
> silently, including the dependency-supplied one. And `composer-exit-on-patch-failure` is
> now `true`, so a patch that fails to apply stops the install instead of printing a
> warning and exiting 0. A clean `composer install` was verified green under both.

**Coverage configuration is wrong.** In `tools/phpunit.xml`, the `<source><include>`
block lists test directories rather than source directories, so coverage and mutation
testing measure the tests themselves. Pre-existing; it makes any future coverage gate
meaningless until corrected.

**`tools/patches/nette-utils-array-offsetcheck.diff` is now declared in the root.** This
paragraph used to record that an experiment added the entry, found it inert because a
dependency's declaration wins, and reverted it. That was right about the mechanism and
wrong about the conclusion. Inert today is exactly what protective later looks like: the
moment `fastybird/json-api`, `fastybird/simple-auth` and `fastybird/datetime-factory`
become first-party source, their `extra.patches` leave the dependency graph, nothing
declares this patch, `composer install` exits 0 in silence, and `ArrayHash::offsetExists()`
reverts -- which breaks the explicit-null path in every Devices state manager. The entry
was re-added on 2026-09-12 under the description key `Bug: Offset check with null support`,
matching the dependency's key character for character. That match is load bearing: a
differing key makes both copies gather, the second fail, `patches_applied` mismatch
permanently, and `checkPatches()` uninstall and re-download `nette/utils` on every
subsequent install.

## Cold reinstall, verified 2026-09-10

The PHP half was proven from scratch: `vendor/` removed entirely, then
`composer install` from the committed lock file.

| Check | Result |
|---|---|
| Install | exit 0, 77 seconds |
| Packages | 253 installs, 0 updates, 0 removals |
| Lock file | unchanged |
| Versions | identical to the lock, including all three dev-branch commits |
| Patches | 9 applied from `tools/patches/`, 2 fetched over the network, 1 known failure |

The three dependencies pinned to branch commits all resolved, so upstream has not
garbage-collected them: `bunny/bunny` at 376626f, `clue/redis-react` at e928901,
`mathsolver/mathsolver` at 84f6f1c. That was the standing reproducibility risk and it is
now retired, though it can return at any time since those are branch references.

**A cold install does not produce `vendor/bin/fb-console`,** and nothing is wrong when
you notice that. Composer does not link a root package's own `bin` entries into
`vendor/bin/`, so that path will never exist while this package is the root. The console
is invoked through the repository's own `bin/fb-console.php`, which is what the compose
services and every supervisor program already use. Two extension documents still tell
readers to run `vendor/bin/fb-console`; that is correct only when the extension is
installed as a dependency of some other application, not here.

**The JavaScript half is proven too.** `node_modules/` removed from the root and from all
nine workspace packages, then `yarn install --ignore-engines --frozen-lockfile`.

| Check | Result |
|---|---|
| Install | exit 0, 160 seconds |
| Lock file | unchanged, so the lock fully determines the tree |
| Root packages | 1174 entries, identical to the pre-wipe count |
| Workspace packages | 9 of 9 restored, every `@fastybird/*` symlink resolving into `src/` |
| Hard errors | none |

Two notes for whoever runs this next. Run the container **detached**. An attached
`docker run` dies with `grpc: the client connection is closing` the moment the invoking
shell is cleaned up, which silently kills the install part-way through. And expect a
warning about `vue-component-type-helpers`: the lockfile pins that one package under both
`^3.3.9` and the floating `latest` tag, the only such entry in the file. Yarn resolves it
to a single version and warns, so it is currently harmless, but a lockfile containing
`latest` is not fully deterministic and should be cleaned up when dependencies are next
touched.

## Open items for later phases

- The existing CI workflows delegate to reusable workflows in the external
  `fastybird/.github` repository, which cannot be parameterised from here. The JavaScript
  job will fail on push until `--ignore-engines` can be passed. The phase that rewrites CI
  owns this.
- Base images are unpinned by digest: `php:8.2-fpm`, `node:20-alpine`, `mariadb`, `redis`.
  `.nvmrc` is advisory, and there is no `packageManager` field pinning yarn.
- A true cold reinstall, with `vendor/` and `node_modules/` removed, has not been proven.
  Three dependencies resolve to branch commits that upstream can garbage-collect:
  `bunny/bunny 0.6.x-dev`, `clue/redis-react 3.x-dev` and `mathsolver/mathsolver dev-main`.
  Worth one run before relying on this.

## Decisions taken while establishing this

Recorded so the state is legible rather than mysterious.

The work was committed sequentially on one branch rather than split into pull requests,
because pushing is the maintainer's call. The commit groups map onto the four intended
pull requests. Local verification substituted for CI gating, using the same commands CI
runs.

A ninth patch target was added and then reverted once evidence showed a dependency's raw
URL takes precedence over a root entry sharing its description key. The stale lock hash
that revert produced was refreshed with `composer update --lock`, verified to move no
package version.

One suppression comment was found to state a falsehood and was corrected rather than
deleted; the defect it masked is now recorded in the spec. Suppressions use
`@phpstan-ignore <identifier>` rather than `@phpstan-ignore-next-line`, because the
latter silences every error on the line regardless of the identifier written after it.

## Phase 6 evidence run (2026-09-17)

Task 3 of `docs/superpowers/plans/2026-09-11-phase-6-modernization.md` run against the
tree as it stood after PR #407 (Track A/Dependabot triage complete). Some of the plan's
own assumptions had already been overtaken by other work landed between 2026-09-11 and
this run -- `Library/WebUi` deleted, `doctrine/orm` upgraded to 3.x, `--ignore-engines`
retired -- so this section records what is true now, and says explicitly where it
diverges from the plan rather than silently reconciling the two.

**Composer.** `composer --version` inside the application image: `2.4.4` (2022-10-27) --
confirms Task 26/PR19's premise, the dev container is still on the pre-CVE-fix line.
`composer audit --locked`: **no security vulnerability advisories found.**
`composer why-not php 8.3`: every first-party package now requires `>=8.4.0` -- PHP 8.4
is the floor everywhere, not a target still being adopted. `composer why-not php 8.4`:
nothing blocks it, for the same reason. Both `T2`'s "PHP 8.4 blocked by
`orisai/object-mapper`" framing and Task 30/31's staged 8.3-then-8.4 plan are moot: this
repo has required 8.4 since before this evidence run, and `why-not` confirms nothing
downstream still expects less.

**Cold install.** `rm -rf vendor && composer install`: `Package operations: 275 installs,
0 updates, 0 removals` against a 275-entry lock. The 32-package gap this document's
original baseline and the Phase 6 plan both recorded (253 installs from 285 entries) is
**gone** -- installs now match the lock exactly. Three patches applied
(`contributte/monolog`, `nette/utils`, `softcreatr/jsonpath`), all three resolving from
local `tools/patches/*` files; none required a network fetch. The `nettrine/orm` patch
the plan's Step 2 expected to see fetched over the network from
`FastyBird/libraries-patches` no longer exists -- it was removed when this repo absorbed
`nettrine/orm`'s successor behaviour during the ORM 3 upgrade.

**PHPUnit coverage filter (Task 5).** Already fixed, not still broken as the plan
assumed. `tools/phpunit.xml`'s `<source><include>` block correctly lists
`../src/FastyBird/*/*/src`, carries an extensive comment explaining the original defect
in the past tense, and a `--filter ZZZ_NoSuchTest --coverage-text` run reports 2177
discovered classes -- nowhere near the 232 test classes the old broken filter measured
(confirmed separately by grep: 232 top-level type declarations exist under
`tests/cases/`, 2591 under `src/`). Whoever fixed this did not update this document to
say so; this entry is that update.

**`make qa` (Task 6, all four sub-fixes).** Also already done: `Makefile`'s `qa:` target
runs `make cs` then `make phpstan` then `make layers` as three sequential recipe lines,
with a comment above it explaining the `A & B` backgrounding bug in the past tense.
Verified live rather than trusted from the comment: writing a deliberately malformed
`ZzzTmp.php` and running `make qa` now exits `2` (`make: *** [Makefile:21: qa] Error 2`),
where the original bug would have exited `0`. `tools/phpstan.neon` and
`tools/phpstan.tests.neon` both pin `phpVersion: 80400` (tracking the runtime, not the
80200-then-80300 staged pin the plan describes -- consistent with PHP already being at
8.4). No stale `LoopWrapper` `excludePaths` entry remains. `tools/infection.json`'s log
paths already carry the `../` prefix. Zero `docker-compose` (v1 syntax) references
remain in the `Makefile`; all four docker targets use `docker compose` v2.

**Docker context and opcache comment (Task 7).** The one item in this sweep that was
genuinely still broken as described. Fixed in PR #413: `.dockerignore` now excludes
`**/node_modules` (four nested copies existed under `src/FastyBird/**` as of that PR) and
`.pnpm-store`; the `docker/prod/Dockerfile` opcache comment, which asserted opcache was
"already active by default" directly beneath a sentence proving the opposite, now says
what the evidence shows. Verified: production image builds, `php -m` lists Zend OPcache,
`composer check-platform-reqs` reports every requirement satisfied. One thing surfaced
and deliberately left for its own investigation: built and run on Apple Silicon, the
image emits `PHP Warning: JIT on AArch64 doesn't support opcache.jit_buffer_size above
128M` against the configured `256M` -- worth a look given this is an appliance and ARM
production hardware is plausible, but out of scope for a comment-accuracy fix.

**pnpm -- the four migration unknowns (Task 3 Step 5), materially different from the
plan's prediction.** `pnpm --version`: `10.34.5`. `pnpm config get
link-workspace-packages`: `undefined` (unset; default applies) -- **not** `false` as the
plan asserted. Tested directly rather than trusted either claim: `pnpm import` against
the current tree (no `pnpm-workspace.yaml`, internal references still plain `"0.0.0"`
semver, not `workspace:*`) auto-links some internal packages by name
(`@fastybird/tools is linked to ... from /app/src/FastyBird/Core/Tools`), confirming
`link-workspace-packages` behaves as `true` by default -- but then **fails outright**
(`ERR_PNPM_NO_MATCHING_VERSION`, exit 1, no `pnpm-lock.yaml` written) on
`@fastybird/metadata-library@0.0.0`, because a real, unrelated package by that exact name
is already published on the public npm registry (versions `1.0.0-dev.0` through
`.24`) and `0.0.0` matches none of them. This is a harder blocker than the plan's own
framing ("would resolve from the registry") suggests: Task 20's `workspace:*` conversion
is not a nicety, `pnpm import` cannot complete without it. Also narrower than recorded:
12 internal `"0.0.0"`-pinned cross-references remain across 4 workspace packages, not the
"31 internal references" the plan carried forward as an unverified upper bound --
consistent with the plan's own note that `Library/WebUi`'s removal was expected to shrink
this count, now confirmed. Steps 6 and 8 of Task 3 could not be run as written: Step 6
needs a working `pnpm-lock.yaml`, which Step 5 shows does not exist yet; Step 8 targets
`@fastybird/web-ui-theme-chalk`, a package that no longer exists now that `Library/WebUi`
is deleted.

**Node image patch levels (Task 3 Step 7).** `node:20` and `node:20-alpine` both report
`v20.20.2`, above the `>=20.19.0` floor the four Node-gated packages need. `yarn 1.22.22`
runs cleanly on `node:24-alpine`. `node:20`'s image creation date: `2026-04-22` -- Docker
Hub is still publishing patched builds past Node 20's own LTS window, which is the
argument for Task 40 staying a security-patching move rather than an urgent one.

**Floating image tags (Task 3 Step 9), worse than the plan's own framing.**
`mariadb:latest` resolves to **12.3.3** -- not "11 or 12" as the plan estimated, and two
full majors past the `10.11` this project's config and production both target.
`redis:latest` resolves to `8.10.1`. `composer:2` (what CI's `tools: composer:v2` step
installs) resolves to `2.10.3`; `composer:2.4` (the dev Dockerfile's pin) resolves to
`2.4.4`, the same pre-CVE-fix version the cold-install check above measured directly.

**jsona (Task 4 Step 3): this document's own "Four modules" was stale, corrected here.**
It is **three**: `Module/Accounts`, `Module/Devices`, `Module/Ui` -- verified by grep
against the current tree, matching the plan's own investigation exactly. 49 import
statements total (21 from the package root, 28 from `jsona/lib/JsonaTypes` -- all nine
distinct names imported there are types or interfaces, erased at compile time). The pin
rests on exactly **6** value imports of `RELATIONSHIP_NAMES_PROP` from
`jsona/lib/simplePropertyMappers`, the one symbol with no path off the subpath.

**`--ignore-engines` (Task 4 Step 2): already corrected, before this run.** This
document's own "Update, since this baseline was taken" note (above) already records the
flag as retired repo-wide following the Node 20 -> 24 move, and a live grep of
`Makefile`, `docker/` and `.github/` during this Phase 6 pass found zero remaining
references. The plan's Step 2 (distinguish `@intlify/shared` from
`stylelint-config-html` as two separate causes) is moot: there is no flag left to
misattribute the cause of.

## Phase 6 — pnpm migration (2026-09-17)

Tasks 19-24. Both forced exceptions recorded above are now retired: `--ignore-engines`
(dropped from the one remaining call site, `build/debian/make_deb.sh`, in a prior PR
this same day) and the `jsona` pin (lifted to `^1.14`, also earlier this day, once the
six `RELATIONSHIP_NAMES_PROP` imports moved off the `jsona/lib/simplePropertyMappers`
subpath the 1.13+ exports map was blocking).

`pnpm@10.34.5` pinned via `packageManager` in `package.json` (hex-encoded sha512;
corepack rejects the registry's own base64 `dist.integrity` directly, verified live).
`pnpm-lock.yaml` generated by `pnpm import`, never by resolve, and `yarn.lock` deleted.
The workspace enumerates to **8** packages, not the 15 the original plan draft
estimated -- that count predates the `Library/WebUi` deletion.

**Not a clean `DRIFTED: 0`, and recorded honestly rather than rounded up.** The
comparison script (Task 21 Step 2) flagged 2 differences against `yarn.lock`'s resolved
version set; both were investigated individually and are real explainable
non-drift, not a defect in the migration:

- `jiti`: yarn's `"jiti-v1@npm:jiti@^1.21.6"` is an `npm:` alias the script's regex
  can't parse -- it mis-keys the alias's local name as if it were the package name.
  The real `jiti@1.21.7` it points at is present in both lockfiles, unchanged.
- `undici-types`: yarn had `6.21.0` and `7.18.2` resolved side by side; pnpm has only
  `7.18.2`. Traced to `@types/conventional-commits-parser`'s own unconstrained
  `"@types/node": "*"` -- a type-only dependency of commitlint's own tooling that
  compiles nothing of this project's. pnpm satisfied the wildcard by reusing the
  already-resolved `@types/node@24.13.4`'s sibling `undici-types@7.18.2` instead of
  yarn's stray second copy.

Both deliberate nested duplicates this document doesn't otherwise track survived the
import: `date-fns` 3.6.0 (`Module/Devices`) and 4.4.0 (`Module/Accounts`) are both
present. `uuid` was never actually duplicated -- both packages already declared the
same `^14.0` range under yarn too, so a single shared resolution is correct on both
package managers.

**A phantom dependency yarn 1's flat hoisting was silently permitting, found by
`pnpm types`'s real exit code.** `config/extensions.ts` imports
`@fastybird/accounts-module`, `@fastybird/devices-module` and
`@fastybird/homekit-connector` directly, but none of the three were ever declared in
root `package.json` -- only reachable at all because yarn 1 hoists every workspace
member's dependencies into one flat tree regardless of what the importing package
itself declares. pnpm's isolated `node_modules` correctly rejected it with three
`TS2307` errors. Fixed by declaring all three as `workspace:*`; no new external
package was touched; pnpm just linked the already-present workspace members.

Full JS gate chain (`install --frozen-lockfile`, `lint:js`, `lint:styles`, `types`,
`build`, `pretty:check`) verified with real, correctly-captured exit codes -- an
earlier verification pass had piped `pnpm types` through `tail` and reported success
while it was actually failing on the phantom-dependency errors above, the exact false-
green mechanism this document's own "sanity-check any harness" principle warns about.
Production image verified directly rather than trusted from `docker build`'s own exit
code: 282 hashed `.js` files in `public/assets/`, `public/.vite/manifest.json` and
`public/index.html` all present, `composer check-platform-reqs` passes against it.
