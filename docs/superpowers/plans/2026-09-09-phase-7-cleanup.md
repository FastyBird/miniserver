# Phase 7, GitHub Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Retire every package-registry and GitHub artifact left over from the pre-merge world (Packagist entries, npm packages, the old `miniserver` repository and the 25 split-target repositories) without leaving a dangling dependency or a live reference to something that no longer exists.

**Architecture:** Three ordered bodies of work, and the order is a safety property, not a preference. First mark packages abandoned/deprecated on Packagist and npm so nobody starts depending on a dead package. Second, confirm the repository rename from Phase 4 actually landed (this plan does not repeat it). Third, and only after a verification task finds zero live references and green CI, delete `FastyBird/miniserver-old` and the 25 split repositories one at a time, each as its own checkbox naming the repository explicitly. GitHub restores a deleted organization repository for 90 days; after that the deletion is final, so verification comes before any delete step, not after.

**Tech Stack:** Packagist web UI/API, npm CLI (`npm deprecate`), GitHub web UI/`gh` CLI for repository settings and deletion, `grep` across the merged repository, GitHub Actions (`ci-tests.yaml`, `release.yml`).

**Spec:** docs/superpowers/specs/2026-09-09-miniserver-merge-design.md

## Global Constraints

- PHP 8.2, Node 20, yarn 1 are the frozen toolchain for the merge; this phase changes no application code or dependency versions.
- No pull request mixes a structural change with a dependency version change (not applicable here: this phase makes no code changes, only registry and GitHub-org actions plus one verification/log commit).
- CI green before the next pull request; specifically `ci-tests.yaml` and the release workflow must already be green on `main` of the renamed `FastyBird/miniserver` repository before any deletion in Task 3 onward.
- Conventional commit format `<type>(<scope>): <subject>` with scope from the list in spec 4.8: `core`, `module`, `connector`, `plugin`, `bridge`, `addon`, `automator`, `library`, `ui`, `infra`, `ci`, `deps`, `docs`, `cross`. This phase's one commit (the cleanup log) uses scope `cross`.
- PHP namespaces stay `FastyBird\<Type>\<Name>`; the `src/FastyBird/` prefix does not change (this phase touches no PHP).
- Irreversibility constraint specific to this phase: GitHub restores a deleted organization repository for 90 days from the deletion date; after that window the deletion is final. Every deletion step is its own checkbox naming one repository, never a loop over a list, so each deletion is individually reviewable and individually reversible within the 90-day window.
- Order constraint specific to this phase: Packagist abandonment (Task 1) and npm deprecation (Task 2) complete before the rename confirmation (Task 3 Step 1), which completes before the reference/CI verification (Task 3 Steps 2-6), which completes before any deletion (Tasks 4 onward).

## Pull Requests

1. **PR1 — Cleanup log and reference verification** (Tasks 1-3): no application code changes; adds `docs/cleanup-log.md` recording the Packagist/npm actions taken, and runs the pre-deletion verification (grep for live references, confirm CI green, confirm the image pulls). This is the only PR in this phase that touches the repository's git history.
2. **Out-of-band actions, not pull requests** (Tasks 1-2, 4-8): Packagist abandonment, npm deprecation, and the repository deletions are GitHub/Packagist/npm org-administration actions taken by the maintainer (Adam Kadlec) directly against packagist.org, npmjs.com and github.com/FastyBird. They are recorded in `docs/cleanup-log.md` from PR1 but are not themselves pull requests against `FastyBird/miniserver`.

---

### Task 1: Mark Packagist packages abandoned

**Files:**
- Create: none yet (the log file is written in Task 3)

**Interfaces:**
- Consumes: Phase 4's rename of `FastyBird/fastybird` to `FastyBird/miniserver` (so the replacement package name resolves), Phase 5/6 completion (D11 requires prior phases green)
- Produces: an abandoned/replacement marker on every live `fastybird/*` Packagist package, consumed by Task 3's verification and by `docs/cleanup-log.md`

Verified against the live Packagist API on 2026-09-09, of the 34 composer names in the root `replace` block (captured from `composer.json` at commit `b1ba77f9^`, since Phase 3 deletes the `replace` block) plus `fastybird/fastybird` and `fastybird/miniserver`, the following 15 exist on Packagist and must be marked abandoned:

```
fastybird/fastybird
fastybird/accounts-module
fastybird/application
fastybird/devices-module
fastybird/exchange
fastybird/fb-mqtt-connector
fastybird/homekit-connector
fastybird/metadata-library
fastybird/modbus-connector
fastybird/rabbitmq-plugin
fastybird/shelly-connector
fastybird/tools
fastybird/triggers-module
fastybird/tuya-connector
fastybird/ui-module
fastybird/virtual-connector
fastybird/web-server-plugin
fastybird/ws-server-plugin
```

(Re-run the check below before acting, since a package can be re-published between the design date and execution.)

- [ ] **Step 1: Re-verify the live Packagist state**

```bash
for n in fastybird/fastybird fastybird/miniserver \
  fastybird/accounts-module fastybird/apikey-plugin fastybird/application \
  fastybird/couchdb-plugin fastybird/date-time-automator fastybird/devices-module \
  fastybird/devices-module-automator fastybird/devices-module-ui-module-bridge \
  fastybird/exchange fastybird/fb-mqtt-connector fastybird/homekit-connector \
  fastybird/metadata-library fastybird/modbus-connector fastybird/ns-panel-connector \
  fastybird/rabbitmq-plugin fastybird/redisdb-cache-plugin fastybird/redisdb-plugin \
  fastybird/redisdb-plugin-devices-module-bridge fastybird/redisdb-plugin-triggers-module-bridge \
  fastybird/shelly-connector fastybird/shelly-connector-homekit-connector-bridge \
  fastybird/sonoff-connector fastybird/tools fastybird/triggers-module \
  fastybird/tuya-connector fastybird/ui-module fastybird/viera-connector \
  fastybird/viera-connector-homekit-connector-bridge fastybird/virtual-connector \
  fastybird/virtual-thermostat-addon fastybird/virtual-thermostat-addon-homekit-connector-bridge \
  fastybird/web-server-plugin fastybird/ws-server-plugin fastybird/zigbee2mqtt-connector; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "https://packagist.org/packages/$n.json")
  echo "$n -> $code"
done
```

Expected: exactly the 18 names below return `200` (the rest, including `fastybird/miniserver`, are handled separately or return `404`): `fastybird/fastybird`, `fastybird/accounts-module`, `fastybird/application`, `fastybird/devices-module`, `fastybird/exchange`, `fastybird/fb-mqtt-connector`, `fastybird/homekit-connector`, `fastybird/metadata-library`, `fastybird/modbus-connector`, `fastybird/rabbitmq-plugin`, `fastybird/shelly-connector`, `fastybird/tools`, `fastybird/triggers-module`, `fastybird/tuya-connector`, `fastybird/ui-module`, `fastybird/virtual-connector`, `fastybird/web-server-plugin`, `fastybird/ws-server-plugin`. If the list of `200` responses differs from this list, use the actual live list for the remaining steps instead of the one printed here.

- [ ] **Step 2: Mark each existing package abandoned, in the Packagist web UI**

For each of the 18 package names confirmed `200` in Step 1 (excluding `fastybird/miniserver`, handled in Step 3): sign in to packagist.org as the package maintainer, open `https://packagist.org/packages/<name>`, click "Abandon Package", and in the "Replacement package" field enter `fastybird/miniserver`. This is a per-package manual action; there is no bulk API endpoint for abandonment on packagist.org.

- [ ] **Step 3: Repoint the existing `fastybird/miniserver` Packagist entry**

Open `https://packagist.org/packages/fastybird/miniserver`, use "Edit" and update the repository URL to the renamed GitHub repository (`https://github.com/FastyBird/miniserver`, unchanged as a URL because Phase 4 renamed `fastybird` into this same name, so this step is a no-op confirmation, not an edit, when Phase 4's rename already updated Packagist's tracked URL via its GitHub webhook). Force an update with the "Update" button so Packagist re-reads the current `composer.json` `name` (`fastybird/miniserver` after Phase 3/4).

- [ ] **Step 4: Verify each abandonment took effect**

```bash
for n in fastybird/fastybird fastybird/accounts-module fastybird/application \
  fastybird/devices-module fastybird/exchange fastybird/fb-mqtt-connector \
  fastybird/homekit-connector fastybird/metadata-library fastybird/modbus-connector \
  fastybird/rabbitmq-plugin fastybird/shelly-connector fastybird/tools \
  fastybird/triggers-module fastybird/tuya-connector fastybird/ui-module \
  fastybird/virtual-connector fastybird/web-server-plugin fastybird/ws-server-plugin; do
  curl -s "https://packagist.org/packages/$n.json" | python3 -c "import json,sys; d=json.load(sys.stdin)['package']; print('$n', d.get('abandoned'))"
done
```

Expected: every line prints `fastybird/miniserver` (or `True` on packagist.org's JSON, which uses the replacement string when set) as the `abandoned` value, never `False`.

### Task 2: Deprecate npm packages

**Files:**
- Create: none yet

**Interfaces:**
- Consumes: npm publish credentials for the `@fastybird` org (maintainer-held, out of band)
- Produces: a deprecation notice on every live `@fastybird/*` package published from this monorepo, consumed by Task 3's verification and by `docs/cleanup-log.md`

Verified against the live npm registry on 2026-09-09, checking the 8 package names in Appendix A's npm-name column plus the additional web-ui workspace packages found under `src/FastyBird/Library/WebUi/`, 10 packages are published from this monorepo (the spec's Appendix A text says "9 published npm package names" as an approximate count; the registry check below is authoritative and finds 10 — `@fastybird/vue-wamp-v1` is excluded because the spec states it is an external package, not published from this monorepo):

```
@fastybird/tools
@fastybird/homekit-connector
@fastybird/ui-module
@fastybird/triggers-module
@fastybird/accounts-module
@fastybird/devices-module
@fastybird/metadata-library
@fastybird/web-ui-library
@fastybird/web-ui-icons
@fastybird/web-ui-theme-chalk
```

(`@fastybird/application`, `@fastybird/web-ui`, `@fastybird/web-ui-components`, `@fastybird/web-ui-utils` and `@fastybird/web-ui-docs` returned `404` on the registry on 2026-09-09 and are not published; re-check before acting.)

- [ ] **Step 1: Re-verify the live npm registry state**

```bash
for p in application tools homekit-connector ui-module triggers-module accounts-module \
  devices-module metadata-library web-ui web-ui-library web-ui-components web-ui-icons \
  web-ui-theme-chalk web-ui-utils web-ui-docs vue-wamp-v1; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "https://registry.npmjs.org/@fastybird/$p")
  echo "@fastybird/$p -> $code"
done
```

Expected: `200` for exactly `tools`, `homekit-connector`, `ui-module`, `triggers-module`, `accounts-module`, `devices-module`, `metadata-library`, `web-ui-library`, `web-ui-icons`, `web-ui-theme-chalk`, and `vue-wamp-v1` (the last is external and is not deprecated in Step 2); `404` for the rest. If the live result differs, use the actual `200` list (minus `vue-wamp-v1`) for Step 2.

- [ ] **Step 2: Deprecate each published package**

```bash
npm deprecate @fastybird/tools "Merged into FastyBird MiniServer"
npm deprecate @fastybird/homekit-connector "Merged into FastyBird MiniServer"
npm deprecate @fastybird/ui-module "Merged into FastyBird MiniServer"
npm deprecate @fastybird/triggers-module "Merged into FastyBird MiniServer"
npm deprecate @fastybird/accounts-module "Merged into FastyBird MiniServer"
npm deprecate @fastybird/devices-module "Merged into FastyBird MiniServer"
npm deprecate @fastybird/metadata-library "Merged into FastyBird MiniServer"
npm deprecate @fastybird/web-ui-library "Merged into FastyBird MiniServer"
npm deprecate @fastybird/web-ui-icons "Merged into FastyBird MiniServer"
npm deprecate @fastybird/web-ui-theme-chalk "Merged into FastyBird MiniServer"
```

Each command requires prior `npm login` (or `NPM_TOKEN`) as a maintainer with publish rights on the `@fastybird` org, and deprecates all versions of the package (no version range given).

- [ ] **Step 3: Verify each deprecation took effect**

```bash
for p in tools homekit-connector ui-module triggers-module accounts-module devices-module \
  metadata-library web-ui-library web-ui-icons web-ui-theme-chalk; do
  msg=$(npm view "@fastybird/$p" deprecated 2>/dev/null)
  echo "@fastybird/$p -> $msg"
done
```

Expected: every line prints `Merged into FastyBird MiniServer`.

### Task 3: Write the cleanup log and pre-deletion verification

**Files:**
- Create: `docs/cleanup-log.md`

**Interfaces:**
- Consumes: Task 1 (Packagist abandonment done), Task 2 (npm deprecation done), Phase 4's rename of `FastyBird/fastybird` to `FastyBird/miniserver`, Phase 4's `ci-tests.yaml` and `release.yml`
- Produces: the confirmation that Tasks 4-8 (deletion) are safe to run; `docs/cleanup-log.md` is the audit trail referenced by the whole-merge acceptance criterion in spec section 6

- [ ] **Step 1: Confirm the repository rename already happened**

```bash
gh repo view FastyBird/miniserver --json name,nameWithOwner,url
gh repo view FastyBird/fastybird 2>&1
```

Expected: the first command prints `"nameWithOwner": "FastyBird/miniserver"` and a `url` of `https://github.com/FastyBird/miniserver`; the second command fails with `GraphQL: Could not resolve to a Repository with the name 'FastyBird/fastybird'.` (the name no longer exists because Phase 4 renamed it). Do not repeat the rename here — if the first command instead shows the pre-rename application-wrapper repository, or the second command still resolves, stop and hand back to Phase 4, since Task 4 below deletes the repository this rename step was supposed to vacate.

- [ ] **Step 2: Confirm remotes, badges and URLs resolve on the renamed repository**

```bash
git remote -v
curl -s -o /dev/null -w "%{http_code}\n" https://github.com/FastyBird/miniserver
curl -s -o /dev/null -w "%{http_code}\n" https://raw.githubusercontent.com/FastyBird/miniserver/main/README.md
```

Expected: `git remote -v` shows `origin` pointing at `https://github.com/FastyBird/miniserver.git` (or the equivalent SSH form) for both fetch and push; both `curl` checks return `200`.

- [ ] **Step 3: Grep the repository for every split-repository and old-miniserver name**

```bash
grep -rn "github.com/FastyBird/miniserver-old\b" --include="*.md" --include="*.json" --include="*.yml" --include="*.yaml" --include="*.neon" . || echo "no match: miniserver-old"
for repo in application exchange tools metadata-library web-ui-library devices-module \
  accounts-module triggers-module ui-module fb-mqtt-connector homekit-connector \
  modbus-connector ns-panel-connector shelly-connector sonoff-connector tuya-connector \
  viera-connector virtual-connector zigbee2mqtt-connector couchdb-plugin redisdb-plugin \
  rabbitmq-plugin web-server-plugin ws-server-plugin virtual-thermostat-addon; do
  matches=$(grep -rn "github.com/FastyBird/$repo\b" --include="*.md" --include="*.json" --include="*.yml" --include="*.yaml" --include="*.neon" . )
  if [ -n "$matches" ]; then
    echo "LIVE REFERENCE to $repo:"
    echo "$matches"
  fi
done
```

Expected: no output lines starting with `LIVE REFERENCE`. On the pre-Phase-3 tree this grep does find hits (verified 2026-09-09, for example `src/FastyBird/Connector/HomeKit/README.md` and `composer.json` link to `github.com/FastyBird/homekit-connector`), which is why this task runs after Phase 3/4 (per-extension badges and repository URLs are removed) and gates every deletion below: if any line prints, fix the reference in the repository (a separate small pull request, scope `docs`) and rerun this step before proceeding to Task 4.

- [ ] **Step 4: Confirm `ci-tests.yaml` and the release workflow are green**

```bash
gh run list --repo FastyBird/miniserver --workflow=ci-tests.yaml --branch main --limit 1 --json status,conclusion
gh run list --repo FastyBird/miniserver --workflow=release.yml --limit 1 --json status,conclusion
```

Expected: both commands print `"status": "completed"` and `"conclusion": "success"` for the most recent run.

- [ ] **Step 5: Confirm the published image pulls**

```bash
docker pull ghcr.io/fastybird/miniserver:latest
docker compose -f docker/prod/docker-compose.yml up -d database
docker compose -f docker/prod/docker-compose.yml run --rm application bin/fb-console.php list >/tmp/fb-console-list.txt 2>&1; echo "exit=$?"
docker compose -f docker/prod/docker-compose.yml down -v
```

Expected: `docker pull` succeeds; the compose-based `bin/fb-console.php list` run against a reachable database exits `0` and `/tmp/fb-console-list.txt` contains the console command list (for example the line `fb:web-server:start`). A bare `docker run` with no database service would hang or fail at the entrypoint's database-wait step (spec section 4.7), so this check always goes through compose, mirroring the Phase 2 smoke test.

- [ ] **Step 6: Write the cleanup log**

```markdown
# GitHub and registry cleanup log

Phase 7 of the MiniServer merge (spec `docs/superpowers/specs/2026-09-09-miniserver-merge-design.md`, section 5 "Phase 7, GitHub cleanup").

## Packagist, abandoned with replacement `fastybird/miniserver`

- fastybird/fastybird
- fastybird/accounts-module
- fastybird/application
- fastybird/devices-module
- fastybird/exchange
- fastybird/fb-mqtt-connector
- fastybird/homekit-connector
- fastybird/metadata-library
- fastybird/modbus-connector
- fastybird/rabbitmq-plugin
- fastybird/shelly-connector
- fastybird/tools
- fastybird/triggers-module
- fastybird/tuya-connector
- fastybird/ui-module
- fastybird/virtual-connector
- fastybird/web-server-plugin
- fastybird/ws-server-plugin

`fastybird/miniserver` repointed at `https://github.com/FastyBird/miniserver` (no change, confirms the Phase 4 rename propagated).

## npm, deprecated with message "Merged into FastyBird MiniServer"

- @fastybird/tools
- @fastybird/homekit-connector
- @fastybird/ui-module
- @fastybird/triggers-module
- @fastybird/accounts-module
- @fastybird/devices-module
- @fastybird/metadata-library
- @fastybird/web-ui-library
- @fastybird/web-ui-icons
- @fastybird/web-ui-theme-chalk

`@fastybird/vue-wamp-v1` is not deprecated: it is an external package, not published from this monorepo.

## GitHub repository deletions

Deleted 90+ days ago are final; GitHub restores a deleted organization repository for 90 days from the deletion date, after which the deletion cannot be undone. Each deletion below is logged with its date once performed.

- [ ] FastyBird/miniserver-old, deleted:
- [ ] FastyBird/application, deleted:
- [ ] FastyBird/exchange, deleted:
- [ ] FastyBird/tools, deleted:
- [ ] FastyBird/metadata-library, deleted:
- [ ] FastyBird/web-ui-library, deleted:
- [ ] FastyBird/devices-module, deleted:
- [ ] FastyBird/accounts-module, deleted:
- [ ] FastyBird/triggers-module, deleted:
- [ ] FastyBird/ui-module, deleted:
- [ ] FastyBird/fb-mqtt-connector, deleted:
- [ ] FastyBird/homekit-connector, deleted:
- [ ] FastyBird/modbus-connector, deleted:
- [ ] FastyBird/ns-panel-connector, deleted:
- [ ] FastyBird/shelly-connector, deleted:
- [ ] FastyBird/sonoff-connector, deleted:
- [ ] FastyBird/tuya-connector, deleted:
- [ ] FastyBird/viera-connector, deleted:
- [ ] FastyBird/virtual-connector, deleted:
- [ ] FastyBird/zigbee2mqtt-connector, deleted:
- [ ] FastyBird/couchdb-plugin, deleted:
- [ ] FastyBird/redisdb-plugin, deleted:
- [ ] FastyBird/rabbitmq-plugin, deleted:
- [ ] FastyBird/web-server-plugin, deleted:
- [ ] FastyBird/ws-server-plugin, deleted:
- [ ] FastyBird/virtual-thermostat-addon, deleted:

## Repositories kept

- FastyBird/.github: kept, organization profile and README assets that several READMEs still reference.
- FastyBird/libraries-patches: **must NOT be deleted.** Vendoring the root manifest's 10 patch files into `tools/patches/` in Phase 0 does *not* make the project independent of this repository. Established empirically during Phase 1 on 2026-09-10: `fastybird/json-api` and `fastybird/datetime-factory` each declare their own `extra.patches` entry for `nette/utils` pointing at a raw URL here, and `fastybird/simple-auth` declares two, for `nette/utils` and `nettrine/orm`. Those dependency-declared patches are fetched at install time even though the root sets `extra.enable-patching: false`, and a dependency's URL takes precedence over a root entry carrying the same description key. Deleting this repository would therefore break `composer install` on every future checkout. It becomes eligible for deletion only after Phase 6 updates or absorbs those three external libraries and a clean `composer install` shows no remaining reference to it. Verify with: `grep -rl "libraries-patches" vendor/*/*/composer.json` returning nothing.
```

Save this content to `docs/cleanup-log.md`, replacing the `deleted:` placeholders with the actual date as each Task 4-8 checkbox below is completed.

- [ ] **Step 7: Commit**

```bash
git add docs/cleanup-log.md
git commit -m "docs(cross): add phase 7 github and registry cleanup log"
```

### Task 3b: Retire the unused GitHub Pages deployment

**Added 2026-09-10**, after the repository rename made the live Pages site visible. The
maintainer confirmed `docs.fastybird.com` "is something we wanted to use for documentation but
was never used" and asked that any deploy to GitHub Pages be removed.

**Files:**
- None in this repository. `docs/CNAME` was already deleted in Phase 4 Task 15, and the 54
  documentation links were repointed at `https://miniserver.fastybird.com/docs` in
  `docs(cross): point documentation links at miniserver.fastybird.com/docs`.

**State at the time of writing:**
- Pages served `http://docs.fastybird.com/` from `main` at path `/docs`, status `built`. The
  repository rename triggered a successful rebuild, which is how this was noticed.
- There is **no Pages workflow** on either `main` or the merge branch. This is the legacy
  branch-based Pages deployment, driven entirely by repository settings plus `docs/CNAME`.
  Searching for `deploy-pages`, `gh-pages`, `peaceiris` and `upload-pages-artifact` returns
  nothing, so disabling it is a settings change only.
- The repository's `homepageUrl` was already `http://miniserver.fastybird.com/`.

- [ ] **Step 1: Confirm nothing has started depending on the Pages site**

```bash
gh api repos/FastyBird/miniserver/pages
grep -rn 'docs\.fastybird\.com' --include='*.md' . | grep -v node_modules | grep -v docs/superpowers
```

Expected: the second command returns nothing. If it returns hits, a later commit reintroduced
the dead domain and must be fixed before disabling Pages.

- [ ] **Step 2: Disable Pages**

```bash
gh api -X DELETE repos/FastyBird/miniserver/pages
gh api repos/FastyBird/miniserver/pages   # expect 404
```

- [ ] **Step 3: Release the DNS record**

`docs.fastybird.com` becomes unused. Removing the DNS record is the operator's call and is not
scriptable from here.

**Open question for the maintainer, deliberately not resolved here.** The stated model,
`FastyBird/smart-panel`, *does* use GitHub Pages — it serves `https://fastybird.github.io/smart-panel/`
from `main` at path `/`, with no `CNAME` file, and reaches `smart-panel.fastybird.com` through
DNS configured outside the repository. So if `miniserver.fastybird.com/docs` is eventually to be
served the same way, Pages is the likely vehicle and this task should *reconfigure* it (source
`main:/`, no CNAME) rather than delete it. Disabling now is reversible either way; re-enabling is
a settings change. Confirm the intent before running Step 2.

### Task 4: Delete FastyBird/miniserver-old

**Files:**
- Modify: `docs/cleanup-log.md` (check the `FastyBird/miniserver-old` line and fill in the date)

**Interfaces:**
- Consumes: Task 3 Steps 1-5 (verification), Task 1 and Task 2 (Packagist/npm already marked, so no dangling dependency is created by this deletion)
- Produces: nothing consumed by a later task; this is a terminal action

- [ ] **Step 1: Authorize the CLI for repository deletion**

```bash
gh auth refresh -h github.com -s delete_repo
gh auth status
```

Expected: `gh auth status` lists `delete_repo` among the token scopes for `github.com`. This is a one-time authorization; `gh repo delete` otherwise fails immediately with `Deletion requires authorization with the delete_repo scope.` on every `gh repo delete` call in Tasks 4-8.

- [ ] **Step 2: Confirm no fork or open pull request depends on this repository**

```bash
gh api repos/FastyBird/miniserver-old/forks --jq 'length'
gh pr list --repo FastyBird/miniserver-old --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
```

Expected: both commands print `0`.

- [ ] **Step 3: Delete the repository**

```bash
gh repo delete FastyBird/miniserver-old --yes
```

Expected: command exits `0` and prints a confirmation that `FastyBird/miniserver-old` was deleted. This is restorable from the GitHub organization's deleted-repositories list for 90 days from today; after that the deletion is final.

- [ ] **Step 4: Verify deletion**

```bash
gh repo view FastyBird/miniserver-old 2>&1
```

Expected: `GraphQL: Could not resolve to a Repository with the name 'FastyBird/miniserver-old'. (repository)`.

- [ ] **Step 5: Update the cleanup log and commit**

Edit `docs/cleanup-log.md`, changing `- [ ] FastyBird/miniserver-old, deleted:` to `- [x] FastyBird/miniserver-old, deleted: <today's date>`.

```bash
git add docs/cleanup-log.md
git commit -m "docs(cross): record deletion of FastyBird/miniserver-old"
```

### Task 5: Delete the core and module split repositories

**Files:**
- Modify: `docs/cleanup-log.md` (check 9 lines: `application`, `exchange`, `tools`, `metadata-library`, `web-ui-library`, `devices-module`, `accounts-module`, `triggers-module`, `ui-module`)

**Interfaces:**
- Consumes: Task 3 (verification), Task 1 and Task 2
- Produces: nothing consumed by a later task

Each repository is its own checkbox and its own `gh repo delete` invocation, run only after Step 1 (fork/PR check) passes for that specific repository.

- [ ] **Step 1: Delete FastyBird/application**

```bash
gh api repos/FastyBird/application/forks --jq 'length'
gh pr list --repo FastyBird/application --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/application --yes
gh repo view FastyBird/application 2>&1
```

Expected: fork and open-PR counts are `0` before deletion; the final `gh repo view` reports `Could not resolve to a Repository with the name 'FastyBird/application'.`

- [ ] **Step 2: Delete FastyBird/exchange**

```bash
gh api repos/FastyBird/exchange/forks --jq 'length'
gh pr list --repo FastyBird/exchange --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/exchange --yes
gh repo view FastyBird/exchange 2>&1
```

Expected: same pattern as Step 1, for `FastyBird/exchange`.

- [ ] **Step 3: Delete FastyBird/tools**

```bash
gh api repos/FastyBird/tools/forks --jq 'length'
gh pr list --repo FastyBird/tools --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/tools --yes
gh repo view FastyBird/tools 2>&1
```

Expected: same pattern, for `FastyBird/tools`.

- [ ] **Step 4: Delete FastyBird/metadata-library**

```bash
gh api repos/FastyBird/metadata-library/forks --jq 'length'
gh pr list --repo FastyBird/metadata-library --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/metadata-library --yes
gh repo view FastyBird/metadata-library 2>&1
```

Expected: same pattern, for `FastyBird/metadata-library`.

- [ ] **Step 5: Delete FastyBird/web-ui-library**

```bash
gh api repos/FastyBird/web-ui-library/forks --jq 'length'
gh pr list --repo FastyBird/web-ui-library --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/web-ui-library --yes
gh repo view FastyBird/web-ui-library 2>&1
```

Expected: same pattern, for `FastyBird/web-ui-library`.

- [ ] **Step 6: Delete FastyBird/devices-module**

```bash
gh api repos/FastyBird/devices-module/forks --jq 'length'
gh pr list --repo FastyBird/devices-module --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/devices-module --yes
gh repo view FastyBird/devices-module 2>&1
```

Expected: same pattern, for `FastyBird/devices-module`.

- [ ] **Step 7: Delete FastyBird/accounts-module**

```bash
gh api repos/FastyBird/accounts-module/forks --jq 'length'
gh pr list --repo FastyBird/accounts-module --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/accounts-module --yes
gh repo view FastyBird/accounts-module 2>&1
```

Expected: same pattern, for `FastyBird/accounts-module`.

- [ ] **Step 8: Delete FastyBird/triggers-module**

```bash
gh api repos/FastyBird/triggers-module/forks --jq 'length'
gh pr list --repo FastyBird/triggers-module --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/triggers-module --yes
gh repo view FastyBird/triggers-module 2>&1
```

Expected: same pattern, for `FastyBird/triggers-module`.

- [ ] **Step 9: Delete FastyBird/ui-module**

```bash
gh api repos/FastyBird/ui-module/forks --jq 'length'
gh pr list --repo FastyBird/ui-module --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/ui-module --yes
gh repo view FastyBird/ui-module 2>&1
```

Expected: same pattern, for `FastyBird/ui-module`.

- [ ] **Step 10: Update the cleanup log and commit**

Edit `docs/cleanup-log.md`, checking the 9 lines for `application`, `exchange`, `tools`, `metadata-library`, `web-ui-library`, `devices-module`, `accounts-module`, `triggers-module` and `ui-module`, each with today's date.

```bash
git add docs/cleanup-log.md
git commit -m "docs(cross): record deletion of core and module split repositories"
```

### Task 6: Delete the connector split repositories

**Files:**
- Modify: `docs/cleanup-log.md` (check 10 lines: `fb-mqtt-connector`, `homekit-connector`, `modbus-connector`, `ns-panel-connector`, `shelly-connector`, `sonoff-connector`, `tuya-connector`, `viera-connector`, `virtual-connector`, `zigbee2mqtt-connector`)

**Interfaces:**
- Consumes: Task 3, Task 1, Task 2
- Produces: nothing consumed by a later task

- [ ] **Step 1: Delete FastyBird/fb-mqtt-connector**

```bash
gh api repos/FastyBird/fb-mqtt-connector/forks --jq 'length'
gh pr list --repo FastyBird/fb-mqtt-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/fb-mqtt-connector --yes
gh repo view FastyBird/fb-mqtt-connector 2>&1
```

Expected: fork/open-PR counts `0` before deletion; final view reports the repository does not resolve.

- [ ] **Step 2: Delete FastyBird/homekit-connector**

```bash
gh api repos/FastyBird/homekit-connector/forks --jq 'length'
gh pr list --repo FastyBird/homekit-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/homekit-connector --yes
gh repo view FastyBird/homekit-connector 2>&1
```

Expected: same pattern, for `FastyBird/homekit-connector`.

- [ ] **Step 3: Delete FastyBird/modbus-connector**

```bash
gh api repos/FastyBird/modbus-connector/forks --jq 'length'
gh pr list --repo FastyBird/modbus-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/modbus-connector --yes
gh repo view FastyBird/modbus-connector 2>&1
```

Expected: same pattern, for `FastyBird/modbus-connector`.

- [ ] **Step 4: Delete FastyBird/ns-panel-connector**

```bash
gh api repos/FastyBird/ns-panel-connector/forks --jq 'length'
gh pr list --repo FastyBird/ns-panel-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/ns-panel-connector --yes
gh repo view FastyBird/ns-panel-connector 2>&1
```

Expected: same pattern, for `FastyBird/ns-panel-connector`.

- [ ] **Step 5: Delete FastyBird/shelly-connector**

```bash
gh api repos/FastyBird/shelly-connector/forks --jq 'length'
gh pr list --repo FastyBird/shelly-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/shelly-connector --yes
gh repo view FastyBird/shelly-connector 2>&1
```

Expected: same pattern, for `FastyBird/shelly-connector`.

- [ ] **Step 6: Delete FastyBird/sonoff-connector**

```bash
gh api repos/FastyBird/sonoff-connector/forks --jq 'length'
gh pr list --repo FastyBird/sonoff-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/sonoff-connector --yes
gh repo view FastyBird/sonoff-connector 2>&1
```

Expected: same pattern, for `FastyBird/sonoff-connector`.

- [ ] **Step 7: Delete FastyBird/tuya-connector**

```bash
gh api repos/FastyBird/tuya-connector/forks --jq 'length'
gh pr list --repo FastyBird/tuya-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/tuya-connector --yes
gh repo view FastyBird/tuya-connector 2>&1
```

Expected: same pattern, for `FastyBird/tuya-connector`.

- [ ] **Step 8: Delete FastyBird/viera-connector**

```bash
gh api repos/FastyBird/viera-connector/forks --jq 'length'
gh pr list --repo FastyBird/viera-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/viera-connector --yes
gh repo view FastyBird/viera-connector 2>&1
```

Expected: same pattern, for `FastyBird/viera-connector`.

- [ ] **Step 9: Delete FastyBird/virtual-connector**

```bash
gh api repos/FastyBird/virtual-connector/forks --jq 'length'
gh pr list --repo FastyBird/virtual-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/virtual-connector --yes
gh repo view FastyBird/virtual-connector 2>&1
```

Expected: same pattern, for `FastyBird/virtual-connector`.

- [ ] **Step 10: Delete FastyBird/zigbee2mqtt-connector**

```bash
gh api repos/FastyBird/zigbee2mqtt-connector/forks --jq 'length'
gh pr list --repo FastyBird/zigbee2mqtt-connector --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/zigbee2mqtt-connector --yes
gh repo view FastyBird/zigbee2mqtt-connector 2>&1
```

Expected: same pattern, for `FastyBird/zigbee2mqtt-connector`.

- [ ] **Step 11: Update the cleanup log and commit**

Edit `docs/cleanup-log.md`, checking the 10 connector lines with today's date.

```bash
git add docs/cleanup-log.md
git commit -m "docs(cross): record deletion of connector split repositories"
```

### Task 7: Delete the plugin split repositories

**Files:**
- Modify: `docs/cleanup-log.md` (check 5 lines: `couchdb-plugin`, `redisdb-plugin`, `rabbitmq-plugin`, `web-server-plugin`, `ws-server-plugin`)

**Interfaces:**
- Consumes: Task 3, Task 1, Task 2
- Produces: nothing consumed by a later task

- [ ] **Step 1: Delete FastyBird/couchdb-plugin**

```bash
gh api repos/FastyBird/couchdb-plugin/forks --jq 'length'
gh pr list --repo FastyBird/couchdb-plugin --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/couchdb-plugin --yes
gh repo view FastyBird/couchdb-plugin 2>&1
```

Expected: fork/open-PR counts `0` before deletion; final view reports the repository does not resolve.

- [ ] **Step 2: Delete FastyBird/redisdb-plugin**

```bash
gh api repos/FastyBird/redisdb-plugin/forks --jq 'length'
gh pr list --repo FastyBird/redisdb-plugin --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/redisdb-plugin --yes
gh repo view FastyBird/redisdb-plugin 2>&1
```

Expected: same pattern, for `FastyBird/redisdb-plugin`.

- [ ] **Step 3: Delete FastyBird/rabbitmq-plugin**

```bash
gh api repos/FastyBird/rabbitmq-plugin/forks --jq 'length'
gh pr list --repo FastyBird/rabbitmq-plugin --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/rabbitmq-plugin --yes
gh repo view FastyBird/rabbitmq-plugin 2>&1
```

Expected: same pattern, for `FastyBird/rabbitmq-plugin`.

- [ ] **Step 4: Delete FastyBird/web-server-plugin**

```bash
gh api repos/FastyBird/web-server-plugin/forks --jq 'length'
gh pr list --repo FastyBird/web-server-plugin --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/web-server-plugin --yes
gh repo view FastyBird/web-server-plugin 2>&1
```

Expected: same pattern, for `FastyBird/web-server-plugin`.

- [ ] **Step 5: Delete FastyBird/ws-server-plugin**

```bash
gh api repos/FastyBird/ws-server-plugin/forks --jq 'length'
gh pr list --repo FastyBird/ws-server-plugin --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/ws-server-plugin --yes
gh repo view FastyBird/ws-server-plugin 2>&1
```

Expected: same pattern, for `FastyBird/ws-server-plugin`.

- [ ] **Step 6: Update the cleanup log and commit**

Edit `docs/cleanup-log.md`, checking the 5 plugin lines with today's date.

```bash
git add docs/cleanup-log.md
git commit -m "docs(cross): record deletion of plugin split repositories"
```

### Task 8: Delete the addon split repository and verify the final count

**Files:**
- Modify: `docs/cleanup-log.md` (check the `virtual-thermostat-addon` line, then verify all 26 checkboxes are checked)

**Interfaces:**
- Consumes: Task 3, Task 1, Task 2, Tasks 4-7 (all other deletions already done)
- Produces: the completed audit trail satisfying spec section 6, "the old repositories deleted"

- [ ] **Step 1: Delete FastyBird/virtual-thermostat-addon**

```bash
gh api repos/FastyBird/virtual-thermostat-addon/forks --jq 'length'
gh pr list --repo FastyBird/virtual-thermostat-addon --state open --json number | python3 -c "import json,sys; print(len(json.load(sys.stdin)))"
gh repo delete FastyBird/virtual-thermostat-addon --yes
gh repo view FastyBird/virtual-thermostat-addon 2>&1
```

Expected: fork/open-PR counts `0` before deletion; final view reports the repository does not resolve.

- [ ] **Step 2: Confirm the full deletion list is closed**

```bash
grep -c '^- \[x\]' docs/cleanup-log.md
grep -c '^- \[ \]' docs/cleanup-log.md
```

Expected: the first command prints `26` (`FastyBird/miniserver-old` plus the 25 split repositories); the second prints `0`.

- [ ] **Step 3: Confirm each deleted repository is gone and each kept repository still resolves**

```bash
for repo in miniserver-old application exchange tools metadata-library web-ui-library \
  devices-module accounts-module triggers-module ui-module fb-mqtt-connector \
  homekit-connector modbus-connector ns-panel-connector shelly-connector sonoff-connector \
  tuya-connector viera-connector virtual-connector zigbee2mqtt-connector couchdb-plugin \
  redisdb-plugin rabbitmq-plugin web-server-plugin ws-server-plugin virtual-thermostat-addon; do
  gh repo view "FastyBird/$repo" >/dev/null 2>&1 && echo "STILL EXISTS: $repo" || true
done
gh repo view FastyBird/miniserver --json nameWithOwner
gh repo view FastyBird/.github --json nameWithOwner
```

Expected: no `STILL EXISTS` lines print; both `gh repo view` calls for the kept repositories succeed and print their `nameWithOwner`.

- [ ] **Step 4: Update the cleanup log and commit**

Edit `docs/cleanup-log.md`, checking the `FastyBird/virtual-thermostat-addon` line with today's date.

```bash
git add docs/cleanup-log.md
git commit -m "docs(cross): record deletion of virtual-thermostat-addon and close phase 7"
```
