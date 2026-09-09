# Phase 3, one application Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the 34 independently-scaffolded extension packages into one application: one set of QA configuration files, one Composer manifest graph backed by a path repository, one configuration directory at `config/`, and the first Doctrine migration.

**Architecture:** The extension directories keep everything that is theirs (`src/`, `tests/`, `assets/`, `config/`, `resources/`, `templates/`, `docs/`, `README.md`, `composer.json`, `package.json`) and lose everything that only made sense while they were split into 25 separate repositories (`.github/`, `tools/`, `Makefile`, `LICENSE.md`, `CHANGELOG-1.0.md`, `.editorconfig`, `.gitattributes`, `.npmignore`, `docs/_Footer.md`, and `.gitignore` where it no longer ignores anything real). The root `composer.json` stops carrying a `replace` block and 34 `autoload.psr-4` entries and instead declares a `path` repository over `src/FastyBird/*/*` with 34 `"@dev"` requirements, so each extension manifest becomes the authoritative description of that extension. `var/config/` becomes `config/`, and `Bootstrap::boot()` learns a three-layer, realpath-deduplicated configuration load order so that `FB_CONFIG_DIR` can point anywhere, including at the shipped directory itself.

**Tech Stack:** PHP 8.2, Composer 2 path repositories, PHPStan 1.10, PHP_CodeSniffer with orisai/coding-standard 3, PHPUnit 10 / paratest 7, Infection 0.27, Nette DI / NEON, nettrine/migrations 0.8, Doctrine ORM 2.15, MariaDB, Docker, GNU make.

**Spec:** docs/superpowers/specs/2026-09-09-miniserver-merge-design.md

## Global Constraints

- PHP 8.2, Node 20, yarn 1 during the merge.
- No dependency upgrades in this phase. The only manifest changes permitted are the structural ones spec 4.3 mandates: adding `nettrine/migrations ^0.8` (a new requirement, not a bump), removing `symplify/monorepo-builder`, and moving ~58 third-party requirements from the root manifest into transitive resolution through the extension manifests. Third-party **versions** must not change; Task 12 verifies this against `composer.lock` package by package.
- No pull request mixes a structural change with a dependency version change.
- CI green before the next pull request starts.
- Conventional commit format `<type>(<scope>): <subject>`, scope from the spec 4.8 list: `core`, `module`, `connector`, `plugin`, `bridge`, `addon`, `automator`, `library`, `ui`, `infra`, `ci`, `deps`, `docs`, `cross`. Subject starts lowercase, no trailing period.
- PHP namespaces stay `FastyBird\<Type>\<Name>`. The `src/FastyBird/` prefix does not change.
- **Ordering hazard 1 (QA before deletion).** The 34 per-package `tools/phpstan.config.src.neon` and `tools/phpstan.config.tests.neon` files hold 99 + 21 `ignoreErrors` entries and 2 `excludePaths` entries that the root configuration currently pulls in through `includes:`. Their content must be harvested into `tools/phpstan.neon` and `tools/phpstan.tests.neon` **before** `src/FastyBird/*/*/tools/` is deleted. Therefore pull request 1 in this phase is the QA consolidation (spec body 4.3, "Root QA tooling") and pull request 2 is the scaffolding removal — the reverse of the order the spec lists them in.
- **Ordering hazard 2 (config move and test bootstrap in one commit).** `tools/phpunit-bootstrap.php` line 6 currently reads `define('FB_CONFIG_DIR', realpath(__DIR__ . '/../config'));`. Today `<root>/config` does not exist, `realpath()` returns `false`, and the test suite therefore loads no application wiring. The moment `var/config/` becomes `config/`, that constant resolves to the real shipped configuration and every one of the 53 test container factories would compile all 34 DI extensions. The `git mv` and the `tools/phpunit-bootstrap.php` change must land in the same commit (Task 15).
- Each extension must tolerate the absence of a file being removed. Every removal step below states the verified present count.
- Never remove per-extension UI build files in this phase (`vite.config.ts`, `tsconfig.json`, `eslint.config.mjs`, `prettier.config.mjs`, `.prettierrc`, `.stylelintrc.json`, `jest.config.mjs`, `commitlint.config.mjs`, `.browserslistrc`, `index.html`, `uno.config.ts`). Those belong to Phase 5.
- Prefer `git mv` and `git rm` so history follows the files.

## Pull Requests

1. **`chore(infra): consolidate QA configuration into root tools`** — Tasks 1–5. Harvests the per-package PHPStan configuration into self-contained root files, regenerates the phpcs root-namespace map to all 122 source and test directories, points Infection at the extension sources, and adds `make composer-validate`. Nothing is deleted from the extensions yet.
2. **`chore(cross): remove per-extension scaffolding`** — Tasks 6–10. Removes `.github/` (35), `tools/` (34), `Makefile` (34), `LICENSE.md` (35), `CHANGELOG-1.0.md` (35), `.editorconfig` (35), `.gitattributes` (34), `.npmignore` (8), `docs/_Footer.md` (22), and 26 of the 35 per-extension `.gitignore` files.
3. **`refactor(infra): composer path repository model`** — Tasks 11–14. Trims the 34 extension manifests, converts the root manifest to the path repository model of spec 4.3, regenerates the lock, deletes the monorepo split tooling, and makes the production image mirror path packages instead of symlinking them.
4. **`feat(core): configuration directory and initial migration`** — Tasks 15–19. Moves `var/config/` to `config/`, repoints every reference, implements and unit-tests the spec 4.5 bootstrap load order, wires `nettrineMigrations`, and commits the first Doctrine migration.

---

### Task 1: Merge the PHPStan source configuration into `tools/phpstan.neon`

**Files:**
- Create: `tools/phpstan.neon`
- Delete: `tools/phpstan.src.neon`
- Modify: `Makefile` (the `PHPSTAN_SRC_CONFIG` variable)

**Interfaces:**
- Consumes: the 34 `src/FastyBird/*/*/tools/phpstan.config.src.neon` files, `tools/phpstan.base.neon`, `tools/phpstan.src.neon`, `tools/phpstan-bootstrap.php`, `tests/PHPStan/conditional.config.php`, `tests/stubs/*.stub`.
- Produces: `tools/phpstan.neon`, a self-contained level-max configuration with 35 `paths` entries, 2 `excludePaths` entries and 99 `ignoreErrors` entries, referenced by the `Makefile`.

- [ ] **Step 1: Confirm the inputs are present and unchanged**

```bash
ls src/FastyBird/*/*/tools/phpstan.config.src.neon | wc -l
grep -h '            path:' src/FastyBird/*/*/tools/phpstan.config.src.neon | wc -l
grep -l 'excludePaths' src/FastyBird/*/*/tools/phpstan.config.src.neon
```

Expected: `34`, then `99`, then exactly these two files:

```
src/FastyBird/Core/Application/tools/phpstan.config.src.neon
src/FastyBird/Core/Tools/tools/phpstan.config.src.neon
```

- [ ] **Step 2: Generate `tools/phpstan.neon`**

Run this generator from the repository root. It rewrites every package-relative `../src/...` path into a root-`tools/`-relative `../src/FastyBird/<Type>/<Name>/src/...` path and folds `tools/phpstan.base.neon` and the head of `tools/phpstan.src.neon` into the output.

```bash
python3 - <<'PYEOF'
import glob, os, re

STUBS = ["BaseLinkInterface", "ContextInterface", "LinkInterface", "PositionInterface",
         "SchemaInterface", "RedisClient", "EventEmitterInterface"]

def packages():
    return sorted(d for d in glob.glob('src/FastyBird/*/*') if os.path.isfile(os.path.join(d, 'composer.json')))

def section(path, name):
    lines = open(path).read().splitlines()
    out, grab = [], False
    for line in lines:
        if re.match(r'^    %s:\s*$' % re.escape(name), line):
            grab = True
            continue
        if grab:
            if line.strip() == '':
                out.append('')
                continue
            if not line.startswith('        '):
                break
            out.append(line)
    while out and out[-1] == '':
        out.pop()
    return out

def build(kind):
    head = ['includes:', '    - ../tests/PHPStan/conditional.config.php', '',
            'parameters:', '    level: max', '',
            '    tmpDir: ../var/tools/PHPStan',
            '    resultCachePath: %%currentWorkingDirectory%%/var/tools/PHPStan/resultCache.%s.php' % kind,
            '', '    exceptions:', '        check:',
            '            missingCheckedExceptionInThrows: true',
            '            tooWideThrowType: true', '',
            '    checkMissingCallableSignature: true',
            '    checkTooWideReturnTypesInProtectedAndPublicMethods: true',
            '    checkInternalClassCaseSensitivity: true', '',
            '    bootstrapFiles:', '        - phpstan-bootstrap.php']
    if kind == 'src':
        head += ['', '    stubFiles:'] + ['        - ../tests/stubs/%s.stub' % s for s in STUBS]
    head += ['', '    paths:']
    paths, scan, excl, ign = [], [], [], []
    for p in packages():
        cfg = os.path.join(p, 'tools', 'phpstan.config.%s.neon' % kind)
        for raw in section(cfg, 'paths'):
            paths.append('        - ../%s/%s' % (p, raw.strip().lstrip('- ').rstrip('/')[3:]))
        for raw in section(cfg, 'scanDirectories'):
            scan.append('        - ../%s/%s' % (p, raw.strip().lstrip('- ').rstrip('/')[3:]))
        for raw in section(cfg, 'excludePaths'):
            if raw.strip().startswith('- '):
                excl.append('            - ../%s/%s' % (p, raw.strip()[2:][3:]))
        for raw in section(cfg, 'ignoreErrors'):
            m = re.match(r'^(\s*)path: \.\./(.*)$', raw)
            ign.append('%spath: ../%s/%s' % (m.group(1), p, m.group(2)) if m else raw)
    head += paths
    if scan:
        head += ['', '    scanDirectories:'] + scan
    if excl:
        head += ['', '    excludePaths:', '        analyseAndScan:'] + excl
    if ign:
        head += ['', '    ignoreErrors:'] + ign
    return '\n'.join(head) + '\n'

open('tools/phpstan.neon', 'w').write(build('src'))
print('tools/phpstan.neon written')
PYEOF
```

- [ ] **Step 3: Check the generated file structurally**

Run: `grep -c '^        - \.\./src/FastyBird' tools/phpstan.neon && grep -c '            path: ' tools/phpstan.neon && sed -n '1,30p' tools/phpstan.neon`
Expected: `35` (the 34 extension `src` directories plus `../src/FastyBird/Core/Application/bin`, which the per-package Core/Application configuration also analyses), then `99`, then a header that begins:

```
includes:
    - ../tests/PHPStan/conditional.config.php

parameters:
    level: max

    tmpDir: ../var/tools/PHPStan
    resultCachePath: %currentWorkingDirectory%/var/tools/PHPStan/resultCache.src.php
```

- [ ] **Step 4: Point the Makefile at the new file and delete the old one**

```bash
sed -i '' 's|^PHPSTAN_SRC_CONFIG=tools/phpstan.src.neon$|PHPSTAN_SRC_CONFIG=tools/phpstan.neon|' Makefile
git rm tools/phpstan.src.neon
```

- [ ] **Step 5: Verify PHPStan still reports nothing**

Run: `docker compose exec -T application make phpstan`
Expected: both invocations print `[OK] No errors`, exit status 0. If PHPStan reports `Ignored error pattern ... was not matched`, an `ignoreErrors` path was rewritten wrongly; compare the failing entry against the per-package file it came from.

- [ ] **Step 6: Commit**

```bash
git add tools/phpstan.neon Makefile
git commit -m "chore(infra): merge phpstan source configuration into tools/phpstan.neon"
```

---

### Task 2: Make `tools/phpstan.tests.neon` self-contained and drop `tools/phpstan.base.neon`

**Files:**
- Modify: `tools/phpstan.tests.neon` (replaced wholesale by the generator)
- Delete: `tools/phpstan.base.neon`

**Interfaces:**
- Consumes: the generator function from Task 1, the 34 `src/FastyBird/*/*/tools/phpstan.config.tests.neon` files.
- Produces: `tools/phpstan.tests.neon` with 34 `paths`, 34 `scanDirectories` and 21 `ignoreErrors` entries and no `includes` of package files, so that Task 7 can delete `src/FastyBird/*/*/tools/`.

- [ ] **Step 1: Generate `tools/phpstan.tests.neon`**

Re-run the generator from Task 1 Step 2 verbatim, changing only the final two lines to:

```python
open('tools/phpstan.tests.neon', 'w').write(build('tests'))
print('tools/phpstan.tests.neon written')
```

- [ ] **Step 2: Check the generated file structurally**

Run: `grep -c '^        - \.\./src/FastyBird.*/tests/cases$' tools/phpstan.tests.neon && grep -c '^        - \.\./src/FastyBird.*/src$' tools/phpstan.tests.neon && grep -c '            path: ' tools/phpstan.tests.neon && grep -c 'stubFiles' tools/phpstan.tests.neon`
Expected: `34`, `34`, `21`, `0`. The tests configuration has no `stubFiles` today and must not gain any. `paths` stays at `tests/cases` for every package — this is exactly the union of the 34 per-package `paths` entries, so the set of analysed files does not change.

- [ ] **Step 3: Delete the now-unused shared base file**

```bash
git rm tools/phpstan.base.neon
```

- [ ] **Step 4: Verify no root PHPStan config references a package file any more**

Run: `grep -rn 'src/FastyBird' tools/phpstan.neon tools/phpstan.tests.neon | grep -c 'phpstan.config'`
Expected: `0`.

- [ ] **Step 5: Verify PHPStan**

Run: `docker compose exec -T application make phpstan`
Expected: both invocations print `[OK] No errors`, exit status 0.

- [ ] **Step 6: Commit**

```bash
git add tools/phpstan.tests.neon
git commit -m "chore(infra): merge phpstan tests configuration into tools/phpstan.tests.neon"
```

---

### Task 3: Regenerate the phpcs root-namespace map for every extension source and test directory

**Files:**
- Modify: `tools/phpcs.xml` (the `TypeNameMatchesFileName.rootNamespaces` property block only)

**Interfaces:**
- Consumes: `autoload.psr-4` of the 34 extension `composer.json` files and the 88 `autoload-dev.psr-4` entries of the root `composer.json`.
- Produces: `tools/phpcs.xml` with 122 `<element>` entries instead of the current 116.

- [ ] **Step 1: Record the current state**

Run: `grep -c '<element key=' tools/phpcs.xml`
Expected: `116`. The six-entry gap is eight missing directories (`Addon/VirtualThermostat/tests/fixtures`, `Bridge/DevicesModuleUiModule/tests/fixtures`, `Bridge/ShellyConnectorHomeKitConnector/tests/fixtures`, `Bridge/VieraConnectorHomeKitConnector/tests/fixtures`, `Bridge/VirtualThermostatAddonHomeKitConnector/tests/fixtures`, `Connector/Virtual/tests/fixtures`, `Core/Application/tests/fixtures`, `Plugin/RedisDbCache/tests/tools`) minus two stale entries pointing at directories that do not exist (`Connector/Zigbee2Mqtt/tests/fixtures/dummy`, `Plugin/ApiKey/tests/fixtures`).

- [ ] **Step 2: Replace the `rootNamespaces` block**

```bash
python3 - <<'PYEOF'
import json, glob, os, re

root = json.load(open('composer.json'))
entries = []
for d in sorted(p for p in glob.glob('src/FastyBird/*/*') if os.path.isfile(os.path.join(p, 'composer.json'))):
    pkg = json.load(open(os.path.join(d, 'composer.json')))
    for ns, rel in pkg.get('autoload', {}).get('psr-4', {}).items():
        entries.append((d + '/' + rel.rstrip('/'), ns.rstrip('\\')))
    for ns, rel in sorted(root['autoload-dev']['psr-4'].items()):
        if rel.rstrip('/').startswith(d + '/'):
            entries.append((rel.rstrip('/'), ns.rstrip('\\')))

seen, ordered = set(), []
for key, ns in entries:
    if key in seen:
        continue
    seen.add(key)
    ordered.append((key, ns))

lines, prev = [], None
for key, ns in ordered:
    pkg = '/'.join(key.split('/')[:4])
    if prev is not None and pkg != prev:
        lines.append('')
    prev = pkg
    lines.append('                <element key="%s" value="%s"/>' % (key, ns))

block = '\n'.join(lines)
source = open('tools/phpcs.xml').read()
new, count = re.subn(
    r'(<property name="rootNamespaces" type="array">\n).*?(\n            </property>)',
    lambda m: m.group(1) + block + m.group(2),
    source,
    flags=re.S,
)
assert count == 1, count
open('tools/phpcs.xml', 'w').write(new)
print(len(ordered), 'elements written')
PYEOF
```

- [ ] **Step 3: Verify the element count and that the removed entries are gone**

Run: `grep -c '<element key=' tools/phpcs.xml && grep -c 'Zigbee2Mqtt/tests/fixtures/dummy' tools/phpcs.xml`
Expected: `122`, then `0`.

- [ ] **Step 4: Verify the coding standard still passes**

Run: `docker compose exec -T application make cs`
Expected: exit status 0, no `TypeNameMatchesFileName` errors. The eight newly-mapped directories contain no `.php` files directly (only `Controllers/`, `Documents/` and `dummy/` subdirectories with JSON fixtures and separately mapped classes), so the new mappings add no findings.

- [ ] **Step 5: Commit**

```bash
git add tools/phpcs.xml
git commit -m "chore(infra): regenerate phpcs root namespace map"
```

---

### Task 4: Point Infection at the extension sources

**Files:**
- Modify: `tools/infection.json` (the `source.directories` array)

**Interfaces:**
- Consumes: `tools/infection.json` as it stands, with `"directories": ["src"]`.
- Produces: `tools/infection.json` with `"directories": ["src/FastyBird/*/*/src"]` as spec 4.3 requires.

- [ ] **Step 1: Edit `tools/infection.json`**

Replace

```json
  "source": {
    "directories": [
      "src"
    ]
  },
```

with

```json
  "source": {
    "directories": [
      "src/FastyBird/*/*/src"
    ]
  },
```

Leave `$schema`, `logs`, `tmpDir` and `mutators` untouched. The glob is resolved by Symfony Finder, which `Infection` uses for source collection, and is evaluated relative to the working directory the Makefile runs from (the repository root).

- [ ] **Step 2: Verify the file is valid JSON and the glob resolves to 34 directories**

Run: `python3 -m json.tool tools/infection.json > /dev/null && echo OK && ls -d src/FastyBird/*/*/src | wc -l`
Expected: `OK`, then `34`.

- [ ] **Step 3: Commit**

```bash
git add tools/infection.json
git commit -m "chore(infra): point infection at the extension sources"
```

---

### Task 5: Add the `composer-validate` make target

**Files:**
- Modify: `Makefile` (new target between `mutations-infection` and the `# DOCKER` section)

**Interfaces:**
- Consumes: the root `composer.json` and the 34 extension `composer.json` files.
- Produces: `make composer-validate`, the gate that Tasks 11 and 12 are verified against.

- [ ] **Step 1: Add the target**

Insert immediately before the `# DOCKER` comment line in `Makefile`:

```make
composer-validate: ## Validate the root and every extension composer manifest
	composer validate --strict
	for manifest in src/FastyBird/*/*/composer.json; do \
		composer validate --strict --no-check-lock "$$manifest" || exit 1; \
	done

```

- [ ] **Step 2: Verify the target is discoverable**

Run: `docker compose exec -T application make list | grep composer-validate`
Expected: one line, `composer-validate      Validate the root and every extension composer manifest`.

- [ ] **Step 3: Verify it passes against the current manifests**

Run: `docker compose exec -T application make composer-validate`
Expected: `./composer.json is valid` for the root followed by 34 further `is valid` lines, exit status 0. No extension manifest declares a package in both `require` and `require-dev`, and none carries a `version` field, so `--strict` has nothing to report.

- [ ] **Step 4: Commit**

```bash
git add Makefile
git commit -m "chore(infra): add composer-validate make target"
```

---

### Task 6: Remove the per-extension GitHub directories

**Files:**
- Delete: `src/FastyBird/*/*/.github/` (35 directories, including `src/FastyBird/Library/WebUi/.github/`)

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: no `.github` directory below `src/`; the 23 `wiki.yml`, 34 × `lint.yaml`, 34 × `qa.yaml`, 34 × `static-analysis.yaml`, 34 × `tests.yaml`, 8 × `ci.yaml` and 35 × `FUNDING.yml` files are gone.

- [ ] **Step 1: Record what is being removed**

Run: `ls -d src/FastyBird/*/*/.github | wc -l && ls src/FastyBird/*/*/.github/workflows/ | sort | uniq -c | grep -E 'yml|yaml'`
Expected: `35`, and the workflow tally `34 tests.yaml`, `34 static-analysis.yaml`, `34 qa.yaml`, `34 lint.yaml`, `23 wiki.yml`, `8 ci.yaml`.

- [ ] **Step 2: Remove them**

```bash
git rm -r -q src/FastyBird/*/*/.github
```

- [ ] **Step 3: Verify**

Run: `ls -d src/FastyBird/*/*/.github 2>/dev/null | wc -l && ls -d .github`
Expected: `0`, then `.github` — the root workflows directory is untouched by this task.

- [ ] **Step 4: Commit**

```bash
git add -A src/FastyBird
git commit -m "chore(cross): remove per-extension github workflows"
```

---

### Task 7: Remove the per-extension QA tooling directories

**Files:**
- Delete: `src/FastyBird/*/*/tools/` (34 directories, 11 files each; absent only from `src/FastyBird/Library/WebUi`)
- Modify: `tools/phpcs.xml` (drop the 34 per-extension `exclude-pattern` lines)

**Interfaces:**
- Consumes: `tools/phpstan.neon` and `tools/phpstan.tests.neon` from Tasks 1 and 2, which must already be self-contained.
- Produces: a tree in which `tools/` exists only at the repository root.

- [ ] **Step 1: Prove nothing at the root still includes a package QA file**

Run: `grep -rn 'phpstan.config' tools/ Makefile | wc -l`
Expected: `0`. If this is not zero, stop: Tasks 1 and 2 were not completed and deleting these directories will break `make phpstan`.

- [ ] **Step 2: Remove the directories**

```bash
git rm -r -q src/FastyBird/*/*/tools
```

- [ ] **Step 3: Drop the now-dangling phpcs exclusions**

```bash
python3 - <<'PYEOF'
import re
path = 'tools/phpcs.xml'
source = open(path).read()
new, count = re.subn(r'\n    <exclude-pattern>src/FastyBird/[^<]*/tools/\*</exclude-pattern>', '', source)
open(path, 'w').write(new)
print(count, 'exclude-pattern lines removed')
PYEOF
```

Expected output: `34 exclude-pattern lines removed`.

- [ ] **Step 4: Verify**

Run: `ls -d src/FastyBird/*/*/tools 2>/dev/null | wc -l && grep -c '<exclude-pattern>' tools/phpcs.xml`
Expected: `0`, then `6` — the four base exclusions plus the two pre-existing, unrelated file-specific excludes for `Module/Devices/src/Entities/Property.php` and `Module/Devices/src/Subscribers/StateEntities.php`.

- [ ] **Step 5: Verify the QA targets still pass**

Run: `docker compose exec -T application sh -c 'make lint && make cs && make phpstan'`
Expected: all three exit 0.

- [ ] **Step 6: Commit**

```bash
git add -A src/FastyBird tools/phpcs.xml
git commit -m "chore(cross): remove per-extension qa tooling"
```

---

### Task 8: Remove the per-extension Makefiles, licences and changelogs

**Files:**
- Delete: `src/FastyBird/*/*/Makefile` (34), `src/FastyBird/*/*/LICENSE.md` (35), `src/FastyBird/*/*/CHANGELOG-1.0.md` (35)

**Interfaces:**
- Consumes: Task 7, which already removed the QA configuration the per-extension Makefiles referenced.
- Produces: one `Makefile`, one `LICENSE.md` and one `CHANGELOG.md`, all at the repository root.

- [ ] **Step 1: Record the counts**

Run: `ls src/FastyBird/*/*/Makefile | wc -l && ls src/FastyBird/*/*/LICENSE.md | wc -l && ls src/FastyBird/*/*/CHANGELOG-1.0.md | wc -l`
Expected: `34`, `35`, `35`. `Library/WebUi` has no `Makefile`; it does have `LICENSE.md` and `CHANGELOG-1.0.md`.

- [ ] **Step 2: Remove them**

```bash
git rm -q src/FastyBird/*/*/Makefile
git rm -q src/FastyBird/*/*/LICENSE.md
git rm -q src/FastyBird/*/*/CHANGELOG-1.0.md
```

- [ ] **Step 3: Verify**

Run: `ls src/FastyBird/*/*/Makefile src/FastyBird/*/*/LICENSE.md src/FastyBird/*/*/CHANGELOG*.md 2>&1 | grep -c 'No such file' && ls LICENSE.md CHANGELOG.md Makefile`
Expected: `3`, then the three root files listed.

- [ ] **Step 4: Commit**

```bash
git add -A src/FastyBird
git commit -m "chore(cross): remove per-extension makefiles licences and changelogs"
```

---

### Task 9: Remove the per-extension editor, packaging and wiki metadata

**Files:**
- Delete: `src/FastyBird/*/*/.editorconfig` (35), `src/FastyBird/*/*/.gitattributes` (34), `src/FastyBird/*/*/.npmignore` (8), `src/FastyBird/*/*/docs/_Footer.md` (22)

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: a single root `.editorconfig` and `.gitattributes`; no npm packaging metadata below `src/` (publishing to npm stops, D5); no GitHub-wiki footers in the extension docs.

- [ ] **Step 1: Record the counts**

Run: `ls src/FastyBird/*/*/.editorconfig | wc -l && ls src/FastyBird/*/*/.gitattributes | wc -l && ls src/FastyBird/*/*/.npmignore | wc -l && ls src/FastyBird/*/*/docs/_Footer.md | wc -l`
Expected: `35`, `34`, `8`, `22`. `.npmignore` exists only in `Connector/HomeKit`, `Core/Application`, `Core/Tools`, `Library/Metadata`, `Module/Accounts`, `Module/Devices`, `Module/Triggers` and `Module/Ui`.

- [ ] **Step 2: Remove them**

```bash
git rm -q src/FastyBird/*/*/.editorconfig
git rm -q src/FastyBird/*/*/.gitattributes
git rm -q src/FastyBird/*/*/.npmignore
git rm -q src/FastyBird/*/*/docs/_Footer.md
```

- [ ] **Step 3: Verify the extension docs survived**

Run: `ls -d src/FastyBird/*/*/docs | wc -l && ls -d src/FastyBird/*/*/README.md | wc -l`
Expected: `34` (every extension except `Library/Metadata`, which has never had a `docs/` directory, plus `Library/WebUi/docs`), then `35`.

- [ ] **Step 4: Commit**

```bash
git add -A src/FastyBird
git commit -m "chore(cross): remove per-extension editor and packaging metadata"
```

---

### Task 10: Prune the per-extension `.gitignore` files

**Files:**
- Delete: the 26 `src/FastyBird/*/*/.gitignore` files belonging to extensions without an `assets/` directory
- Modify: `src/FastyBird/Core/Tools/.gitignore` and `src/FastyBird/Library/Metadata/.gitignore` (drop entries for files deleted in Tasks 8 and 9)

**Interfaces:**
- Consumes: Tasks 7–9, which removed `Makefile`, `.editorconfig`, `.gitattributes` and `.npmignore` from every extension.
- Produces: 9 remaining `.gitignore` files — the 8 UI extensions plus `Library/WebUi` — each of which still ignores something real (`/dist`, `/node_modules`, `tsconfig.tsbuildinfo`) that the root `.gitignore` does not cover, because the root patterns `/node_modules` and `/vendor` are anchored to the repository root.

- [ ] **Step 1: Delete the 26 files whose entries are all covered by the root `.gitignore` or no longer exist**

Every non-UI extension `.gitignore` contains exactly `.idea`, `/vendor`, `/var`, `composer.lock`, `.DS_Store`. `.idea` and `.DS_Store` are already in the root `.gitignore`; `/vendor`, `/var` and `composer.lock` cannot appear inside an extension once Task 12 makes the root the only Composer project.

```bash
git rm -q \
  src/FastyBird/Addon/VirtualThermostat/.gitignore \
  src/FastyBird/Automator/DateTime/.gitignore \
  src/FastyBird/Automator/DevicesModule/.gitignore \
  src/FastyBird/Bridge/DevicesModuleUiModule/.gitignore \
  src/FastyBird/Bridge/RedisDbPluginDevicesModule/.gitignore \
  src/FastyBird/Bridge/RedisDbPluginTriggersModule/.gitignore \
  src/FastyBird/Bridge/ShellyConnectorHomeKitConnector/.gitignore \
  src/FastyBird/Bridge/VieraConnectorHomeKitConnector/.gitignore \
  src/FastyBird/Bridge/VirtualThermostatAddonHomeKitConnector/.gitignore \
  src/FastyBird/Connector/FbMqtt/.gitignore \
  src/FastyBird/Connector/Modbus/.gitignore \
  src/FastyBird/Connector/NsPanel/.gitignore \
  src/FastyBird/Connector/Shelly/.gitignore \
  src/FastyBird/Connector/Sonoff/.gitignore \
  src/FastyBird/Connector/Tuya/.gitignore \
  src/FastyBird/Connector/Viera/.gitignore \
  src/FastyBird/Connector/Virtual/.gitignore \
  src/FastyBird/Connector/Zigbee2Mqtt/.gitignore \
  src/FastyBird/Core/Exchange/.gitignore \
  src/FastyBird/Plugin/ApiKey/.gitignore \
  src/FastyBird/Plugin/CouchDb/.gitignore \
  src/FastyBird/Plugin/RabbitMq/.gitignore \
  src/FastyBird/Plugin/RedisDb/.gitignore \
  src/FastyBird/Plugin/RedisDbCache/.gitignore \
  src/FastyBird/Plugin/WebServer/.gitignore \
  src/FastyBird/Plugin/WsServer/.gitignore
```

- [ ] **Step 2: Trim the two kept files that list deleted scaffolding**

`src/FastyBird/Core/Tools/.gitignore` and `src/FastyBird/Library/Metadata/.gitignore` both end with entries for `.editorconfig`, `.gitattributes`, `.gitignore`, `.npmignore` and `Makefile`, none of which exist any more. Replace the contents of **both** files with exactly:

```
.idea
/dist
/node_modules
/vendor
/var
.DS_Store
composer.lock
yarn.lock
tsconfig.tsbuildinfo
```

This is byte-identical to the six other UI extension `.gitignore` files.

- [ ] **Step 3: Verify**

Run: `ls src/FastyBird/*/*/.gitignore | wc -l && ls src/FastyBird/*/*/.gitignore`
Expected: `9`, and exactly these paths:

```
src/FastyBird/Connector/HomeKit/.gitignore
src/FastyBird/Core/Application/.gitignore
src/FastyBird/Core/Tools/.gitignore
src/FastyBird/Library/Metadata/.gitignore
src/FastyBird/Library/WebUi/.gitignore
src/FastyBird/Module/Accounts/.gitignore
src/FastyBird/Module/Devices/.gitignore
src/FastyBird/Module/Triggers/.gitignore
src/FastyBird/Module/Ui/.gitignore
```

- [ ] **Step 4: Verify the working tree is clean of surprises**

Run: `git status --short | grep -v '^D ' | grep -v '^M ' | wc -l`
Expected: `0` — nothing has become untracked as a result of the removals.

- [ ] **Step 5: Verify the whole suite once more before opening the pull request**

Run: `docker compose exec -T application sh -c 'make lint && make cs && make phpstan && make tests' && docker compose exec -T ui-server yarn build`
Expected: all five exit 0.

- [ ] **Step 6: Commit**

```bash
git add -A src/FastyBird
git commit -m "chore(cross): prune per-extension gitignore files"
```

---

### Task 11: Trim the 34 extension composer manifests

**Files:**
- Modify: all 34 `src/FastyBird/*/*/composer.json` files

**Interfaces:**
- Consumes: `make composer-validate` from Task 5.
- Produces: 34 manifests holding only `name`, `description`, `keywords`, `homepage`, `license`, `authors`, `support`, `require`, `autoload`, `autoload-dev`, `minimum-stability`, `prefer-stable`, with every inter-extension constraint expressed as `"@dev"`. Task 12 depends on this: the root path repository reads these files.

- [ ] **Step 1: Record the current state**

```bash
grep -h '"fastybird/[a-z0-9-]*": "dev-main"' src/FastyBird/*/*/composer.json | wc -l
grep -l '"scripts"' src/FastyBird/*/*/composer.json | wc -l
python3 -c "
import json, glob
keys = set()
for f in glob.glob('src/FastyBird/*/*/composer.json'):
    keys |= set(json.load(open(f)).keys())
print(sorted(keys))"
```

Expected: `119` inter-extension `dev-main` constraints, `0` manifests with a `scripts` block (so the spec's instruction to drop `scripts` is a verified no-op), and the key set

```
['authors', 'autoload', 'autoload-dev', 'bin', 'config', 'description', 'extra', 'homepage', 'keywords', 'license', 'minimum-stability', 'name', 'prefer-stable', 'require', 'require-dev', 'repositories', 'suggest', 'support', 'type']
```

Keys removed by the trim and why: `require-dev` (QA tooling lives at the root, spec 4.3), `bin` (only `Core/Application` has it; the four console entry points are reached through the root `bin/` directory, spec 4.7), `config` (`allow-plugins` and `sort-packages` are root-only settings), `extra` (contains only `branch-alias` and `patches`; both are root concerns now), `repositories` (Composer ignores this key outside the root package; the `mathsolver/mathsolver` VCS entry stays at the root), `type` (the custom values `fastybird-connector`, `fastybird-module`, `fastybird-plugin`, `fastybird-bridge`, `fastybird-addon`, `fastybird-automator`, `fastybird-library`, `fastybird` have no installer and are referenced nowhere in the repository — verified with `grep -rn 'fastybird-connector' --include='*.php' --include='*.ts' --include='*.neon' src/`, which returns nothing), and `suggest` (present only in `Connector/Modbus` and `Plugin/WebServer`; advisory text for a package that is no longer published).

- [ ] **Step 2: Rewrite the manifests**

```bash
python3 - <<'PYEOF'
import json, glob

KEEP = ["name", "description", "keywords", "homepage", "license", "authors", "support",
        "require", "autoload", "autoload-dev", "minimum-stability", "prefer-stable"]

files = sorted(glob.glob('src/FastyBird/*/*/composer.json'))
internal = {json.load(open(f))['name'] for f in files}
assert len(internal) == 34, len(internal)

for f in files:
    data = json.load(open(f))
    out = {key: data[key] for key in KEEP if key in data}
    out['require'] = {
        name: ('@dev' if name in internal else constraint)
        for name, constraint in data['require'].items()
    }
    open(f, 'w').write(json.dumps(out, indent=2, ensure_ascii=False) + "\n")

print(len(files), 'manifests rewritten')
PYEOF
```

The `dev-main` to `@dev` rewrite is not cosmetic. Composer derives the version of a `path` package from the checked-out git branch, so `"fastybird/application": "dev-main"` only resolves while the working branch is called `main`. `"@dev"` is constraint `*` plus a dev stability flag and resolves on any branch. All 119 `dev-main` occurrences name one of the 34 internal packages; the external `fastybird/datetime-factory`, `fastybird/json-api` and `fastybird/simple-auth` constraints are untouched.

- [ ] **Step 3: Verify the rewrite**

```bash
grep -l '"require-dev"' src/FastyBird/*/*/composer.json | wc -l
grep -h '"dev-main"' src/FastyBird/*/*/composer.json | wc -l
grep -h '"fastybird/datetime-factory"' src/FastyBird/*/*/composer.json | sort -u
python3 -c "
import json, glob
extra = set()
for f in glob.glob('src/FastyBird/*/*/composer.json'):
    extra |= set(json.load(open(f)).keys())
print(sorted(extra))"
```

Expected: `0`, `0`, `    \"fastybird/datetime-factory\": \"^0.7.1\",`, and the key set

```
['authors', 'autoload', 'autoload-dev', 'description', 'homepage', 'keywords', 'license', 'minimum-stability', 'name', 'prefer-stable', 'require', 'support']
```

- [ ] **Step 4: Verify every manifest still validates**

Run: `docker compose exec -T application make composer-validate`
Expected: 35 `is valid` lines, exit status 0.

- [ ] **Step 5: Verify the application is unaffected so far**

Run: `docker compose exec -T application sh -c 'make phpstan && make tests'`
Expected: exit status 0 for both. The root manifest does not yet reference these files, so Composer has not read them and `vendor/` is unchanged.

- [ ] **Step 6: Commit**

```bash
git add src/FastyBird/*/*/composer.json
git commit -m "refactor(cross): trim extension composer manifests"
```

---

### Task 12: Convert the root manifest to the path repository model

**Files:**
- Modify: `composer.json` (`type`, `require`, `require-dev`, `repositories`, `autoload`, `replace`, `extra.patches`, `minimum-stability`, `prefer-stable`)
- Modify: `composer.lock` (regenerated)

**Interfaces:**
- Consumes: the trimmed extension manifests from Task 11, `tools/patches/*.patch` produced by Phase 0, `make composer-validate` from Task 5.
- Produces: a root manifest with a `path` repository over `src/FastyBird/*/*`, 34 `"@dev"` requirements, an empty `autoload.psr-4`, no `replace`, and the 88 `autoload-dev.psr-4` entries verbatim. Every later task installs against this.

- [ ] **Step 1: Confirm Phase 0 vendored the patches**

Run: `ls tools/patches/*.patch | wc -l && ls tools/patches/`
Expected: `10`, and these basenames:

```
contributte-monolog-src-loggerholder-php.patch
dg-bypass-finals-src-nativewrapper-php.patch
doctrine-dbal-src-connection-php.patch
doctrine-orm-lib-doctrine-orm-mapping-classmetadatafactory-php.patch
doctrine-orm-lib-doctrine-orm-persisters-entity-basicentitypersister-php.patch
doctrine-orm-lib-doctrine-orm-persisters-entity-joinedsubclasspersister-php.patch
nettrine-orm-src-managerregistry-php.patch
ramsey-uuid-doctrine-src-uuidbinarytype-php.patch
react-event-loop-src-loop-php.patch
softcreatr-jsonpath-src-filters-querymatchfilter-php.patch
```

- [ ] **Step 2: Snapshot the current lock for the version-drift check**

```bash
git show HEAD:composer.lock > /tmp/composer.lock.before
```

- [ ] **Step 3: Rewrite the root manifest**

```bash
python3 - <<'PYEOF'
import json

data = json.load(open('composer.json'))

packages = sorted(data['replace'])
assert len(packages) == 34, len(packages)

SHELL = ['contributte/console', 'contributte/event-dispatcher', 'contributte/monolog',
         'contributte/translation', 'contributte/vite', 'cweagans/composer-patches',
         'doctrine/orm', 'nette/application', 'nette/bootstrap', 'nettrine/fixtures',
         'symplify/vendor-patches', 'vlucas/phpdotenv']

old = data['require']
for name in SHELL:
    assert name in old, name

data['type'] = 'project'

require = {name: value for name, value in old.items()
           if name == 'php' or name.startswith('ext-')}
rest = {name: old[name] for name in SHELL}
rest['nettrine/migrations'] = '^0.8'
for name in packages:
    rest[name] = '@dev'
for name in sorted(rest):
    require[name] = rest[name]
data['require'] = require

del data['require-dev']['symplify/monorepo-builder']
del data['replace']

data['repositories'] = [
    {'type': 'path', 'url': 'src/FastyBird/*/*', 'options': {'symlink': True}},
    {'type': 'vcs', 'url': 'https://github.com/mathsolver/mathsolver.git'},
]

data['autoload'] = {'psr-4': {}}

for target, patches in data['extra']['patches'].items():
    for title, location in list(patches.items()):
        patches[title] = 'tools/patches/' + location.rsplit('/', 1)[-1]

data['minimum-stability'] = 'dev'
data['prefer-stable'] = True

open('composer.json', 'w').write(json.dumps(data, indent=4, ensure_ascii=False) + "\n")
print('root manifest rewritten,', len(require), 'require entries')
PYEOF
```

Expected output: `root manifest rewritten, 66 require entries` — `php`, 18 `ext-*`, 12 shell packages, `nettrine/migrations`, and the 34 extensions.

Three notes on what this script does and does not do:

- `mangoweb/monolog-tracy-handler` and `sabre/xml` are the only two dropped root requirements that no extension manifest declares. `grep -rni "sabre\|mangoweb" --include='*.php' --include='*.neon' --include='*.json' --include='*.xml' .` matches nothing outside `composer.json` itself, so neither is used and neither is re-declared anywhere.
- `minimum-stability: dev` and `prefer-stable: true` are added because Composer only honours stability flags declared in the **root** package. `bunny/bunny 0.6.x-dev` (`Plugin/RabbitMq`), `clue/redis-react ^3@dev` (`Plugin/RedisDb`, `Plugin/RedisDbCache`) and `mathsolver/mathsolver @dev` (`Core/Tools`) are now required only through extension manifests, and without this pair they would become unresolvable. Every extension manifest already declares exactly this pair.
- `enable-patching: false`, `config.allow-plugins`, `bin` and the 88 `autoload-dev.psr-4` entries are left untouched.

- [ ] **Step 4: Verify the manifest shape**

```bash
python3 -c "
import json
d = json.load(open('composer.json'))
print('type', d['type'])
print('replace' in d)
print('autoload', d['autoload'])
print('autoload-dev entries', len(d['autoload-dev']['psr-4']))
print('dev requirements', sum(1 for v in d['require'].values() if v == '@dev'))
print('monorepo-builder' in json.dumps(d))
print(d['repositories'][0])
print(sorted({p for t in d['extra']['patches'].values() for p in t.values()})[0])
"
```

Expected:

```
type project
False
autoload {'psr-4': {}}
autoload-dev entries 88
dev requirements 34
False
{'type': 'path', 'url': 'src/FastyBird/*/*', 'options': {'symlink': True}}
tools/patches/contributte-monolog-src-loggerholder-php.patch
```

- [ ] **Step 5: Regenerate the lock**

```bash
docker compose exec -T -e COMPOSER_ROOT_VERSION=dev-main application composer update --no-interaction --prefer-dist
```

`COMPOSER_ROOT_VERSION` keeps the version Composer records for the 34 path packages stable at `dev-main` regardless of the branch the work happens on.

- [ ] **Step 6: Verify that no third-party version moved**

```bash
python3 - <<'PYEOF'
import json

def versions(path):
    lock = json.load(open(path))
    return {p['name']: p['version'] for p in lock['packages'] + lock['packages-dev']}

before, after = versions('/tmp/composer.lock.before'), versions('composer.lock')
changed = {k: (before[k], after[k]) for k in before if k in after and before[k] != after[k]}
print('changed:', changed)
print('added:', sorted(set(after) - set(before)))
print('removed:', sorted(set(before) - set(after)))
PYEOF
```

Expected: `changed: {}`; `added:` exactly the 34 `fastybird/*` package names; `removed:` `symplify/monorepo-builder` plus the packages required only by it. Any entry in `changed` is a dependency version change and violates D11 — revert the lock, add the offending package back to the root `require` at its current constraint, and re-run from Step 5.

- [ ] **Step 7: Verify installation with symlinked path packages**

```bash
rm -rf vendor
docker compose exec -T -e COMPOSER_ROOT_VERSION=dev-main application composer install --no-interaction
test -L vendor/fastybird/shelly-connector && echo "symlinked"
ls vendor/fastybird | wc -l
```

Expected: `symlinked`, then `34`.

- [ ] **Step 8: Verify installation with mirrored path packages**

```bash
rm -rf vendor
docker compose exec -T -e COMPOSER_ROOT_VERSION=dev-main -e COMPOSER_MIRROR_PATH_REPOS=1 application composer install --no-interaction
test ! -L vendor/fastybird/shelly-connector && test -d vendor/fastybird/shelly-connector && echo "mirrored"
test -f vendor/fastybird/shelly-connector/src/Connector.php && echo "sources copied"
```

Expected: `mirrored`, then `sources copied`. This is the mode the production image build uses, because a symlink into `src/` cannot be copied into an image layer that does not contain `src/`.

- [ ] **Step 9: Restore the symlink install and verify the application**

```bash
rm -rf vendor
docker compose exec -T -e COMPOSER_ROOT_VERSION=dev-main application composer install --no-interaction
docker compose exec -T application sh -c 'make composer-validate && make lint && make cs && make phpstan && make tests'
```

Expected: all exit 0. `make tests` proves the 88 `autoload-dev` entries still resolve now that `autoload.psr-4` is empty and the 34 source namespaces come from `vendor/fastybird/*`.

- [ ] **Step 10: Commit**

```bash
git add composer.json composer.lock
git commit -m "refactor(infra): switch root manifest to composer path repositories"
```

---

### Task 13: Remove the monorepo split tooling

**Files:**
- Delete: `monorepo-builder.php`, `.github/workflows/monorepo.yaml`, `.github/workflows/monorepo-matrix.json`, `fastybird`

**Interfaces:**
- Consumes: Task 12, which already dropped `symplify/monorepo-builder` from `require-dev`.
- Produces: a repository with no machinery for pushing packages to the 25 split repositories. Phase 7 deletes those repositories.

- [ ] **Step 1: Confirm nothing references the files being removed**

```bash
grep -rn 'monorepo' --include='*.json' --include='*.yaml' --include='*.yml' --include='*.php' --include='Makefile' . 2>/dev/null | grep -v node_modules | grep -v '^./vendor/' | grep -v docs/superpowers
grep -rn 'fastybird' Makefile | grep -c './fastybird'
```

Expected: the first command shows content hits only in `.github/workflows/monorepo.yaml` (plus, before Task 12 ran, the `composer.json` line that has since been removed) — `monorepo-builder.php` and `.github/workflows/monorepo-matrix.json` never appear in this grep's output because their content has no lowercase `monorepo` substring; their removal is justified by the file list itself, not by this grep. The second command prints `0`. The root `fastybird` script only does `include(__DIR__ . '/bin/fb-console.php')`, which `bin/fb-console` already does.

- [ ] **Step 2: Remove them**

```bash
git rm -q monorepo-builder.php .github/workflows/monorepo.yaml .github/workflows/monorepo-matrix.json fastybird
```

- [ ] **Step 3: Verify the remaining workflows and console entry point**

```bash
ls .github/workflows/
docker compose exec -T application php bin/fb-console.php list | head -5
```

Expected: `lint.yaml  qa.yaml  static-analysis.yaml  tests.yaml` (Phase 4 replaces these), and a console banner followed by the command list.

- [ ] **Step 4: Commit**

```bash
git commit -m "chore(infra): remove monorepo split tooling"
```

---

### Task 14: Mirror path packages in the production image

**Files:**
- Modify: `docker/prod/Dockerfile` (the `vendor` stage created in Phase 2)

**Interfaces:**
- Consumes: `docker/prod/Dockerfile` from Phase 2 and the path repository from Task 12.
- Produces: a production image whose `vendor/fastybird/*` directories are real directories, not symlinks into a `src/` tree that the `vendor` stage does not ship.

- [ ] **Step 1: Locate the vendor stage**

Run: `grep -n 'AS vendor' docker/prod/Dockerfile && grep -n 'composer install' docker/prod/Dockerfile`
Expected: one `FROM ... AS vendor` line and one `composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative` line, per spec 4.7.

- [ ] **Step 2: Export the mirroring flag in that stage**

Phase 2 already set `ENV COMPOSER_ALLOW_SUPERUSER=1 \` / `    COMPOSER_MIRROR_PATH_REPOS=1` immediately after `WORKDIR /app` in the `vendor` stage, so `COMPOSER_MIRROR_PATH_REPOS` is already exported. Extend that existing block instead of adding a second, duplicate one. Replace:

```dockerfile
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MIRROR_PATH_REPOS=1
```

with:

```dockerfile
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MIRROR_PATH_REPOS=1 \
    COMPOSER_ROOT_VERSION=dev-main
```

- [ ] **Step 3: Verify the flags are set before the install**

Run: `awk '/AS vendor/,/composer install/' docker/prod/Dockerfile`
Expected: the stage header, `WORKDIR /app`, the single extended `ENV` block (now three lines), the `COPY` lines, then the `composer install` line — the `ENV`s must precede the install.

- [ ] **Step 4: Build the image and prove the path packages were copied**

```bash
docker build -f docker/prod/Dockerfile -t miniserver:phase3 .
docker run --rm --entrypoint sh miniserver:phase3 -c 'test -L vendor/fastybird/shelly-connector && echo SYMLINK || echo DIRECTORY; ls vendor/fastybird | wc -l'
```

Expected: `DIRECTORY`, then `34`.

- [ ] **Step 5: Commit**

```bash
git add docker/prod/Dockerfile
git commit -m "chore(infra): mirror path repositories in the production image"
```

---

### Task 15: Move `var/config/` to `config/`

**Files:**
- Modify (move): `var/config/` to `config/` — `common.neon`, `defaults.neon`, `extensions.ts`, `.gitignore`, `supervisor/modules/devices-module.conf`, `supervisor/plugins/web-server.conf`, `supervisor/plugins/ws-server.conf`, `supervisor/system/application.conf`
- Modify: the four supervisor `.conf` files (relative paths lose one level)
- Delete: `config/.gitignore`
- Modify: `.gitignore` (root)
- Modify: `tools/phpunit-bootstrap.php` (line 6)

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: `config/` at the repository root, the default value of `FB_CONFIG_DIR` per spec 4.5 / D9. Task 16 repoints the remaining references; Task 17 changes the bootstrap.

- [ ] **Step 1: Move the directory with history**

```bash
git mv var/config config
find config -type f | sort
```

Expected:

```
config/.gitignore
config/common.neon
config/defaults.neon
config/extensions.ts
config/supervisor/modules/devices-module.conf
config/supervisor/plugins/web-server.conf
config/supervisor/plugins/ws-server.conf
config/supervisor/system/application.conf
```

- [ ] **Step 2: Fix the supervisor relative paths**

The programs used `%(here)s/../../../../bin/fb-console.php` and `%(here)s/../../../logs/...` from `var/config/supervisor/<group>/`. From `config/supervisor/<group>/` the repository root is one level nearer and the log directory is `var/logs`.

```bash
sed -i '' \
  -e 's|%(here)s/\.\./\.\./\.\./\.\./bin/fb-console\.php|%(here)s/../../../bin/fb-console.php|' \
  -e 's|%(here)s/\.\./\.\./\.\./logs/|%(here)s/../../../var/logs/|' \
  config/supervisor/modules/devices-module.conf \
  config/supervisor/plugins/web-server.conf \
  config/supervisor/plugins/ws-server.conf \
  config/supervisor/system/application.conf
```

- [ ] **Step 3: Verify the supervisor paths resolve**

```bash
grep -h 'command = \|logfile = ' config/supervisor/*/*.conf | sort -u
( cd config/supervisor/modules && ls ../../../bin/fb-console.php && ls -d ../../../var/logs )
```

Expected: the `command` lines all read `php %(here)s/../../../bin/fb-console.php ...`, the log lines all read `%(here)s/../../../var/logs/...`, and both `ls` calls succeed. `config/supervisor/system/application.conf` still runs `yarn workspace @fastybird/application dev`; that stale program is Phase 5's problem, only its log paths change here.

- [ ] **Step 4: Move the ignore rules to the root `.gitignore`**

```bash
git rm -q config/.gitignore
printf '/config/local.neon\n/config/supervisor/*.local.conf\n' >> .gitignore
```

`config/.gitignore` contained only `local.neon`. Spec 4.5 additionally requires `config/supervisor/*.local.conf` to be ignored so operator-written connector programs in the default location do not show up as repository changes.

- [ ] **Step 5: Keep the test suite isolated from the shipped wiring**

`tools/phpunit-bootstrap.php` line 5 and 6 currently read:

```php
define('FB_APP_DIR', realpath(__DIR__ . '/..'));
define('FB_CONFIG_DIR', realpath(__DIR__ . '/../config'));
```

Replace both with:

```php
define('FB_APP_DIR', realpath(__DIR__ . '/../tests'));
define('FB_CONFIG_DIR', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'config');
```

Until this commit `realpath(__DIR__ . '/../config')` returned `false` because `<root>/config` did not exist, so the test suite loaded no application wiring. With `config/` now present, both `FB_APP_DIR/config` (added by Task 17) and `FB_CONFIG_DIR` would resolve to the shipped configuration and every one of the 53 test container factories would compile all 34 DI extensions. Pointing both constants at `<root>/tests`, which has no `config/` subdirectory, preserves the current behaviour exactly. `FB_APP_DIR` is only read inside `Bootstrap.php`, and all 53 factories override the `appDir` and `wwwDir` static parameters immediately after `Bootstrap::boot()`.

- [ ] **Step 6: Verify the test suite**

```bash
grep -rn "'appDir' =>" src/FastyBird/*/*/tests/cases/unit/*.php | wc -l
docker compose exec -T application make tests
```

Expected: `53`, then exit status 0 with the same number of tests as before the move.

- [ ] **Step 7: Commit**

```bash
git add -A config var .gitignore tools/phpunit-bootstrap.php
git commit -m "refactor(core): move var/config to config"
```

---

### Task 16: Repoint every remaining reference to the configuration directory

**Files:**
- Modify: `public/index.php` (delete the hard-coded `FB_CONFIG_DIR` definition)
- Modify: `src/FastyBird/Core/Application/assets/main.ts` (the `extensions` import)
- Modify: the development Docker Compose files that set `FB_CONFIG_DIR`
- Modify: `docker/prod/Dockerfile` (`FB_CONFIG_DIR`, `FB_LOGS_DIR`, `FB_TEMP_DIR`, `VOLUME`, and the `mkdir`/`chown` lines)

**Interfaces:**
- Consumes: `config/` from Task 15.
- Produces: no reference to `var/config` anywhere in the repository, and shipped production defaults of `FB_CONFIG_DIR=/data/config`, `FB_LOGS_DIR=/data/logs`, `FB_TEMP_DIR=/data/temp`, `VOLUME /data`, per spec 4.5/4.7/4.9. Task 17 can then rely on `FB_CONFIG_DIR` actually being honoured over HTTP.

- [ ] **Step 1: Enumerate what still points at the old location**

Run: `grep -rn 'var/config' --include='*' . 2>/dev/null | grep -v node_modules | grep -v '^\./\.git/' | grep -v '^\./vendor/' | grep -v docs/superpowers`
Expected before this task, in the pre-Phase-2 tree, exactly six hits: five `FB_CONFIG_DIR: /app/var/config` lines in the development Compose file and one import in `src/FastyBird/Core/Application/assets/main.ts`. Phase 2 moved the Compose services into `docker/dev/docker-compose.yml`, so the five Compose hits are there instead. Treat this command's output as the authoritative work list; every hit must end up as `/app/config` in development and `/data/config` in production (spec 4.5).

- [ ] **Step 2: Repoint the development Compose environment**

```bash
grep -rln 'FB_CONFIG_DIR' docker/dev docker-compose.yml 2>/dev/null | xargs sed -i '' 's|/app/var/config|/app/config|g'
grep -rn 'FB_CONFIG_DIR' docker/dev docker-compose.yml 2>/dev/null
```

Expected: every development service now shows `FB_CONFIG_DIR: /app/config`.

- [ ] **Step 3: Switch the production image's shipped defaults to the `/data` layout**

Phase 2's `docker/prod/Dockerfile` ships `FB_CONFIG_DIR=/app/var/config`, `FB_LOGS_DIR=/app/var/logs`, `FB_TEMP_DIR=/app/var/temp` and `VOLUME ${APP_PATH}/var` — it explicitly deferred the `/data` switch to this phase. Replace:

```dockerfile
    && mkdir -p var/logs var/temp \
```

with:

```dockerfile
    && mkdir -p /data/config /data/logs /data/temp \
```

(adjusting the surrounding `chown`/ownership arguments on that line to cover `/data` instead of `var/logs var/temp`), replace:

```dockerfile
    FB_CONFIG_DIR=/app/var/config \
    FB_LOGS_DIR=/app/var/logs \
    FB_TEMP_DIR=/app/var/temp
```

with:

```dockerfile
    FB_CONFIG_DIR=/data/config \
    FB_LOGS_DIR=/data/logs \
    FB_TEMP_DIR=/data/temp
```

and replace:

```dockerfile
VOLUME ${APP_PATH}/var
```

with:

```dockerfile
VOLUME /data
```

Run: `grep -n 'FB_CONFIG_DIR\|FB_LOGS_DIR\|FB_TEMP_DIR\|^VOLUME' docker/prod/Dockerfile`
Expected: `FB_CONFIG_DIR=/data/config`, `FB_LOGS_DIR=/data/logs`, `FB_TEMP_DIR=/data/temp`, `VOLUME /data`.

- [ ] **Step 4: Repoint the frontend extension registry import**

In `src/FastyBird/Core/Application/assets/main.ts`, replace

```ts
import { extensions } from '../../../../../var/config/extensions';
```

with

```ts
import { extensions } from '../../../../../config/extensions';
```

The file lives at `src/FastyBird/Core/Application/assets/main.ts`, so five `..` segments reach the repository root either way.

- [ ] **Step 5: Remove the hard-coded configuration directory from the web entry point**

In `public/index.php`, delete this line entirely:

```php
define('FB_CONFIG_DIR', realpath(FB_APP_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'config'));
```

Do not replace it. `Bootstrap::initConstants()` only defines `FB_CONFIG_DIR` when it is not already defined, so this line makes the `FB_CONFIG_DIR` environment variable unreachable over HTTP — exactly the path the production image uses. It is also a fatal error when `$_ENV['FB_APP_DIR']` is set, because in that branch `public/index.php` never defines `FB_APP_DIR` and PHP 8 throws on an undefined constant.

- [ ] **Step 6: Verify nothing references the old path**

```bash
grep -rn 'var/config' --include='*' . 2>/dev/null | grep -v node_modules | grep -v '^\./\.git/' | grep -v '^\./vendor/' | grep -v docs/superpowers | wc -l
php -l public/index.php
```

Expected: `0`, then `No syntax errors detected in public/index.php`.

- [ ] **Step 7: Verify the frontend still builds**

Run: `docker compose exec -T ui-server sh -c 'yarn types && yarn build'`
Expected: both exit 0 and `public/index.html` plus `public/assets/` are produced.

- [ ] **Step 8: Commit**

```bash
git add public/index.php src/FastyBird/Core/Application/assets/main.ts docker docker-compose.yml
git commit -m "refactor(core): repoint configuration directory references"
```

---

### Task 17: Implement the three-layer configuration load order

**Files:**
- Create: `src/FastyBird/Core/Application/tests/cases/unit/Boot/BootstrapConfigFilesTest.php`
- Modify: `src/FastyBird/Core/Application/src/Boot/Bootstrap.php` (`boot()` and `initConstants()`)

**Interfaces:**
- Consumes: `config/` from Task 15, the isolated test bootstrap from Task 15 Step 5.
- Produces: `FastyBird\Core\Application\Boot\Bootstrap::resolveConfigFiles()` and the spec 4.5 load order. Task 19's deliverable check depends on it.

- [ ] **Step 1: Write the failing test**

Create `src/FastyBird/Core/Application/tests/cases/unit/Boot/BootstrapConfigFilesTest.php` with exactly:

```php
<?php declare(strict_types = 1);

namespace FastyBird\Core\Application\Tests\Cases\Unit\Boot;

use FastyBird\Core\Application\Boot;
use Nette;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function mkdir;
use function realpath;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR as DS;

final class BootstrapConfigFilesTest extends TestCase
{

	private string $workDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->workDir = realpath(sys_get_temp_dir()) . DS . 'fb-config-' . uniqid();

		mkdir($this->workDir . DS . 'extension', 0777, true);
		mkdir($this->workDir . DS . 'app', 0777, true);
		mkdir($this->workDir . DS . 'overrides', 0777, true);

		file_put_contents($this->workDir . DS . 'extension' . DS . 'common.neon', "parameters:\n");
		file_put_contents($this->workDir . DS . 'extension' . DS . 'defaults.neon', "parameters:\n");
		file_put_contents($this->workDir . DS . 'app' . DS . 'common.neon', "parameters:\n");
		file_put_contents($this->workDir . DS . 'overrides' . DS . 'local.neon', "parameters:\n");
	}

	/**
	 * @throws Nette\IOException
	 */
	protected function tearDown(): void
	{
		Nette\Utils\FileSystem::delete($this->workDir);

		parent::tearDown();
	}

	public function testMissingFilesAreSkippedAndOrderIsPreserved(): void
	{
		$files = Boot\Bootstrap::resolveConfigFiles([
			[$this->workDir . DS . 'extension', ['common.neon', 'defaults.neon']],
			[$this->workDir . DS . 'app', ['common.neon', 'defaults.neon']],
			[$this->workDir . DS . 'overrides', ['common.neon', 'defaults.neon', 'local.neon']],
		]);

		self::assertSame(
			[
				$this->workDir . DS . 'extension' . DS . 'common.neon',
				$this->workDir . DS . 'extension' . DS . 'defaults.neon',
				$this->workDir . DS . 'app' . DS . 'common.neon',
				$this->workDir . DS . 'overrides' . DS . 'local.neon',
			],
			$files,
		);
	}

	public function testSameDirectoryReachedThroughSymlinkIsLoadedOnce(): void
	{
		symlink($this->workDir . DS . 'app', $this->workDir . DS . 'link');

		$files = Boot\Bootstrap::resolveConfigFiles([
			[$this->workDir . DS . 'app', ['common.neon', 'defaults.neon']],
			[$this->workDir . DS . 'link', ['common.neon', 'defaults.neon', 'local.neon']],
		]);

		self::assertSame([$this->workDir . DS . 'app' . DS . 'common.neon'], $files);
	}

}
```

- [ ] **Step 2: Run it and confirm it fails for the right reason**

Run: `XDEBUG_MODE=off vendor/bin/phpunit -c tools/phpunit.xml --filter BootstrapConfigFilesTest`
Expected: 2 errors, both `Error: Call to undefined method FastyBird\Core\Application\Boot\Bootstrap::resolveConfigFiles()`.

- [ ] **Step 3: Add `resolveConfigFiles()` to `Bootstrap`**

In `src/FastyBird/Core/Application/src/Boot/Bootstrap.php`, insert this method immediately after `boot()` and before `initConstants()`:

```php
	/**
	 * Builds the ordered list of configuration files to load. Files that do not exist are skipped
	 * and any file whose resolved absolute path was already collected is skipped as well, so that
	 * pointing FB_CONFIG_DIR at the application configuration directory, or at a symlink to it,
	 * loads those files exactly once.
	 *
	 * @param array<array{string, array<string>}> $sources
	 *
	 * @return array<string>
	 */
	public static function resolveConfigFiles(array $sources): array
	{
		$files = [];

		foreach ($sources as [$directory, $names]) {
			foreach ($names as $name) {
				$path = $directory . DS . $name;

				if (!file_exists($path)) {
					continue;
				}

				$realPath = realpath($path);

				if ($realPath === false || in_array($realPath, $files, true)) {
					continue;
				}

				$files[] = $realPath;
			}
		}

		return $files;
	}
```

`file_exists`, `realpath` and `in_array` are already in the file's `use function` list.

- [ ] **Step 4: Replace the config loading in `boot()`**

Replace this block in `boot()`:

```php
		// Default extension config
		$config->addConfig(__DIR__ . DS . '..' . DS . '..' . DS . 'config' . DS . 'common.neon');
		$config->addConfig(__DIR__ . DS . '..' . DS . '..' . DS . 'config' . DS . 'defaults.neon');

		if (file_exists(FB_CONFIG_DIR . DS . 'common.neon')) {
			$config->addConfig(FB_CONFIG_DIR . DS . 'common.neon');
		}

		if (file_exists(FB_CONFIG_DIR . DS . 'defaults.neon')) {
			$config->addConfig(FB_CONFIG_DIR . DS . 'defaults.neon');
		}

		if (file_exists(FB_CONFIG_DIR . DS . 'local.neon')) {
			$config->addConfig(FB_CONFIG_DIR . DS . 'local.neon');
		}
```

with:

```php
		// Shipped extension defaults, then the application wiring, then the operator overrides
		$configFiles = self::resolveConfigFiles([
			[__DIR__ . DS . '..' . DS . '..' . DS . 'config', ['common.neon', 'defaults.neon']],
			[strval(FB_APP_DIR) . DS . 'config', ['common.neon', 'defaults.neon']],
			[strval(FB_CONFIG_DIR), ['common.neon', 'defaults.neon', 'local.neon']],
		]);

		foreach ($configFiles as $configFile) {
			$config->addConfig($configFile);
		}
```

- [ ] **Step 5: Remove the `var/config` fallback from `initConstants()`**

Replace:

```php
		} elseif (!defined('FB_CONFIG_DIR')) {
			if (realpath(FB_APP_DIR . DS . 'config') !== false) {
				define('FB_CONFIG_DIR', realpath(FB_APP_DIR . DS . 'config'));
			} else {
				define('FB_CONFIG_DIR', realpath(FB_APP_DIR . DS . 'var' . DS . 'config'));
			}
		}
```

with:

```php
		} elseif (!defined('FB_CONFIG_DIR')) {
			define('FB_CONFIG_DIR', realpath(FB_APP_DIR . DS . 'config'));
		}
```

- [ ] **Step 6: Run the test again**

Run: `XDEBUG_MODE=off vendor/bin/phpunit -c tools/phpunit.xml --filter BootstrapConfigFilesTest`
Expected: `OK (2 tests, 2 assertions)`.

- [ ] **Step 7: Verify the constant is gone and the console still boots**

```bash
grep -c "var' . DS . 'config" src/FastyBird/Core/Application/src/Boot/Bootstrap.php
docker compose exec -T application php bin/fb-console.php list | head -3
docker compose exec -T -e FB_CONFIG_DIR=/app/config application php bin/fb-console.php list | head -3
```

Expected: `0`, then the same console banner twice — the second invocation points `FB_CONFIG_DIR` at the application configuration directory itself, which the deduplication must reduce to a single load of each file rather than a duplicate-extension error.

- [ ] **Step 8: Verify the full suite and the coding standard**

Run: `docker compose exec -T application sh -c 'make csf && make cs && make phpstan && make tests'`
Expected: all exit 0.

- [ ] **Step 9: Commit**

```bash
git add src/FastyBird/Core/Application/src/Boot/Bootstrap.php src/FastyBird/Core/Application/tests/cases/unit/Boot/BootstrapConfigFilesTest.php
git commit -m "feat(core): load configuration from application and override directories"
```

---

### Task 18: Wire Doctrine migrations into the application configuration

**Files:**
- Modify: `config/common.neon` (register `nettrineMigrations` and configure its directory)
- Create: `migrations/.gitkeep`

**Interfaces:**
- Consumes: `nettrine/migrations ^0.8` added to the root `require` in Task 12, `config/` from Task 15, the bootstrap change from Task 17.
- Produces: the `migrations:*` console commands, which Task 19 uses.

- [ ] **Step 1: Register the extension**

In `config/common.neon`, inside the `extensions:` block, insert the `nettrineMigrations` line directly after `nettrineFixtures` and before `ipubPhone`, keeping the existing column alignment:

```neon
    nettrineFixtures                                : Nettrine\Fixtures\DI\FixturesExtension
    nettrineMigrations                              : Nettrine\Migrations\DI\MigrationsExtension
    ipubPhone                                       : IPub\Phone\DI\PhoneExtension
```

- [ ] **Step 2: Configure the migrations directory**

Append to the end of `config/common.neon`, after the existing `nettrineFixtures:` block:

```neon

nettrineMigrations:
    directory: %appDir%/migrations
```

This is the block the old miniserver repository carried at `config/common.neon:213`. `%appDir%` is `FB_APP_DIR`, the repository root, so migrations live in `migrations/` at the root as spec 4.1 requires.

- [ ] **Step 3: Create the directory**

```bash
mkdir -p migrations
touch migrations/.gitkeep
```

Migration classes are tracked, so unlike `src/FastyBird/Core/Application/migrations/.gitignore` this directory gets a `.gitkeep`, not an ignore-everything rule.

- [ ] **Step 4: Verify the commands appear**

Run: `docker compose exec -T application php bin/fb-console.php list | grep '^  migrations:'`
Expected: at minimum `migrations:diff`, `migrations:migrate`, `migrations:status` and `migrations:generate`. If the container fails to compile, the `nettrineMigrations` block is misplaced — it must be a top-level key, not nested inside `nettrineFixtures`.

- [ ] **Step 5: Verify the rest of the suite is unaffected**

Run: `docker compose exec -T application sh -c 'make phpstan && make tests'`
Expected: both exit 0.

- [ ] **Step 6: Commit**

```bash
git add config/common.neon migrations/.gitkeep
git commit -m "feat(core): wire doctrine migrations"
```

---

### Task 19: Generate the initial migration and verify the phase deliverable

**Files:**
- Create: `migrations/Version<timestamp>.php` (name assigned by Doctrine)
- Delete: `migrations/.gitkeep`

**Interfaces:**
- Consumes: Tasks 12, 15, 17 and 18; a MariaDB 10.11 instance with an empty schema.
- Produces: the first Doctrine migration, which the production entrypoint's `migrations:migrate --no-interaction --allow-no-migration` applies. This closes Phase 3.

- [ ] **Step 1: Start an empty MariaDB sharing the `application` container's network**

The verification commands in Steps 3 and 5 run `php bin/fb-console.php` inside the `application` container (per spec 4.10, the toolchain is frozen to PHP 8.2, unlike the 8.5.6 host), so the ad hoc MariaDB container must be reachable from there, not just from the host. Joining `miniserver-migration-db`'s network namespace to the running `application` container's makes `127.0.0.1:3306` resolve identically from the host, from `application`, and from `miniserver-migration-db` itself.

```bash
docker compose up -d application
docker run -d --name miniserver-migration-db \
  --network container:$(docker compose ps -q application) \
  -e MARIADB_ROOT_PASSWORD=root \
  -e MARIADB_DATABASE=miniserver \
  -e MARIADB_USER=miniserver \
  -e MARIADB_PASSWORD=miniserver \
  mariadb:10.11
until docker exec miniserver-migration-db mariadb -uminiserver -pminiserver -e 'select 1' miniserver >/dev/null 2>&1; do sleep 2; done
echo "database ready"
```

Expected: `database ready`.

- [ ] **Step 2: Confirm the schema is empty**

Run: `docker exec miniserver-migration-db mariadb -uminiserver -pminiserver -e 'show tables' miniserver`
Expected: no output.

- [ ] **Step 3: Generate the migration**

```bash
docker compose exec -T \
  -e FB_APP_PARAMETER__DATABASE_HOST=127.0.0.1 \
  -e FB_APP_PARAMETER__DATABASE_PORT=3306 \
  -e FB_APP_PARAMETER__DATABASE_DBNAME=miniserver \
  -e FB_APP_PARAMETER__DATABASE_USERNAME=miniserver \
  -e FB_APP_PARAMETER__DATABASE_PASSWORD=miniserver \
  -e FB_APP_PARAMETER__DATABASE_VERSION=10.11.6-MariaDB \
  application php bin/fb-console.php migrations:diff --no-interaction
```

Expected: `Generated new migration class to "…/migrations/VersionYYYYMMDDHHMMSS.php"`. If it reports `No changes detected in your mapping information`, the container did not register the module extensions — re-check that `config/common.neon` is being loaded by re-running `docker compose exec -T application php bin/fb-console.php list | grep 'fb:devices-module:install'`.

- [ ] **Step 4: Inspect the result**

```bash
ls migrations/
grep -c 'CREATE TABLE' migrations/Version*.php
head -12 migrations/Version*.php
```

Expected: one `Version*.php` file; a `CREATE TABLE` count in the dozens covering the Accounts, Devices, Triggers and Ui module entities; and a header showing the namespace Doctrine used, which must match the `nettrineMigrations` directory configured in Task 18.

- [ ] **Step 5: Prove the migration applies to an empty database**

```bash
docker exec miniserver-migration-db mariadb -uroot -proot -e 'drop database miniserver; create database miniserver; grant all on miniserver.* to miniserver@"%"'
docker compose exec -T \
  -e FB_APP_PARAMETER__DATABASE_HOST=127.0.0.1 \
  -e FB_APP_PARAMETER__DATABASE_PORT=3306 \
  -e FB_APP_PARAMETER__DATABASE_DBNAME=miniserver \
  -e FB_APP_PARAMETER__DATABASE_USERNAME=miniserver \
  -e FB_APP_PARAMETER__DATABASE_PASSWORD=miniserver \
  -e FB_APP_PARAMETER__DATABASE_VERSION=10.11.6-MariaDB \
  application php bin/fb-console.php migrations:migrate --no-interaction --allow-no-migration
docker exec miniserver-migration-db mariadb -uminiserver -pminiserver -e 'show tables' miniserver | wc -l
```

Expected: `[OK] Successfully migrated to version …`, then a table count greater than 30, including `doctrine_migration_versions`.

- [ ] **Step 6: Track the migration and drop the placeholder**

```bash
git add migrations/Version*.php
git rm -q migrations/.gitkeep
```

- [ ] **Step 7: Verify the phase deliverable — QA, both install modes, frontend**

```bash
docker compose exec -T application make composer-validate
rm -rf vendor && docker compose exec -T -e COMPOSER_ROOT_VERSION=dev-main application composer install --no-interaction
docker compose exec -T application sh -c 'make lint && make cs && make phpstan && make tests'
rm -rf vendor && docker compose exec -T -e COMPOSER_ROOT_VERSION=dev-main -e COMPOSER_MIRROR_PATH_REPOS=1 application composer install --no-interaction
docker compose exec -T application sh -c 'make lint && make phpstan && make tests'
rm -rf vendor && docker compose exec -T -e COMPOSER_ROOT_VERSION=dev-main application composer install --no-interaction
docker compose exec -T ui-server yarn build
```

Expected: every command exits 0 in both install modes.

- [ ] **Step 8: Verify the phase deliverable — production image with an external configuration directory**

```bash
docker build -f docker/prod/Dockerfile -t miniserver:phase3 .
mkdir -p /tmp/miniserver-config
printf 'parameters:\n    security:\n        signature: %s\n' "$(openssl rand -base64 32)" > /tmp/miniserver-config/local.neon
ls /tmp/miniserver-config
docker run -d --name miniserver-phase3 --link miniserver-migration-db:database \
  -e FB_CONFIG_DIR=/data/config \
  -e FB_APP_PARAMETER__DATABASE_HOST=database \
  -e FB_APP_PARAMETER__DATABASE_DBNAME=miniserver \
  -e FB_APP_PARAMETER__DATABASE_USERNAME=miniserver \
  -e FB_APP_PARAMETER__DATABASE_PASSWORD=miniserver \
  -e FB_APP_PARAMETER__DATABASE_VERSION=10.11.6-MariaDB \
  -v /tmp/miniserver-config:/data/config \
  -p 8080:80 miniserver:phase3
sleep 20
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/
docker exec miniserver-phase3 php bin/fb-console.php list > /dev/null && echo "console ok"
```

Expected: `/tmp/miniserver-config` contains only `local.neon`; `curl` prints `200`; `console ok`. This is spec Phase 3's deliverable sentence: a container started with `FB_CONFIG_DIR` pointing at a directory containing only `local.neon` boots, because the shipped wiring now comes from `FB_APP_DIR/config` and `FB_CONFIG_DIR` supplies only overrides.

- [ ] **Step 9: Tear down**

```bash
docker rm -f miniserver-phase3 miniserver-migration-db
rm -rf /tmp/miniserver-config
git status --short
```

Expected: `git status --short` shows only the staged `migrations/` changes.

- [ ] **Step 10: Commit**

```bash
git add migrations
git commit -m "feat(core): add initial database migration"
```
