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

**`jsona` is pinned to `~1.12.0`.** Four modules import a symbol from
`jsona/lib/simplePropertyMappers`. Version 1.13 introduced an `exports` map listing only
`"."`, which blocks subpath imports even though the files are still present, so three
packages fail to build and the application shell never builds at all. The declared range
`^1.12` permitted 1.14. This is not an upgrade: 1.12 is what the code was authored
against, and pinning restores that.

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
