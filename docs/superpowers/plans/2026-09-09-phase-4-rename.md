# Phase 4, Rename And Conventions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename every identity value in the merged repository from `fastybird`/`fastybird` to `miniserver` (composer, npm, console, token issuer, database, image, manifests, docs), replace the reusable-workflow CI with self-contained conventions (`ci-tests.yaml`, release, PR-title and release-drafter workflows, commitlint, dependabot), write the root documentation set, remove the committed security signature, and rename the two GitHub repositories.

**Architecture:** This phase touches identity strings and documentation only — no PHP or TypeScript behaviour changes beyond the console name, token issuer, database defaults and the security-signature bootstrap. Work is scripted where a change repeats across many files (34 `composer.json`, 9 `package.json`, 35 `README.md`) and written by hand where content is unique (docs, CI workflows, entrypoint). The GitHub repository rename is a manual operator step, not a code change, and is given as its own checklist.

**Tech Stack:** Composer/PHP manifests, npm/yarn manifests, Nette NEON configuration, GitHub Actions YAML, POSIX shell (Docker entrypoint), Markdown documentation, `gh` CLI for the repository rename.

**Spec:** docs/superpowers/specs/2026-09-09-miniserver-merge-design.md

## Global Constraints

- PHP 8.2, Node 20, yarn 1 during this phase; pnpm arrives in Phase 6 (D10).
- No dependency version changes in this phase; adding `husky`, `@commitlint/cli` and `@commitlint/config-conventional` as new devDependencies is tooling required by 4.8, not a version bump of an existing package, and stays in its own pull request regardless (D11).
- No pull request mixes a structural change with a dependency version change (D11). CI is green before the next pull request starts (D11).
- Conventional commit format `<type>(<scope>): <subject>`, scope from `core, module, connector, plugin, bridge, addon, automator, library, ui, infra, ci, deps, docs, cross`.
- PHP namespaces stay `FastyBird\<Type>\<Name>`; the `src/FastyBird/` prefix does not change (D3, D4).
- Extension `composer.json` `name` fields are never renamed — they stay exactly as listed in the former root `replace` block (4.3). Only `support.issues`, `support.source` and, for the 9 frontend manifests, `repository.url` change.
- Extension `package.json` `version` fields stay `1.0.0-dev.24` in this phase; normalising them to `0.0.0` is Phase 5 scope (4.4) and is out of scope here.
- This plan assumes Phase 3 has already moved `var/config/` to `config/`, renamed `tools/phpstan.src.neon` to `tools/phpstan.neon`, added `make composer-validate`, and produced `migrations/`; and Phase 2 has already produced `docker/dev/`, `docker/prod/Dockerfile`, `docker/prod/docker-compose.yml`, `docker/dev/docker-compose.yml` and `.github/workflows/docker-build.yaml`. Every step below that reads or edits one of those paths is verifying it against the file Phase 2/3 actually produced, not assuming its exact byte content.
- No repository is deleted in this phase (D1 reserves deletion for Phase 7). The GitHub rename step only renames.

## Pull Requests

1. **Identity rename** — Tasks 1–6: root manifests, application config identity, extension manifest URLs, extension README cleanup, Docker Compose database identity, security-signature removal and entrypoint generation.
2. **CI and release conventions** — Tasks 7–10: `ci-tests.yaml`, `release.yml`, `lint-pr.yml` + `release-drafter.yml` + config, `dependabot.yml`. No dependency changes, so it can ride together in one pull request.
3. **Commitlint tooling** — Task 11: commitlint + husky. Kept as its own pull request because it adds `husky`, `@commitlint/cli` and `@commitlint/config-conventional` as new devDependencies, per the Global Constraints rule that new devDependencies never ride with structural/CI changes (D11).
4. **Documentation set** — Tasks 12–19: `CONTRIBUTING.md`, `CLAUDE.md`, `AGENTS.md`, root `README.md` + `docs/README.md` (+ removing `docs/CNAME` and `docs/index.md`), `docs/architecture.md`, `docs/configuration.md`, `docs/deployment.md`, `.env.example` + `.gitattributes`.
5. **GitHub repository rename** — Task 20: manual operator checklist, no pull request (there is nothing left to review once the rename runs; CI on the renamed repository is the verification).

---

### Task 1: Rename root composer.json and package.json identity

**Files:**
- Modify: `composer.json` (root) — `name`
- Modify: `package.json` (root) — `name`, `version`, `bugs`, `repository.url`

**Interfaces:**
- Consumes: Phase 3's root `composer.json` (`type: project`, path-repository model already in place) and root `package.json` (yarn workspaces, lerna scripts still present).
- Produces: the renamed root identity that every later task's verification checks against.

- [ ] **Step 1: Change the root composer name**

Confirmed current value: `"name": "fastybird/fastybird"` on line 2 of `composer.json`.

```bash
perl -pi -e 's/"name": "fastybird\/fastybird"/"name": "fastybird\/miniserver"/' composer.json
```

- [ ] **Step 2: Verify the composer name change**

Run: `grep -n '"name"' composer.json | head -1`
Expected: `    "name": "fastybird/miniserver",`

- [ ] **Step 3: Change the root package.json name, version, bugs and repository fields**

Confirmed current values in `package.json`: `"name": "@fastybird/fastybird"`, `"version": "1.0.0-dev.24"`, `"bugs": "https://github.com/FastyBird/fastybird/issues"`, `"url": "https://github.com/FastyBird/fastybird.git"` inside `"repository"`.

```bash
perl -pi -e 's/"name": "\@fastybird\/fastybird"/"name": "\@fastybird\/miniserver"/' package.json
perl -pi -e 's/"version": "1\.0\.0-dev\.24"/"version": "1.0.0-alpha.1"/' package.json
perl -pi -e 's#"bugs": "https://github\.com/FastyBird/fastybird/issues"#"bugs": "https://github.com/FastyBird/miniserver/issues"#' package.json
perl -pi -e 's#"url": "https://github\.com/FastyBird/fastybird\.git"#"url": "https://github.com/FastyBird/miniserver.git"#' package.json
```

- [ ] **Step 4: Verify the package.json identity fields**

Run: `grep -n '"name"\|"version"\|"bugs"\|"url"' package.json | head -5`
Expected:
```
  "name": "@fastybird/miniserver",
  "version": "1.0.0-alpha.1",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
    "url": "https://github.com/FastyBird/miniserver.git"
```

- [ ] **Step 5: Verify composer still validates and the homepage is already correct**

Run: `docker compose exec -T application composer validate --no-check-all --no-check-publish composer.json && grep -n '"homepage"' composer.json package.json`
Expected: `./composer.json is valid` (or `... is valid, but warnings were found` — warnings are acceptable, errors are not), and both `"homepage"` lines read `https://www.fastybird.com` unchanged (no edit needed there — the table's target value already matches).

- [ ] **Step 6: Commit**

```bash
git add composer.json package.json
git commit -m "chore(infra): rename root composer and npm package identity to miniserver"
```

### Task 2: Rename application identity in shipped config

**Files:**
- Modify: `config/common.neon` — `contributteConsole.name`
- Modify: `config/defaults.neon` — `database.dbname`, `database.username`, `database.password`, `security.issuer`

**Interfaces:**
- Consumes: Phase 3's move of `var/config/{common,defaults}.neon` to `config/{common,defaults}.neon` (paths unchanged in content).
- Produces: the console banner and default database/token identity that `docs/README.md`, `docs/configuration.md` and the CI `php-tests` job assume.

- [ ] **Step 1: Rename the console application name**

Confirmed current line in `config/common.neon`: `    name: "FastyBird:IoTServer!"` under the `contributteConsole:` key.

```bash
perl -pi -e 's/name: "FastyBird:IoTServer!"/name: "FastyBird:MiniServer!"/' config/common.neon
```

- [ ] **Step 2: Verify**

Run: `grep -n 'name: "FastyBird' config/common.neon`
Expected: `    name: "FastyBird:MiniServer!"`

- [ ] **Step 3: Rename the database defaults and token issuer**

Confirmed current block in `config/defaults.neon`:
```
    database:
        version: 5.7
        host: 127.0.0.1
        port: 3306
        driver: pdo_mysql
        memory: false
        dbname: fastybird
        username: fastybird
        password: fastybird
```
and
```
    security:
        issuer: com.fastybird.iot
        signature: 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k='
```

```bash
perl -pi -e 's/dbname: fastybird$/dbname: miniserver/' config/defaults.neon
perl -pi -e 's/username: fastybird$/username: miniserver/' config/defaults.neon
perl -pi -e 's/password: fastybird$/password: miniserver/' config/defaults.neon
perl -pi -e "s/issuer: com\.fastybird\.iot/issuer: com.fastybird.miniserver/" config/defaults.neon
```

- [ ] **Step 4: Verify the database and issuer rename**

Run: `sed -n '/database:/,/redis:/p;/security:/,/api:/p' config/defaults.neon`
Expected:
```
    database:
        version: 5.7
        host: 127.0.0.1
        port: 3306
        driver: pdo_mysql
        memory: false
        dbname: miniserver
        username: miniserver
        password: miniserver

    redis:
...
    security:
        issuer: com.fastybird.miniserver
        signature: 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k='
```
(the signature literal is removed in Task 6, not here — this step only confirms the issuer and database values changed).

- [ ] **Step 5: Commit**

```bash
git add config/common.neon config/defaults.neon
git commit -m "chore(infra): rename console name, token issuer and database defaults to miniserver"
```

### Task 3: Normalise support and repository URLs in every extension manifest

**Files:**
- Modify: `src/FastyBird/*/*/composer.json` (34 files) — `support.issues`, `support.source`
- Modify: `src/FastyBird/Core/Tools/package.json`, `src/FastyBird/Core/Application/package.json`, `src/FastyBird/Module/Ui/package.json`, `src/FastyBird/Module/Triggers/package.json`, `src/FastyBird/Module/Accounts/package.json`, `src/FastyBird/Module/Devices/package.json`, `src/FastyBird/Library/WebUi/package.json`, `src/FastyBird/Library/Metadata/package.json`, `src/FastyBird/Connector/HomeKit/package.json` (9 files) — `repository.url`

**Interfaces:**
- Consumes: the 34 extension `composer.json` files (each carries `"support": {"email": ..., "issues": "https://github.com/FastyBird/fastybird/issues", "source": "https://github.com/FastyBird/<split-slug>"}`) and the 9 `package.json` files (each carries `"repository": {"type": "git", "url": "https://github.com/FastyBird/<split-slug-or-interface>.git"}`), confirmed by direct inspection — including `Core/Application/package.json`, which wrongly points at `FastyBird/interface.git` today.
- Produces: every manifest's support/repository field pointing at the one merged repository, so Task 4's README cleanup and Phase 7's eventual repository deletions leave no live manifest reference to a split repository.

- [ ] **Step 1: Rewrite composer.json support fields**

```bash
find src/FastyBird -mindepth 3 -maxdepth 3 -name composer.json -print0 \
  | xargs -0 perl -pi -e 's#"issues": "https://github\.com/FastyBird/fastybird/issues"#"issues": "https://github.com/FastyBird/miniserver/issues"#'
find src/FastyBird -mindepth 3 -maxdepth 3 -name composer.json -print0 \
  | xargs -0 perl -pi -e 's#"source": "https://github\.com/FastyBird/[^"]+"#"source": "https://github.com/FastyBird/miniserver"#'
```

- [ ] **Step 2: Verify no composer.json still references a split repository**

Run: `grep -rn 'github.com/FastyBird/fastybird/issues\|"source": "https://github.com/FastyBird/[a-z]' src/FastyBird --include=composer.json | wc -l`
Expected: `0`

Run: `grep -c 'https://github.com/FastyBird/miniserver' src/FastyBird/Connector/Shelly/composer.json src/FastyBird/Plugin/ApiKey/composer.json src/FastyBird/Core/Application/composer.json`
Expected: `2` for each file (one `issues`, one `source`).

- [ ] **Step 3: Rewrite the 9 package.json repository URLs**

```bash
for f in src/FastyBird/Core/Tools/package.json \
         src/FastyBird/Core/Application/package.json \
         src/FastyBird/Module/Ui/package.json \
         src/FastyBird/Module/Triggers/package.json \
         src/FastyBird/Module/Accounts/package.json \
         src/FastyBird/Module/Devices/package.json \
         src/FastyBird/Library/WebUi/package.json \
         src/FastyBird/Library/Metadata/package.json \
         src/FastyBird/Connector/HomeKit/package.json; do
  perl -pi -e 's#"url": "https://github\.com/FastyBird/[^"]+\.git"#"url": "https://github.com/FastyBird/miniserver.git"#' "$f"
done
```

- [ ] **Step 4: Verify**

Run: `grep -A2 '"repository"' src/FastyBird/Core/Application/package.json src/FastyBird/Module/Devices/package.json`
Expected: both show
```
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
```

Run: `grep -rl 'FastyBird/interface' src/FastyBird`
Expected: no output (the stale `Core/Application` reference is gone).

- [ ] **Step 5: Composer and npm manifests still validate**

Run: `docker compose exec -T application make composer-validate`
Expected: exits 0, every extension `composer.json` reported valid.

- [ ] **Step 6: Commit**

```bash
git add src/FastyBird/*/*/composer.json src/FastyBird/Core/Tools/package.json src/FastyBird/Core/Application/package.json src/FastyBird/Module/Ui/package.json src/FastyBird/Module/Triggers/package.json src/FastyBird/Module/Accounts/package.json src/FastyBird/Module/Devices/package.json src/FastyBird/Library/WebUi/package.json src/FastyBird/Library/Metadata/package.json src/FastyBird/Connector/HomeKit/package.json
git commit -m "chore(infra): point extension manifest support and repository URLs at the miniserver repository"
```

### Task 4: Strip split-repository badges and links from every extension README

**Files:**
- Modify: `src/FastyBird/*/*/README.md` (34 files), `src/FastyBird/Plugin/RabbitMq/readme.md` (1 file, lowercase name) — badge block removal, split-repo wiki-link block removal, footer repository line removal

**Interfaces:**
- Consumes: the 35 extension README files, each carrying a badge block (`flat.badgen.net` / `badgen.net` / `img.shields.io` / `stryker-mutator.io` URLs), an extension-specific "## Documentation" paragraph linking to `github.com/FastyBird/<split-slug>/wiki`, and a footer line `repository [https://github.com/fastybird/<split-slug>](...)`.
- Produces: READMEs with no live reference to any of the 25 split-target repositories; the generic footer boilerplate that references `github.com/FastyBird/fastybird` (Contributing, Feedback, Changelog paragraphs — the monorepo itself, not a split target) is untouched here because it is corrected by the identity rename, not removed.

- [ ] **Step 1: Write the cleanup script**

```bash
cat > /tmp/strip-readme-split-links.py <<'PYEOF'
import re
import sys
from pathlib import Path

paths = sorted(Path("src/FastyBird").glob("*/*/README.md")) + sorted(Path("src/FastyBird").glob("*/*/readme.md"))

badge_markers = ("badgen.net", "img.shields.io", "stryker-mutator.io")

footer_pattern = re.compile(
    r"Homepage \[https://www\.fastybird\.com\]\(https://www\.fastybird\.com\) and\n"
    r"repository \[https://github\.com/fastybird/[^\]]+\]\(https://github\.com/fastybird/[^\)]+\)\.",
    re.IGNORECASE,
)

doc_block_pattern = re.compile(r"## Documentation\n.*?(?=\n# |\n## |\Z)", re.S)


def strip_wiki_block(match: "re.Match[str]") -> str:
    block = match.group(0)
    if re.search(r"github\.com/fastybird/(?!fastybird\b)(?!\.github\b)[\w.-]+/wiki", block, re.IGNORECASE):
        return ""
    return block


changed = []

for path in paths:
    text = path.read_text()
    original = text

    lines = [line for line in text.split("\n") if not any(marker in line for marker in badge_markers)]
    text = "\n".join(lines)

    text = footer_pattern.sub("Homepage [https://www.fastybird.com](https://www.fastybird.com).", text)
    text = doc_block_pattern.sub(strip_wiki_block, text)

    text = re.sub(r"\n{3,}", "\n\n", text)

    if text != original:
        path.write_text(text)
        changed.append(str(path))

print(f"changed {len(changed)} files")
PYEOF
python3 /tmp/strip-readme-split-links.py
```

- [ ] **Step 2: Verify the script touched every extension README**

Expected from Step 1's own output: `changed 35 files`.

- [ ] **Step 3: Verify no badge or split-repository link remains**

Run: `grep -rl 'badgen.net\|img.shields.io\|stryker-mutator.io' src/FastyBird --include=README.md --include=readme.md | wc -l`
Expected: `0`

Run: `grep -rohiE "github\.com/fastybird/[A-Za-z0-9._-]+" src/FastyBird --include=README.md --include=readme.md | sort -u | grep -vi '\.github$'`
Expected: no output (every `fastybird/<split-slug>` footer link is gone, regardless of case; `fastybird/.github` badge-image references are untouched on purpose since that repository is not being deleted).

- [ ] **Step 4: Spot-check one README reads cleanly**

Run: `sed -n '1,20p' src/FastyBird/Connector/Shelly/README.md`
Expected: the `<p align="center">` header image, the `# FastyBird IoT Shelly connector` heading, then directly the `***` separator and the `## What is Shelly connector?` section — no badge lines in between.

- [ ] **Step 5: Commit**

```bash
git add src/FastyBird/*/*/README.md src/FastyBird/Plugin/RabbitMq/readme.md
git commit -m "docs(cross): remove split-repository badges and links from extension READMEs"
```

### Task 5: Rename the database identity in Docker Compose

**Files:**
- Modify: `docker/dev/docker-compose.yml` — `DATABASE_DBNAME` default
- Modify: `docker/prod/docker-compose.yml` — `DATABASE_DBNAME` default

**Interfaces:**
- Consumes: Phase 2's `docker/dev/docker-compose.yml` and `docker/prod/docker-compose.yml`, ported from the old miniserver repository's compose files, which default `DATABASE_USERNAME` and `DATABASE_PASSWORD` to `miniserver` already but still default `DATABASE_DBNAME` to `fb_miniserver`.
- Produces: a database default consistent with Task 2's `config/defaults.neon` (`dbname: miniserver`), so a container started with no `DATABASE_DBNAME` override creates and connects to the same database name the application itself defaults to.

- [ ] **Step 1: Confirm the current default before changing it**

Run: `grep -n 'DATABASE_DBNAME' docker/dev/docker-compose.yml docker/prod/docker-compose.yml`
Expected: every line reads `...${DATABASE_DBNAME:-fb_miniserver}` (this is the value ported from the old miniserver repository's compose files; if Phase 2 already used `miniserver` instead, skip Step 2 and record that in this task's commit message body).

- [ ] **Step 2: Rename the default**

```bash
perl -pi -e 's/\$\{DATABASE_DBNAME:-fb_miniserver\}/\$\{DATABASE_DBNAME:-miniserver\}/g' docker/dev/docker-compose.yml docker/prod/docker-compose.yml
```

- [ ] **Step 3: Verify**

Run: `grep -n 'DATABASE_DBNAME' docker/dev/docker-compose.yml docker/prod/docker-compose.yml`
Expected: every line reads `...${DATABASE_DBNAME:-miniserver}`.

- [ ] **Step 4: Compose files still parse**

Run: `docker compose -f docker/dev/docker-compose.yml config --quiet && docker compose -f docker/prod/docker-compose.yml config --quiet`
Expected: exits 0, no output.

- [ ] **Step 5: Commit**

```bash
git add docker/dev/docker-compose.yml docker/prod/docker-compose.yml
git commit -m "chore(infra): default the compose database name to miniserver"
```

### Task 6: Remove the committed security signature and generate it at container start

**Files:**
- Modify: `.env` (root) — remove the `SECURITY_SIGNATURE` line
- Modify: `config/defaults.neon` — blank the `security.signature` default
- Modify: `.gitignore` (root) — stop tracking `.env`
- Modify: `docker/prod/docker-entrypoint.sh` — generate and persist a signature on first start

**Interfaces:**
- Consumes: the literal signature `g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k=` committed in both `.env` and `config/defaults.neon` (same value in both files, confirmed by direct comparison), and Phase 2's `docker/prod/docker-entrypoint.sh`, which already contains the MariaDB-wait loop and the `migrations:migrate` call ported from the old miniserver repository's `.docker/prod/docker-entrypoint.sh`.
- Produces: no secret value reachable from git history going forward from this commit (D2 already means no history is imported from the old repository, so this does not need history rewriting); `docs/configuration.md` (Task 17) documents `FB_APP_PARAMETER__SECURITY_SIGNATURE` as the operator-supplied alternative.

- [ ] **Step 1: Remove the signature line from the tracked .env file**

Confirmed current content of `.env` is exactly one line: `SECURITY_SIGNATURE=g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k=`.

```bash
: > .env
```

- [ ] **Step 2: Stop tracking .env going forward**

```bash
printf '/.env\n' >> .gitignore
```

- [ ] **Step 3: Verify**

Run: `cat .env; echo '---'; tail -3 .gitignore`
Expected:
```
---
.idea
/node_modules
/vendor
/composer.lock
/yarn.lock
.DS_Store
/.env
```
(`.env` itself is now empty; the tracked-but-empty file stays as a placeholder so `docker-compose`'s automatic `.env` loading does not warn about a missing file).

- [ ] **Step 4: Blank the signature default in config/defaults.neon**

Confirmed current line (already present after Task 2's edits to the same file): `        signature: 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k='`.

```bash
perl -pi -e "s/signature: 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k='/signature:                                # set via FB_APP_PARAMETER__SECURITY_SIGNATURE or config\/local.neon, see docs\/configuration.md/" config/defaults.neon
```

- [ ] **Step 5: Verify**

Run: `grep -n 'signature' config/defaults.neon`
Expected: `        signature:                                # set via FB_APP_PARAMETER__SECURITY_SIGNATURE or config/local.neon, see docs/configuration.md`

- [ ] **Step 6: Rewrite the production entrypoint to generate a signature on first start**

Read the file Phase 2 produced first: `cat docker/prod/docker-entrypoint.sh`. It already contains a MariaDB-wait loop (`php bin/fb-console.php dbal:run-sql "select 1"`, 20 attempts, 1 second apart) and a `migrations:migrate --no-interaction --allow-no-migration` call before `exec`-ing supervisord, ported from the old miniserver repository's `.docker/prod/docker-entrypoint.sh`. Replace its full content with:

```sh
#!/bin/sh
set -e

APP_DIR=/app
CONFIG_DIR="${FB_CONFIG_DIR:-/data/config}"
LOGS_DIR="${FB_LOGS_DIR:-/data/logs}"
TEMP_DIR="${FB_TEMP_DIR:-/data/temp}"

mkdir -p "${CONFIG_DIR}" "${LOGS_DIR}" "${TEMP_DIR}"

LOCAL_NEON="${CONFIG_DIR}/local.neon"

if [ -z "${FB_APP_PARAMETER__SECURITY_SIGNATURE}" ] && { [ ! -f "${LOCAL_NEON}" ] || ! grep -q 'signature:' "${LOCAL_NEON}"; }; then
	SIGNATURE=$(php -r 'echo base64_encode(random_bytes(32));')

	if [ ! -f "${LOCAL_NEON}" ]; then
		printf 'parameters:\n' > "${LOCAL_NEON}"
	fi

	printf '    security:\n        signature: '"'"'%s'"'"'\n' "${SIGNATURE}" >> "${LOCAL_NEON}"

	(>&2 echo "Generated a new security signature into ${LOCAL_NEON}")
fi

attempt_left=20

until php "${APP_DIR}/bin/fb-console.php" dbal:run-sql "select 1" >/dev/null 2>&1;
do
	attempt_left=$((attempt_left-1))

	if [ "${attempt_left}" -eq "0" ]; then
		(>&2 echo "Database did not answer. Aborting migrations.")

		break
	else
		(>&2 echo "Waiting for database to be ready...")
	fi

	sleep 1
done

if [ "${attempt_left}" != "0" ]; then
	php "${APP_DIR}/bin/fb-console.php" migrations:migrate --no-interaction --allow-no-migration
fi

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
```

- [ ] **Step 7: Verify the entrypoint is syntactically valid POSIX shell**

Run: `sh -n docker/prod/docker-entrypoint.sh`
Expected: exits 0, no output.

- [ ] **Step 8: Build the production image and confirm signature generation**

Run: `docker build -f docker/prod/Dockerfile -t miniserver-prod-test . && docker run --rm -e FB_CONFIG_DIR=/data/config -v miniserver-test-data:/data --entrypoint /bin/sh miniserver-prod-test -c 'mkdir -p /data/config && /usr/local/bin/docker-entrypoint & sleep 3; cat /data/config/local.neon; kill %1 2>/dev/null || true'`
Expected: `/data/config/local.neon` now contains a `parameters:` / `security:` / `signature: '<base64 value>'` block with a freshly generated value (different from the removed literal).

- [ ] **Step 9: Commit**

```bash
git add .env .gitignore config/defaults.neon docker/prod/docker-entrypoint.sh
git commit -m "fix(infra): stop committing the security signature and generate it on first container start"
```

### Task 7: Add ci-tests.yaml and delete the reusable-workflow and Phase 2 docker-build jobs

**Files:**
- Create: `.github/workflows/ci-tests.yaml`
- Delete: `.github/workflows/lint.yaml`
- Delete: `.github/workflows/qa.yaml`
- Delete: `.github/workflows/static-analysis.yaml`
- Delete: `.github/workflows/tests.yaml`
- Delete: `.github/workflows/docker-build.yaml`

**Interfaces:**
- Consumes: `make lint`, `make cs`, `make phpstan`, `make tests` (all confirmed present in the root `Makefile`); `yarn lint:js`, `yarn types`, `yarn build` (confirmed present in root `package.json` scripts, unchanged from Phase 1); `docker build -f docker/prod/Dockerfile .` (Phase 2 deliverable command, confirmed in the spec's Phase 2 acceptance text); Phase 3's `COMPOSER_MIRROR_PATH_REPOS=1` requirement for the path-repository model.
- Produces: the single CI entry point every later pull request in this repository is measured against; Task 8–11 add sibling workflow files next to this one.

- [ ] **Step 1: Remove the four reusable-workflow files and Phase 2's standalone docker-build workflow**

```bash
git rm .github/workflows/lint.yaml .github/workflows/qa.yaml .github/workflows/static-analysis.yaml .github/workflows/tests.yaml .github/workflows/docker-build.yaml
```

- [ ] **Step 2: Create ci-tests.yaml**

```yaml
name: "CI Tests"

on:
  pull_request:
  push:
    branches:
      - "main"
    tags:
      - "v*"
  schedule:
    - cron: "0 8 * * 1" # At 08:00 on Monday

env:
  COMPOSER_MIRROR_PATH_REPOS: 1

jobs:
  php-lint:
    name: "PHP Lint"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup PHP"
        uses: "shivammathur/setup-php@v2"
        with:
          php-version: "8.2"
          extensions: "bcmath, curl, gd, gmp, iconv, intl, mbstring, openssl, pcntl, sockets, sodium, sqlite3, zip"
          tools: "composer:v2"

      - name: "Composer install"
        run: "composer install --no-interaction --no-progress --prefer-dist"

      - name: "make lint"
        run: "make lint"

  php-cs:
    name: "PHP Coding Standard"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup PHP"
        uses: "shivammathur/setup-php@v2"
        with:
          php-version: "8.2"
          extensions: "bcmath, curl, gd, gmp, iconv, intl, mbstring, openssl, pcntl, sockets, sodium, sqlite3, zip"
          tools: "composer:v2"

      - name: "Composer install"
        run: "composer install --no-interaction --no-progress --prefer-dist"

      - name: "make cs"
        run: "make cs"

  php-phpstan:
    name: "PHP Static Analysis"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup PHP"
        uses: "shivammathur/setup-php@v2"
        with:
          php-version: "8.2"
          extensions: "bcmath, curl, gd, gmp, iconv, intl, mbstring, openssl, pcntl, sockets, sodium, sqlite3, zip"
          tools: "composer:v2"

      - name: "Composer install"
        run: "composer install --no-interaction --no-progress --prefer-dist"

      - name: "make phpstan"
        run: "make phpstan"

  php-tests:
    name: "PHP Tests"
    runs-on: "ubuntu-latest"

    services:
      mariadb:
        image: "mariadb:10.11"
        env:
          MARIADB_ROOT_PASSWORD: "root"
          MARIADB_DATABASE: "miniserver_test"
          MARIADB_USER: "miniserver"
          MARIADB_PASSWORD: "miniserver"
        ports:
          - "3306:3306"
        options: >-
          --health-cmd="healthcheck.sh --connect --innodb_initialized"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5
      redis:
        image: "redis:7"
        ports:
          - "6379:6379"
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    env:
      FB_APP_PARAMETER__DATABASE_HOST: "127.0.0.1"
      FB_APP_PARAMETER__DATABASE_PORT: "3306"
      FB_APP_PARAMETER__DATABASE_USERNAME: "miniserver"
      FB_APP_PARAMETER__DATABASE_PASSWORD: "miniserver"
      FB_APP_PARAMETER__DATABASE_DBNAME: "miniserver_test"
      FB_APP_PARAMETER__REDIS_HOST: "127.0.0.1"
      FB_APP_PARAMETER__REDIS_PORT: "6379"
      FB_APP_PARAMETER__SECURITY_SIGNATURE: "ci-test-signature-not-used-in-production"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup PHP"
        uses: "shivammathur/setup-php@v2"
        with:
          php-version: "8.2"
          extensions: "bcmath, curl, gd, gmp, iconv, intl, mbstring, openssl, pcntl, sockets, sodium, sqlite3, zip"
          tools: "composer:v2"

      - name: "Composer install"
        run: "composer install --no-interaction --no-progress --prefer-dist"

      - name: "make tests"
        run: "make tests"

  js-lint:
    name: "JS Lint"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup Node"
        uses: "actions/setup-node@v4"
        with:
          node-version: "20"
          cache: "yarn"

      - name: "Yarn install"
        run: "yarn install --frozen-lockfile"

      - name: "yarn lint:js"
        run: "yarn lint:js"

  js-types:
    name: "JS Types"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup Node"
        uses: "actions/setup-node@v4"
        with:
          node-version: "20"
          cache: "yarn"

      - name: "Yarn install"
        run: "yarn install --frozen-lockfile"

      - name: "yarn types"
        run: "yarn types"

  js-build:
    name: "JS Build"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup Node"
        uses: "actions/setup-node@v4"
        with:
          node-version: "20"
          cache: "yarn"

      - name: "Yarn install"
        run: "yarn install --frozen-lockfile"

      - name: "yarn build"
        run: "yarn build"

  docker-build:
    name: "Docker Build"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Build production image"
        run: "docker build -f docker/prod/Dockerfile -t ghcr.io/fastybird/miniserver:ci ."

      - name: "Start MariaDB"
        run: |
          docker run -d --name miniserver-ci-database \
            -e MARIADB_ROOT_PASSWORD=root \
            -e MARIADB_DATABASE=miniserver \
            -e MARIADB_USER=miniserver \
            -e MARIADB_PASSWORD=miniserver \
            -p 3306:3306 \
            mariadb:10.11

      - name: "Smoke test the image"
        run: |
          docker run -d --name miniserver-ci-app \
            --link miniserver-ci-database:database \
            -e FB_APP_PARAMETER__DATABASE_HOST=database \
            -e FB_APP_PARAMETER__DATABASE_USERNAME=miniserver \
            -e FB_APP_PARAMETER__DATABASE_PASSWORD=miniserver \
            -e FB_APP_PARAMETER__DATABASE_DBNAME=miniserver \
            -e FB_APP_PARAMETER__SECURITY_SIGNATURE=ci-smoke-test-signature \
            -p 8080:80 \
            ghcr.io/fastybird/miniserver:ci
          sleep 10
          curl --fail --retry 5 --retry-delay 3 http://127.0.0.1:8080/
          docker exec miniserver-ci-app php bin/fb-console.php list
```

- [ ] **Step 3: Verify the workflow YAML parses**

Run: `python3 -c "import yaml, sys; yaml.safe_load(open('.github/workflows/ci-tests.yaml'))" && echo OK`
Expected: `OK`

- [ ] **Step 4: Verify the deleted workflows are gone and the referenced make/yarn commands exist**

Run: `ls .github/workflows/ && grep -E '^(lint|cs|phpstan|tests):' Makefile && grep -E '"(lint:js|types|build)":' package.json`
Expected: `lint.yaml`, `qa.yaml`, `static-analysis.yaml`, `tests.yaml` and `docker-build.yaml` are absent from the listing; the `Makefile` grep prints all four targets; the `package.json` grep prints all three scripts.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/ci-tests.yaml
git commit -m "ci(ci): replace the reusable-workflow CI with a self-contained ci-tests.yaml"
```

### Task 8: Add the GHCR release workflow

**Files:**
- Create: `.github/workflows/release.yml`

**Interfaces:**
- Consumes: `docker/prod/Dockerfile` (Phase 2 deliverable), the image name `ghcr.io/fastybird/miniserver` (4.9 identity table).
- Produces: the "release workflow publishes an image for a pre-release tag" deliverable this phase's acceptance criteria requires.

- [ ] **Step 1: Create release.yml**

```yaml
name: "Release"

on:
  release:
    types: [ "published" ]

permissions:
  contents: read
  packages: write

jobs:
  publish-image:
    name: "Build and push production image"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Setup Docker Buildx"
        uses: "docker/setup-buildx-action@v3"

      - name: "Log in to GHCR"
        uses: "docker/login-action@v3"
        with:
          registry: "ghcr.io"
          username: "${{ github.actor }}"
          password: "${{ secrets.GITHUB_TOKEN }}"

      - name: "Build and push"
        uses: "docker/build-push-action@v6"
        with:
          context: "."
          file: "docker/prod/Dockerfile"
          push: true
          tags: |
            ghcr.io/fastybird/miniserver:${{ github.event.release.tag_name }}
            ghcr.io/fastybird/miniserver:latest
```

- [ ] **Step 2: Verify the workflow YAML parses**

Run: `python3 -c "import yaml, sys; yaml.safe_load(open('.github/workflows/release.yml'))" && echo OK`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/release.yml
git commit -m "ci(ci): add the GHCR release workflow"
```

### Task 9: Add PR-title linting and release-drafter

**Files:**
- Create: `.github/workflows/lint-pr.yml`
- Create: `.github/workflows/release-drafter.yml`
- Create: `.github/release-drafter.yml`

**Interfaces:**
- Consumes: the scope list `core, module, connector, plugin, bridge, addon, automator, library, ui, infra, ci, deps, docs, cross` and type list `feat, fix, docs, style, refactor, test, chore, perf, ci, build, revert` (4.8, SmartPanel's type list confirmed by direct inspection of `/Users/akadlec/Development/FastyBird/smart-panel/commitlint.config.js`).
- Produces: PR-title enforcement that Task 11's `commitlint.config.js` must stay in sync with (documented in Task 12's `CONTRIBUTING.md`).

- [ ] **Step 1: Create lint-pr.yml**

```yaml
name: "Lint PR"

on:
  pull_request_target:
    types: [ "opened", "reopened", "edited", "synchronize" ]

jobs:
  validate:
    name: "Validate PR title"
    runs-on: "ubuntu-latest"
    permissions:
      pull-requests: read

    steps:
      - uses: "amannn/action-semantic-pull-request@48f256284bd46cdaab1048c3721360e808335d50" # v6.1.1
        env:
          GITHUB_TOKEN: "${{ secrets.GITHUB_TOKEN }}"
        with:
          types: |
            feat
            fix
            docs
            style
            refactor
            test
            chore
            perf
            ci
            build
            revert
          scopes: |
            core
            module
            connector
            plugin
            bridge
            addon
            automator
            library
            ui
            infra
            ci
            deps
            docs
            cross
          requireScope: true

      - name: "Validate PR title subject"
        env:
          PR_TITLE: "${{ github.event.pull_request.title }}"
        run: |
          SUBJECT=$(printf '%s' "$PR_TITLE" | sed -E 's/^[a-z]+\([a-z]+\): //')
          FIRST_CHAR=$(printf '%s' "$SUBJECT" | cut -c1)
          if [ "$FIRST_CHAR" != "$(printf '%s' "$FIRST_CHAR" | tr '[:upper:]' '[:lower:]')" ]; then
            echo "PR title subject must not start with an uppercase letter: $SUBJECT"
            exit 1
          fi
          if printf '%s' "$SUBJECT" | grep -qE '\.$'; then
            echo "PR title subject must not end with a period: $SUBJECT"
            exit 1
          fi
```

- [ ] **Step 2: Create release-drafter.yml**

```yaml
name: "Release Drafter"

on:
  push:
    branches:
      - "main"
  workflow_dispatch:

permissions:
  contents: write

jobs:
  draft-release:
    name: "Draft new release"
    runs-on: "ubuntu-latest"

    steps:
      - name: "Draft new release"
        uses: "release-drafter/release-drafter@v7"
        env:
          GITHUB_TOKEN: "${{ secrets.GITHUB_TOKEN }}"
```

- [ ] **Step 3: Create the release-drafter config**

```yaml
name-template: v$RESOLVED_VERSION
tag-template: v$RESOLVED_VERSION

categories:
  - title: Breaking Changes
    labels:
      - breaking change
  - title: Featured Changes
    labels:
      - feature
      - enhancement
  - title: Bug Fixes
    labels:
      - fix
      - bugfix
      - bug
  - title: Documentation Updates
    labels:
      - documentation
  - title: Maintenance
    labels:
      - chore
      - dependencies

autolabeler:
  - label: fix
    branch:
      - '/fix\/.+/'
    title:
      - /fix/i
  - label: feature
    branch:
      - '/feature\/.+/'
  - label: documentation
    branch:
      - '/docs?\//'
    title:
      - /docs?/i
  - label: chore
    branch:
      - '/chore\//'

change-template: '- $TITLE @$AUTHOR [#$NUMBER]'

template: |
  ## What's Changed

  $CHANGES
```

- [ ] **Step 4: Verify all three files parse**

Run: `python3 -c "import yaml; [yaml.safe_load(open(f)) for f in ['.github/workflows/lint-pr.yml', '.github/workflows/release-drafter.yml', '.github/release-drafter.yml']]" && echo OK`
Expected: `OK`

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/lint-pr.yml .github/workflows/release-drafter.yml .github/release-drafter.yml
git commit -m "ci(ci): add PR-title linting and release-drafter"
```

### Task 10: Add dependabot

**Files:**
- Create: `.github/dependabot.yml`

**Interfaces:**
- Consumes: the three package ecosystems present in the repository at this phase — `composer` (root `composer.json`), `npm` (root `package.json`), `github-actions` (`.github/workflows/*`).
- Produces: weekly dependency-update PRs from Phase 4 onward; Phase 6 is where those bumps are actually merged.

- [ ] **Step 1: Create dependabot.yml**

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    commit-message:
      prefix: "chore"
      include: "scope"

  - package-ecosystem: "npm"
    directory: "/"
    schedule:
      interval: "weekly"
    commit-message:
      prefix: "chore"
      include: "scope"

  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    commit-message:
      prefix: "chore"
      include: "scope"
```

- [ ] **Step 2: Verify**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/dependabot.yml'))" && echo OK`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
git add .github/dependabot.yml
git commit -m "ci(ci): add weekly dependabot updates for composer, npm and github-actions"
```

### Task 11: Add commitlint and the husky commit-msg hook

**Files:**
- Create: `commitlint.config.js`
- Create: `.husky/commit-msg`
- Modify: `package.json` (root) — `devDependencies`, `scripts.prepare`

**Interfaces:**
- Consumes: the same type and scope lists Task 9's `lint-pr.yml` enforces on PR titles (must stay in sync per Task 12's `CONTRIBUTING.md`).
- Produces: local commit-message enforcement from this pull request onward.

- [ ] **Step 1: Create commitlint.config.js**

```javascript
// Enforces conventional commits on every local commit (via the husky commit-msg hook).
// Shares the same type and scope vocabulary as .github/workflows/lint-pr.yml and
// CONTRIBUTING.md -- keep all three in sync when a scope is added or removed.
// The built-in subject-case rule is disabled in favour of a custom rule that only
// checks the first character, mirroring SmartPanel's commitlint.config.js and
// .github/workflows/lint-pr.yml's first-character-only check -- commit subjects
// routinely contain embedded uppercase (filenames, acronyms, proper nouns).
module.exports = {
	extends: ['@commitlint/config-conventional'],
	plugins: [
		{
			rules: {
				'subject-first-char-lowercase': (parsed) => {
					const subject = parsed.subject || '';

					if (subject.length === 0) {
						return [true];
					}

					const firstChar = subject[0];

					return [
						firstChar === firstChar.toLowerCase(),
						'subject must start with a lowercase character',
					];
				},
			},
		},
	],
	rules: {
		'type-enum': [
			2,
			'always',
			['feat', 'fix', 'docs', 'style', 'refactor', 'test', 'chore', 'perf', 'ci', 'build', 'revert'],
		],
		'scope-enum': [
			2,
			'always',
			[
				'core',
				'module',
				'connector',
				'plugin',
				'bridge',
				'addon',
				'automator',
				'library',
				'ui',
				'infra',
				'ci',
				'deps',
				'docs',
				'cross',
			],
		],
		'scope-empty': [2, 'never'],
		'subject-case': [0],
		'subject-first-char-lowercase': [2, 'always'],
		'subject-empty': [2, 'never'],
		'subject-full-stop': [2, 'never', '.'],
	},
};
```

- [ ] **Step 2: Add husky and commitlint as devDependencies and a prepare script**

Confirmed current `package.json` has a `"devDependencies": { "lerna": "^8.1" }` block and no `"scripts"."prepare"` entry.

```bash
perl -pi -e 's/"devDependencies": \{\n    "lerna": "\^8\.1"\n  \}/"devDependencies": {\n    "\@commitlint\/cli": "^19.5",\n    "\@commitlint\/config-conventional": "^19.5",\n    "husky": "^9.1",\n    "lerna": "^8.1"\n  }/' package.json 2>/dev/null || true
```

Since multi-line `perl -pi` across a JSON block is fragile, apply the edit directly instead: open `package.json` and change
```
  "devDependencies": {
    "lerna": "^8.1"
  },
```
to
```
  "devDependencies": {
    "@commitlint/cli": "^19.5",
    "@commitlint/config-conventional": "^19.5",
    "husky": "^9.1",
    "lerna": "^8.1"
  },
```
and add `"prepare": "husky"` to the `"scripts"` block, immediately after the existing `"test"` entry:
```
    "test": "lerna run test --stream --ignore '@fastybird/web-ui'",
    "prepare": "husky"
```

- [ ] **Step 3: Verify package.json is still valid JSON and contains the new entries**

Run: `python3 -c "import json; d = json.load(open('package.json')); assert 'husky' in d['devDependencies']; assert d['scripts']['prepare'] == 'husky'; print('OK')"`
Expected: `OK`

- [ ] **Step 4: Install and let husky create its hook directory**

Run: `docker compose exec -T ui-server yarn install --frozen-lockfile=false && docker compose exec -T ui-server yarn prepare`
Expected: exits 0; a `.husky/_` directory now exists.

- [ ] **Step 5: Create the commit-msg hook**

```bash
mkdir -p .husky
cat > .husky/commit-msg <<'EOF'
yarn commitlint --edit "$1"
EOF
chmod +x .husky/commit-msg
```

- [ ] **Step 6: Verify the hook rejects a bad message and accepts a good one**

Run: `echo 'bad commit message' | docker compose exec -T ui-server yarn commitlint`
Expected: non-zero exit, reporting `subject may not be empty` and/or `type may not be empty` style errors (the scope-enum and type-enum rules reject it).

Run: `echo 'docs(cross): add phase 4 conventions' | docker compose exec -T ui-server yarn commitlint`
Expected: exits 0, no output.

Run: `echo 'docs(docs): add CLAUDE.md' | docker compose exec -T ui-server yarn commitlint`
Expected: exits 0, no output (only the first character of the subject is checked, so the embedded uppercase in `CLAUDE.md` is allowed, matching `lint-pr.yml`'s first-character-only check).

- [ ] **Step 7: Commit**

```bash
git add commitlint.config.js .husky/commit-msg package.json
git commit -m "ci(ci): enforce conventional commits locally with commitlint and husky"
```

### Task 12: Write CONTRIBUTING.md

**Files:**
- Create: `CONTRIBUTING.md`

**Interfaces:**
- Consumes: the type/scope lists from Tasks 9 and 11.
- Produces: the document Task 9's PR-title workflow and Task 11's commitlint config both point back to for the human-readable scope table.

- [ ] **Step 1: Create CONTRIBUTING.md**

```markdown
# Contributing to FastyBird MiniServer

Thanks for contributing. This document covers the conventions that are enforced automatically -- commit messages and PR titles.

For development setup, see [README.md](./README.md). For architecture, configuration and deployment, see [docs/](./docs/).

## Commit messages and PR titles

Conventional commits, one logical change per commit:

```
<type>(<scope>): <subject>
```

The scope is required on both local commit messages and PR titles. `commitlint` enforces this on every commit via the husky `commit-msg` hook (see [`commitlint.config.js`](./commitlint.config.js)), and [`lint-pr.yml`](./.github/workflows/lint-pr.yml) enforces it again on the PR title.

### Types

| Type | Use for |
|---|---|
| `feat` | A new feature |
| `fix` | A bug fix |
| `docs` | Documentation only |
| `style` | Formatting, whitespace -- no behaviour change |
| `refactor` | Restructuring that neither fixes a bug nor adds a feature |
| `test` | Adding or correcting tests |
| `chore` | Maintenance, tooling, dependency bumps |
| `perf` | A performance improvement |
| `ci` | CI configuration and workflows |
| `build` | Build system, packaging, toolchain |
| `revert` | Reverting a previous commit |

### Scopes

The scope is the surface you changed, mirroring the extension type directories under `src/FastyBird/`.

| Scope | Covers |
|---|---|
| `core` | `src/FastyBird/Core/**` -- Application, Exchange, Tools |
| `module` | `src/FastyBird/Module/**` -- Accounts, Devices, Triggers, Ui |
| `connector` | `src/FastyBird/Connector/**` -- the ten device connectors |
| `plugin` | `src/FastyBird/Plugin/**` -- ApiKey, CouchDb, RabbitMq, RedisDb, RedisDbCache, WebServer, WsServer |
| `bridge` | `src/FastyBird/Bridge/**` -- the six inter-extension bridges |
| `addon` | `src/FastyBird/Addon/**` -- VirtualThermostat |
| `automator` | `src/FastyBird/Automator/**` -- DateTime, DevicesModule |
| `library` | `src/FastyBird/Library/**` -- Metadata, WebUi |
| `ui` | Root frontend build tooling shared across extensions: `index.html`, `vite.config.ts`, `uno.config.ts`, `eslint.config.mjs`, `prettier.config.mjs`, `stylelint.config.mjs`, `config/extensions.ts` |
| `infra` | `docker/**`, `docker-compose.yml`, `Makefile`, `bin/**`, `config/**` (shipped wiring), root `composer.json`/`tools/**` |
| `ci` | `.github/**` |
| `deps` | Dependency version bumps |
| `docs` | `docs/**` and root `*.md` |
| `cross` | Genuinely cross-cutting changes that do not fit a single row above |

Adding a scope means editing three files together: [`commitlint.config.js`](./commitlint.config.js), [`.github/workflows/lint-pr.yml`](./.github/workflows/lint-pr.yml), and this table.

### Subject

- Lowercase first character, no trailing period.
- Imperative mood ("add", not "added" or "adds").

## Pull requests

- One pull request per logical change. Per the merge design (D11), a pull request never mixes a structural change (a move, rename, deletion or manifest restructure) with a dependency version change.
- CI (`ci-tests.yaml`) must be green before the next pull request starts.
```

- [ ] **Step 2: Verify**

Run: `test -f CONTRIBUTING.md && grep -c '^| \`' CONTRIBUTING.md`
Expected: file exists; the scope table grep prints `14` (one row per scope, matching the closed list in `commitlint.config.js`).

- [ ] **Step 3: Commit**

```bash
git add CONTRIBUTING.md
git commit -m "docs(docs): add CONTRIBUTING.md"
```

### Task 13: Write CLAUDE.md

**Files:**
- Create: `CLAUDE.md`

**Interfaces:**
- Consumes: the verified command inventory (`bin/fb-console`, `make` targets, `yarn` scripts) and the extension inventory from the spec's Appendix A.
- Produces: the file `claude-md-management` skills and future AI-agent sessions in this repository read first.

- [ ] **Step 1: Create CLAUDE.md**

```markdown
# FastyBird MiniServer -- AI Agent Instructions

MiniServer is a single PHP (Nette) + Vue application repository, merged from the former `fastybird` framework monorepo and `miniserver` deployment wrapper. Every extension keeps its own backend, frontend, tests, docs and manifests under `src/FastyBird/<Type>/<Name>/` -- there is no `apps/` reshape.

## Requirements

- **PHP**: 8.2
- **Node**: 20
- **Package manager**: yarn 1 (pnpm arrives in Phase 6 of the merge)

## Layout

- `src/FastyBird/<Type>/<Name>/` -- 35 extensions across 8 types: Addon (1), Automator (2), Bridge (6), Connector (10), Core (3), Library (2), Module (4), Plugin (7). See `docs/architecture.md` for the full inventory and dependency layering.
- `config/` -- shipped wiring (`common.neon`, `defaults.neon`, `extensions.ts`, `supervisor/*.conf`). `config/local.neon` is git-ignored and is where local overrides and secrets belong.
- `public/` -- `index.php` is the single entry point for both the JSON:API backend and the Vue SPA shell.
- `bin/` -- `fb-console`/`fb-console.php` (Symfony-style console), `fb-supervisor`/`fb-supervisor.php` (supervisor event listener).
- `docker/dev/`, `docker/prod/` -- development and production Docker Compose and Dockerfiles.
- `build/debian/` -- unsupported Debian packaging, kept but not validated.
- `docs/` -- `README.md` index, `architecture.md`, `configuration.md`, `deployment.md`.

## Commands

```bash
# PHP
make cs                # PHP_CodeSniffer
make csf                # PHP_CodeSniffer, auto-fix
make lint               # php-parallel-lint
make phpstan            # PHPStan, level max
make tests              # PHPUnit via paratest
make composer-validate  # composer validate --strict, root and every extension

# JS
yarn dev                # Vite dev server with hot reload
yarn build              # build every UI package then the application shell
yarn types               # vue-tsc --noEmit across every UI package
yarn lint:js             # ESLint
yarn lint:styles         # stylelint
yarn pretty:check        # Prettier check

# Docker
make up                 # docker-compose up -d
make down               # docker-compose down
make bash                # shell into the application container as www-data
```

## Console commands (`bin/fb-console <command>`)

Install commands exist per module/connector/addon, not as a single `fb:initialize` (that command does not exist, despite what the old miniserver README claimed): `fb:devices-module:install`, `fb:accounts-module:install`, `fb:triggers-module:install`, `fb:ui-module:install`, `fb:<connector>:install` for each of the ten connectors, `fb:virtual-thermostat-addon:install`, `fb:api-key:create`. Runtime commands: `fb:web-server:start`, `fb:ws-server:start`, `fb:devices-module:exchange`, `fb:devices-module:connector <identifier>`, `fb:<connector>:execute`, `fb:<connector>:discover` (NsPanel, Shelly, Sonoff, Tuya, Viera, Zigbee2Mqtt only), `fb:<bridge>:build` (the three HomeKit bridges).

## Conventions

Conventional commits (`<type>(<scope>): <subject>`), scope required, enforced by commitlint locally and by `lint-pr.yml` on PR titles. See [CONTRIBUTING.md](./CONTRIBUTING.md) for the type and scope tables.

## Architecture reference

Read [docs/architecture.md](./docs/architecture.md) before changing request routing, the config load order, or how extensions register their DI extensions. Read [docs/configuration.md](./docs/configuration.md) before wiring an extension that is present in the tree but not registered by default (RedisDb and its two bridges, RedisDbCache, CouchDb, RabbitMq, the two automators, ApiKey). Read [docs/deployment.md](./docs/deployment.md) before changing anything under `docker/` or `config/supervisor/`.
```

- [ ] **Step 2: Verify**

Run: `test -f CLAUDE.md && grep -c 'fb:initialize' CLAUDE.md`
Expected: file exists; grep prints `1` (the one mention explicitly says the command does not exist, matching the spec's confirmed fact that `fb:initialize` is dead).

- [ ] **Step 3: Commit**

```bash
git add CLAUDE.md
git commit -m "docs(docs): add CLAUDE.md"
```

### Task 14: Write AGENTS.md

**Files:**
- Create: `AGENTS.md`

**Interfaces:**
- Consumes: the same verified facts as Task 13.
- Produces: the generic (non-Claude-specific) agent instructions file some tools read instead of `CLAUDE.md`.

- [ ] **Step 1: Create AGENTS.md**

```markdown
# AGENTS.md

This file mirrors [CLAUDE.md](./CLAUDE.md) for agent tooling that reads `AGENTS.md` instead. Keep both in sync.

## Requirements

PHP 8.2, Node 20, yarn 1.

## Commands

```bash
make lint && make cs && make phpstan && make tests   # PHP quality gate
yarn lint:js && yarn types && yarn build              # JS quality gate
```

## Layout

`src/FastyBird/<Type>/<Name>/` holds 35 extensions, each with its own `src/`, `tests/`, optional `assets/`, `docs/`, `README.md`, `composer.json` and (for the 9 with a frontend) `package.json`. `config/` is the shipped wiring; `config/local.neon` is git-ignored and holds local overrides and secrets. `docs/architecture.md`, `docs/configuration.md` and `docs/deployment.md` are the authoritative references for, respectively, how the application boots and routes requests, how to enable an extension that ships in the tree but is not wired by default, and how the Docker images and supervisor processes are structured.

## Conventions

Conventional commits, required scope, enforced by commitlint (`commitlint.config.js`) and `lint-pr.yml`. See [CONTRIBUTING.md](./CONTRIBUTING.md).

## Do not

- Do not rename a PHP namespace or a `composer.json` package `name` -- the merge design keeps `FastyBird\<Type>\<Name>` and every package name exactly as it was before the merge (out of scope until a later, separate decision).
- Do not reintroduce a committed secret into `.env` or `config/defaults.neon` -- the security signature is generated at container start (see `docker/prod/docker-entrypoint.sh`) or supplied via `FB_APP_PARAMETER__SECURITY_SIGNATURE` / `config/local.neon`.
```

- [ ] **Step 2: Verify**

Run: `test -f AGENTS.md && echo OK`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
git add AGENTS.md
git commit -m "docs(docs): add AGENTS.md"
```

### Task 15: Write the root README.md and docs/README.md index; remove docs/CNAME and docs/index.md

**Files:**
- Modify: `README.md` (root, currently empty)
- Create: `docs/README.md`
- Delete: `docs/CNAME`
- Delete: `docs/index.md`

**Interfaces:**
- Consumes: `docs/assets/fastybird_miniserver_readme.png` (Phase 2 asset, ported from the old miniserver repository's `docs/assets/fastybird_miniserver_readme.png`), the real console command inventory, and the image name `ghcr.io/fastybird/miniserver`.
- Produces: the repository's front door; `docs/architecture.md`, `docs/configuration.md` and `docs/deployment.md` (Tasks 16-18) are linked from `docs/README.md`.

- [ ] **Step 1: Confirm the Phase 2 asset landed where expected**

Run: `ls docs/assets/ 2>/dev/null || echo "missing"`
Expected: `fastybird_miniserver_readme.png` is listed. If it is missing, port it now with `git mv` from wherever Phase 2 placed it before continuing -- do not invent a new image.

- [ ] **Step 2: Write the root README.md**

```markdown
![FastyBird MiniServer](docs/assets/fastybird_miniserver_readme.png)

<h1 align="center">FastyBird MiniServer</h1>

<p align="center">A self-hosted IoT server: devices, connectors, automations and a JSON:API + Vue admin interface, in one PHP application.</p>

## What is FastyBird MiniServer?

MiniServer is a standalone application built on the [FastyBird](https://www.fastybird.com) IoT extension set, developed on top of the [Nette](https://nette.org) and [Symfony](https://symfony.com) frameworks. It integrates third-party device ecosystems (Shelly, Tuya, Sonoff, Viera, NsPanel, Zigbee2Mqtt, Modbus, generic MQTT, virtual devices) and Apple HomeKit, behind a JSON:API backend and a Vue 3 admin UI served from the same entry point.

## Requirements

PHP 8.2, Node 20, yarn 1, MariaDB. Redis, CouchDB and RabbitMQ are optional -- see [docs/configuration.md](docs/configuration.md).

## Getting started

### With Docker (recommended)

```sh
docker compose -f docker/prod/docker-compose.yml up -d
```

This builds `docker/prod/Dockerfile`, starts the application and a MariaDB database, and runs pending migrations on first start. See [docs/deployment.md](docs/deployment.md) for the full process layout (nginx, php-fpm, the WebSocket server and the devices-module exchange worker under supervisord) and for the pre-built image at `ghcr.io/fastybird/miniserver`.

### Traditional installation

```sh
composer install --no-dev --prefer-dist --classmap-authoritative
yarn install --frozen-lockfile && yarn build
```

Then create the schema and any module-specific data:

```sh
bin/fb-console orm:schema-tool:create
bin/fb-console fb:devices-module:install
bin/fb-console fb:accounts-module:install
```

Point a web server at `public/`, or run the built-in ReactPHP server for local use:

```sh
bin/fb-console fb:web-server:start
```

## Documentation

- [docs/architecture.md](docs/architecture.md) -- extension inventory, request routing, config load order
- [docs/configuration.md](docs/configuration.md) -- enabling the extensions that ship in the tree but are not wired by default
- [docs/deployment.md](docs/deployment.md) -- Docker images, supervisor processes, production defaults

## Feedback

Use the [issue tracker](https://github.com/FastyBird/miniserver/issues) for bugs, or [mail](mailto:code@fastybird.com) us for ideas that can improve the project.

## Changelog

For release info check the [release page](https://github.com/FastyBird/miniserver/releases).

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img alt="akadlec" width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4" />
				</a>
				<br>
				<a href="https://github.com/akadlec">Adam Kadlec</a>
			</td>
		</tr>
	</tbody>
</table>

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and repository [https://github.com/FastyBird/miniserver](https://github.com/FastyBird/miniserver).
```

- [ ] **Step 3: Write docs/README.md as the docs index**

```markdown
# FastyBird MiniServer documentation

- [architecture.md](./architecture.md) -- extension inventory, request routing, the DI config load order and where each extension's own `docs/` fits in
- [configuration.md](./configuration.md) -- `local.neon` snippets for every extension that ships in the tree but is not registered by default
- [deployment.md](./deployment.md) -- Docker images, the supervisor program layout, and production environment defaults

Each extension under `src/FastyBird/<Type>/<Name>/` keeps its own `README.md` and `docs/` for extension-specific detail (device protocols, entity schemas, and so on). This directory only holds the application-level documentation that spans extensions.
```

- [ ] **Step 4: Delete docs/CNAME and docs/index.md**

```bash
git rm docs/CNAME docs/index.md
```

- [ ] **Step 5: Verify**

Run: `test -s README.md && test -f docs/README.md && test ! -f docs/CNAME && test ! -f docs/index.md && echo OK`
Expected: `OK`

- [ ] **Step 6: Commit**

```bash
git add README.md docs/README.md
git commit -m "docs(docs): write the root README and docs index, remove the docs.fastybird.com CNAME and stub index"
```

### Task 16: Write docs/architecture.md

**Files:**
- Create: `docs/architecture.md`

**Interfaces:**
- Consumes: the spec's Appendix A extension inventory and 2.1's request-routing and dependency-layering facts, plus the confirmed `public/index.php` and `Boot/Bootstrap.php` behaviour.
- Produces: the architecture reference `CLAUDE.md` and `AGENTS.md` point to.

- [ ] **Step 1: Create docs/architecture.md**

```markdown
# Architecture

## Extension layout

Every extension lives under `src/FastyBird/<Type>/<Name>/` with its own `src/` (PHP, namespace `FastyBird\<Type>\<Name>`), `tests/cases/unit`, optional `assets/` (Vue 3 UI), optional `config/`, `docs/`, `README.md`, `composer.json` and, when `assets/` exists, `package.json`. There are 35 extensions across 8 types:

| Type | Count | Extensions |
|---|---|---|
| Addon | 1 | VirtualThermostat |
| Automator | 2 | DateTime, DevicesModule |
| Bridge | 6 | DevicesModuleUiModule, RedisDbPluginDevicesModule, RedisDbPluginTriggersModule, ShellyConnectorHomeKitConnector, VieraConnectorHomeKitConnector, VirtualThermostatAddonHomeKitConnector |
| Connector | 10 | FbMqtt, HomeKit, Modbus, NsPanel, Shelly, Sonoff, Tuya, Viera, Virtual, Zigbee2Mqtt |
| Core | 3 | Application, Exchange, Tools |
| Library | 2 | Metadata, WebUi (a nested lerna workspace, no `composer.json`) |
| Module | 4 | Accounts, Devices, Triggers, Ui |
| Plugin | 7 | ApiKey, CouchDb, RabbitMq, RedisDb, RedisDbCache, WebServer, WsServer |

Dependency layering, from the extension manifests: `Library/Metadata` has no FastyBird dependency. `Core/Tools` depends on `datetime-factory` and `metadata-library`. `Core/Application` depends on `simple-auth`. `Core/Exchange` depends on `application` and `metadata-library`. Modules depend on `application`, `exchange`, `json-api`, `metadata-library`, `simple-auth` and `tools`. Connectors depend on `application`, `devices-module`, `metadata-library` and `tools` (HomeKit also on `exchange`). Plugins depend on `application` and `tools`; most also on `metadata-library`. Bridges, the addon and the automators depend on the packages they bridge.

## Request routing

`public/index.php` is the single entry point for both the API and the UI. Requests whose path starts with the API prefix are routed to the ReactPHP-based `FastyBird\Plugin\WebServer\Application`; everything else goes to the Nette application, which serves the Vue SPA shell through `contributte/vite` and Latte templates.

## Configuration load order

`FastyBird\Core\Application\Boot\Bootstrap::boot()` defines `FB_APP_DIR`, `FB_PUBLIC_DIR`, `FB_RESOURCES_DIR`, `FB_TEMP_DIR`, `FB_LOGS_DIR` and `FB_CONFIG_DIR` from `$_ENV`/`getenv()`, defaulting to `<app>/config` for `FB_CONFIG_DIR`. It loads, in order, `src/FastyBird/Core/Application/config/common.neon`, that same directory's `defaults.neon`, then `FB_CONFIG_DIR/common.neon`, `FB_CONFIG_DIR/defaults.neon` and `FB_CONFIG_DIR/local.neon`, each only when the file exists. Environment variables named `FB_APP_PARAMETER__<SECTION>_<KEY>` become container parameters (double underscore after the prefix, single underscore between section and key); `APP_ENV=dev` enables debug mode. See [configuration.md](./configuration.md) for how to add extension-specific wiring through `local.neon`.

## Runtime processes

HTTP: nginx in front of php-fpm, every request handled by `public/index.php`. The ReactPHP server behind `bin/fb-console fb:web-server:start` remains available for local runs without nginx.

Long-running workers under supervisord: `fb:ws-server:start` (WebSocket server, port 8888) and `fb:devices-module:exchange`. Connector processes run as `fb:devices-module:connector <identifier>`, one supervisor program per configured connector -- see [deployment.md](./deployment.md) for the program template.

## Frontend

`config/extensions.ts` registers which extensions' `assets/entry.ts` the Vite build includes; today that is `accounts-module`, `devices-module` and `homekit-connector` (`triggers-module` and `ui-module` have a UI but are not registered -- registering them is a functional change, out of scope for this merge). `src/FastyBird/Core/Application/assets/main.ts` reads that registry and the application version/description from the root `package.json` at build time.
```

- [ ] **Step 2: Verify**

Run: `test -f docs/architecture.md && grep -c '| Addon | 1' docs/architecture.md`
Expected: file exists; grep prints `1`.

- [ ] **Step 3: Commit**

```bash
git add docs/architecture.md
git commit -m "docs(docs): add docs/architecture.md"
```

### Task 17: Write docs/configuration.md with real local.neon snippets for every unwired extension

**Files:**
- Create: `docs/configuration.md`

**Interfaces:**
- Consumes: the real DI extension class names and `getConfigSchema()` contents read directly from `src/FastyBird/Plugin/RedisDb/src/DI/RedisDbExtension.php`, `src/FastyBird/Plugin/RedisDbCache/src/DI/RedisDbCacheExtension.php`, `src/FastyBird/Bridge/RedisDbPluginDevicesModule/src/DI/RedisDbPluginDevicesModuleExtension.php`, `src/FastyBird/Bridge/RedisDbPluginTriggersModule/src/DI/RedisDbPluginTriggersModuleExtension.php`, `src/FastyBird/Plugin/CouchDb/src/DI/CouchDbExtension.php`, `src/FastyBird/Plugin/RabbitMq/src/DI/RabbitMqExtension.php`, `src/FastyBird/Automator/DateTime/src/DI/DateTimeExtension.php`, `src/FastyBird/Automator/DevicesModule/src/DI/DevicesModuleExtension.php` and `src/FastyBird/Plugin/ApiKey/src/DI/ApiKeyExtension.php`.
- Produces: the copy-paste reference for enabling any of the 9 extensions the spec (D6, 2.1) confirms are present in the tree but not registered in `config/common.neon`.

- [ ] **Step 1: Create docs/configuration.md**

```markdown
# Configuration

Shipped wiring lives in `config/common.neon` and `config/defaults.neon`. Operator overrides go in `config/local.neon`, which is git-ignored, or in environment variables named `FB_APP_PARAMETER__<SECTION>_<KEY>` (see [architecture.md](./architecture.md#configuration-load-order)).

None of the extensions below are registered by default. Each snippet is a complete `config/local.neon` that registers the extension's DI extension class alongside whatever `config/common.neon` already registers -- Nette merges the `extensions:` sections, so you do not need to repeat the ones already wired.

## Redis-backed state storage (RedisDb plugin)

Registers `FastyBird\Plugin\RedisDb\DI\RedisDbExtension` (service name `fbRedisDbPlugin`):

```neon
extensions:
    fbRedisDbPlugin: FastyBird\Plugin\RedisDb\DI\RedisDbExtension

fbRedisDbPlugin:
    client:
        host: redis
        port: 6379
    exchange:
        channel: fb_exchange
```

`client.username` and `client.password` default to `null`; add them if your Redis instance requires auth. `exchange.channel` defaults to the metadata library's `EXCHANGE_CHANNEL_NAME` constant if omitted.

### Devices module state bridge

Registers `FastyBird\Bridge\RedisDbPluginDevicesModule\DI\RedisDbPluginDevicesModuleExtension` (service name `fbRedisDbPluginDevicesModuleBridge`), so device/channel/connector property state reads and writes go through Redis instead of the default in-memory/Doctrine store:

```neon
extensions:
    fbRedisDbPluginDevicesModuleBridge: FastyBird\Bridge\RedisDbPluginDevicesModule\DI\RedisDbPluginDevicesModuleExtension

fbRedisDbPluginDevicesModuleBridge:
    database: 0
```

`database` selects the Redis logical database (0-15) and defaults to `0`.

### Triggers module state bridge

Registers `FastyBird\Bridge\RedisDbPluginTriggersModule\DI\RedisDbPluginTriggersModuleExtension` (service name `fbRedisDbPluginTriggersModuleBridge`):

```neon
extensions:
    fbRedisDbPluginTriggersModuleBridge: FastyBird\Bridge\RedisDbPluginTriggersModule\DI\RedisDbPluginTriggersModuleExtension

fbRedisDbPluginTriggersModuleBridge:
    database: 1
```

`database` defaults to `1` -- one Redis logical database apart from the devices-module bridge's default of `0`, so both bridges can run against the same Redis instance without colliding.

## Redis-backed Nette cache storage (RedisDbCache plugin)

Registers `FastyBird\Plugin\RedisDbCache\DI\RedisDbCacheExtension` (service name `fbRedisDbCachePlugin`). Unlike `RedisDb` above, this replaces the framework's own cache storage, not application state:

```neon
extensions:
    fbRedisDbCachePlugin: FastyBird\Plugin\RedisDbCache\DI\RedisDbCacheExtension

fbRedisDbCachePlugin:
    client:
        host: redis
        port: 6379
        database: 0
```

## CouchDB state storage (CouchDb plugin)

Registers `FastyBird\Plugin\CouchDb\DI\CouchDbExtension` (service name `fbCouchDbPlugin`):

```neon
extensions:
    fbCouchDbPlugin: FastyBird\Plugin\CouchDb\DI\CouchDbExtension

fbCouchDbPlugin:
    connection:
        database: state_storage
        host: couchdb
        port: 5984
        username: admin
        password: admin
```

`connection.port` must be set explicitly to `5984` (CouchDB's real port) -- the extension's own schema default is `5672`, left over from being adapted from the RabbitMq plugin.

## RabbitMQ message exchange (RabbitMq plugin)

Registers `FastyBird\Plugin\RabbitMq\DI\RabbitMqExtension` (service name `fbRabbitMqPlugin`):

```neon
extensions:
    fbRabbitMqPlugin: FastyBird\Plugin\RabbitMq\DI\RabbitMqExtension

fbRabbitMqPlugin:
    client:
        host: rabbitmq
        port: 5672
        vhost: /
        username: guest
        password: guest
```

`exchange.name` and `queue.name` are optional and default to the plugin's own exchange name and an anonymous queue respectively.

## Automators

Both automators take no configuration -- registering the DI extension is enough to make their conditions/actions available to the triggers module:

```neon
extensions:
    fbDateTimeAutomator: FastyBird\Automator\DateTime\DI\DateTimeExtension
    fbDevicesModuleAutomator: FastyBird\Automator\DevicesModule\DI\DevicesModuleExtension
```

## API key authentication (ApiKey plugin)

Registers `FastyBird\Plugin\ApiKey\DI\ApiKeyExtension` (service name `fbApiKeyPlugin`); it also takes no configuration:

```neon
extensions:
    fbApiKeyPlugin: FastyBird\Plugin\ApiKey\DI\ApiKeyExtension
```

Once registered, create a key with:

```sh
bin/fb-console fb:api-key:create
```

## Compose services

`docker/dev/docker-compose.yml` and `docker/prod/docker-compose.yml` put `redis`, `couchdb` and `rabbitmq` behind Compose profiles because none of the extensions above are registered by default. Enable the matching profile when you register the extension, for example:

```sh
docker compose -f docker/dev/docker-compose.yml --profile redis up -d
```
```

- [ ] **Step 2: Verify every class name in the doc matches the real source**

Run: `for c in "FastyBird\\Plugin\\RedisDb\\DI\\RedisDbExtension" "FastyBird\\Plugin\\RedisDbCache\\DI\\RedisDbCacheExtension" "FastyBird\\Bridge\\RedisDbPluginDevicesModule\\DI\\RedisDbPluginDevicesModuleExtension" "FastyBird\\Bridge\\RedisDbPluginTriggersModule\\DI\\RedisDbPluginTriggersModuleExtension" "FastyBird\\Plugin\\CouchDb\\DI\\CouchDbExtension" "FastyBird\\Plugin\\RabbitMq\\DI\\RabbitMqExtension" "FastyBird\\Automator\\DateTime\\DI\\DateTimeExtension" "FastyBird\\Automator\\DevicesModule\\DI\\DevicesModuleExtension" "FastyBird\\Plugin\\ApiKey\\DI\\ApiKeyExtension"; do grep -qF "$c" docs/configuration.md && echo "found: $c" || echo "MISSING: $c"; done`
Expected: `found:` for all 9 lines, no `MISSING:`.

- [ ] **Step 3: Commit**

```bash
git add docs/configuration.md
git commit -m "docs(docs): add docs/configuration.md with local.neon snippets for every unwired extension"
```

### Task 18: Write docs/deployment.md

**Files:**
- Create: `docs/deployment.md`

**Interfaces:**
- Consumes: spec 4.5 (production defaults), 4.6 (runtime processes, supervisor template verbatim), 4.7 (Docker layout).
- Produces: the deployment reference `CLAUDE.md` and the root `README.md` link to.

- [ ] **Step 1: Create docs/deployment.md**

```markdown
# Deployment

## Docker images

`docker/dev/` holds the development Compose file and Dockerfiles (`docker/dev/{nginx,node,php}`); `docker/prod/Dockerfile` builds the production image in three stages: `ui` (`node:20`, `yarn install --frozen-lockfile && yarn build`), `vendor` (a Composer image, `composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative` with `COMPOSER_MIRROR_PATH_REPOS=1`), and `runtime` (`php:8.2-fpm` with nginx and supervisor, the PHP extensions `bcmath`, `gd`, `gmp`, `intl`, `pcntl`, `pdo_mysql`, `sockets`, `zip`, `opcache`).

The production image is published to `ghcr.io/fastybird/miniserver`, tagged `latest` on `main` and with the release version on tags (see `.github/workflows/release.yml`).

### Production defaults

| Variable | Default |
|---|---|
| `FB_CONFIG_DIR` | `/data/config` |
| `FB_LOGS_DIR` | `/data/logs` |
| `FB_TEMP_DIR` | `/data/temp` |

All three live under the single declared volume `/data`. The container exposes port `80` (HTTP) and `8888` (WebSocket), with a healthcheck on `GET /`.

The entrypoint (`docker/prod/docker-entrypoint.sh`) creates these three directories, generates `config/local.neon` with a random security signature on first start if neither that file nor `FB_APP_PARAMETER__SECURITY_SIGNATURE` already supplies one, waits for the database to answer, runs `bin/fb-console migrations:migrate --no-interaction --allow-no-migration`, then execs supervisord with the programs `php-fpm`, `nginx`, `ws-server` and `exchange`.

## Runtime processes

HTTP is served by nginx in front of php-fpm; every request hits `public/index.php`. Two long-running workers run under supervisord by default:

- `fb:ws-server:start` -- the WebSocket server, port `8888`
- `fb:devices-module:exchange` -- the devices-module message exchange consumer

Each configured connector runs as its own supervisor program, one per connector identifier, started with:

```sh
php /app/bin/fb-console.php fb:devices-module:connector <identifier> -n
```

The shipped supervisor programs live in `config/supervisor/` and are tracked in git. An operator adding a connector writes its program file into `FB_CONFIG_DIR/supervisor/` -- `.gitignore` excludes `config/supervisor/*.local.conf` so files an operator drops into the *default* config location do not show up as repository changes either. Use this template verbatim, one file per connector:

```ini
[program:fb.connector.<identifier>]
command = php /app/bin/fb-console.php fb:devices-module:connector <identifier> -n
process_name = %(program_name)s
numprocs = 1
autostart = true
autorestart = true
startsecs = 5
startretries = 3
redirect_stderr = true
stdout_logfile = /data/logs/%(program_name)s.log
stderr_logfile = /data/logs/%(program_name)s-error.log
```

The `fb-supervisor` event listener (`bin/fb-supervisor`) stops supervisord when a program dies unexpectedly, so the container's own restart policy takes over.

## Development

```sh
docker compose -f docker/dev/docker-compose.yml up -d
```

Default services: `web-server` (nginx), `application` (php-fpm with xdebug), `ui-server` (node running `yarn dev`), `ws-server`, `devices-module` (exchange), `migrations` (one-shot) and `database` (MariaDB). `redis`, `couchdb`, `rabbitmq` and `mqtt` are behind Compose profiles -- see [configuration.md](./configuration.md) for which extension needs which profile. A LAN-facing macvlan network is available as an override: `docker compose -f docker/dev/docker-compose.yml -f docker/dev/docker-compose.lan.yml up -d`.

## Debian packaging (unsupported)

`build/debian/` carries the old miniserver repository's `control`, `postinst`, `prerm`, `fb-miniserver.service` and `make_deb.sh`, ported as a starting point for a later appliance-style install path. It is not validated against the current PHP 8.2 / MariaDB stack and should not be used to deploy this application today -- use Docker.
```

- [ ] **Step 2: Verify the supervisor template matches the spec verbatim**

Run: `sed -n '/\[program:fb.connector.<identifier>\]/,/stderr_logfile/p' docs/deployment.md`
Expected:
```
[program:fb.connector.<identifier>]
command = php /app/bin/fb-console.php fb:devices-module:connector <identifier> -n
process_name = %(program_name)s
numprocs = 1
autostart = true
autorestart = true
startsecs = 5
startretries = 3
redirect_stderr = true
stdout_logfile = /data/logs/%(program_name)s.log
stderr_logfile = /data/logs/%(program_name)s-error.log
```

- [ ] **Step 3: Commit**

```bash
git add docs/deployment.md
git commit -m "docs(docs): add docs/deployment.md"
```

### Task 19: Write .env.example and trim .gitattributes

**Files:**
- Create: `.env.example`
- Modify: `.gitattributes`

**Interfaces:**
- Consumes: the real environment variable names read from the old miniserver repository's `docker-compose.yml`/`docker-compose.prod.yml` (`DATABASE_USERNAME`, `DATABASE_PASSWORD`, `DATABASE_DBNAME`, `SECURITY_SIGNATURE`, `API_PREFIX`, `API_PREFIXED_MODULES`, `API_KEY`, `ROOT_PASSWORD`, `HTTP_PORT`, `MYSQL_PORT`, `REDIS_PORT`, `COUCHDB_USERNAME`, `COUCHDB_PASSWORD`, `COUCHDB_PORT`, `RABBITMQ_USERNAME`, `RABBITMQ_PASSWORD`, `RABBITMQ_PORT`, `RABBITMQ_MANAGEMENT_PORT`, `MQTT_PORT`, `WORKER_STATUS_PORT`, `LOCAL_NETWORK_*`), which `docker/dev/docker-compose.yml` and `docker/prod/docker-compose.yml` (Phase 2) already consume via `${VAR:-default}` substitution.
- Produces: the template Task 6's `.gitignore` entry for `.env` implies must exist.

- [ ] **Step 1: Create .env.example**

```sh
# FastyBird MiniServer -- Docker Compose variable substitution template.
# Copy this file to `.env` and adjust for your deployment. docker-compose
# reads `.env` automatically for `${VAR:-default}` substitution in the
# compose files under docker/dev/ and docker/prod/. All variables here are
# optional; the default shown in each compose file is used when unset.

# Signs authentication tokens (fbSimpleAuth). STRONGLY set this in production --
# the production entrypoint generates and persists a random value into
# config/local.neon on first start if this is left unset, but setting it
# explicitly means every replica of a multi-instance deployment shares the
# same signature.
SECURITY_SIGNATURE=

# Database credentials, consumed as FB_APP_PARAMETER__DATABASE_* by the application
# and as MYSQL_USER / MYSQL_PASSWORD / MYSQL_DATABASE by the database service.
DATABASE_USERNAME=miniserver
DATABASE_PASSWORD=miniserver
DATABASE_DBNAME=miniserver
ROOT_PASSWORD=root

# API
API_PREFIX=/api
API_PREFIXED_MODULES=true
# Required only when the ApiKey plugin (docs/configuration.md) is registered.
API_KEY=

# Ports published on the host by docker/dev/docker-compose.yml.
HTTP_PORT=80
MYSQL_PORT=3306
REDIS_PORT=6379
COUCHDB_PORT=5984
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672
MQTT_PORT=1883
WORKER_STATUS_PORT=9001

# Only used when the couchdb or rabbitmq Compose profile is enabled.
COUCHDB_USERNAME=admin
COUCHDB_PASSWORD=admin
RABBITMQ_USERNAME=admin
RABBITMQ_PASSWORD=admin

# Only used with docker/dev/docker-compose.lan.yml (macvlan override).
LOCAL_NETWORK_BACKEND_IP_ADDRESS=192.168.0.10
LOCAL_NETWORK_WORKER_IP_ADDRESS=192.168.0.20
LOCAL_NETWORK_SUBNET=192.168.0.0/24
LOCAL_NETWORK_GATEWAY=192.168.0.1
LOCAL_NETWORK_IP_RANGE=192.168.0.6/24
LOCAL_NETWORK_DRIVER=eth0
```

- [ ] **Step 2: Trim .gitattributes**

Confirmed current content:
```
# Not archived
/.docker export-ignore
/.github export-ignore
/docs export-ignore
/tests export-ignore
/tools export-ignore
/var export-ignore
.editorconfig export-ignore
.gitattributes export-ignore
.gitignore export-ignore
docker-compose.yml export-ignore
Makefile export-ignore
monorepo-builder export-ignore
```
`/.docker` no longer exists (Phase 2 replaced it with `docker/`), `monorepo-builder.php` no longer exists (Phase 3 deleted it), and `docs/` now carries real documentation worth keeping in any archive, not a stub. Rewrite to:

```
# Not archived
/.github export-ignore
/tests export-ignore
/tools export-ignore
/var export-ignore
.editorconfig export-ignore
.gitattributes export-ignore
.gitignore export-ignore
Makefile export-ignore
```

- [ ] **Step 3: Verify**

Run: `test -f .env.example && grep -c '=' .env.example && grep -c 'monorepo-builder\|\.docker\|/docs' .gitattributes`
Expected: file exists; the `.env.example` grep prints a count greater than `10`; the `.gitattributes` grep prints `0`.

- [ ] **Step 4: Commit**

```bash
git add .env.example .gitattributes
git commit -m "docs(infra): add .env.example and trim stale .gitattributes export-ignore entries"
```

### Task 20: Rename the GitHub repositories (manual operator checklist)

**Files:**
- None (GitHub repository metadata only; no files in this repository change)

**Interfaces:**
- Consumes: the two repositories `FastyBird/miniserver` (old, no PHP code since 2022) and `FastyBird/fastybird` (this repository, `git remote -v` confirms `origin` is `https://github.com/FastyBird/fastybird.git`).
- Produces: `FastyBird/miniserver` pointing at the history this whole merge has been building, and `FastyBird/miniserver-old` preserving the old wrapper repository under D1 and D2 (no history import, nothing deleted yet -- deletion is Phase 7).

- [ ] **Step 1: Confirm both repositories exist under their current names**

Run: `gh repo view FastyBird/miniserver --json name,url && gh repo view FastyBird/fastybird --json name,url`
Expected: both commands print a `name`/`url` pair with no error.

- [ ] **Step 2: Rename the old miniserver repository out of the way first**

```bash
gh repo rename miniserver-old --repo FastyBird/miniserver --yes
```

Expected: `gh` reports the repository is now `FastyBird/miniserver-old`. This must run before Step 3 -- GitHub will not let two repositories in the same organization hold the name `miniserver` at once.

- [ ] **Step 3: Rename this repository to miniserver**

```bash
gh repo rename miniserver --repo FastyBird/fastybird --yes
```

Expected: `gh` reports the repository is now `FastyBird/miniserver`.

- [ ] **Step 4: Update the local git remote**

```bash
git remote set-url origin https://github.com/FastyBird/miniserver.git
git remote -v
```

Expected: both `origin` lines now read `https://github.com/FastyBird/miniserver.git`. GitHub's rename leaves an automatic redirect from the old URL, so this step is a courtesy that avoids relying on the redirect, not a requirement for pushes to keep working.

- [ ] **Step 5: Verify CI runs green on the renamed repository**

Run: `gh run list --repo FastyBird/miniserver --branch main --limit 1`
Expected: the most recent run (triggered by the rename's implicit push event or by re-running the last workflow with `gh run rerun`) shows `completed` / `success` for `ci-tests.yaml`.

- [ ] **Step 6: Verify the release workflow publishes an image for a pre-release tag**

```bash
gh release create v1.0.0-alpha.1 --repo FastyBird/miniserver --title "v1.0.0-alpha.1" --prerelease --notes "Phase 4 rename and conventions"
gh run list --repo FastyBird/miniserver --workflow release.yml --limit 1
```

Expected: the `release.yml` run shows `completed` / `success`; `docker pull ghcr.io/fastybird/miniserver:v1.0.0-alpha.1` then succeeds.

- [ ] **Step 7: Confirm nothing was deleted**

Run: `gh repo view FastyBird/miniserver-old --json name,isArchived,url && gh repo view FastyBird/miniserver --json name,isArchived,url`
Expected: both repositories still exist and report `"isArchived": false`. Per D1, deleting `FastyBird/miniserver-old` and the 25 split-target repositories is Phase 7's job, after Packagist and npm deprecation -- do not delete anything here.

This task has no commit step: it changes GitHub repository metadata, not files in this working tree.
