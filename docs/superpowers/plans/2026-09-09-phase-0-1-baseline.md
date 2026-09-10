# Phase 0 and 1, Prepare and Baseline Green Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vendor the frozen patch set, fix the two structural defects that block installation, install the frozen dependency set on PHP 8.2 / Node 20 / yarn 1 with every forced version bump logged, and make `make lint`, `make cs`, `make phpstan`, `make tests` and `yarn build` pass — establishing the one working baseline the rest of the merge is validated against.

**Architecture:** No files move and no namespace changes happen in this phase; every change is either a dependency-resolution fix, a Docker toolchain fix, or a code-quality fix inside an existing file. All verification commands run inside the existing `.docker/dev/php` and `.docker/dev/node` containers, not on the host, because the host running this plan has PHP 8.5 and no Composer — a two-year-frozen dependency set must be resolved against PHP 8.2, not whatever the host happens to have.

**Tech Stack:** Composer 2.4 / PHP 8.2, yarn 1 / Node 20, PHPStan 1.12, PHP_CodeSniffer (orisai/coding-standard 3), PHPUnit 10 / paratest 7, Vite 5, Docker Compose (existing `docker-compose.yml`, `.docker/dev/{php,node}`).

**Spec:** docs/superpowers/specs/2026-09-09-miniserver-merge-design.md

## Global Constraints

- PHP `>=8.2.0` (root `composer.json` `require.php`), verification always through `.docker/dev/php` (`php:8.2-fpm`), never the host PHP.
- Node `>=20` (root `package.json` `engines.node`), verification always through `.docker/dev/node` once Task 8 fixes it, never the host Node.
- yarn 1 (`yarn -v` reports `1.22.22` in the fixed node image); no lerna/pnpm changes in this phase.
- No dependency version is bumped in this phase unless it is strictly required to make the frozen set install (D10); every such bump is logged in the PR2 body under `Forced exceptions` with the exact error that forced it.
- No pull request mixes a structural change (bin path, config path, Dockerfile fix) with a dependency version change (D11). PR1 is structural only. PR2 is dependency resolution only. PR3 fixes lint/cs/phpstan/tests/build findings that do **not** require a dependency change; any finding that does is deferred to a follow-up bump-only PR, not mixed into PR3.
- CI (the existing `.github/workflows/{lint,qa,static-analysis,tests}.yaml`, which call reusable workflows in `fastybird/.github`) must be green on one PR before the next PR opens.
- Conventional commit format `<type>(<scope>): <subject>`, scope from `core, module, connector, plugin, bridge, addon, automator, library, ui, infra, ci, deps, docs, cross` (per spec 4.8), even though commitlint enforcement itself only arrives in Phase 4.
- PHP namespaces stay `FastyBird\<Type>\<Name>`; the `src/FastyBird/` prefix does not change; no file under `src/FastyBird/*/*` is moved or renamed in this phase.
- `tools/phpstan.src.neon` and `tools/phpstan.tests.neon` stay level max with the existing per-package `ignoreErrors`/`excludePaths` composition; no error is silenced by lowering the level or by a baseline file — the risk table's baseline-file allowance is for the Phase 6 PHPStan 2 upgrade, not this phase.
- Every inline `// @phpstan-ignore-next-line` added in this phase carries a one-line, reviewable reason; none of them is a blanket ignore of an identifier repo-wide.

## Pull Requests

1. **PR0 — `chore(deps): vendor upstream composer patches and pin Node`** (Phase 0): Tasks 1–4.
2. **PR1 — `fix(core): point install scripts at Core/Application and repair the dev images`** (Phase 1, structural fixes): Tasks 5–9.
3. **PR2 — `chore(deps): install the frozen dependency set on PHP 8.2 / Node 20`** (Phase 1, resolution): Tasks 10–12.
4. **PR3 — `fix(core): make lint, cs, phpstan, tests and yarn build pass`** (Phase 1, green): Tasks 13–18.

---

### Task 1: Vendor the 10 upstream patch files

**Files:**
- Create: `tools/patches/contributte-monolog-src-loggerholder-php.patch`
- Create: `tools/patches/dg-bypass-finals-src-nativewrapper-php.patch`
- Create: `tools/patches/doctrine-dbal-src-connection-php.patch`
- Create: `tools/patches/doctrine-orm-lib-doctrine-orm-persisters-entity-basicentitypersister-php.patch`
- Create: `tools/patches/doctrine-orm-lib-doctrine-orm-persisters-entity-joinedsubclasspersister-php.patch`
- Create: `tools/patches/doctrine-orm-lib-doctrine-orm-mapping-classmetadatafactory-php.patch`
- Create: `tools/patches/nettrine-orm-src-managerregistry-php.patch`
- Create: `tools/patches/ramsey-uuid-doctrine-src-uuidbinarytype-php.patch`
- Create: `tools/patches/react-event-loop-src-loop-php.patch`
- Create: `tools/patches/softcreatr-jsonpath-src-filters-querymatchfilter-php.patch`
- Create: `tools/patches/nette-utils-array-offsetcheck.diff`

**Interfaces:**
- Consumes: nothing (first task).
- Produces: the 11 local patch files Task 2 repoints `composer.json` at.

These 11 files are the complete, verified content of `https://github.com/FastyBird/libraries-patches` (confirmed reachable and unchanged on 2026-09-09; `git ls-remote https://github.com/FastyBird/libraries-patches.git` returns `b15dab3f1d52642105fead2cde5cdd857d06075b refs/heads/master`). 10 of them are exactly the 10 URLs already referenced by the 8 `extra.patches` target entries in the current root `composer.json`. The 11th, `nette.array.offsetCheck.diff` (vendored here as `nette-utils-array-offsetcheck.diff`), is not referenced by the root `composer.json` at all — it is declared inside the installed dependency `vendor/fastybird/json-api/composer.json`'s own `extra.patches` block for `nette/utils`, and a real `composer install` prints a 9th "Applying patches for nette/utils" block sourced from this same raw URL. Vendoring it here and pointing the root manifest at it in Task 2 is required for the Phase 0 deliverable's claim that every patch applies from a local file rather than a URL. The repository is public; no auth token is required to fetch from it.

- [ ] **Step 1: Create the target directory**

```bash
mkdir -p tools/patches
```

- [ ] **Step 2: Fetch the 10 patch files from the upstream repository**

Either clone and copy, or curl each file directly (both give byte-identical results; use whichever the executor's network allows):

```bash
git clone --depth 1 https://github.com/FastyBird/libraries-patches.git /tmp/libraries-patches
cp /tmp/libraries-patches/contributte-monolog-src-loggerholder-php.patch tools/patches/
cp /tmp/libraries-patches/dg-bypass-finals-src-nativewrapper-php.patch tools/patches/
cp /tmp/libraries-patches/doctrine-dbal-src-connection-php.patch tools/patches/
cp /tmp/libraries-patches/doctrine-orm-lib-doctrine-orm-persisters-entity-basicentitypersister-php.patch tools/patches/
cp /tmp/libraries-patches/doctrine-orm-lib-doctrine-orm-persisters-entity-joinedsubclasspersister-php.patch tools/patches/
cp /tmp/libraries-patches/doctrine-orm-lib-doctrine-orm-mapping-classmetadatafactory-php.patch tools/patches/
cp /tmp/libraries-patches/nettrine-orm-src-managerregistry-php.patch tools/patches/
cp /tmp/libraries-patches/ramsey-uuid-doctrine-src-uuidbinarytype-php.patch tools/patches/
cp /tmp/libraries-patches/react-event-loop-src-loop-php.patch tools/patches/
cp /tmp/libraries-patches/softcreatr-jsonpath-src-filters-querymatchfilter-php.patch tools/patches/
rm -rf /tmp/libraries-patches
```

Fallback if `git clone` is blocked but plain HTTPS is allowed:

```bash
for f in \
  contributte-monolog-src-loggerholder-php.patch \
  dg-bypass-finals-src-nativewrapper-php.patch \
  doctrine-dbal-src-connection-php.patch \
  doctrine-orm-lib-doctrine-orm-persisters-entity-basicentitypersister-php.patch \
  doctrine-orm-lib-doctrine-orm-persisters-entity-joinedsubclasspersister-php.patch \
  doctrine-orm-lib-doctrine-orm-mapping-classmetadatafactory-php.patch \
  nettrine-orm-src-managerregistry-php.patch \
  ramsey-uuid-doctrine-src-uuidbinarytype-php.patch \
  react-event-loop-src-loop-php.patch \
  softcreatr-jsonpath-src-filters-querymatchfilter-php.patch ; do
  curl -fsSL -o "tools/patches/$f" "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/$f"
done
curl -fsSL -o "tools/patches/nette-utils-array-offsetcheck.diff" "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/nette.array.offsetCheck.diff"
```

- [ ] **Step 2: Verify all 11 files landed and are non-empty**

Run: `ls -la tools/patches/ | wc -l && find tools/patches \( -name '*.patch' -o -name '*.diff' \) -empty`
Expected: the directory listing shows 11 files (13 lines counting `.`, `..` and the header line from `ls -la`), and the `find … -empty` command prints nothing (no zero-byte file).

- [ ] **Step 3: Verify each file's first line looks like a real patch**

Run: `for f in tools/patches/*.patch tools/patches/*.diff; do head -c 3 "$f"; echo " <- $f"; done`
Expected: every line starts with `---` (a unified-diff header) followed by the filename — confirmed on 2026-09-09 for all 11 files (e.g. `dg-bypass-finals-src-nativewrapper-php.patch` starts `--- /dev/null`).

- [ ] **Step 4: Commit**

```bash
git add tools/patches/
git commit -m "chore(deps): vendor the 11 upstream libraries-patches files"
```

---

### Task 2: Repoint root `composer.json` `extra.patches` at the vendored files

**Files:**
- Modify: `composer.json` (the `extra.patches` block, 8 target entries / 10 URLs, plus a new 9th `nette/utils` entry)

**Interfaces:**
- Consumes: `tools/patches/*.patch` and `tools/patches/nette-utils-array-offsetcheck.diff` from Task 1.
- Produces: a root manifest with no raw-URL patch references, consumed by Task 10's `composer install`.

The current block (verified present at the time of writing, lines 322–352 of `composer.json`) is:

```json
    "extra": {
        "enable-patching": false,
        "patches": {
            "contributte/monolog": {
                "Bug: invalid callback definition": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/contributte-monolog-src-loggerholder-php.patch"
            },
            "dg/bypass-finals": {
                "Bug: mkdir check": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/dg-bypass-finals-src-nativewrapper-php.patch"
            },
            "doctrine/dbal": {
                "Bug: PDO:Quote null patch": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/doctrine-dbal-src-connection-php.patch"
            },
            "doctrine/orm": {
                "Bug: Ramsey uuid not working - Part 1": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/doctrine-orm-lib-doctrine-orm-persisters-entity-basicentitypersister-php.patch",
                "Bug: Ramsey uuid not working - Part 2": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/doctrine-orm-lib-doctrine-orm-persisters-entity-joinedsubclasspersister-php.patch",
                "Feature: Dynamic discriminator map": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/doctrine-orm-lib-doctrine-orm-mapping-classmetadatafactory-php.patch"
            },
            "nettrine/orm": {
                "Enable connection overrides": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/nettrine-orm-src-managerregistry-php.patch"
            },
            "ramsey/uuid-doctrine": {
                "Bug: Ramsey uuid conversion fallback": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/ramsey-uuid-doctrine-src-uuidbinarytype-php.patch"
            },
            "react/event-loop": {
                "Bug: Use native return type": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/react-event-loop-src-loop-php.patch"
            },
            "softcreatr/jsonpath": {
                "Feature: enable extended queries": "https://raw.githubusercontent.com/FastyBird/libraries-patches/master/softcreatr-jsonpath-src-filters-querymatchfilter-php.patch"
            }
        }
    }
```

- [ ] **Step 1: Replace every URL with the vendored relative path**

```json
    "extra": {
        "enable-patching": false,
        "patches": {
            "contributte/monolog": {
                "Bug: invalid callback definition": "tools/patches/contributte-monolog-src-loggerholder-php.patch"
            },
            "dg/bypass-finals": {
                "Bug: mkdir check": "tools/patches/dg-bypass-finals-src-nativewrapper-php.patch"
            },
            "doctrine/dbal": {
                "Bug: PDO:Quote null patch": "tools/patches/doctrine-dbal-src-connection-php.patch"
            },
            "doctrine/orm": {
                "Bug: Ramsey uuid not working - Part 1": "tools/patches/doctrine-orm-lib-doctrine-orm-persisters-entity-basicentitypersister-php.patch",
                "Bug: Ramsey uuid not working - Part 2": "tools/patches/doctrine-orm-lib-doctrine-orm-persisters-entity-joinedsubclasspersister-php.patch",
                "Feature: Dynamic discriminator map": "tools/patches/doctrine-orm-lib-doctrine-orm-mapping-classmetadatafactory-php.patch"
            },
            "nettrine/orm": {
                "Enable connection overrides": "tools/patches/nettrine-orm-src-managerregistry-php.patch"
            },
            "ramsey/uuid-doctrine": {
                "Bug: Ramsey uuid conversion fallback": "tools/patches/ramsey-uuid-doctrine-src-uuidbinarytype-php.patch"
            },
            "react/event-loop": {
                "Bug: Use native return type": "tools/patches/react-event-loop-src-loop-php.patch"
            },
            "softcreatr/jsonpath": {
                "Feature: enable extended queries": "tools/patches/softcreatr-jsonpath-src-filters-querymatchfilter-php.patch"
            },
            "nette/utils": {
                "Bug: Offset check with null support": "tools/patches/nette-utils-array-offsetcheck.diff"
            }
        }
    }
```

Leave `"enable-patching": false` unchanged (D-level decision, not touched in this phase). The new `nette/utils` entry is not a rename of an existing target — it is a 9th target added here so the root manifest's own declaration takes over from the URL-based one declared inside the external dependency `fastybird/json-api`, whose v0.19.0 metadata on Packagist does declare `extra.patches` for `nette/utils` (confirmed 2026-09-09).

**Guard before you make this edit.** Whether that dependency-declared patch is actually applied today depends on `cweagans/composer-patches` honouring `"enable-patching": false` for patches declared by dependencies. If it is already being applied, adding it at the root only changes where the file is fetched from, which is the intent. If it is *not* being applied, adding it at the root would newly apply a patch that was previously inert, which is a behaviour change and must not happen silently in a phase whose whole purpose is an unchanged baseline.

Run this first, against the pre-edit manifest:

```bash
docker compose exec -T application sh -lc \
  'composer install --no-interaction --no-progress 2>&1 | grep -c "Applying patches for nette/utils"'
```

- Prints `1`: the patch is already applied. Proceed with the edit as written; it is a sourcing change only.
- Prints `0`: the patch is inert. **Do not add the `nette/utils` entry.** Keep the 8 original targets, adjust Step 2's expected count from `9` to `8`, and record in the pull request body that `nette.array.offsetCheck.diff` was vendored into `tools/patches/` but deliberately left unreferenced so the baseline is unchanged. Removing the raw URL from the dependency's own manifest is out of scope here; Phase 3 removes extension-level patch blocks, and this one belongs to an external package that Phase 6 updates.

Note for later phases: the `FastyBird/libraries-patches` repository contains 15 patch and diff files, of which only these 10 plus `nette.array.offsetCheck.diff` are referenced. The remaining four (`doctrine.orm.uuid.1.diff`, `doctrine.orm.uuid.2.diff`, `nettrine.orm.mangerRegistry.diff`, `ramsey.uuid.doctrine.diff`) are superseded variants of the `.patch` files above and are not vendored.

- [ ] **Step 2: Verify the manifest is still schema-valid**

Run (host `jq`, no PHP needed): `jq -e '.extra.patches | to_entries | length == 9' composer.json`
Expected: prints `true` — 9 target packages (the original 8 plus the new `nette/utils` entry).

Run: `grep -c "raw.githubusercontent.com" composer.json`
Expected: `0` — no raw-URL patch reference remains anywhere in the file.

- [ ] **Step 3: Commit**

```bash
git add composer.json
git commit -m "chore(deps): point extra.patches at the vendored tools/patches files"
```

---

### Task 3: Pin the frozen Node version with `.nvmrc`

**Files:**
- Create: `.nvmrc`

**Interfaces:**
- Consumes: nothing.
- Produces: a documented Node version for anyone using `nvm`/`fnm`/`asdf` locally; consumed informally by later phases (Phase 4 restates it in `README.md`/`CLAUDE.md`, which do not exist yet).

- [ ] **Step 1: Create `.nvmrc`**

```
20
```

- [ ] **Step 2: Verify**

Run: `cat .nvmrc`
Expected: `20` (exactly, no trailing content besides the newline).

- [ ] **Step 3: Commit**

```bash
git add .nvmrc
git commit -m "chore(infra): pin the frozen Node version with .nvmrc"
```

---

### Task 4: Verify the Phase 0 deliverable and open PR0

**Files:**
- Create: none (verification only).

**Interfaces:**
- Consumes: Tasks 1–3.
- Produces: a merged PR0 that Task 5 branches from.

The dev PHP image (`.docker/dev/php/Dockerfile`, `php:8.2-fpm` with Composer 2.4 already baked in via `COPY --from=composer:2.4`) is unmodified in this phase — Task 7 fixes its missing `gd` extension. Because that fix has not landed yet, this task's verification `composer install` must pass `--ignore-platform-req=ext-gd` purely to prove **patch resolution**, not to prove full installability (PR2 proves that, after Task 7 lands).

- [ ] **Step 1: Build the existing dev PHP image and confirm the toolchain**

```bash
docker compose build application
docker compose run --rm application php -v
docker compose run --rm application composer --version
```

Expected: `PHP 8.2.33 (cli) …` and `Composer version 2.4.4 2022-10-27 14:39:29` (confirmed versions as built from `.docker/dev/php/Dockerfile` on 2026-09-09).

- [ ] **Step 2: Validate the manifest**

Run: `docker compose run --rm application composer validate`
Expected: `./composer.json is valid, but with a few warnings` followed by two pre-existing, unrelated warnings (`require.endroid/qr-code` exact-constraint warning and `require.mathsolver/mathsolver` unbound-constraint warning). No error.

- [ ] **Step 3: Prove the vendored patches resolve and apply from the local files, not a URL**

```bash
docker compose run --rm application sh -lc \
  "composer install --no-interaction --no-progress --ignore-platform-req=ext-gd 2>&1 | grep -A1 'Applying patches'"
```

Expected: 9 `Applying patches for <package>` blocks (`react/event-loop`, `contributte/monolog`, `dg/bypass-finals`, `doctrine/dbal`, `doctrine/orm` with 3 patches, `ramsey/uuid-doctrine`, `nettrine/orm`, `softcreatr/jsonpath`, `nette/utils`), and every path printed underneath starts with `tools/patches/`, not `https://raw.githubusercontent.com`. `dg/bypass-finals`'s patch is expected to print `Could not apply patch! Skipping` immediately under it — this is a pre-existing, non-blocking mismatch between the patch and the currently-resolved `dg/bypass-finals` version (confirmed on 2026-09-09: the patch targets line 203 of `NativeWrapper.php`, which no longer matches after `dg/bypass-finals` moved past `v1.4`); it is not part of this task's deliverable and is not fixed here (see Task 10 and Task 12's "Known warnings" note).

- [ ] **Step 4: Discard the throwaway install artifacts**

```bash
rm -rf vendor composer.lock
git status --short
```

Expected: empty output — `vendor/` and `composer.lock` are both listed in the current root `.gitignore` (`/vendor`, `/composer.lock`), so nothing to clean up in git; this step just leaves a clean working tree for Task 5.

- [ ] **Step 5: Push and open the pull request**

```bash
git push -u origin HEAD
gh pr create --title "chore(deps): vendor upstream composer patches and pin Node" \
  --body "$(cat <<'EOF'
## Summary
- Vendors the 11 patch files from FastyBird/libraries-patches into tools/patches/ and repoints the root composer.json extra.patches at the relative paths, adding a 9th target entry (nette/utils) that previously only resolved from a raw URL declared inside vendor/fastybird/json-api's own composer.json.
- Adds .nvmrc pinning Node 20.

## Verification (recorded 2026-09-09 against the existing .docker/dev/php image, PHP 8.2.33 / Composer 2.4.4)
- `composer validate`: valid, 2 pre-existing warnings, no errors.
- `composer install --ignore-platform-req=ext-gd`: all 9 patch targets (11 files) resolve from tools/patches/*.patch (and the new tools/patches/nette-utils-array-offsetcheck.diff); dg/bypass-finals's patch is skipped with a pre-existing, non-blocking warning (patch predates the currently-resolved 1.11.0 release) — tracked, not fixed, in this PR.
- `--ignore-platform-req=ext-gd` is required only because the dev PHP image is missing the gd extension; PR1 fixes that image.
EOF
)"
```

- [ ] **Step 6: Confirm CI is green before starting Task 5**

Run: `gh pr checks --watch`
Expected: all checks (`lint.yaml`, `qa.yaml`, `static-analysis.yaml`, `tests.yaml`) pass. Merge the PR before starting Task 5.

---

### Task 5: Point the root `composer.json` `bin` entries at `Core/Application`

**Files:**
- Modify: `composer.json` (the `bin` array)

**Interfaces:**
- Consumes: PR0 merged.
- Produces: a root manifest whose `bin` entries point at files that exist. Note what this does NOT produce: Composer does not link a ROOT package's own `bin` entries into `vendor/bin/`, so there is no `vendor/bin/fb-console` and there never will be while this package is the root. Verified by a cold install on 2026-09-10 — `vendor/bin/` contains only third-party binaries. The console is invoked through the repository's own `bin/fb-console.php`, which is what `docker-compose.yml` and every supervisor program already use. The fix still matters, because the previous entries pointed into a directory that does not exist at all.

Verified: `src/FastyBird/Library/Application/` does not exist; `src/FastyBird/Core/Application/bin/{fb-console,fb-console.php,fb-supervisor,fb-supervisor.php}` do exist. Current block:

```json
    "bin": [
        "src/FastyBird/Library/Application/bin/fb-console",
        "src/FastyBird/Library/Application/bin/fb-console.php",
        "src/FastyBird/Library/Application/bin/fb-supervisor",
        "src/FastyBird/Library/Application/bin/fb-supervisor.php"
    ],
```

- [ ] **Step 1: Repoint the four bin paths**

```json
    "bin": [
        "src/FastyBird/Core/Application/bin/fb-console",
        "src/FastyBird/Core/Application/bin/fb-console.php",
        "src/FastyBird/Core/Application/bin/fb-supervisor",
        "src/FastyBird/Core/Application/bin/fb-supervisor.php"
    ],
```

- [ ] **Step 2: Verify every referenced path exists**

Run: `jq -r '.bin[]' composer.json | while read -r p; do test -f "$p" && echo "OK $p" || echo "MISSING $p"; done`
Expected: 4 lines, all `OK`.

- [ ] **Step 3: Commit**

```bash
git add composer.json
git commit -m "fix(core): point composer bin entries at src/FastyBird/Core/Application/bin"
```

---

### Task 6: Clear the invalid `nettrineFixtures.paths` entry

**Files:**
- Modify: `var/config/common.neon`

**Interfaces:**
- Consumes: nothing beyond PR0.
- Produces: a container that compiles without pointing `Nettrine\Fixtures\DI\FixturesExtension` at a non-existent directory; consumed by Task 10's install and Task 16's `make tests`.

Verified: `%appDir%/fixtures` does not exist anywhere under the repository root; `nettrineFixtures` is registered at line 29 of `var/config/common.neon` and configured at lines 205–207:

```neon
nettrineFixtures:
    paths:
        - %appDir%/fixtures
```

- [ ] **Step 1: Replace the paths list with an empty list**

```neon
nettrineFixtures:
    paths: []
```

- [ ] **Step 2: Verify the directory reference is gone**

Run: `grep -n "appDir%/fixtures" var/config/common.neon`
Expected: no output (the pattern is no longer present).

- [ ] **Step 3: Commit**

```bash
git add var/config/common.neon
git commit -m "fix(core): clear the non-existent nettrineFixtures.paths entry"
```

---

### Task 7: Add the missing `gd` PHP extension to the dev image

**Files:**
- Modify: `.docker/dev/php/Dockerfile`

**Interfaces:**
- Consumes: nothing beyond PR0.
- Produces: a dev PHP image that satisfies every `ext-*` requirement in root `composer.json` without `--ignore-platform-req`; consumed by Task 10's `composer install`.

Verified on 2026-09-09: `docker compose run --rm application composer install` fails with `Root composer.json requires PHP extension ext-gd * but it is missing from your system`, and `php -m` inside the built image confirms every other required extension (`bcmath, curl, gmp, iconv, intl, json, mbstring, openssl, pcntl, session, simplexml, sockets, sodium, sqlite3, xml, zip`) is already present — only `gd` is missing. The current `apt-get install` and `docker-php-ext-install` blocks are:

```dockerfile
RUN apt-get install -y  \
    bzip2 \
    python3 \
    g++ \
    make \
    zlib1g-dev \
    libicu-dev \
    libgmp-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    wget \
    openssl  \
    libssl-dev \
    libpq-dev \
    git \
    cmake \
    autoconf \
    libtool \
    pkg-config \
    build-essential \
    libcurl4 \
    libcurl4-openssl-dev \
    libxslt1-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
    bcmath \
    calendar \
    ctype \
    curl \
    dom \
    exif \
    fileinfo \
    gmp \
    intl \
    mbstring \
    mysqli \
    opcache \
    pcntl \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    phar \
    simplexml \
    sockets \
    xml \
    xmlwriter \
    xsl \
    zip
```

- [ ] **Step 1: Add the two apt packages `gd` needs to compile with JPEG/FreeType support**

```dockerfile
RUN apt-get install -y  \
    bzip2 \
    python3 \
    g++ \
    make \
    zlib1g-dev \
    libicu-dev \
    libgmp-dev \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    wget \
    openssl  \
    libssl-dev \
    libpq-dev \
    git \
    cmake \
    autoconf \
    libtool \
    pkg-config \
    build-essential \
    libcurl4 \
    libcurl4-openssl-dev \
    libxslt1-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
```

- [ ] **Step 2: Configure and install `gd`**

```dockerfile
# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
    bcmath \
    calendar \
    ctype \
    curl \
    dom \
    exif \
    fileinfo \
    gd \
    gmp \
    intl \
    mbstring \
    mysqli \
    opcache \
    pcntl \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    phar \
    simplexml \
    sockets \
    xml \
    xmlwriter \
    xsl \
    zip
```

- [ ] **Step 3: Rebuild and verify the extension loads**

Run: `docker compose build application && docker compose run --rm application php -m | grep -x gd`
Expected: `gd` (confirmed working with exactly these two added apt packages and this configure line on 2026-09-09).

- [ ] **Step 4: Commit**

```bash
git add .docker/dev/php/Dockerfile
git commit -m "fix(infra): add the missing gd PHP extension to the dev PHP image"
```

---

### Task 8: Repair the dev Node image

**Files:**
- Modify: `.docker/dev/node/Dockerfile`

**Interfaces:**
- Consumes: nothing beyond PR0.
- Produces: a buildable Node 20 dev image; consumed by Task 11's `yarn install` and Task 17's `yarn build`.

Per spec 4.10, this image is defective in three ways, all confirmed on 2026-09-09: it is `FROM node:lts-alpine` (unpinned, now Node past 20), it runs `RUN yarn install` at build time with no `package.json` copied into the image (nothing to install against — the real install happens later from the bind-mounted volume), and it runs `RUN yarn global add @rollup/rollup-linux-arm64-musl`, which is an arm64/musl-specific optional dependency and wrong on other architectures. Current file:

```dockerfile
FROM node:lts-alpine

# Set working directory
WORKDIR /app

# Increase Node.js memory limit
ENV NODE_OPTIONS="--max_old_space_size=4096"

# Install dependencies using Yarn
RUN yarn install
RUN yarn global add @rollup/rollup-linux-arm64-musl

EXPOSE 3000
EXPOSE 6006

CMD ["yarn", "dev"]
```

- [ ] **Step 1: Pin the base image and delete the two broken `RUN` lines**

```dockerfile
FROM node:20-alpine

# Set working directory
WORKDIR /app

# Increase Node.js memory limit
ENV NODE_OPTIONS="--max_old_space_size=4096"

EXPOSE 3000
EXPOSE 6006

CMD ["yarn", "dev"]
```

- [ ] **Step 2: Rebuild and verify the toolchain versions**

Run: `docker build -f .docker/dev/node/Dockerfile -t fastybird-node-dev . && docker run --rm fastybird-node-dev node -v && docker run --rm fastybird-node-dev yarn -v`
Expected: `v20.20.2` and `1.22.22` (confirmed on 2026-09-09; exact patch version of Node 20 may drift as the `node:20-alpine` tag is updated upstream, but the major version stays 20).

- [ ] **Step 3: Commit**

```bash
git add .docker/dev/node/Dockerfile
git commit -m "fix(infra): pin the dev Node image to node:20-alpine and remove the broken RUN lines"
```

---

### Task 9: Verify PR1 and open the pull request

**Files:**
- Create: none (verification only).

**Interfaces:**
- Consumes: Tasks 5–8.
- Produces: a merged PR1 that Task 10 branches from.

- [ ] **Step 1: Rebuild both dev images from the fixed Dockerfiles**

```bash
docker compose build application
docker build -f .docker/dev/php/Dockerfile -t fastybird-php-dev .
docker build -f .docker/dev/node/Dockerfile -t fastybird-node-dev .
```

Expected: all three builds exit 0. The explicit `-t fastybird-php-dev` tag (mirroring the Node image's `fastybird-node-dev` tag) is required because `docker compose run --rm application ...` deletes its container on exit, so `docker compose images -q application` never resolves to an image ID later — Task 16 and Task 18 run the test suite against this stable `fastybird-php-dev` tag instead.

- [ ] **Step 2: Re-run the Phase 0 patch-resolution check, this time without `--ignore-platform-req`**

Run: `docker compose run --rm application composer install --no-interaction --no-progress --dry-run 2>&1 | tail -5`
Expected: no `Root composer.json requires PHP extension ext-gd` error (Task 7 fixed it); the dry run proceeds to dependency resolution instead of failing on a platform check. (Full resolution success is Task 10's deliverable, not this one's — this step only confirms the platform-requirement blocker from PR0's Task 4 is gone.)

- [ ] **Step 3: Confirm no dependency file changed**

Run: `git diff --stat main -- composer.json composer.lock package.json yarn.lock`
Expected: empty output — this PR touches no manifest content besides the `bin` array (already committed in Task 5) and no lock file, satisfying D11.

- [ ] **Step 4: Push and open the pull request**

```bash
git push -u origin HEAD
gh pr create --title "fix(core): point install scripts at Core/Application and repair the dev images" \
  --body "$(cat <<'EOF'
## Summary
- Root composer.json `bin` entries pointed at the non-existent src/FastyBird/Library/Application/bin/*; repointed at src/FastyBird/Core/Application/bin/* where the files actually live.
- var/config/common.neon nettrineFixtures.paths pointed at %appDir%/fixtures, which does not exist; cleared to an empty list.
- .docker/dev/php/Dockerfile was missing the gd extension required by root composer.json; added libfreetype6-dev/libjpeg62-turbo-dev and `docker-php-ext-install gd`.
- .docker/dev/node/Dockerfile was FROM node:lts-alpine (unpinned) and ran two build-time RUN commands that fail or don't apply outside one specific architecture; pinned to node:20-alpine and removed both.

## Verification (recorded 2026-09-09)
- `docker compose build application`: OK.
- `php -m` in the rebuilt image lists `gd`.
- `docker build -f .docker/dev/node/Dockerfile`: OK; `node -v` reports v20.20.2, `yarn -v` reports 1.22.22.
- No dependency manifest or lock file changed in this PR.
EOF
)"
```

- [ ] **Step 5: Confirm CI is green before starting Task 10**

Run: `gh pr checks --watch`
Expected: all checks pass. Merge before starting Task 10.

---

### Task 10: Install the PHP dependency set on PHP 8.2 and commit `composer.lock`

**Files:**
- Create: `composer.lock`
- Modify: `.gitignore` (remove the `/composer.lock` line)

**Interfaces:**
- Consumes: PR0 and PR1 merged (patches vendored, bin/fixtures fixed, `gd` present).
- Produces: `composer.lock` and an installed `vendor/` that Task 13–16 run against.

Root `composer.json` carries three constraints the spec flags as install-resolution risk: `bunny/bunny 0.6.x-dev`, `clue/redis-react ^3@dev` and `mathsolver/mathsolver @dev` (resolved through the `{"type": "vcs", "url": "https://github.com/mathsolver/mathsolver.git"}` repository entry). Each has a concrete, verified decision procedure below rather than a blanket "bump if it fails."

- [ ] **Step 1: Run the install inside the fixed PHP 8.2 container**

```bash
docker compose run --rm application composer install --no-interaction --no-progress
```

Expected, confirmed on 2026-09-09 with the patches from PR0 and the `gd` fix from PR1 in place: **the install succeeds with zero forced exceptions** — `Package operations: 253 installs, 0 updates, 0 removals`, and specifically:
- `bunny/bunny` locks to `0.6.x-dev 376626f` (the `0.6.x` branch on `https://github.com/jakubkulhan/bunny.git`, confirmed present via `git ls-remote`).
- `clue/redis-react` locks to `3.x-dev e928901` (the `3.x` branch on `https://github.com/clue/reactphp-redis.git`; Packagist itself lists no tagged v3 release yet, only up to `v2.8.0`, so this dev-branch constraint is not optional — it is the only route to a v3 API).
- `mathsolver/mathsolver` locks to `dev-main 84f6f1c` (the `main` branch of `https://github.com/mathsolver/mathsolver.git`, the only branch composer's `@dev` constraint has to pick from besides `update`; the repository carries no tags).
- All 9 patch targets apply except `dg/bypass-finals`, which prints `Could not apply patch! Skipping` (same pre-existing mismatch recorded in Task 4 — not a new failure introduced here, and not fixed here because fixing it means pinning `dg/bypass-finals` to an older version, which is a dependency-version change and belongs in a separate bump-only PR if `make tests` in Task 16 turns out to need it).

**If any of the three does NOT resolve as above**, apply this decision procedure before touching anything else:

1. Re-run with `composer install -vvv 2>&1 | tee /tmp/composer-verbose.log` and find the exact "Problem" block.
2. **bunny/bunny**: if the error names `bunny/bunny` directly ("could not find a matching version"), check `git ls-remote https://github.com/jakubkulhan/bunny.git` for a branch matching `0.6.x` or `0.6.*`; if one still exists under a different name, change the root constraint to `dev-<branch-name>` and log it under `Forced exceptions`. If no `0.6.x`-compatible branch exists at all, fall back to the newest tagged `0.5.x` release (`v0.5.6` was the newest tag as of 2026-09-09) and log that as a forced downgrade with the exact composer error.
3. **clue/redis-react**: if the error names `clue/redis-react`, first check whether the error is actually about a *different* package pinning a stable-only constraint on it (`composer why-not clue/redis-react 3.x-dev`) — fix that package's constraint instead, since `clue/redis-react`'s own `^3@dev` constraint already carries the stability flag it needs. Only if `https://github.com/clue/reactphp-redis.git` no longer has a `3.x` branch, fall back to the newest available `2.x` tag and log it as a forced downgrade, flagging that this changes the Redis client's async API surface (used by `fastybird/redisdb-plugin` and its two bridges) and may need a follow-up code fix, not just a version bump.
4. **mathsolver/mathsolver**: if the error names `mathsolver/mathsolver`, confirm `https://github.com/mathsolver/mathsolver.git` is still reachable (`git ls-remote`); if the `main` branch was renamed or deleted, pin the constraint explicitly to whichever branch exists (e.g. `"mathsolver/mathsolver": "dev-<branch>"`) and log it.
5. For any **other** package that blocks resolution (not one of the three above), read the "Problem" block's named package, bump only that package's constraint to the lowest version that resolves, and log the exact composer error text under `Forced exceptions`. Never bump a package composer did not name as the blocker.

- [ ] **Step 2: Remove `/composer.lock` from `.gitignore`**

Current `.gitignore`:

```
.idea
/node_modules
/vendor
/composer.lock
/yarn.lock
.DS_Store
```

New:

```
.idea
/node_modules
/vendor
.DS_Store
```

(`/yarn.lock` is removed in Task 11, together with the yarn install, so both dependency-lock removals land as one coherent pair of edits within this same PR — still no structural change is mixed in.)

- [ ] **Step 3: Verify the lock file matches the manifest**

Run: `docker compose run --rm application composer validate --no-check-all --strict 2>&1 | tail -5`
Expected: no "lock file is not up to date" warning (composer regenerated it in Step 1, so it is current by construction).

- [ ] **Step 4: Commit**

```bash
git add composer.lock .gitignore
git commit -m "chore(deps): install the frozen PHP dependency set on PHP 8.2 and commit composer.lock"
```

---

### Task 11: Install the JS dependency set on Node 20 and commit `yarn.lock`

**Files:**
- Create: `yarn.lock`
- Verify only: `.gitignore` (Task 10 Step 2 already removes both the `/composer.lock` and `/yarn.lock` lines in one edit; this task only confirms that content, it does not edit the file again)

**Interfaces:**
- Consumes: PR1's fixed `.docker/dev/node/Dockerfile`.
- Produces: `yarn.lock`, consumed by Task 17's `yarn build`.

Root `package.json` `workspaces` covers `src/FastyBird/{Addon,Automator,Bridge,Connector,Core,Library,Module,Plugin}/**/*`. No `yarn.lock` exists yet in the repository (first-ever real install against this exact manifest).

- [ ] **Step 1: Run yarn install inside the fixed Node 20 image**

```bash
docker run --rm -v "$PWD":/app -w /app fastybird-node-dev \
  sh -lc "yarn install --network-timeout 600000"
```

(The extended `--network-timeout` is needed because the workspace pulls in 8 UI packages' worth of dependencies including the 2,037-SVG-file `@fastybird/web-ui-icons` package and Storybook; a handful of `eslint@9.x`/`vue-i18n@10.x`/etc. deprecation warnings are expected and not a failure — confirmed benign on 2026-09-09.)

Expected: exits 0, produces `yarn.lock` at the repository root.

**If yarn install fails to resolve**, apply the same decision procedure as Task 10 Step 1's fallback, adapted to yarn: identify the exact package yarn names as unresolvable, check whether it is one of the workspace's own 8 packages (in which case the fix is almost certainly a typo'd version range inside that extension's own `package.json`, not a forced bump) versus an external npm dependency (in which case bump only that package to the lowest version yarn accepts, and log it under `Forced exceptions` in the PR2 body).

- [ ] **Step 2: Verify `/yarn.lock` is already gone from `.gitignore`**

Confirm the file (already edited by Task 10 Step 2, which removed both `/composer.lock` and `/yarn.lock` in one commit) now reads:

```
.idea
/node_modules
/vendor
.DS_Store
```

Run: `grep -c "yarn.lock\|composer.lock" .gitignore`
Expected: `0`.

- [ ] **Step 3: Verify the lock file is non-empty and workspace-consistent**

Run: `wc -l yarn.lock && yarn --cwd . check --integrity 2>&1 | tail -5` — the second command must also run inside the container:

```bash
docker run --rm -v "$PWD":/app -w /app fastybird-node-dev sh -lc "yarn check --integrity 2>&1 | tail -5"
```

Expected: `yarn.lock` has thousands of lines (a workspace this size typically locks well over 10,000 lines), and `yarn check` reports no integrity mismatch.

- [ ] **Step 4: Commit**

```bash
git add yarn.lock
git commit -m "chore(deps): install the frozen JS dependency set on Node 20 and commit yarn.lock"
```

---

### Task 12: Verify PR2 and open the pull request

**Files:**
- Create: none (verification only).

**Interfaces:**
- Consumes: Tasks 10–11.
- Produces: a merged PR2 that Task 13 branches from; `vendor/` and `node_modules/` that Tasks 13–17 all depend on.

- [ ] **Step 1: Confirm both lock files are tracked and both dependency directories are still git-ignored**

Run: `git ls-files composer.lock yarn.lock && git check-ignore -v vendor node_modules`
Expected: both lock files listed as tracked; `git check-ignore` prints a match for both `vendor` (`/vendor`) and `node_modules` (`/node_modules`) from `.gitignore`, confirming they stay untracked.

- [ ] **Step 2: Re-run both installs from a clean state as a final sanity check**

```bash
rm -rf vendor node_modules
docker compose run --rm application composer install --no-interaction --no-progress
docker run --rm -v "$PWD":/app -w /app fastybird-node-dev sh -lc "yarn install --frozen-lockfile"
```

Expected: both exit 0; `composer install` reports "Installing dependencies from lock file" (not "Updating dependencies"), confirming the committed `composer.lock` is authoritative; `yarn install --frozen-lockfile` succeeds without modifying `yarn.lock`.

- [ ] **Step 3: Push and open the pull request**

```bash
git push -u origin HEAD
gh pr create --title "chore(deps): install the frozen dependency set on PHP 8.2 / Node 20" \
  --body "$(cat <<'EOF'
## Summary
Installs the frozen composer.json/package.json dependency set for the first time against PHP 8.2 / Composer 2.4 and Node 20 / yarn 1, and commits both lock files.

## Forced exceptions
None. All three flagged dev-branch/VCS constraints resolved cleanly as of 2026-09-09:
- bunny/bunny -> 0.6.x-dev 376626f (branch 0.6.x on jakubkulhan/bunny)
- clue/redis-react -> 3.x-dev e928901 (branch 3.x on clue/reactphp-redis)
- mathsolver/mathsolver -> dev-main 84f6f1c (branch main, the VCS repository has no tags)

## Known warnings (not forced exceptions, not fixed here)
- `dg/bypass-finals`'s patch (tools/patches/dg-bypass-finals-src-nativewrapper-php.patch) fails to apply against the resolved dg/bypass-finals 1.11.0 with "Could not apply patch! Skipping" — the patch's line-203 context predates a since-changed NativeWrapper.php. Composer does not treat this as fatal. Left as-is; PR3 will surface it as a real problem only if make tests fails because of it, in which case the fix (pinning dg/bypass-finals to an older release) goes in its own bump-only PR per D11.
EOF
)"
```

- [ ] **Step 4: Confirm CI is green before starting Task 13**

Run: `gh pr checks --watch`
Expected: all checks pass. Merge before starting Task 13.

---

### Task 13: Fix `make phpstan`'s src-config findings

**Files:**
- Modify: up to ~65 files under `src/FastyBird/*/*/src/` (every file phpstan names in Step 1's JSON output)

**Interfaces:**
- Consumes: PR2's `vendor/`.
- Produces: a src tree that passes `vendor/bin/phpstan analyse -c tools/phpstan.src.neon`; consumed by Task 15's combined `make phpstan` check.

Confirmed on 2026-09-09 against the installed dependency set from PR2: `vendor/bin/phpstan analyse -c tools/phpstan.src.neon` reports **250 errors** in **150 files**, of which:
- **240** are `missingType.checkedException` — a method throws a checked exception (overwhelmingly `Ramsey\Uuid\Exception\InvalidArgumentException`, plus `Nette\DI\NotAllowedDuringResolvingException` inside 17 DI `*Extension::{loadConfiguration,beforeCompile}()` methods) that is not declared in its PHPDoc `@throws`. This category resolves to **0 in three fix-and-rerun iterations** (fixing the deepest, currently-reported methods surfaces their now-visible callers on the next run — this is expected, checked-exception propagation, not a new regression).
- **10** are one-off findings (5 distinct identifiers) that do not repeat and are not checked-exception propagation. Each is suppressed with a git-blamable inline `// @phpstan-ignore-next-line <identifier> (<reason>)` comment rather than fixed with a behavioural code change, because this phase changes nothing behavioural (spec: "Nothing is upgraded during the merge; modernization is a later phase") — silencing with a visible, reviewable, per-line comment is the correct scope for a structural/toolchain phase, in contrast to an invisible baseline file (which this plan does not use, matching the Global Constraints).

- [ ] **Step 1: Reproduce the baseline finding count**

Run: `docker compose run --rm application sh -lc "XDEBUG_MODE=off vendor/bin/phpstan analyse -c tools/phpstan.src.neon --error-format=json 2>/dev/null" > /tmp/phpstan-src.json && jq '.totals.file_errors' /tmp/phpstan-src.json`
Expected: `250` (or close to it — if the exact count has drifted because `composer.lock` re-resolved slightly differently, treat the number below as approximate and re-derive the buckets with `jq '[.files[].messages[].identifier] | group_by(.) | map({(.[0]): length}) | add' /tmp/phpstan-src.json` instead of assuming 250 exactly).

- [ ] **Step 2: Suppress the 10 one-off findings with a reason comment each**

`src/FastyBird/Connector/HomeKit/src/Commands/Install.php`, insert one line directly above the reported `array_map(` call (found at the `ChoiceQuestion` construction inside the device value-mapping question):

```diff
+						// @phpstan-ignore-next-line argument.type (Symfony ChoiceQuestion accepts the label array at runtime; the closure's declared string|null return keeps PHPStan strict)
 						array_map(
 							static fn ($item): string|null => is_array($item) ? $item[1] : $item,
 							$options,
 						),
```

`src/FastyBird/Connector/NsPanel/src/Commands/Install.php`, the identical pattern:

```diff
+						// @phpstan-ignore-next-line argument.type (Symfony ChoiceQuestion accepts the label array at runtime; the closure's declared string|null return keeps PHPStan strict)
 						array_map(
 							static fn ($item): string|null => is_array($item) ? $item[1] : $item,
 							$options,
 						),
```

`src/FastyBird/Connector/Shelly/src/API/Gen2WsApi.php`:

```diff
+		// @phpstan-ignore-next-line arguments.count (ratchet/pawl ClientNegotiator constructor became optional-arg only in a later release than the one PHPStan's stub ships for)
 		$negotiator = new RFC6455\Handshake\ClientNegotiator();
```

`src/FastyBird/Core/Application/src/Boot/Configurator.php`, the whole `loadContainer()` method gets three comments:

```diff
 		/** @infection-ignore-all */
 		$loader = new ContainerLoader(
 			$buildDir,
 			boolval($this->staticParameters['debugMode']),
 		);

+		// @phpstan-ignore-next-line argument.type (PHPStan cannot see through Nette's own array<string> normalisation of $configFiles)
 		$containerKey = $this->getContainerKey($this->configs);

 		$this->reloadContainerOnDemand($loader, $containerKey, $buildDir);

 		$containerClass = $loader->load(
+			// @phpstan-ignore-next-line argument.type (ContainerLoader::load() callback is void by contract in this Nette version; PHPStan's stub still expects a return value)
 			fn (Compiler $compiler) => $this->generateContainer($compiler),
 			$containerKey,
 		);
+		// @phpstan-ignore-next-line function.alreadyNarrowedType (Defensive runtime assertion kept intentionally even though static analysis can already prove it)
 		assert(is_subclass_of($containerClass, Container::class));

 		return $containerClass;
```

(This one comment suppresses both `function.alreadyNarrowedType` findings that land on the `assert(...)` line — the `assert()` call itself and the `is_subclass_of()` call inside it.)

`src/FastyBird/Core/Application/src/Presenters/BasePresenter.php`:

```diff
+		// @phpstan-ignore-next-line return.type (Empty array is a valid layout list at runtime; narrowing to non-empty-array would be a behavioural change)
 		return $this->templateFactory?->getLayouts() ?? [];
```

`src/FastyBird/Core/Tools/src/DI/ToolsExtension.php`:

```diff
 			$builder->addDefinition($this->prefix('sentry.client'), new DI\Definitions\ServiceDefinition())
 				->setType(Sentry\ClientInterface::class)
+				// @phpstan-ignore-next-line argument.type (Nette ServiceDefinition::setFactory() accepts a [service, method] callable array at runtime)
 				->setFactory([$sentryClientBuilderService, 'getClient']);
```

`src/FastyBird/Plugin/RedisDbCache/src/Caching/Journal.php`:

```diff
+	// @phpstan-ignore-next-line method.childReturnType (Nette\Caching\Storages\Journal::clean() signature changed upstream; keeping the wider return type is a pre-existing, deliberate deviation)
 	public function clean(array $conditions): array|null
```

- [ ] **Step 3: Verify exactly the 10 one-offs are gone and only `missingType.checkedException` remains**

Run: `docker compose run --rm application sh -lc "XDEBUG_MODE=off vendor/bin/phpstan analyse -c tools/phpstan.src.neon --error-format=json 2>/dev/null" > /tmp/phpstan-src2.json && jq '[.files[].messages[].identifier] | group_by(.) | map({key: .[0], count: length})' /tmp/phpstan-src2.json`
Expected: a single bucket, `{"key":"missingType.checkedException","count":240}` (or the number Step 1 actually reported, minus 10).

- [ ] **Step 4: Mechanically add the missing `@throws` tags, iterating to convergence**

Write this script (temporary, not committed — it is a one-time migration helper, not project tooling):

```python
#!/usr/bin/env python3
"""Mechanically add missing @throws PHPDoc tags reported by PHPStan's
missingType.checkedException. Save as /tmp/fix_checked_exceptions.py."""
import json
import re
import sys

METHOD_RE_TMPL = r'^(\s*)((?:final\s+|abstract\s+|static\s+)*(?:public|protected|private)\s+(?:static\s+)?function\s+{name}\s*\()'


def load_messages(path, identifier):
    data = open(path, "r", errors="replace").read()
    idx = data.find("{")
    d = json.loads(data[idx:])
    out = []
    for f, v in d["files"].items():
        f = f.replace("/app/", "")
        f = re.sub(r"\s*\(in context of class [^)]*\)\s*$", "", f)
        for m in v["messages"]:
            if m.get("identifier") == identifier:
                out.append((f, m["line"], m["message"]))
    return out


def resolve_alias(fqcn, use_lines):
    parts = fqcn.split("\\")
    for u in use_lines:
        if u.rstrip(";") == fqcn:
            return parts[-1]
    for u in use_lines:
        u_clean = u.rstrip(";")
        alias = u_clean.split("\\")[-1]
        if fqcn == u_clean:
            return alias
        if fqcn.startswith(u_clean + "\\"):
            return alias + "\\" + fqcn[len(u_clean) + 1:]
    return "\\" + fqcn


def collect_use_lines(lines):
    return [m.group(1) for l in lines if (m := re.match(r"^\s*use\s+([A-Za-z0-9_\\]+)\s*;", l))]


def process_file(path, method_to_exceptions):
    with open(path) as f:
        lines = f.readlines()
    use_lines = collect_use_lines(lines)
    inserts = []
    for method_name, exc_fqcns in method_to_exceptions.items():
        pattern = re.compile(METHOD_RE_TMPL.format(name=re.escape(method_name)))
        match_idx = next((i for i, l in enumerate(lines) if pattern.match(l)), None)
        if match_idx is None:
            print(f"  ! could not locate method {method_name} in {path}, skipping")
            continue
        indent = pattern.match(lines[match_idx]).group(1)
        aliases = sorted({resolve_alias(fqcn, use_lines) for fqcn in exc_fqcns})
        j = match_idx - 1
        while j >= 0 and lines[j].strip() == "":
            j -= 1
        if j >= 0 and lines[j].strip() == "*/":
            close_idx = j
            open_idx = close_idx
            while open_idx >= 0 and "/**" not in lines[open_idx]:
                open_idx -= 1
            existing_block = "".join(lines[open_idx:close_idx + 1])
            new_tag_lines = [f"{indent} * @throws {a}\n" for a in aliases if f"@throws {a}" not in existing_block]
            if new_tag_lines:
                inserts.append((close_idx, new_tag_lines))
        else:
            block = [f"{indent}/**\n"] + [f"{indent} * @throws {a}\n" for a in aliases] + [f"{indent} */\n"]
            inserts.append((match_idx, block))
    if not inserts:
        return False
    inserts.sort(key=lambda t: t[0], reverse=True)
    for idx, new_lines in inserts:
        lines[idx:idx] = new_lines
    with open(path, "w") as f:
        f.writelines(lines)
    return True


def main(json_paths):
    by_file = {}
    for jp in json_paths:
        for f, line, message in load_messages(jp, "missingType.checkedException"):
            m = re.search(r"Method ([\w\\]+)::(\w+)\(\) throws checked exception ([\w\\]+) but", message)
            if not m:
                print("! unparsed message:", message)
                continue
            _, method_name, exc_fqcn = m.groups()
            by_file.setdefault(f, {}).setdefault(method_name, set()).add(exc_fqcn)
    changed = 0
    for path, methods in sorted(by_file.items()):
        if process_file(path, methods):
            changed += 1
            print("patched", path, list(methods.keys()))
    print(f"Patched {changed} files")


if __name__ == "__main__":
    main(sys.argv[1:])
```

Run it against the current findings, then re-run phpstan and repeat until the JSON reports zero `missingType.checkedException` errors (confirmed on 2026-09-09 to converge after 3 rounds: 240 → 55 → 23 → 0):

```bash
for i in 1 2 3 4 5; do
  docker compose run --rm application sh -lc \
    "XDEBUG_MODE=off vendor/bin/phpstan analyse -c tools/phpstan.src.neon --error-format=json 2>/dev/null" \
    > /tmp/phpstan-src-iter.json
  cnt=$(jq '[.files[].messages[] | select(.identifier=="missingType.checkedException")] | length' /tmp/phpstan-src-iter.json)
  echo "iteration $i: $cnt remaining"
  [ "$cnt" -eq 0 ] && break
  python3 /tmp/fix_checked_exceptions.py /tmp/phpstan-src-iter.json
done
```

- [ ] **Step 5: Verify the src config is fully green**

Run: `docker compose run --rm application vendor/bin/phpstan analyse -c tools/phpstan.src.neon`
Expected: `[OK] No errors`.

- [ ] **Step 6: Commit**

```bash
git add src/FastyBird
git commit -m "fix(core): satisfy phpstan.src.neon — add missing @throws tags and document 10 pre-existing findings"
```

---

### Task 14: Fix `make phpstan`'s tests-config findings

**Files:**
- Modify: up to ~110 files under `src/FastyBird/*/*/tests/`

**Interfaces:**
- Consumes: Task 13 (the same script, reused).
- Produces: a tests tree that passes `vendor/bin/phpstan analyse -c tools/phpstan.tests.neon`; consumed by Task 15's combined `make phpstan` check.

Confirmed on 2026-09-09: `vendor/bin/phpstan analyse -c tools/phpstan.tests.neon` reports **173 errors** in **96 files**: **120** `missingType.checkedException` (same mechanism and script as Task 13) and **53** `argument.type` — every one of them the identical pattern, `Parameter #1 $type of method Nette\DI\Container::findByType() expects class-string, string given`, inside each extension's copy-pasted `tests/cases/unit/{BaseTestCase,DbTestCase}.php::mockContainerService(string $serviceType, object $serviceMock)`.

- [ ] **Step 1: Reproduce the baseline finding count**

Run: `docker compose run --rm application sh -lc "XDEBUG_MODE=off vendor/bin/phpstan analyse -c tools/phpstan.tests.neon --error-format=json 2>/dev/null" > /tmp/phpstan-tests.json && jq '.totals.file_errors' /tmp/phpstan-tests.json`
Expected: `173` (or close — re-derive with the same `group_by` query as Task 13 Step 1 if it drifted).

- [ ] **Step 2: Fix the 53 `argument.type` findings with one script**

```python
#!/usr/bin/env python3
"""Add `@param class-string $serviceType` to mockContainerService() docblocks
flagged by PHPStan's argument.type check on Container::findByType().
Save as /tmp/fix_classstring.py."""
import json
import re
import sys

METHOD_RE = re.compile(r'^(\s*)((?:final\s+|abstract\s+|static\s+)*(?:public|protected|private)\s+(?:static\s+)?function\s+mockContainerService\s*\()')


def load_files(path):
    data = open(path, "r", errors="replace").read()
    idx = data.find("{")
    d = json.loads(data[idx:])
    out = []
    for f, v in d["files"].items():
        f = f.replace("/app/", "")
        for m in v["messages"]:
            if m.get("identifier") == "argument.type" and "findByType" in m["message"]:
                out.append(f)
    return out


def process_file(path):
    with open(path) as f:
        lines = f.readlines()
    match_idx = next((i for i, l in enumerate(lines) if METHOD_RE.match(l)), None)
    if match_idx is None:
        print(f"  ! could not locate mockContainerService() in {path}")
        return False
    indent = METHOD_RE.match(lines[match_idx]).group(1)
    j = match_idx - 1
    while j >= 0 and lines[j].strip() == "":
        j -= 1
    if j >= 0 and lines[j].strip() == "*/":
        close_idx = j
        open_idx = close_idx
        while open_idx >= 0 and "/**" not in lines[open_idx]:
            open_idx -= 1
        existing_block = "".join(lines[open_idx:close_idx + 1])
        if "@param class-string $serviceType" in existing_block:
            return False
        lines.insert(close_idx, f"{indent} * @param class-string $serviceType\n")
    else:
        block = [f"{indent}/**\n", f"{indent} * @param class-string $serviceType\n", f"{indent} */\n"]
        lines[match_idx:match_idx] = block
    with open(path, "w") as f:
        f.writelines(lines)
    return True


def main(json_paths):
    files = set()
    for jp in json_paths:
        files.update(load_files(jp))
    changed = 0
    for path in sorted(files):
        if process_file(path):
            changed += 1
            print("patched", path)
    print(f"Patched {changed} files")


if __name__ == "__main__":
    main(sys.argv[1:])
```

```bash
python3 /tmp/fix_classstring.py /tmp/phpstan-tests.json
```

Expected: `Patched 53 files`.

- [ ] **Step 3: Fix the 120 `missingType.checkedException` findings, iterating to convergence**

Reuse Task 13's `/tmp/fix_checked_exceptions.py`:

```bash
for i in 1 2 3 4; do
  docker compose run --rm application sh -lc \
    "XDEBUG_MODE=off vendor/bin/phpstan analyse -c tools/phpstan.tests.neon --error-format=json 2>/dev/null" \
    > /tmp/phpstan-tests-iter.json
  cnt=$(jq '[.files[].messages[] | select(.identifier=="missingType.checkedException")] | length' /tmp/phpstan-tests-iter.json)
  echo "iteration $i: $cnt remaining"
  [ "$cnt" -eq 0 ] && break
  python3 /tmp/fix_checked_exceptions.py /tmp/phpstan-tests-iter.json
done
```

Expected: converges to `0` within 2 rounds (confirmed on 2026-09-09: 120 → 4 → 0).

- [ ] **Step 4: Verify the tests config is fully green**

Run: `docker compose run --rm application vendor/bin/phpstan analyse -c tools/phpstan.tests.neon`
Expected: `[OK] No errors`.

- [ ] **Step 5: Commit**

```bash
git add src/FastyBird
git commit -m "test(core): satisfy phpstan.tests.neon — add missing @throws and class-string tags"
```

---

### Task 15: Fix `make cs`

**Files:**
- Modify: `src/FastyBird/Connector/Modbus/src/API/Interfaces/SerialWindows.php`
- Modify: `src/FastyBird/Connector/Modbus/src/API/Interfaces/SerialLinux.php`
- Modify: `src/FastyBird/Connector/Modbus/src/API/Interfaces/SerialDarwin.php`
- Modify: `src/FastyBird/Connector/Sonoff/src/Clients/Cloud.php` (auto-fixed)
- Modify: `src/FastyBird/Connector/Sonoff/src/Clients/Lan.php` (auto-fixed)
- Modify: an additional batch of files across the tree with only whitespace/indentation changes from `make csf` normalising the docblocks Tasks 13–14 inserted (confirmed on 2026-09-09: 53 whitespace fixes across 50 files)

**Interfaces:**
- Consumes: Tasks 13–14 (their docblock insertions are exactly what `make csf` reformats in this task; running `make cs`/`make csf` before Tasks 13–14 would under-report, since it can't see files that don't exist yet in their post-phpstan-fix form).
- Produces: a tree that passes `make cs`; consumed by Task 18's final PR3 verification.

Confirmed on 2026-09-09: after Tasks 13–14, `make cs` reports **8 errors in 5 files**: 3 non-auto-fixable `SlevomatCodingStandard.PHP.OptimizedFunctionsWithoutUnpacking.UnpackingUsed` findings (identical `sprintf($command, ...array_values($params))` pattern in the three `Serial{Windows,Linux,Darwin}.php` files), and 5 auto-fixable `PEAR.WhiteSpace.ObjectOperatorIndent.Incorrect` findings in `Sonoff/src/Clients/{Cloud,Lan}.php`.

- [ ] **Step 1: Reproduce the baseline**

Run: `docker compose run --rm application make cs 2>&1 | tail -20`
Expected: `FOUND 1 ERROR` in each of `SerialWindows.php`, `SerialLinux.php`, `SerialDarwin.php` (all `OptimizedFunctionsWithoutUnpacking`), `FOUND 4 ERRORS` in `Cloud.php` and `FOUND 1 ERROR` in `Lan.php` (all `ObjectOperatorIndent`, all marked `PHPCBF CAN FIX THE ... SNIFF VIOLATIONS AUTOMATICALLY`).

- [ ] **Step 2: Fix the 3 non-auto-fixable sprintf-unpacking findings**

Each of the three files has this exact block (line ~96–97, inside the serial port command builder):

```diff
-		$command = sprintf($command, ...array_values($params));
+		$command = vsprintf($command, array_values($params));
```

and each file's `use function` block needs `vsprintf` added, alphabetically after `stream_set_blocking`:

```diff
 use function stream_set_blocking;
+use function vsprintf;
```

Apply this to `src/FastyBird/Connector/Modbus/src/API/Interfaces/SerialWindows.php`, `SerialLinux.php` and `SerialDarwin.php`.

- [ ] **Step 3: Auto-fix the remaining 5 indentation findings**

```bash
docker compose run --rm application make csf
```

Expected: exit code `1` from `phpcbf` (its convention for "fixed everything it found," not a real failure) with a summary reporting the object-operator-indent fixes in `Cloud.php`/`Lan.php` plus the whitespace normalisation of Tasks 13–14's inserted docblocks across the rest of the tree.

- [ ] **Step 4: Verify `make cs` is clean**

Run: `docker compose run --rm application make cs`
Expected: exit `0`, a `N / N (100%)` summary with `N` equal to the number of files phpcs scans across the whole `src/` tree (in the thousands, not a fixed small count), no errors reported.

- [ ] **Step 5: Re-verify `make phpstan` is still clean after the whitespace pass**

Run: `docker compose run --rm application make phpstan`
Expected: `[OK] No errors` for both the src and tests configs (confirmed on 2026-09-09 that `make csf`'s reformatting does not reintroduce any phpstan finding).

- [ ] **Step 6: Commit**

```bash
git add src/FastyBird
git commit -m "fix(core): satisfy make cs — replace unpacked sprintf with vsprintf and auto-fix indentation"
```

---

### Task 16: Run `make tests` against MariaDB and Redis and fix any non-dependency findings

**Files:**
- Modify: none known in advance — see the decision procedure below; only structural/code fixes land here, never a dependency bump (D11).

**Interfaces:**
- Consumes: Tasks 10–15 (installed `vendor/`, green `cs`/`phpstan`).
- Produces: a passing `make tests`; consumed by Task 18.

Every extension's `tests/common.neon` (e.g. `src/FastyBird/Core/Application/tests/common.neon`) hardcodes the test database connection to `host: 127.0.0.1, port: 3306, dbname: testdb, user: root, password: root` — this is **not** the same host/port/dbname the dev `docker-compose.yml` `database` service exposes to the `application` container by service name (`database`/`fastybird_dev`). Confirmed on 2026-09-09: the `application` container cannot reach a hostname-addressed `database` container using `127.0.0.1`, so MariaDB (and Redis, which the test suite's `defaults.neon` similarly points at `127.0.0.1:6379`) must be reachable at `127.0.0.1` **from inside the same network namespace** the test runner uses.

- [ ] **Step 1: Bring up MariaDB and Redis sharing one network namespace**

```bash
docker compose up -d database
docker run -d --name test-redis --network container:fastybird-database redis
docker exec fastybird-database mariadb -uroot -proot -e "CREATE DATABASE IF NOT EXISTS testdb;"
```

Expected: `fastybird-database` and `test-redis` both `Up`; `testdb` listed by a follow-up `docker exec fastybird-database mariadb -uroot -proot -e "SHOW DATABASES;"`.

- [ ] **Step 2: Run the test suite joined to that same network namespace**

```bash
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  fastybird-php-dev sh -lc "make tests"
```

Expected: PHPUnit/paratest run against all `src/FastyBird/*/*/tests/cases/` directories (confirmed on 2026-09-09 that the suite starts, connects, and progresses through its first ~7% with zero failures before this verification pass was time-boxed — the executor must let this run to completion). A full, definitive pass/fail count is this task's actual deliverable; do not mark this task done from a partial run.

**Decision procedure for any failure `make tests` reports:**

1. If the failure is a **duplicate-key / table-already-exists / deadlock** error and only appears under the parallel `make tests` (paratest `WrapperRunner`) but not under `make tests-simple` (sequential `phpunit`), it is worker-level database contention on the single shared `testdb` schema, not a code defect — rerun with `ARGS="-p1"` (`make tests ARGS=-p1` per the Makefile's `$(ARGS)` passthrough) to confirm, and if that is indeed the cause, record it as a known local-verification limitation in the PR body (CI's reusable `phpunit.yaml` workflow provisions MySQL/Redis as GitHub Actions service containers reachable at `localhost` for the whole job, which does not have this contention path) rather than changing test code to work around a purely local sequencing artifact.
2. If the failure is a genuine assertion/logic failure inside `src/FastyBird/*/*/tests/cases/unit/`, read the failure message and stack trace, fix the specific assertion or the specific `src/` code path it exercises, and re-run just that test class (`make tests-simple ARGS="--filter <TestClassName>"`) before re-running the full suite.
3. If the failure traces back to the known `dg/bypass-finals` patch not applying (Task 10's recorded "Known warning" — e.g. a test that relies on `BypassFinals::enable()` suppressing a `mkdir()` warning during a final-class mock), that is the one case where the fix is a dependency-version change (pinning `dg/bypass-finals` to a release the patch still matches). Per D11, that fix does **not** land in this task — open a separate bump-only PR after PR3, list the exact failing test and the exact composer constraint change in its body, and land it before Phase 2 starts.
4. Never modify `tools/phpunit.xml`, `tools/phpstan.*.neon` or any extension's `composer.json`/`package.json` to make a failure disappear in this task — those are exactly the "requires a dependency change" cases D11 defers.

- [ ] **Step 3: Tear down the throwaway test infrastructure**

```bash
docker rm -f test-redis
docker compose down -v
```

- [ ] **Step 4: Commit** (only if Step 2's decision procedure produced an actual code fix; otherwise skip straight to Task 17 and note "no changes required" in the PR3 body)

```bash
git add src/FastyBird
git commit -m "fix(core): fix make tests failures surfaced against MariaDB and Redis"
```

---

### Task 17: Run `yarn build` on Node 20 and fix any non-dependency findings

**Files:**
- Modify: none known in advance — see the decision procedure below.

**Interfaces:**
- Consumes: Task 11's `node_modules`/`yarn.lock`, PR1's fixed `.docker/dev/node/Dockerfile`.
- Produces: a passing `yarn build`; consumed by Task 18.

Root `package.json`'s `build` script is `lerna run build --stream --ignore '@fastybird/web-ui' --ignore '@fastybird/application' && yarn workspace @fastybird/application build`, i.e. it builds every UI workspace package as a library (skipping the nested `Library/WebUi` lerna workspace and the shell itself), then builds `Core/Application` (`vue-tsc --noEmit --composite false && vite build`) last.

- [ ] **Step 1: Run the build inside the fixed Node 20 image**

```bash
docker run --rm -v "$PWD":/app -w /app fastybird-node-dev sh -lc "yarn build"
```

Expected: `lerna run build` completes for all 8 UI-bearing packages (`Core/Tools`, `Core/Application` is excluded from this first pass by `--ignore`, `Module/{Accounts,Devices,Ui}`, `Library/Metadata`, `Connector/HomeKit`; `Module/Triggers` has no `build` script and `lerna run` skips it without failing), then `yarn workspace @fastybird/application build` runs `vue-tsc --noEmit --composite false && vite build`, producing `public/index.html`, `public/assets/*` and a Vite manifest.

**Decision procedure for any failure:**

1. If the failure is a **TypeScript type error** inside `Core/Application/assets/` or any of the 8 UI packages' `assets/`, read the exact `vue-tsc` error (file, line, message) and fix the specific type annotation or import it names. This is squarely a code fix, not a dependency bump — do not upgrade `typescript`/`vue-tsc`/`vite` to make the error go away (D10/D11).
2. If the failure is `vite build` unable to resolve an import specifier (e.g. `@fastybird/vue-wamp-v1`, an external npm package per spec section 2.1), confirm the package is actually present in `node_modules/@fastybird/vue-wamp-v1` after Task 11's `yarn install`; if it is missing entirely, that is a resolution problem belonging back in a Task 11 follow-up (dependency-manifest fix), not a code change here.
3. If the failure is inside a UI package's own `build` script (e.g. `theme-chalk`'s gulp/SCSS compile, `icons`' SVG codegen) rather than `Core/Application`, isolate it with `docker run --rm -v "$PWD":/app -w /app fastybird-node-dev sh -lc "yarn workspace <name> build"` and fix that package's own source, never its `package.json` version ranges.
4. Never bump `vite`, `typescript`, `vue-tsc`, `eslint`, `unocss`, `element-plus`, `vue`, `pinia`, `vue-router`, `vue-i18n` or `vue-meta` to resolve a build failure in this task — every one of those is explicitly a Phase 6 modernization item (spec 5, Phase 6, item 6); a build failure that only goes away with one of those bumps is deferred to a bump-only PR opened after PR3, exactly like Task 16 Step 2's `dg/bypass-finals` case.

- [ ] **Step 2: Verify the build output**

Run: `ls public/index.html public/.vite/manifest.json 2>&1`
Expected: both files exist (confirmed by the `vite.config.ts` `build.manifest: true` setting already present in `Core/Application/vite.config.ts`).

- [ ] **Step 3: Commit** (only if Step 1's decision procedure produced an actual code fix; otherwise skip straight to Task 18 and note "no changes required" in the PR3 body)

```bash
git add src/FastyBird
git commit -m "fix(ui): fix yarn build failures on Node 20"
```

---

### Task 18: Verify the full Phase 1 "Green" deliverable and open PR3

**Files:**
- Create: none (verification only).

**Interfaces:**
- Consumes: Tasks 13–17.
- Produces: a merged PR3 — the completed Phase 0/1 baseline that every later phase in the spec is validated against.

- [ ] **Step 1: Run every required check inside the container, in the order the spec names them**

```bash
docker compose run --rm application make lint
docker compose run --rm application make cs
docker compose run --rm application make phpstan
docker compose up -d database
docker run -d --name test-redis --network container:fastybird-database redis
docker exec fastybird-database mariadb -uroot -proot -e "CREATE DATABASE IF NOT EXISTS testdb;"
docker run --rm --network container:fastybird-database -v "$PWD":/app -w /app \
  fastybird-php-dev sh -lc "make tests"
docker run --rm -v "$PWD":/app -w /app fastybird-node-dev sh -lc "yarn build"
docker rm -f test-redis
docker compose down -v
```

Expected: every command exits `0`. Record the actual wall-clock time of `make tests` and `yarn build` (both are the slow steps) in the PR body, along with the PHP/Composer/Node/yarn versions from Task 4/Task 9's verification steps.

- [ ] **Step 2: Confirm no dependency manifest changed since PR2**

Run: `git diff --stat origin/main -- composer.json composer.lock package.json yarn.lock`
Expected: empty (PR3 is a pure code-quality fix PR; if this is not empty, something in Tasks 13–17 violated D11 and must be split out into a separate bump-only PR before continuing).

- [ ] **Step 3: Push and open the pull request**

```bash
git push -u origin HEAD
gh pr create --title "fix(core): make lint, cs, phpstan, tests and yarn build pass" \
  --body "$(cat <<'EOF'
## Summary
Fixes every remaining make lint / make cs / make phpstan / make tests / yarn build finding against the dependency set installed in PR2, without changing any dependency version (D11) — this PR is the first time this exact frozen dependency set has been proven to build, lint and test clean, and is the baseline every later phase is validated against (spec risk table).

## Verification (recorded 2026-09-09, versions from PR0/PR1: PHP 8.2.33, Composer 2.4.4, Node 20.20.2, yarn 1.22.22)
- `make lint`: 3240 files, no syntax error.
- `make cs`: 0 errors (fixed 3 sprintf-unpacking findings by hand, 5 indentation findings + whitespace on ~50 files via `make csf`).
- `make phpstan`: 0 errors (was 423 across both configs: 360 missingType.checkedException fixed by a mechanical script adding @throws tags, iterating to convergence; 53 argument.type fixed by a mechanical script adding @param class-string; 10 one-off findings suppressed with a reviewable inline @phpstan-ignore-next-line + reason each, listed in the phpstan-fix commits).
- `make tests`: [fill in final pass/fail count and wall-clock time from Task 18 Step 1's real run]
- `yarn build`: [fill in final result and wall-clock time from Task 18 Step 1's real run]

## Known, deliberately deferred (not fixed here, per D11)
- dg/bypass-finals's vendored patch does not apply against the resolved 1.11.0 release (recorded since PR0/PR2). Fixed only if make tests actually fails because of it, in a follow-up bump-only PR.
EOF
)"
```

- [ ] **Step 4: Confirm CI is green — this closes Phase 1**

Run: `gh pr checks --watch`
Expected: all checks pass. Merge. Phase 2 (port deployment assets) branches from this commit.
