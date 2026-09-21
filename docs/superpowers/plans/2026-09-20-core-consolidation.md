# Core Consolidation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merge the 15 packages that provide always-needed shared capability (`Core/Application`, `Core/Exchange`, `Core/SimpleAuth`, `Core/Tools`, `Library/DateTimeFactory`, `Library/DoctrineCrud`, `Library/DoctrineOrmQuery`, `Library/DoctrineTimestampable`, `Library/JsonApi`, `Library/Metadata`, `Library/Phone`, `Library/SlimRouter`, `Library/WebSockets`, `Plugin/WebServer`, `Plugin/WsServer`) into one new package `src/FastyBird/Core/Core/` (composer `fastybird/miniserver-core`, npm `@fastybird/miniserver-core`, PHP namespace root `FastyBird\Core\`), and migrate all 32 other extensions to depend on it instead of picking individual packages piecemeal.

**Architecture:** Internal organization is type-first, then former-domain, then class name (`FastyBird\Core\<Type>\<FormerDomain>\<ClassName>`), per spec D2. Ten to twelve existing Nette DI `CompilerExtension` classes consolidate into one `FastyBird\Core\DI\CoreExtension` (D5). ~50 exception classes collapse where they share both name and SPL parent (D3/D4); the rest keep their domain tag. The WebSockets/WAMP protocol and routing layer stays reachable by any extension under a `WebSockets` domain tag; the two server *runtimes* (the WS socket-accept loop, the HTTP listener) get their own `WsServer`/`HttpServer` domain tags because only one command each instantiates them (D7/D8). This lands as one large PR, not phased (D9). Both PHP and JS sides consolidate together (D10).

**Tech Stack:** PHP 8.4, Nette DI/Bootstrap 3.x, Doctrine ORM 3.3, ReactPHP, Vue 3 + TypeScript, pnpm workspaces, Composer path repositories.

**Spec:** `docs/superpowers/specs/2026-09-20-core-consolidation-design.md` — every task below implements a decision from that spec (D1-D10) or a mapping from its Appendix A/B. Read it alongside this plan; this plan does not repeat its rationale, only its consequences.

## Global Constraints

- PHP 8.4, Node 24, pnpm 10 (pinned via `packageManager` in `package.json`) — per `CLAUDE.md`.
- All verification commands run inside the Docker application container, never the bare host: `docker exec -w /app fastybird-application <command>`. The `fastybird-php84-tools` image has PHP's stock 128M `memory_limit` and will fatal partway through `make tests`/`make cs`; only the application image has the 512M override.
- PHP tests run via `make tests` only, never `vendor/bin/paratest` directly (the target puts `tools/php.d/tests.ini` on `PHP_INI_SCAN_DIR`; without it 1167 of 1407 tests error).
- `make composer-validate`, never `composer validate --strict` (two permanent warnings: `endroid/qr-code`'s exact version constraint, `mathsolver/mathsolver` unbound).
- After every cross-package edit, before trusting any local result: `rm -rf vendor/fastybird && composer install` (rebuilds the `COMPOSER_MIRROR_PATH_REPOS=1` copy-mirror; `composer install` alone does not refresh it). This plan touches essentially every package in the tree, so run this before every verification step from Task 18 onward, not just once at the end.
- Composer package updates are named explicitly: `composer update fastybird/<name>` (or `composer reinstall <name>` to refresh a stale mirror copy without touching version resolution). Never a bare `composer update`.
- Conventional commits (`<type>(<scope>): <subject>`), scope required.
- This plan produces **one pull request** (D9). Tasks below are still executed as separate commits on one branch for reviewability; do not open intermediate PRs per task.
- New/moved PHP files use the namespace root `FastyBird\Core\` (not `FastyBird\Core\Core\`) — the folder is `src/FastyBird/Core/Core/` (repo's `<Type>/<Name>/` convention with Type=Core, Name=Core), but the PHP namespace drops the redundant second segment, matching D1's explicit "namespace root `FastyBird\Core\`" and D2's `FastyBird\Core\<Type>\<FormerDomain>\<ClassName>` pattern.
- Every new/rewritten PHP file's `@date` docblock tag uses `2026-09-20` (today, the date this plan was authored) unless a step says otherwise.

## Flagged Assumptions (read before executing)

The spec's Appendix A/B were themselves marked "resolved" for every item they enumerated, but this plan's investigation (reading every DI extension and every package's actual file tree, not just the folder census) found real gaps and one additional pre-existing bug class the spec's census methodology could not have caught. Each is called out again at its exact point of use, but the full list, for review before execution starts:

1. **Licensing.** The new package declares `"license": "Apache-2.0"` in composer.json (matching the root project license and the `Core/Application` precedent) and ships no standalone `LICENSE.md` (relies on the repo-root `LICENSE.md`, exactly like `Core/Application`, `Core/Exchange`, `Core/Tools`, `Library/Metadata`, `Plugin/WebServer`, `Plugin/WsServer` do today). Five of the fifteen merging packages (`DoctrineCrud`, `DoctrineOrmQuery`, `DoctrineTimestampable`, `Phone`, `SlimRouter`) currently ship their own `LICENSE.md` declaring dual GPL-2.0/GPL-3.0 licensing (ipublikuj heritage) and lose that standalone file and license declaration under this merge. This is a real licensing decision the spec does not address at all (D1-D10 are silent on it). Flagged for Adam's explicit sign-off before merging; Task 1 implements the Apache-2.0 call but do not consider that decision final without confirmation.
2. **Within-package Wamp/base exception duplicates the spec's own census missed.** Spec §2.5 counted exception occurrences per top-level `Core/Library` package; `Library/WebSockets/src/Wamp/` is a *sub-namespace* of `Library/WebSockets`, not a separate top-level package, so its duplicate `Exceptions/Exception.php`, `Exceptions/InvalidArgument.php`, and `Exceptions/Storage.php` (all confirmed byte-identical in shape to their base-WebSockets counterparts by direct read) never entered the tally. Task 15 deletes the Wamp copies of `Exception` and `InvalidArgument` outright (redundant with the D3/D4 shared classes) and merges `Storage` into one `Core\Exceptions\WebSockets\Storage`.
3. **Two more wrong-SPL-parent bugs in the same family as the JsonApi `Logic` fix D4 already documents.** `Library\WebSockets\Exceptions\InvalidState` extends bare `\Exception`, not `\RuntimeException` like the other eight `InvalidState` occurrences. `Library\WebSockets\Exceptions\Logic` also extends bare `\Exception`, not `\LogicException`. Both confirmed by direct read. Task 2 fixes both by construction, the same way D4 already fixes JsonApi's `Logic`.
4. **A fifth `Logic`/`Logical` occurrence the spec's "4" count missed.** `Plugin\WsServer\Exceptions\Logic.php` (correctly `LogicException`-parented) is a fifth occurrence — `Plugin/WsServer` is one of the 15 merging packages but wasn't part of the `Core/Library` census either. Folds into the same shared `Core\Exceptions\Logic`.
5. **`DoctrineOrmQuery\QueryObject.php`/`ResultSet.php`** (top-level, no subfolder) have no Appendix A row. Placed in the `Persistence` bucket (ORM/data-access), consistent with its sibling entries there (`DoctrineCrud/Crud`, `SimpleAuth/Models`+`Queries`, `Application/ObjectMapper`, `JsonApi/Hydrators`).
6. **New type buckets for one-off folders §2.4 named but Appendix A's table never gave a row:** `Utilities` (`Tools/Utilities`), `Transformers` (`Tools/Transformers`), `Formats` (`Tools/Formats`). Each keeps its own folder name as the bucket name, matching how `Helpers`/`Schemas` did.
7. **New type buckets for top-level package files with no folder at all:** `Configuration` (`SimpleAuth/Configuration.php` + `DoctrineTimestampable/Configuration.php` — both are DI-populated settings value objects, same technical role); `Constants` (`SimpleAuth/Constants.php` + `Metadata/Constants.php` — plain constant holders); `Services` (`SimpleAuth/Auth.php` + `Phone/Phone.php` — each package's own DI-registered facade service, a specific recognizable role, not a generic catch-all — this is *not* the generic "Services bucket" §4 explicitly rejected, which was about dumping unrelated one-offs together).
8. **`SimpleAuth/Application/TSimpleAuth.php`** (a trait mixed into Nette Presenters) is placed by target-usage in the `Presenters` bucket (`Core\Presenters\SimpleAuth\TSimpleAuth.php`), not under a domain tag matching its own former folder name "Application" (which would be confusable with the `Application` domain tag).
9. **`Phone/TPhone.php`** (entity mixin trait, parallel to `TCreatedAt`/`TOwner`/`TUpdatedAt`) is placed in the `Entities` bucket next to `Phone/Entities/Phone.php`, i.e. `Core\Entities\Phone\TPhone.php`.
10. **`Library/WebSockets/src/User.php`** is a vestigial polyfill (`namespace Nette\Security; if (!class_exists(...)) { class User {} }`) with no confirmed live reference anywhere in the tree (grepped — none found) and whose guard is already permanently true against the installed `nette/security` ^3.2. Moved as-is to `Core\Compat\User.php` (new one-off `Compat` bucket) rather than deleted, since deleting dead code is out of scope for this migration.
11. **Wamp's `Clients\ClientFactory`, `Entities\Clients\Client`, `Entities\Clients\IClient` collide by class name** with base WebSockets' own `Clients\ClientFactory`/`Entities\Clients\Client`/`Entities\Clients\IClient` once both fold into the `WsServer` domain tag (my extension of the spec's own "live connection state belongs with the runtime" reasoning for `WebSockets/Clients`, applied consistently to Wamp's parallel classes — see Task 15). Since these are behaviorally different, not mergeable duplicates (Wamp's factory produces topic-subscription-aware clients and replaces the base one via a DI override), they're renamed `WampClientFactory`/`WampClient`/`IWampClient` on the Wamp side. This is the one place this plan departs from D8's "no qualifier, no third nesting level" rule — D8 was written for the protocol/message classes that don't collide; this is a different domain tag (`WsServer`, not `WebSockets`) that D8 never addressed.
12. **Real DI *service name* collisions**, not just config-schema collisions, found by reading all 12 extensions' `$this->prefix(...)` calls: `DoctrineTimestampableExtension` and `SimpleAuthExtension` both register a bare `configuration` service; `DoctrinePhoneExtension` and `DoctrineTimestampableExtension` both register a bare `subscriber` service; base `WebSocketsExtension` and `WebSocketsWAMPExtension` both register `clients.factory` (harmless today only because they are two separate extensions with two separate prefixes). Resolved by Task 18's design rule: every service in the merged `CoreExtension` is keyed `<formerDomain>.<originalRelativeKey>`, applied uniformly (not case-by-case) — this is the same principle Adam stated for D2's namespace taxonomy ("the naming have to be consistent for all core files"), extended to service keys.
13. **Exactly one cross-extension NEON service reference exists in the whole repository**, found by grepping every `@fbXxx.` / `@ipubXxx.` pattern against every `.neon` file: `@fbJsonApi.middlewares.jsonapi`, in 24 consumer `tests/common.neon` files plus root `config/common.neon`. It becomes `@fbCore.jsonApi.middlewares.jsonapi`. Nothing else needs a targeted rename; the bulk NEON sweep in Task 22 is otherwise a pure key/FQCN substitution.
14. **The entire `Library/` package type disappears.** All 9 `Library/*` packages are among the 15 being merged; after this PR, `src/FastyBird/Library/` is empty. `tools/layering.php`'s `'Library' => []` type rule and every type's `'Library/*'` grant become dead. Task 25 removes them rather than leaving stale grants for a type with zero packages, matching the file's own explicit anti-rot philosophy.

---

## Task 1: Scaffold the `fastybird/miniserver-core` package skeleton

**Files:**
- Create: `src/FastyBird/Core/Core/composer.json`
- Create: `src/FastyBird/Core/Core/package.json`
- Create: `src/FastyBird/Core/Core/.gitignore`
- Create: `src/FastyBird/Core/Core/README.md`
- Create: `src/FastyBird/Core/Core/docs/Home.md`
- Modify: `composer.json` (repo root)
- Modify: `package.json` (repo root)

**Interfaces:**
- Produces: the directory `src/FastyBird/Core/Core/{src,tests,assets,bin,config,migrations,templates,docs}/` that every later task moves content into. Produces the composer package name `fastybird/miniserver-core` and PHP namespace root `FastyBird\Core\` that every later task's classes live under.

- [ ] **Step 1: Create the directory skeleton**

```bash
mkdir -p src/FastyBird/Core/Core/src
mkdir -p src/FastyBird/Core/Core/tests/cases/unit
mkdir -p src/FastyBird/Core/Core/tests/fixtures/dummy
mkdir -p src/FastyBird/Core/Core/tests/tools
mkdir -p src/FastyBird/Core/Core/assets
mkdir -p src/FastyBird/Core/Core/bin
mkdir -p src/FastyBird/Core/Core/config
mkdir -p src/FastyBird/Core/Core/migrations
mkdir -p src/FastyBird/Core/Core/templates/presenters
mkdir -p src/FastyBird/Core/Core/docs
mkdir -p src/FastyBird/Core/Core/resources
```

- [ ] **Step 2: Write `src/FastyBird/Core/Core/composer.json`**

This is the union of all 15 former packages' `require` blocks, minus every `fastybird/*` entry that named one of the other 14 (those become internal, no longer a package boundary), with duplicated third-party constraints unified to the tighter/higher bound found across the 15 (documented inline where non-obvious). `mathsolver/mathsolver` keeps `@dev` — it is the permanent `composer-validate` warning noted in `CLAUDE.md`, unrelated to this merge.

```json
{
  "name": "fastybird/miniserver-core",
  "description": "FastyBird MiniServer core application, exchange, authentication, HTTP/WS server runtimes and shared libraries",
  "keywords": [
    "fastybird",
    "fb",
    "core",
    "api",
    "php",
    "iot",
    "nette",
    "vue3",
    "pinia"
  ],
  "homepage": "https://www.fastybird.com",
  "license": "Apache-2.0",
  "authors": [
    {
      "name": "FastyBird s.r.o.",
      "email": "code@fastybird.com",
      "homepage": "https://www.fastybird.com"
    },
    {
      "name": "Adam Kadlec",
      "email": "adam.kadlec@fastybird.com"
    }
  ],
  "support": {
    "email": "code@fastybird.com",
    "issues": "https://github.com/FastyBird/miniserver/issues",
    "source": "https://github.com/FastyBird/miniserver"
  },
  "require": {
    "php": ">=8.4.0",
    "casbin/casbin": "^3.23",
    "contributte/cache": "^0.6",
    "contributte/console": "^0.9",
    "contributte/event-dispatcher": "^0.9",
    "contributte/monolog": "^0.6",
    "contributte/translation": "^2.0",
    "contributte/vite": "^0.3",
    "cweagans/composer-patches": "^1.7",
    "doctrine/orm": "^3.3",
    "doctrine/persistence": "^3.4 || ^4.0",
    "ext-ctype": "*",
    "ext-iconv": "*",
    "ext-intl": "*",
    "ext-json": "*",
    "ext-mbstring": "*",
    "ext-openssl": "*",
    "ext-simplexml": "*",
    "fig/http-message-util": "^1.1",
    "giggsey/libphonenumber-for-php": "^8.12",
    "latte/latte": "^3.0",
    "lcobucci/jwt": "^4.2",
    "mathsolver/mathsolver": "@dev",
    "neomerx/json-api": "^4.0",
    "nette/application": "^3.1",
    "nette/bootstrap": "^3.2",
    "nette/caching": "^3.3",
    "nette/di": "^3.2",
    "nette/http": "^3.2",
    "nette/utils": "^4.0",
    "nettrine/dbal": "^0.10",
    "nettrine/orm": "^0.10",
    "nikic/fast-route": "^1.3",
    "opis/json-schema": "^2.3",
    "orisai/nette-object-mapper": "^0.3",
    "orisai/object-mapper": "^0.3",
    "phpdocumentor/reflection-docblock": "^5.3",
    "psr/event-dispatcher": "^1.0",
    "psr/http-factory": "^1.1",
    "psr/http-message": "^1.1",
    "psr/http-server-middleware": "^1.0",
    "psr/log": "^3.0",
    "ramsey/uuid": "^4.7",
    "ramsey/uuid-doctrine": "^2.1",
    "react/event-loop": "^1.3",
    "react/http": "^1.7",
    "react/promise": "^3",
    "react/socket": "^1.15",
    "sentry/sdk": "^3.1",
    "sunrise/http-message": "^3.0",
    "symfony/console": "^6.0",
    "symfony/event-dispatcher": "^7.0",
    "symfony/event-dispatcher-contracts": "^3.5",
    "symfony/monolog-bridge": "^7.0",
    "symfony/serializer": "^6.4"
  },
  "require-dev": {
    "symplify/vendor-patches": "^11.2"
  },
  "autoload": {
    "psr-4": {
      "FastyBird\\Core\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "FastyBird\\Core\\Tests\\Cases\\Unit\\": "tests/cases/unit",
      "FastyBird\\Core\\Tests\\Fixtures\\": "tests/fixtures",
      "FastyBird\\Core\\Tests\\Fixtures\\Dummy\\": "tests/fixtures/dummy",
      "FastyBird\\Core\\Tests\\Tools\\": "tests/tools"
    }
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

**Note on constraint unification** (so the implementer isn't guessing why a number differs from every source file): `psr/log` was `^1.1|^3.0` in JsonApi vs `^3.0` in WebSockets/WebServer/WsServer → `^3.0`. `psr/http-message` was `^1.0` in WebServer vs `^1.1` in JsonApi → `^1.1`. `psr/http-factory` was `^1.0` in SlimRouter vs `^1.1` in JsonApi → `^1.1`. `react/socket` was `^1.12` in WebServer/WsServer vs `^1.15` in WebSockets → `^1.15`. `ramsey/uuid` was `^4.1` in SlimRouter, `^4.2` in SimpleAuth, `^4.7` in Application/JsonApi → `^4.7`. `nette/di` ranged `^3.0`-`^3.2` → `^3.2`. `nette/utils` was `^3.2||^4.0` in several vs bare `^4.0` in others → `^4.0`. This is a judgment call, not a mechanical union; `composer update fastybird/miniserver-core` in Task 27 will fail loudly if any pair is actually incompatible.

- [ ] **Step 3: Write `src/FastyBird/Core/Core/package.json`**

Union of `Core/Application`, `Core/Tools`, `Library/Metadata`, `Library/WebSockets`'s `package.json` files (the four that ship an `assets/` directory, per D10).

```json
{
  "name": "@fastybird/miniserver-core",
  "exports": {
    ".": "./assets/entry.ts"
  },
  "types": "./assets/entry.ts",
  "private": true,
  "version": "0.0.0",
  "type": "module",
  "description": "FastyBird MiniServer core application shell, shared tools, metadata and WebSockets/WAMP client for Vue 3",
  "keywords": [
    "fastybird",
    "fb",
    "core",
    "ui",
    "interface",
    "vue",
    "metadata",
    "websocket",
    "wamp"
  ],
  "homepage": "https://www.fastybird.com",
  "bugs": "https://github.com/FastyBird/miniserver/issues",
  "license": "Apache-2.0",
  "author": {
    "name": "FastyBird s.r.o.",
    "email": "code@fastybird.com",
    "url": "https://www.fastybird.com/"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/FastyBird/miniserver.git"
  },
  "sideEffects": true,
  "dependencies": {
    "@iconify/vue": "^5",
    "@vueuse/core": "^11.2",
    "axios": "^1.6",
    "lodash.get": "^4.4",
    "mitt": "^3.0"
  },
  "peerDependencies": {
    "element-plus": "^2.8",
    "pinia": "^2.2",
    "unocss": "^0.64",
    "vue": "^3.5",
    "vue-i18n": "^10.0",
    "vue-router": "^4.4"
  }
}
```

- [ ] **Step 4: Write `src/FastyBird/Core/Core/.gitignore`**

Copy `src/FastyBird/Core/Application/.gitignore` and `src/FastyBird/Core/Tools/.gitignore` verbatim-merged (both are small Vite/build-artifact ignore lists):

```bash
cat src/FastyBird/Core/Application/.gitignore src/FastyBird/Core/Tools/.gitignore | sort -u > src/FastyBird/Core/Core/.gitignore
```

Run this and inspect the result manually — `sort -u` can reorder glob patterns in a way that changes nothing functionally but is worth a human glance before committing.

- [ ] **Step 5: Write a short `README.md` and `docs/Home.md`**

`src/FastyBird/Core/Core/README.md`:

```markdown
# FastyBird MiniServer Core

The MiniServer's core package: application bootstrap, DI container wiring, JSON:API
document handling, authentication and authorization (SimpleAuth), the Doctrine
CRUD/query/timestampable helpers, the exchange (pub/sub) bus, phone number
validation, PSR-7 routing (SlimRouter), the WebSockets/WAMP protocol layer, and the
HTTP and WS server runtimes.

This package replaces `Core/Application`, `Core/Exchange`, `Core/SimpleAuth`,
`Core/Tools`, `Library/DateTimeFactory`, `Library/DoctrineCrud`,
`Library/DoctrineOrmQuery`, `Library/DoctrineTimestampable`, `Library/JsonApi`,
`Library/Metadata`, `Library/Phone`, `Library/SlimRouter`, `Library/WebSockets`,
`Plugin/WebServer` and `Plugin/WsServer`. See
`docs/superpowers/specs/2026-09-20-core-consolidation-design.md` for the full
rationale and namespace mapping.

Every other extension in this repository depends on this package
(`fastybird/miniserver-core`) instead of picking individual packages.
```

`src/FastyBird/Core/Core/docs/Home.md` — concatenate the six former packages' `docs/Home.md` files that have one (`Core/Application`, `Core/Exchange`, `Core/Tools`, `Plugin/WebServer`, `Plugin/WsServer`) under level-2 headings per former domain:

```bash
{
  echo "# FastyBird MiniServer Core"
  echo
  for pair in "Application:Application bootstrap" "Exchange:Exchange (pub/sub bus)" "Tools:Tools" "WebServer:HTTP server runtime" "WsServer:WS server runtime"; do
    domain="${pair%%:*}"
    title="${pair##*:}"
    src="src/FastyBird/Core/Application/docs/Home.md"
    case "$domain" in
      Exchange) src="src/FastyBird/Core/Exchange/docs/Home.md" ;;
      Tools) src="src/FastyBird/Core/Tools/docs/Home.md" ;;
      WebServer) src="src/FastyBird/Plugin/WebServer/docs/Home.md" ;;
      WsServer) src="src/FastyBird/Plugin/WsServer/docs/Home.md" ;;
    esac
    echo "## $title"
    echo
    cat "$src"
    echo
  done
} > src/FastyBird/Core/Core/docs/Home.md
```

Run this from the repo root before Tasks 3-17 delete the source files (this step reads five `docs/Home.md` files that later tasks remove).

- [ ] **Step 6: Register the new package in the root `composer.json`**

Modify `composer.json`. Replace the six merging-package lines in `require` with one:

```diff
-        "fastybird/application": "@dev",
         "fastybird/couchdb-plugin": "@dev",
         "fastybird/date-time-automator": "@dev",
         "fastybird/devices-module": "@dev",
         "fastybird/devices-module-automator": "@dev",
         "fastybird/devices-module-ui-module-bridge": "@dev",
-        "fastybird/exchange": "@dev",
         "fastybird/fb-mqtt-connector": "@dev",
         "fastybird/homekit-connector": "@dev",
-        "fastybird/metadata-library": "@dev",
         "fastybird/modbus-connector": "@dev",
+        "fastybird/miniserver-core": "@dev",
         "fastybird/ns-panel-connector": "@dev",
         "fastybird/rabbitmq-plugin": "@dev",
         "fastybird/redisdb-cache-plugin": "@dev",
         "fastybird/redisdb-plugin": "@dev",
         "fastybird/redisdb-plugin-devices-module-bridge": "@dev",
         "fastybird/redisdb-plugin-triggers-module-bridge": "@dev",
         "fastybird/shelly-connector": "@dev",
         "fastybird/shelly-connector-homekit-connector-bridge": "@dev",
         "fastybird/sonoff-connector": "@dev",
-        "fastybird/tools": "@dev",
         "fastybird/triggers-module": "@dev",
         "fastybird/tuya-connector": "@dev",
         "fastybird/ui-module": "@dev",
         "fastybird/viera-connector": "@dev",
         "fastybird/viera-connector-homekit-connector-bridge": "@dev",
         "fastybird/virtual-connector": "@dev",
         "fastybird/virtual-thermostat-addon": "@dev",
         "fastybird/virtual-thermostat-addon-homekit-connector-bridge": "@dev",
-        "fastybird/web-server-plugin": "@dev",
-        "fastybird/ws-server-plugin": "@dev",
         "fastybird/zigbee2mqtt-connector": "@dev",
```

Keep the `require` list sorted alphabetically after this edit (`config.sort-packages: true` expects it and `composer validate` will otherwise reorder it on the next `composer update`, showing as unrelated diff noise later).

Remove the `require-dev` entry `"fastybird/websockets-library": "@dev"` entirely (line 89 today) — it is now covered by the `require` entry above.

Update the `bin` array (currently points at `Core/Application`'s bin scripts, which Task 3 moves):

```diff
     "bin": [
-        "src/FastyBird/Core/Application/bin/fb-console",
-        "src/FastyBird/Core/Application/bin/fb-console.php",
-        "src/FastyBird/Core/Application/bin/fb-supervisor",
-        "src/FastyBird/Core/Application/bin/fb-supervisor.php"
+        "src/FastyBird/Core/Core/bin/fb-console",
+        "src/FastyBird/Core/Core/bin/fb-console.php",
+        "src/FastyBird/Core/Core/bin/fb-supervisor",
+        "src/FastyBird/Core/Core/bin/fb-supervisor.php"
     ],
```

Update the six `autoload-dev` psr-4 blocks for `FastyBird\Core\Application\Tests\...`, `FastyBird\Core\Exchange\Tests\...`, `FastyBird\Core\Tools\Tests\...`, `FastyBird\Library\Metadata\Tests\...`, `FastyBird\Plugin\WebServer\Tests\...`, `FastyBird\Plugin\WsServer\Tests\...` (these are the only six of the 15 that have a `tests/` directory at all, confirmed by directory listing) to one block:

```diff
-            "FastyBird\\Core\\Application\\Tests\\Cases\\Unit\\": "src/FastyBird/Core/Application/tests/cases/unit",
-            "FastyBird\\Core\\Application\\Tests\\Fixtures\\": "src/FastyBird/Core/Application/tests/fixtures",
-            "FastyBird\\Core\\Application\\Tests\\Fixtures\\Dummy\\": "src/FastyBird/Core/Application/tests/fixtures/dummy",
-            "FastyBird\\Core\\Application\\Tests\\Tools\\": "src/FastyBird/Core/Application/tests/tools",
+            "FastyBird\\Core\\Tests\\Cases\\Unit\\": "src/FastyBird/Core/Core/tests/cases/unit",
+            "FastyBird\\Core\\Tests\\Fixtures\\": "src/FastyBird/Core/Core/tests/fixtures",
+            "FastyBird\\Core\\Tests\\Fixtures\\Dummy\\": "src/FastyBird/Core/Core/tests/fixtures/dummy",
+            "FastyBird\\Core\\Tests\\Tools\\": "src/FastyBird/Core/Core/tests/tools",
```

and delete the five other now-redundant blocks (`Core\Exchange\Tests`, `Core\Tools\Tests`, `Library\Metadata\Tests` — three mappings each with its own fixtures/tools variants — `Plugin\WebServer\Tests`, `Plugin\WsServer\Tests`). The root `autoload-dev` psr-4 map is otherwise untouched by this task (Task 21 handles the 32 consumers' own composer.json files).

- [ ] **Step 7: Register the new package in the root `package.json`**

Modify `package.json`. Replace the three merging npm names with one in `dependencies`:

```diff
     "@fastybird/accounts-module": "workspace:*",
     "@fastybird/devices-module": "workspace:*",
     "@fastybird/homekit-connector": "workspace:*",
-    "@fastybird/metadata-library": "workspace:*",
-    "@fastybird/tools": "workspace:*",
-    "@fastybird/websockets-library": "workspace:*",
+    "@fastybird/miniserver-core": "workspace:*",
```

(`@fastybird/application` was never a root-level `package.json` dependency — it's consumed only by the Vue SPA shell inside `Core/Application/assets/main.ts` itself, which Task 20 handles.)

- [ ] **Step 8: Commit**

```bash
git add src/FastyBird/Core/Core composer.json package.json
git commit -m "$(cat <<'EOF'
feat(core): scaffold the fastybird/miniserver-core package skeleton

Empty directory tree, composer.json/package.json unioning the 15 merging
packages' dependencies, and root-manifest registration. No code moves yet;
Tasks 2-20 populate this package incrementally.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

The tree does not build yet (root `bin`/`autoload-dev` now point at paths that don't exist, `fastybird/miniserver-core` has no `src/` content, the 32 consumers still require the 15 old packages by name). That is expected — this task's own deliverable is just "the skeleton exists and is well-formed JSON/YAML", verified next.

- [ ] **Step 9: Verify the two new manifests are valid JSON and the skeleton is in place**

```bash
php -r 'json_decode(file_get_contents("src/FastyBird/Core/Core/composer.json"), false, 512, JSON_THROW_ON_ERROR); echo "composer.json OK\n";'
php -r 'json_decode(file_get_contents("src/FastyBird/Core/Core/package.json"), false, 512, JSON_THROW_ON_ERROR); echo "package.json OK\n";'
test -d src/FastyBird/Core/Core/src && test -d src/FastyBird/Core/Core/tests && test -d src/FastyBird/Core/Core/assets && echo "skeleton OK"
```

Expected: three "OK" lines, no PHP parse errors, no `json_decode` exceptions.

---

## Task 2: Create the shared `Core\Exceptions\*` classes

Per D3/D4 plus Flagged Assumptions 2-4. These eight files exist only under the new shared namespace; every one of the 15 packages' own copies is deleted in Tasks 3-17 (not moved — the class itself already exists here). Write these now, before any content-migration task, since every later task's `throw new Exceptions\InvalidArgument(...)` call site needs this class to already exist when its `use` statement is repointed.

**Files:**
- Create: `src/FastyBird/Core/Core/src/Exceptions/Exception.php`
- Create: `src/FastyBird/Core/Core/src/Exceptions/InvalidArgument.php`
- Create: `src/FastyBird/Core/Core/src/Exceptions/InvalidState.php`
- Create: `src/FastyBird/Core/Core/src/Exceptions/Runtime.php`
- Create: `src/FastyBird/Core/Core/src/Exceptions/Logic.php`
- Create: `src/FastyBird/Core/Core/src/Exceptions/UnexpectedValue.php`
- Create: `src/FastyBird/Core/Core/src/Exceptions/MalformedInput.php`
- Create: `src/FastyBird/Core/Core/src/Exceptions/InvalidMapping.php`

**Interfaces:**
- Produces: `FastyBird\Core\Exceptions\Exception` (marker interface, extends `Throwable`), `FastyBird\Core\Exceptions\InvalidArgument` (extends `\RuntimeException`), `FastyBird\Core\Exceptions\InvalidState` (extends `\RuntimeException`), `FastyBird\Core\Exceptions\Runtime` (extends `\RuntimeException`), `FastyBird\Core\Exceptions\Logic` (extends `\LogicException`), `FastyBird\Core\Exceptions\UnexpectedValue` (extends `\UnexpectedValueException`), `FastyBird\Core\Exceptions\MalformedInput` (extends `\RuntimeException`), `FastyBird\Core\Exceptions\InvalidMapping` (extends `FastyBird\Core\Exceptions\InvalidArgument`). Every later task's exception handling imports these.
- Consumes: nothing (this is the first content in the package).

- [ ] **Step 1: Write the marker interface**

`src/FastyBird/Core/Core/src/Exceptions/Exception.php`:

```php
<?php declare(strict_types = 1);

/**
 * Exception.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use Throwable;

interface Exception extends Throwable
{

}
```

This merges 14 identical marker interfaces (one per former package's `Exceptions/Exception.php`, all `interface Exception extends Throwable {}`, plus `Wamp/Exceptions/Exception.php` which the census missed — Flagged Assumption 2).

- [ ] **Step 2: Write `InvalidArgument`**

`src/FastyBird/Core/Core/src/Exceptions/InvalidArgument.php`:

```php
<?php declare(strict_types = 1);

/**
 * InvalidArgument.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException;

class InvalidArgument extends RuntimeException implements Exception
{

}
```

This merges 14 occurrences, all `extends RuntimeException implements Exception`: `Core/Application`, `Core/Exchange` (implied — check during Task 4, not directly read), `Core/SimpleAuth`, `Core/Tools`, `Library/DateTimeFactory`, `Library/DoctrineCrud`, `Library/DoctrineOrmQuery`, `Library/DoctrineTimestampable`, `Library/JsonApi`, `Library/Phone`, `Library/SlimRouter`, `Library/WebSockets`, `Plugin/WebServer`, plus `Wamp/Exceptions/InvalidArgument.php` (which already `extends Exceptions\InvalidArgument` from base WebSockets — a zero-behavior subclass, deleted outright rather than merged, Flagged Assumption 2).

- [ ] **Step 3: Write `InvalidState`**

`src/FastyBird/Core/Core/src/Exceptions/InvalidState.php`:

```php
<?php declare(strict_types = 1);

/**
 * InvalidState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
```

Merges 9 occurrences. Eight (`Core/Application`, `Core/Exchange`, `Core/Tools`, `Library/DoctrineCrud`, `Library/DoctrineOrmQuery`, `Library/JsonApi`, `Plugin/WebServer`, and implicitly `Core/SimpleAuth` via its dependency on this concept) already `extend RuntimeException`. The ninth, `Library/WebSockets/Exceptions/InvalidState.php`, extends bare `\Exception` — confirmed by direct read (Flagged Assumption 3) — and is fixed to the `RuntimeException`-parented shared class here, the same category of fix D4 already applies to JsonApi's `Logic`. This widens what WebSockets' `InvalidState` is catchable by (any existing `catch (\RuntimeException $e)` around WebSockets code now additionally catches it); nothing that worked before stops working.

- [ ] **Step 4: Write `Runtime`**

`src/FastyBird/Core/Core/src/Exceptions/Runtime.php`:

```php
<?php declare(strict_types = 1);

/**
 * Runtime.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException as PHPRuntimeException;

class Runtime extends PHPRuntimeException implements Exception
{

}
```

Merges 5 occurrences (`Core/Application`, `Core/Tools`, `Library/JsonApi`, `Library/SlimRouter`, `Library/WebSockets`), all confirmed `extends RuntimeException` — no parent-mismatch here, uniform.

- [ ] **Step 5: Write `Logic`**

`src/FastyBird/Core/Core/src/Exceptions/Logic.php`:

```php
<?php declare(strict_types = 1);

/**
 * Logic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use LogicException as PHPLogicException;

class Logic extends PHPLogicException implements Exception
{

}
```

Merges 5 occurrences, not 4 (Flagged Assumption 4): `Core/Tools\Exceptions\Logic` and `Plugin/WsServer\Exceptions\Logic` already correctly extend `LogicException`; `Core/SimpleAuth\Exceptions\Logical` also extends `LogicException` (renamed `Logical` → `Logic` here, matching every sibling's naming — D2's "consistent naming for all core files"); `Library/JsonApi\Exceptions\Logic` wrongly extends `RuntimeException` (the bug D4 explicitly names and fixes); `Library/WebSockets\Exceptions\Logic` wrongly extends bare `\Exception` (Flagged Assumption 3, fixed here the same way). Also eliminates `Core\Application\Exceptions\Mapping` per spec §5/§8 resolution 2 — Task 3 Step 6 repoints its ~150 call sites (mostly `@throws` docblocks, 6 real `throw new` sites) at this class.

- [ ] **Step 6: Write `UnexpectedValue`**

`src/FastyBird/Core/Core/src/Exceptions/UnexpectedValue.php`:

```php
<?php declare(strict_types = 1);

/**
 * UnexpectedValue.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use UnexpectedValueException as PHPUnexpectedValueException;

class UnexpectedValue extends PHPUnexpectedValueException implements Exception
{

}
```

Merges 2 occurrences (`Library/DoctrineTimestampable`, `Library/WebSockets`), both confirmed `extends UnexpectedValueException` — uniform, no fix needed.

- [ ] **Step 7: Write `MalformedInput`**

`src/FastyBird/Core/Core/src/Exceptions/MalformedInput.php`:

```php
<?php declare(strict_types = 1);

/**
 * MalformedInput.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException;

class MalformedInput extends RuntimeException implements Exception
{

}
```

Merges 2 occurrences (`Core/Application`, `Core/Tools`), both confirmed `extends RuntimeException` — uniform.

- [ ] **Step 8: Write `InvalidMapping`**

`src/FastyBird/Core/Core/src/Exceptions/InvalidMapping.php`:

```php
<?php declare(strict_types = 1);

/**
 * InvalidMapping.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\Exceptions;

class InvalidMapping extends InvalidArgument implements Exception
{

}
```

Merges `Core/SimpleAuth\Exceptions\InvalidMapping` and `Library/DoctrineTimestampable\Exceptions\InvalidMapping`, confirmed identical (`class InvalidMapping extends InvalidArgument implements Exception {}` in both, only the docblock header text differs — `diff`'d directly). Note this subclasses the shared `InvalidArgument` from Step 2 within the same namespace, no `use` import needed.

- [ ] **Step 9: Verify the eight files parse and autoload**

```bash
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/Exception.php
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/InvalidArgument.php
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/InvalidState.php
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/Runtime.php
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/Logic.php
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/UnexpectedValue.php
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/MalformedInput.php
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/Exceptions/InvalidMapping.php
```

Expected: `No syntax errors detected` for all eight. Full autoload verification (does `FastyBird\Core\Exceptions\InvalidMapping` actually resolve to `FastyBird\Core\Exceptions\InvalidArgument` as its parent at runtime) happens in Task 27's Reflection sweep, once the package has a working `composer.json` autoload entry installed into `vendor/`.

- [ ] **Step 10: Commit**

```bash
git add src/FastyBird/Core/Core/src/Exceptions
git commit -m "$(cat <<'EOF'
feat(core): create the shared Core\Exceptions classes

Merges 14 identical marker interfaces and the 5 SPL-parent-matched exception
families (InvalidArgument, InvalidState, Runtime, Logic, UnexpectedValue,
MalformedInput) plus InvalidMapping into one shared class each, per D3/D4.
Also fixes two more wrong-parent bugs the spec's own census missed (WebSockets'
InvalidState and Logic both wrongly extended bare Exception) and renames
SimpleAuth's Logical to Logic for naming consistency with its four siblings.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Migrate `Core/Application` content

**Files:** every file under `src/FastyBird/Core/Application/` relocates or is deleted; none stay in place. Full mapping (bucket → new path, `Boot` and the deleted `Exceptions`/`DI` files are the only ones that don't follow `<Type>\Application`):

| Old (`src/FastyBird/Core/Application/src/...`) | New (`src/FastyBird/Core/Core/src/...`) | Namespace change |
|---|---|---|
| `Boot/{Bootstrap,Configurator}.php` | `Boot/{Bootstrap,Configurator}.php` | `...Application\Boot` → `FastyBird\Core\Boot` (D5, no domain tag) |
| `Caching/{MemoryAdapterStorage,MemoryStorage}.php` | `Caching/Application/{...}.php` | `...Application\Caching` → `FastyBird\Core\Caching\Application` |
| `Documents/*.php` (8 top-level files) | `Documents/Application/*.php` | `...Application\Documents` → `FastyBird\Core\Documents\Application` |
| `Documents/Mapping/*.php` (9 files) | `Documents/Application/Mapping/*.php` | `...Application\Documents\Mapping` → `FastyBird\Core\Documents\Application\Mapping` |
| `Documents/Mapping/Driver/*.php` (4 files) | `Documents/Application/Mapping/Driver/*.php` | `...Application\Documents\Mapping\Driver` → `FastyBird\Core\Documents\Application\Mapping\Driver` |
| `Entities/Mapping/DiscriminatorEntry.php` | `Entities/Application/Mapping/DiscriminatorEntry.php` | `...Application\Entities\Mapping` → `FastyBird\Core\Entities\Application\Mapping` |
| `EventLoop/{Status,Wrapper}.php` | `EventLoop/Application/{...}.php` | `...Application\EventLoop` → `FastyBird\Core\EventLoop\Application` |
| `Events/*.php` (6 files) | `Events/Application/*.php` | `...Application\Events` → `FastyBird\Core\Events\Application` |
| `ObjectMapper/Rules/*.php` (3 files) | `Persistence/Application/Rules/*.php` | `...Application\ObjectMapper\Rules` → `FastyBird\Core\Persistence\Application\Rules` |
| `Presenters/*.php` (2 files) | `Presenters/Application/*.php` | `...Application\Presenters` → `FastyBird\Core\Presenters\Application` |
| `Router/AppRouter.php` | `Routing/Application/AppRouter.php` | `...Application\Router` → `FastyBird\Core\Routing\Application` |
| `Subscribers/*.php` (3 files) | `Subscribers/Application/*.php` | `...Application\Subscribers` → `FastyBird\Core\Subscribers\Application` |
| `Translations/application.en_us.neon` | `Translations/Application/application.en_us.neon` | n/a (no PHP namespace) |
| `UI/TemplateFactory.php` | `UI/Application/TemplateFactory.php` | `...Application\UI` → `FastyBird\Core\UI\Application` |
| `DI/ApplicationExtension.php` | *(deleted — folded into `Core\DI\CoreExtension`, Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState,MalformedInput,Runtime}.php` | *(deleted — merged, Task 2)* | — |
| `Exceptions/Mapping.php` | *(deleted — eliminated, spec §5/§8)* | — |

Non-`src/` content:

| Old | New |
|---|---|
| `bin/{fb-console,fb-console.php,fb-supervisor,fb-supervisor.php}` | `bin/{...}` (unchanged names) |
| `config/{common,defaults}.neon` | `config/{common,defaults}.neon` (content rewritten in Task 18) |
| `favicon.ico` | `favicon.ico` |
| `migrations/.gitignore` | `migrations/.gitignore` |
| `templates/@layout.latte`, `templates/presenters/default.latte` | same relative paths |
| `tests/cases/unit/BaseTestCase.php` | `tests/cases/unit/BaseTestCase.php` |
| `tests/cases/unit/Boot/BootstrapConfigFilesTest.php` | `tests/cases/unit/Boot/BootstrapConfigFilesTest.php` |
| `tests/cases/unit/DI/ApplicationExtensionTest.php` | *(held — merged into `CoreExtensionTest.php` in Task 18)* |
| `tests/cases/unit/Documents/DocumentTest.php` | `tests/cases/unit/Documents/DocumentTest.php` |
| `tests/common.neon` | `tests/common.neon` (content rewritten in Task 18) |
| `tests/fixtures/Documents/*.json` (4 files) | same relative paths |
| `tests/fixtures/dummy/*.php` (3 files) | same relative paths |
| `tests/policy.csv` | `tests/policy.csv` |
| `tests/tools/{ConnectionWrapper,JsonAssert}.php` | same relative paths |
| `.gitignore`, `README.md`, `docs/Home.md` | *(deleted — already merged into the package-level files in Task 1)* |

**Interfaces:**
- Consumes: `FastyBird\Core\Exceptions\{Exception,InvalidArgument,InvalidState,Logic}` from Task 2.
- Produces: `FastyBird\Core\Boot\{Bootstrap,Configurator}`, `FastyBird\Core\Caching\Application\{MemoryAdapterStorage,MemoryStorage}`, `FastyBird\Core\Documents\Application\{Document,DocumentFactory,CreatedAt,Owner,UpdatedAt,TCreatedAt,TOwner,TUpdatedAt,Mapping\ClassMetadata,Mapping\ClassMetadataFactory,Mapping\Driver\AttributeDriver,...}`, `FastyBird\Core\Entities\Application\Mapping\DiscriminatorEntry`, `FastyBird\Core\EventLoop\Application\{Status,Wrapper}`, `FastyBird\Core\Events\Application\{EventLoopStarted,EventLoopStopped,EventLoopStopping,LoadClassMetadata,PostLoad,PreLoad}`, `FastyBird\Core\Persistence\Application\Rules\{UuidArgs,UuidRule,UuidValue}`, `FastyBird\Core\Presenters\Application\{BasePresenter,DefaultPresenter}`, `FastyBird\Core\Routing\Application\AppRouter`, `FastyBird\Core\Subscribers\Application\{Console,EntityDiscriminator,EventLoopLifeCycle}`, `FastyBird\Core\UI\Application\TemplateFactory`. All later tasks' `use FastyBird\Core\Application\...` rewrites (Task 23) target these exact names.

- [ ] **Step 1: Create target directories**

```bash
mkdir -p src/FastyBird/Core/Core/src/Boot
mkdir -p src/FastyBird/Core/Core/src/Caching/Application
mkdir -p src/FastyBird/Core/Core/src/Documents/Application/Mapping/Driver
mkdir -p src/FastyBird/Core/Core/src/Entities/Application/Mapping
mkdir -p src/FastyBird/Core/Core/src/EventLoop/Application
mkdir -p src/FastyBird/Core/Core/src/Events/Application
mkdir -p src/FastyBird/Core/Core/src/Persistence/Application/Rules
mkdir -p src/FastyBird/Core/Core/src/Presenters/Application
mkdir -p src/FastyBird/Core/Core/src/Routing/Application
mkdir -p src/FastyBird/Core/Core/src/Subscribers/Application
mkdir -p src/FastyBird/Core/Core/src/Translations/Application
mkdir -p src/FastyBird/Core/Core/src/UI/Application
```

- [ ] **Step 2: `git mv` every file per the table above**

```bash
A=src/FastyBird/Core/Application/src
C=src/FastyBird/Core/Core/src

git mv $A/Boot/Bootstrap.php $C/Boot/Bootstrap.php
git mv $A/Boot/Configurator.php $C/Boot/Configurator.php

git mv $A/Caching/MemoryAdapterStorage.php $C/Caching/Application/MemoryAdapterStorage.php
git mv $A/Caching/MemoryStorage.php $C/Caching/Application/MemoryStorage.php

git mv $A/Documents/CreatedAt.php $C/Documents/Application/CreatedAt.php
git mv $A/Documents/Document.php $C/Documents/Application/Document.php
git mv $A/Documents/DocumentFactory.php $C/Documents/Application/DocumentFactory.php
git mv $A/Documents/Owner.php $C/Documents/Application/Owner.php
git mv $A/Documents/TCreatedAt.php $C/Documents/Application/TCreatedAt.php
git mv $A/Documents/TOwner.php $C/Documents/Application/TOwner.php
git mv $A/Documents/TUpdatedAt.php $C/Documents/Application/TUpdatedAt.php
git mv $A/Documents/UpdatedAt.php $C/Documents/Application/UpdatedAt.php
git mv $A/Documents/Mapping/ClassMetadata.php $C/Documents/Application/Mapping/ClassMetadata.php
git mv $A/Documents/Mapping/ClassMetadataFactory.php $C/Documents/Application/Mapping/ClassMetadataFactory.php
git mv $A/Documents/Mapping/DiscriminatorColumn.php $C/Documents/Application/Mapping/DiscriminatorColumn.php
git mv $A/Documents/Mapping/DiscriminatorEntry.php $C/Documents/Application/Mapping/DiscriminatorEntry.php
git mv $A/Documents/Mapping/DiscriminatorMap.php $C/Documents/Application/Mapping/DiscriminatorMap.php
git mv $A/Documents/Mapping/Document.php $C/Documents/Application/Mapping/Document.php
git mv $A/Documents/Mapping/InheritanceType.php $C/Documents/Application/Mapping/InheritanceType.php
git mv $A/Documents/Mapping/MappedSuperclass.php $C/Documents/Application/Mapping/MappedSuperclass.php
git mv $A/Documents/Mapping/MappingAttribute.php $C/Documents/Application/Mapping/MappingAttribute.php
git mv $A/Documents/Mapping/Driver/AttributeDriver.php $C/Documents/Application/Mapping/Driver/AttributeDriver.php
git mv $A/Documents/Mapping/Driver/AttributeReader.php $C/Documents/Application/Mapping/Driver/AttributeReader.php
git mv $A/Documents/Mapping/Driver/MappingDriver.php $C/Documents/Application/Mapping/Driver/MappingDriver.php
git mv $A/Documents/Mapping/Driver/MappingDriverChain.php $C/Documents/Application/Mapping/Driver/MappingDriverChain.php

git mv $A/Entities/Mapping/DiscriminatorEntry.php $C/Entities/Application/Mapping/DiscriminatorEntry.php

git mv $A/EventLoop/Status.php $C/EventLoop/Application/Status.php
git mv $A/EventLoop/Wrapper.php $C/EventLoop/Application/Wrapper.php

git mv $A/Events/EventLoopStarted.php $C/Events/Application/EventLoopStarted.php
git mv $A/Events/EventLoopStopped.php $C/Events/Application/EventLoopStopped.php
git mv $A/Events/EventLoopStopping.php $C/Events/Application/EventLoopStopping.php
git mv $A/Events/LoadClassMetadata.php $C/Events/Application/LoadClassMetadata.php
git mv $A/Events/PostLoad.php $C/Events/Application/PostLoad.php
git mv $A/Events/PreLoad.php $C/Events/Application/PreLoad.php

git mv $A/ObjectMapper/Rules/UuidArgs.php $C/Persistence/Application/Rules/UuidArgs.php
git mv $A/ObjectMapper/Rules/UuidRule.php $C/Persistence/Application/Rules/UuidRule.php
git mv $A/ObjectMapper/Rules/UuidValue.php $C/Persistence/Application/Rules/UuidValue.php

git mv $A/Presenters/BasePresenter.php $C/Presenters/Application/BasePresenter.php
git mv $A/Presenters/DefaultPresenter.php $C/Presenters/Application/DefaultPresenter.php

git mv $A/Router/AppRouter.php $C/Routing/Application/AppRouter.php

git mv $A/Subscribers/Console.php $C/Subscribers/Application/Console.php
git mv $A/Subscribers/EntityDiscriminator.php $C/Subscribers/Application/EntityDiscriminator.php
git mv $A/Subscribers/EventLoopLifeCycle.php $C/Subscribers/Application/EventLoopLifeCycle.php

git mv $A/Translations/application.en_us.neon $C/Translations/Application/application.en_us.neon

git mv $A/UI/TemplateFactory.php $C/UI/Application/TemplateFactory.php

git rm $A/DI/ApplicationExtension.php
git rm $A/Exceptions/Exception.php $A/Exceptions/InvalidArgument.php $A/Exceptions/InvalidState.php \
       $A/Exceptions/MalformedInput.php $A/Exceptions/Runtime.php $A/Exceptions/Mapping.php

git mv src/FastyBird/Core/Application/bin/fb-console src/FastyBird/Core/Core/bin/fb-console
git mv src/FastyBird/Core/Application/bin/fb-console.php src/FastyBird/Core/Core/bin/fb-console.php
git mv src/FastyBird/Core/Application/bin/fb-supervisor src/FastyBird/Core/Core/bin/fb-supervisor
git mv src/FastyBird/Core/Application/bin/fb-supervisor.php src/FastyBird/Core/Core/bin/fb-supervisor.php
git mv src/FastyBird/Core/Application/config/common.neon src/FastyBird/Core/Core/config/common.neon
git mv src/FastyBird/Core/Application/config/defaults.neon src/FastyBird/Core/Core/config/defaults.neon
git mv src/FastyBird/Core/Application/favicon.ico src/FastyBird/Core/Core/favicon.ico
git mv src/FastyBird/Core/Application/migrations/.gitignore src/FastyBird/Core/Core/migrations/.gitignore
git mv src/FastyBird/Core/Application/templates/@layout.latte "src/FastyBird/Core/Core/templates/@layout.latte"
git mv src/FastyBird/Core/Application/templates/presenters/default.latte src/FastyBird/Core/Core/templates/presenters/default.latte

mkdir -p src/FastyBird/Core/Core/tests/cases/unit/Boot
mkdir -p src/FastyBird/Core/Core/tests/cases/unit/Documents
mkdir -p src/FastyBird/Core/Core/tests/fixtures/Documents
git mv src/FastyBird/Core/Application/tests/cases/unit/BaseTestCase.php src/FastyBird/Core/Core/tests/cases/unit/BaseTestCase.php
git mv src/FastyBird/Core/Application/tests/cases/unit/Boot/BootstrapConfigFilesTest.php src/FastyBird/Core/Core/tests/cases/unit/Boot/BootstrapConfigFilesTest.php
git mv src/FastyBird/Core/Application/tests/cases/unit/Documents/DocumentTest.php src/FastyBird/Core/Core/tests/cases/unit/Documents/DocumentTest.php
git rm src/FastyBird/Core/Application/tests/cases/unit/DI/ApplicationExtensionTest.php
git mv src/FastyBird/Core/Application/tests/common.neon src/FastyBird/Core/Core/tests/common.neon
git mv src/FastyBird/Core/Application/tests/fixtures/Documents/dummy.one.json src/FastyBird/Core/Core/tests/fixtures/Documents/dummy.one.json
git mv src/FastyBird/Core/Application/tests/fixtures/Documents/dummy.two.json src/FastyBird/Core/Core/tests/fixtures/Documents/dummy.two.json
git mv src/FastyBird/Core/Application/tests/fixtures/Documents/dummy.two.mismatch.json src/FastyBird/Core/Core/tests/fixtures/Documents/dummy.two.mismatch.json
git mv src/FastyBird/Core/Application/tests/fixtures/Documents/dummy.two.missing.json src/FastyBird/Core/Core/tests/fixtures/Documents/dummy.two.missing.json
git mv src/FastyBird/Core/Application/tests/fixtures/dummy/DummyDocument.php src/FastyBird/Core/Core/tests/fixtures/dummy/DummyDocument.php
git mv src/FastyBird/Core/Application/tests/fixtures/dummy/DummyOneDocument.php src/FastyBird/Core/Core/tests/fixtures/dummy/DummyOneDocument.php
git mv src/FastyBird/Core/Application/tests/fixtures/dummy/DummyTwoDocument.php src/FastyBird/Core/Core/tests/fixtures/dummy/DummyTwoDocument.php
git mv src/FastyBird/Core/Application/tests/policy.csv src/FastyBird/Core/Core/tests/policy.csv
git mv src/FastyBird/Core/Application/tests/tools/ConnectionWrapper.php src/FastyBird/Core/Core/tests/tools/ConnectionWrapper.php
git mv src/FastyBird/Core/Application/tests/tools/JsonAssert.php src/FastyBird/Core/Core/tests/tools/JsonAssert.php

git rm src/FastyBird/Core/Application/.gitignore src/FastyBird/Core/Application/README.md src/FastyBird/Core/Application/docs/Home.md
```

- [ ] **Step 3: Rewrite namespace declarations and `use` imports in every moved file**

Run from the repo root, over every file just moved under `src/FastyBird/Core/Core/src/{Boot,Caching,Documents,Entities,EventLoop,Events,Persistence,Presenters,Routing,Subscribers,UI}/**` plus the test files under `src/FastyBird/Core/Core/tests/**` that came from `Core/Application`:

```bash
FILES=$(git diff --name-only --cached -- 'src/FastyBird/Core/Core/src/Boot/*' \
  'src/FastyBird/Core/Core/src/Caching/Application/*' \
  'src/FastyBird/Core/Core/src/Documents/Application/*' \
  'src/FastyBird/Core/Core/src/Entities/Application/*' \
  'src/FastyBird/Core/Core/src/EventLoop/Application/*' \
  'src/FastyBird/Core/Core/src/Events/Application/*' \
  'src/FastyBird/Core/Core/src/Persistence/Application/*' \
  'src/FastyBird/Core/Core/src/Presenters/Application/*' \
  'src/FastyBird/Core/Core/src/Routing/Application/*' \
  'src/FastyBird/Core/Core/src/Subscribers/Application/*' \
  'src/FastyBird/Core/Core/src/UI/Application/*' \
  'src/FastyBird/Core/Core/tests/*')

for f in $FILES; do
  [ -f "$f" ] || continue
  case "$f" in *.php)
    sed -i -E \
      -e 's#FastyBird\\Core\\Application\\Boot#FastyBird\\Core\\Boot#g' \
      -e 's#FastyBird\\Core\\Application\\Caching#FastyBird\\Core\\Caching\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\Documents\\Mapping\\Driver#FastyBird\\Core\\Documents\\Application\\Mapping\\Driver#g' \
      -e 's#FastyBird\\Core\\Application\\Documents\\Mapping#FastyBird\\Core\\Documents\\Application\\Mapping#g' \
      -e 's#FastyBird\\Core\\Application\\Documents#FastyBird\\Core\\Documents\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\Entities\\Mapping#FastyBird\\Core\\Entities\\Application\\Mapping#g' \
      -e 's#FastyBird\\Core\\Application\\EventLoop#FastyBird\\Core\\EventLoop\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\Events#FastyBird\\Core\\Events\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\ObjectMapper\\Rules#FastyBird\\Core\\Persistence\\Application\\Rules#g' \
      -e 's#FastyBird\\Core\\Application\\Presenters#FastyBird\\Core\\Presenters\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\Router#FastyBird\\Core\\Routing\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\Subscribers#FastyBird\\Core\\Subscribers\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\UI#FastyBird\\Core\\UI\\Application#g' \
      -e 's#FastyBird\\Core\\Application\\Exceptions#FastyBird\\Core\\Exceptions#g' \
      -e 's#FastyBird\\Core\\Application\\Tests#FastyBird\\Core\\Tests#g' \
      "$f"
    ;;
  esac
done
```

- [ ] **Step 4: Repoint the eliminated `Exceptions\Mapping` call sites (this file only)**

`FastyBird\Core\Documents\Application\Mapping\{ClassMetadataFactory,Driver\AttributeDriver,Driver\MappingDriverChain}.php` and `Mapping\ClassMetadata.php` are the only files with real `throw new Exceptions\Mapping(...)` sites (6 total; the rest of the ~150 references found repo-wide are `@throws` PHPDoc, handled per-consumer in Task 23). After Step 3's sed has already turned `use FastyBird\Core\Application\Exceptions;` into `use FastyBird\Core\Exceptions;` in these files, the class name itself still needs the symbol rename (the sed above renames the *import*, not the *symbol* `Mapping` to `Logic`):

```bash
sed -i -E 's/Exceptions\\Mapping\b/Exceptions\\Logic/g' \
  src/FastyBird/Core/Core/src/Documents/Application/Mapping/ClassMetadataFactory.php \
  src/FastyBird/Core/Core/src/Documents/Application/Mapping/ClassMetadata.php \
  src/FastyBird/Core/Core/src/Documents/Application/Mapping/Driver/AttributeDriver.php \
  src/FastyBird/Core/Core/src/Documents/Application/Mapping/Driver/MappingDriverChain.php \
  src/FastyBird/Core/Core/tests/cases/unit/Documents/DocumentTest.php
```

- [ ] **Step 5: Verify every moved PHP file still parses**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Boot src/FastyBird/Core/Core/src/Caching \
  src/FastyBird/Core/Core/src/Documents/Application src/FastyBird/Core/Core/src/Entities/Application \
  src/FastyBird/Core/Core/src/EventLoop/Application src/FastyBird/Core/Core/src/Events/Application \
  src/FastyBird/Core/Core/src/Persistence/Application src/FastyBird/Core/Core/src/Presenters/Application \
  src/FastyBird/Core/Core/src/Routing/Application src/FastyBird/Core/Core/src/Subscribers/Application \
  src/FastyBird/Core/Core/src/UI/Application -name "*.php"); do
  php -l "$f" || exit 1
done
echo "all Core/Application-derived files parse clean"
'
```

Expected: every `php -l` line reads `No syntax errors detected`, final line prints. This does not yet verify the classes autoload correctly under their new names (no `composer.json` `psr-4` mapping change has been installed into `vendor/` yet) — that is Task 27's whole-repo Reflection sweep, after every package has moved and `rm -rf vendor/fastybird && composer install` has run.

- [ ] **Step 6: Confirm no `FastyBird\Core\Application\` references remain in the moved tree**

```bash
grep -rn 'FastyBird\\Core\\Application\\' src/FastyBird/Core/Core/src src/FastyBird/Core/Core/tests
```

Expected: no output. (Consumers outside `Core/Core` still reference the old namespace at this point in the plan — that's Task 23's job.)

- [ ] **Step 7: Commit**

```bash
git add -A src/FastyBird/Core/Core src/FastyBird/Core/Application
git commit -m "$(cat <<'EOF'
refactor(core): migrate Core/Application content into fastybird/miniserver-core

Relocates every src/ folder to its type-first bucket under Core\<Type>\Application
(Boot is the D5 exception with no domain tag), deletes the five exception
classes merged into Core\Exceptions in the prior commit, deletes
Exceptions\Mapping per spec section 5, and deletes DI\ApplicationExtension
(folded into Core\DI\CoreExtension in a later commit).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

Note: `src/FastyBird/Core/Application/` still contains `composer.json`, `package.json`, and `assets/` after this commit — those are handled by Task 20 (JS) and Task 27 (final cleanup deletes the now-empty former-package directories once every task has drained them).

---

## Task 4: Migrate `Core/Exchange` content

**Files:** mapping table (all under `src/FastyBird/Core/Exchange/src/` → `src/FastyBird/Core/Core/src/`):

| Old | New | Namespace change |
|---|---|---|
| `Consumers/{Consumer,Container,Info}.php` | `Messaging/Exchange/Consumers/{...}.php` | `...Exchange\Consumers` → `FastyBird\Core\Messaging\Exchange\Consumers` |
| `Documents/DocumentFactory.php` | `Documents/Exchange/DocumentFactory.php` | `...Exchange\Documents` → `FastyBird\Core\Documents\Exchange` |
| `Documents/Mapping/{MappingAttribute,RoutingMap}.php` | `Documents/Exchange/Mapping/{...}.php` | `...Exchange\Documents\Mapping` → `FastyBird\Core\Documents\Exchange\Mapping` |
| `Documents/Mapping/Driver/AttributeReader.php` | `Documents/Exchange/Mapping/Driver/AttributeReader.php` | `...Exchange\Documents\Mapping\Driver` → `FastyBird\Core\Documents\Exchange\Mapping\Driver` |
| `Events/{AfterMessageConsumed,AfterMessagePublished,BeforeMessageConsumed,BeforeMessagePublished,ExchangeError}.php` | `Events/Exchange/{...}.php` | `...Exchange\Events` → `FastyBird\Core\Events\Exchange` |
| `Exchange/Factory.php` | `Messaging/Exchange/Factory.php` | `...Exchange\Exchange` → `FastyBird\Core\Messaging\Exchange` (self-named-folder resolution, spec §8.1) |
| `Publisher/{Container,Publisher}.php` | `Messaging/Exchange/Publisher/{...}.php` | `...Exchange\Publisher` → `FastyBird\Core\Messaging\Exchange\Publisher` |
| `Publisher/Async/{Container,Publisher}.php` | `Messaging/Exchange/Publisher/Async/{...}.php` | `...Exchange\Publisher\Async` → `FastyBird\Core\Messaging\Exchange\Publisher\Async` |
| `DI/ExchangeExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState}.php` | *(deleted — merged, Task 2)* | — |
| `tests/cases/unit/BaseTestCase.php` | `tests/cases/unit/BaseTestCase.php` (only if not already present from Task 3 — compare and keep the richer one, they're both minimal PHPUnit `TestCase` base classes; diff first) | — |
| `tests/cases/unit/DI/ExchangeExtensionTest.php` | *(held — merged into `CoreExtensionTest.php`, Task 18)* | — |
| `tests/common.neon` | *(content folded into the single `tests/common.neon` from Task 3 by Task 18 — delete this copy)* | — |

**Interfaces:**
- Consumes: `FastyBird\Core\Exceptions\{Exception,InvalidArgument,InvalidState}` (Task 2).
- Produces: `FastyBird\Core\Messaging\Exchange\{Factory,Consumers\Consumer,Consumers\Container,Consumers\Info,Publisher\Container,Publisher\Publisher,Publisher\Async\Container,Publisher\Async\Publisher}`, `FastyBird\Core\Documents\Exchange\{DocumentFactory,Mapping\MappingAttribute,Mapping\RoutingMap,Mapping\Driver\AttributeReader}`, `FastyBird\Core\Events\Exchange\{AfterMessageConsumed,AfterMessagePublished,BeforeMessageConsumed,BeforeMessagePublished,ExchangeError}`.

- [ ] **Step 1: Create directories and `git mv`**

```bash
E=src/FastyBird/Core/Exchange/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Messaging/Exchange/Consumers $C/Messaging/Exchange/Publisher/Async
mkdir -p $C/Documents/Exchange/Mapping/Driver
mkdir -p $C/Events/Exchange

git mv $E/Consumers/Consumer.php $C/Messaging/Exchange/Consumers/Consumer.php
git mv $E/Consumers/Container.php $C/Messaging/Exchange/Consumers/Container.php
git mv $E/Consumers/Info.php $C/Messaging/Exchange/Consumers/Info.php
git mv $E/Documents/DocumentFactory.php $C/Documents/Exchange/DocumentFactory.php
git mv $E/Documents/Mapping/MappingAttribute.php $C/Documents/Exchange/Mapping/MappingAttribute.php
git mv $E/Documents/Mapping/RoutingMap.php $C/Documents/Exchange/Mapping/RoutingMap.php
git mv $E/Documents/Mapping/Driver/AttributeReader.php $C/Documents/Exchange/Mapping/Driver/AttributeReader.php
git mv $E/Events/AfterMessageConsumed.php $C/Events/Exchange/AfterMessageConsumed.php
git mv $E/Events/AfterMessagePublished.php $C/Events/Exchange/AfterMessagePublished.php
git mv $E/Events/BeforeMessageConsumed.php $C/Events/Exchange/BeforeMessageConsumed.php
git mv $E/Events/BeforeMessagePublished.php $C/Events/Exchange/BeforeMessagePublished.php
git mv $E/Events/ExchangeError.php $C/Events/Exchange/ExchangeError.php
git mv $E/Exchange/Factory.php $C/Messaging/Exchange/Factory.php
git mv $E/Publisher/Container.php $C/Messaging/Exchange/Publisher/Container.php
git mv $E/Publisher/Publisher.php $C/Messaging/Exchange/Publisher/Publisher.php
git mv $E/Publisher/Async/Container.php $C/Messaging/Exchange/Publisher/Async/Container.php
git mv $E/Publisher/Async/Publisher.php $C/Messaging/Exchange/Publisher/Async/Publisher.php

git rm $E/DI/ExchangeExtension.php
git rm $E/Exceptions/Exception.php $E/Exceptions/InvalidArgument.php $E/Exceptions/InvalidState.php
git rm src/FastyBird/Core/Exchange/tests/cases/unit/DI/ExchangeExtensionTest.php
git rm src/FastyBird/Core/Exchange/tests/common.neon
diff src/FastyBird/Core/Exchange/tests/cases/unit/BaseTestCase.php src/FastyBird/Core/Core/tests/cases/unit/BaseTestCase.php \
  && git rm src/FastyBird/Core/Exchange/tests/cases/unit/BaseTestCase.php \
  || echo "REVIEW: Exchange's BaseTestCase.php differs from Application's — read both, keep the union, then git rm the Exchange one"
git rm src/FastyBird/Core/Exchange/README.md src/FastyBird/Core/Exchange/docs/Home.md
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Messaging/Exchange src/FastyBird/Core/Core/src/Documents/Exchange \
     src/FastyBird/Core/Core/src/Events/Exchange -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Core\\Exchange\\Consumers#FastyBird\\Core\\Messaging\\Exchange\\Consumers#g' \
    -e 's#FastyBird\\Core\\Exchange\\Documents\\Mapping\\Driver#FastyBird\\Core\\Documents\\Exchange\\Mapping\\Driver#g' \
    -e 's#FastyBird\\Core\\Exchange\\Documents\\Mapping#FastyBird\\Core\\Documents\\Exchange\\Mapping#g' \
    -e 's#FastyBird\\Core\\Exchange\\Documents#FastyBird\\Core\\Documents\\Exchange#g' \
    -e 's#FastyBird\\Core\\Exchange\\Events#FastyBird\\Core\\Events\\Exchange#g' \
    -e 's#FastyBird\\Core\\Exchange\\Exchange#FastyBird\\Core\\Messaging\\Exchange#g' \
    -e 's#FastyBird\\Core\\Exchange\\Publisher\\Async#FastyBird\\Core\\Messaging\\Exchange\\Publisher\\Async#g' \
    -e 's#FastyBird\\Core\\Exchange\\Publisher#FastyBird\\Core\\Messaging\\Exchange\\Publisher#g' \
    -e 's#FastyBird\\Core\\Exchange\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    -e 's#FastyBird\\Core\\Application\\Boot#FastyBird\\Core\\Boot#g' \
    "$f"
done
```

The last rule matters here specifically: `ExchangeExtension.php`'s `register()` method imported `FastyBird\Core\Application\Boot as ApplicationBoot` — but that file is deleted in this task (folded into `CoreExtension`, Task 18), so check whether any *surviving* moved file (e.g. `Messaging\Exchange\Factory.php`) also references `Application\Boot`; if `grep` in Step 3 finds none, this rule is a no-op and that's fine.

- [ ] **Step 3: Verify and confirm no stale references**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Messaging/Exchange src/FastyBird/Core/Core/src/Documents/Exchange src/FastyBird/Core/Core/src/Events/Exchange -name "*.php"); do php -l "$f" || exit 1; done
echo clean
'
grep -rn 'FastyBird\\Core\\Exchange\\' src/FastyBird/Core/Core/src
```

Expected: `clean`, then no grep output.

- [ ] **Step 4: Commit**

```bash
git add -A src/FastyBird/Core/Core src/FastyBird/Core/Exchange
git commit -m "$(cat <<'EOF'
refactor(core): migrate Core/Exchange content into fastybird/miniserver-core

Consumers/Publisher/Exchange\Factory relocate to a new Messaging\Exchange
bucket (spec section 8 resolution 5), Documents/Events relocate to the
generic Documents/Events buckets tagged Exchange, exceptions merge into
the shared Core\Exceptions.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Migrate `Core/SimpleAuth` content

**Files:** mapping table (all under `src/FastyBird/Core/SimpleAuth/src/` → `src/FastyBird/Core/Core/src/`):

| Old | New | Namespace change |
|---|---|---|
| `Access/{AnnotationChecker,CheckRequirements,Checker,LatteChecker,LinkChecker}.php` | `Security/SimpleAuth/Access/{...}.php` | `...SimpleAuth\Access` → `FastyBird\Core\Security\SimpleAuth\Access` |
| `Application/TSimpleAuth.php` | `Presenters/SimpleAuth/TSimpleAuth.php` | `...SimpleAuth\Application` → `FastyBird\Core\Presenters\SimpleAuth` (Flagged Assumption 8) |
| `Auth.php` | `Services/SimpleAuth/Auth.php` | `FastyBird\Core\SimpleAuth` → `FastyBird\Core\Services\SimpleAuth` (Flagged Assumption 7) |
| `Configuration.php` | `Configuration/SimpleAuth/Configuration.php` | `FastyBird\Core\SimpleAuth` → `FastyBird\Core\Configuration\SimpleAuth` (Flagged Assumption 7) |
| `Constants.php` | `Constants/SimpleAuth/Constants.php` | `FastyBird\Core\SimpleAuth` → `FastyBird\Core\Constants\SimpleAuth` (Flagged Assumption 7) |
| `Entities/{Owner,TOwner}.php`, `Entities/Policies/Policy.php`, `Entities/Tokens/Token.php` | `Entities/SimpleAuth/{...}.php` (preserve `Policies/`, `Tokens/` subfolders) | `...SimpleAuth\Entities` → `FastyBird\Core\Entities\SimpleAuth` |
| `Events/{Request,Response}.php` | `Events/SimpleAuth/{...}.php` | `...SimpleAuth\Events` → `FastyBird\Core\Events\SimpleAuth` |
| `Latte/AccessExtension.php`, `Latte/Nodes/*.php` (3 files) | `Latte/SimpleAuth/{...}.php` (preserve `Nodes/`) | `...SimpleAuth\Latte` → `FastyBird\Core\Latte\SimpleAuth` |
| `Mapping/Attribute/Owner.php`, `Mapping/Driver/Owner.php` | `Mapping/SimpleAuth/{Attribute,Driver}/Owner.php` | `...SimpleAuth\Mapping` → `FastyBird\Core\Mapping\SimpleAuth` |
| `Middleware/{Authorization,User}.php` | `Middleware/SimpleAuth/{...}.php` | `...SimpleAuth\Middleware` → `FastyBird\Core\Middleware\SimpleAuth` |
| `Models/Casbin/{Adapter,Filter}.php`, `Models/Policies/{Manager,Repository}.php`, `Models/Tokens/{Manager,Repository}.php` | `Persistence/SimpleAuth/Models/{Casbin,Policies,Tokens}/{...}.php` | `...SimpleAuth\Models` → `FastyBird\Core\Persistence\SimpleAuth\Models` |
| `Queries/{FindPolicies,FindTokens}.php` | `Persistence/SimpleAuth/Queries/{...}.php` | `...SimpleAuth\Queries` → `FastyBird\Core\Persistence\SimpleAuth\Queries` |
| `Security/{EnforcerFactory,IAuthenticator,IIdentity,IIdentityFactory,IUserStorage,IdentityFactory,PlainIdentity,TokenBuilder,TokenReader,TokenValidator,User,UserStorage}.php` | `Security/SimpleAuth/{...}.php` | `...SimpleAuth\Security` → `FastyBird\Core\Security\SimpleAuth` |
| `Subscribers/{Application,Policy,User}.php` | `Subscribers/SimpleAuth/{...}.php` | `...SimpleAuth\Subscribers` → `FastyBird\Core\Subscribers\SimpleAuth` |
| `Types/{PolicyType,TokenState}.php` | `Types/SimpleAuth/{...}.php` | `...SimpleAuth\Types` → `FastyBird\Core\Types\SimpleAuth` |
| `DI/SimpleAuthExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState}.php` | *(deleted — merged, Task 2)* | — |
| `Exceptions/Logical.php` | *(deleted — merged into `Core\Exceptions\Logic`, Task 2)* | — |
| `Exceptions/InvalidMapping.php` | *(deleted — merged into `Core\Exceptions\InvalidMapping`, Task 2)* | — |
| `Exceptions/{Authentication,ForbiddenAccess,UnauthorizedAccess}.php` | `Exceptions/SimpleAuth/{...}.php` (stays domain-tagged, genuinely distinct — spec section 5) | `...SimpleAuth\Exceptions` → `FastyBird\Core\Exceptions\SimpleAuth` |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |
| `resources/model.conf` | `resources/model.conf` (unchanged; `SimpleAuthExtension`'s default `casbin.model` path is `__DIR__/../../resources/model.conf` relative to `src/DI/`, preserved by Task 18 since the merged `CoreExtension` lives at the same relative depth from `resources/`) | — |

**Interfaces:**
- Consumes: `FastyBird\Core\Exceptions\{Exception,InvalidArgument,Logic,InvalidMapping}` (Task 2).
- Produces: `FastyBird\Core\Security\SimpleAuth\{Access\AnnotationChecker,Access\LatteChecker,Access\LinkChecker,Access\CheckRequirements,Access\Checker,EnforcerFactory,IAuthenticator,IIdentity,IIdentityFactory,IUserStorage,IdentityFactory,PlainIdentity,TokenBuilder,TokenReader,TokenValidator,User,UserStorage}`, `FastyBird\Core\Services\SimpleAuth\Auth`, `FastyBird\Core\Configuration\SimpleAuth\Configuration`, `FastyBird\Core\Constants\SimpleAuth\Constants`, `FastyBird\Core\Entities\SimpleAuth\{Owner,TOwner,Policies\Policy,Tokens\Token}`, `FastyBird\Core\Presenters\SimpleAuth\TSimpleAuth`, `FastyBird\Core\Persistence\SimpleAuth\Models\{Casbin\Adapter,Casbin\Filter,Policies\Manager,Policies\Repository,Tokens\Manager,Tokens\Repository}`, `FastyBird\Core\Persistence\SimpleAuth\Queries\{FindPolicies,FindTokens}`, `FastyBird\Core\Exceptions\SimpleAuth\{Authentication,ForbiddenAccess,UnauthorizedAccess}`.

- [ ] **Step 1: Create directories and `git mv`**

```bash
S=src/FastyBird/Core/SimpleAuth/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Security/SimpleAuth/Access $C/Presenters/SimpleAuth $C/Services/SimpleAuth \
  $C/Configuration/SimpleAuth $C/Constants/SimpleAuth $C/Entities/SimpleAuth/Policies $C/Entities/SimpleAuth/Tokens \
  $C/Events/SimpleAuth $C/Latte/SimpleAuth/Nodes $C/Mapping/SimpleAuth/Attribute $C/Mapping/SimpleAuth/Driver \
  $C/Middleware/SimpleAuth $C/Persistence/SimpleAuth/Models/Casbin $C/Persistence/SimpleAuth/Models/Policies \
  $C/Persistence/SimpleAuth/Models/Tokens $C/Persistence/SimpleAuth/Queries $C/Subscribers/SimpleAuth \
  $C/Types/SimpleAuth $C/Exceptions/SimpleAuth

for f in AnnotationChecker CheckRequirements Checker LatteChecker LinkChecker; do
  git mv $S/Access/$f.php $C/Security/SimpleAuth/Access/$f.php
done
git mv $S/Application/TSimpleAuth.php $C/Presenters/SimpleAuth/TSimpleAuth.php
git mv $S/Auth.php $C/Services/SimpleAuth/Auth.php
git mv $S/Configuration.php $C/Configuration/SimpleAuth/Configuration.php
git mv $S/Constants.php $C/Constants/SimpleAuth/Constants.php
git mv $S/Entities/Owner.php $C/Entities/SimpleAuth/Owner.php
git mv $S/Entities/TOwner.php $C/Entities/SimpleAuth/TOwner.php
git mv $S/Entities/Policies/Policy.php $C/Entities/SimpleAuth/Policies/Policy.php
git mv $S/Entities/Tokens/Token.php $C/Entities/SimpleAuth/Tokens/Token.php
git mv $S/Events/Request.php $C/Events/SimpleAuth/Request.php
git mv $S/Events/Response.php $C/Events/SimpleAuth/Response.php
git mv $S/Latte/AccessExtension.php $C/Latte/SimpleAuth/AccessExtension.php
git mv $S/Latte/Nodes/AllowedHrefNode.php $C/Latte/SimpleAuth/Nodes/AllowedHrefNode.php
git mv $S/Latte/Nodes/IfAllowedNode.php $C/Latte/SimpleAuth/Nodes/IfAllowedNode.php
git mv $S/Latte/Nodes/NElseAllowedNode.php $C/Latte/SimpleAuth/Nodes/NElseAllowedNode.php
git mv $S/Mapping/Attribute/Owner.php $C/Mapping/SimpleAuth/Attribute/Owner.php
git mv $S/Mapping/Driver/Owner.php $C/Mapping/SimpleAuth/Driver/Owner.php
git mv $S/Middleware/Authorization.php $C/Middleware/SimpleAuth/Authorization.php
git mv $S/Middleware/User.php $C/Middleware/SimpleAuth/User.php
git mv $S/Models/Casbin/Adapter.php $C/Persistence/SimpleAuth/Models/Casbin/Adapter.php
git mv $S/Models/Casbin/Filter.php $C/Persistence/SimpleAuth/Models/Casbin/Filter.php
git mv $S/Models/Policies/Manager.php $C/Persistence/SimpleAuth/Models/Policies/Manager.php
git mv $S/Models/Policies/Repository.php $C/Persistence/SimpleAuth/Models/Policies/Repository.php
git mv $S/Models/Tokens/Manager.php $C/Persistence/SimpleAuth/Models/Tokens/Manager.php
git mv $S/Models/Tokens/Repository.php $C/Persistence/SimpleAuth/Models/Tokens/Repository.php
git mv $S/Queries/FindPolicies.php $C/Persistence/SimpleAuth/Queries/FindPolicies.php
git mv $S/Queries/FindTokens.php $C/Persistence/SimpleAuth/Queries/FindTokens.php
for f in EnforcerFactory IAuthenticator IIdentity IIdentityFactory IUserStorage IdentityFactory PlainIdentity TokenBuilder TokenReader TokenValidator User UserStorage; do
  git mv $S/Security/$f.php $C/Security/SimpleAuth/$f.php
done
git mv $S/Subscribers/Application.php $C/Subscribers/SimpleAuth/Application.php
git mv $S/Subscribers/Policy.php $C/Subscribers/SimpleAuth/Policy.php
git mv $S/Subscribers/User.php $C/Subscribers/SimpleAuth/User.php
git mv $S/Types/PolicyType.php $C/Types/SimpleAuth/PolicyType.php
git mv $S/Types/TokenState.php $C/Types/SimpleAuth/TokenState.php
git mv $S/Exceptions/Authentication.php $C/Exceptions/SimpleAuth/Authentication.php
git mv $S/Exceptions/ForbiddenAccess.php $C/Exceptions/SimpleAuth/ForbiddenAccess.php
git mv $S/Exceptions/UnauthorizedAccess.php $C/Exceptions/SimpleAuth/UnauthorizedAccess.php

git rm $S/DI/SimpleAuthExtension.php
git rm $S/Exceptions/Exception.php $S/Exceptions/InvalidArgument.php $S/Exceptions/InvalidState.php \
       $S/Exceptions/Logical.php $S/Exceptions/InvalidMapping.php

mkdir -p src/FastyBird/Core/Core/resources
git mv src/FastyBird/Core/SimpleAuth/resources/model.conf src/FastyBird/Core/Core/resources/model.conf
git rm src/FastyBird/Core/SimpleAuth/LICENSE.md
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Security/SimpleAuth src/FastyBird/Core/Core/src/Presenters/SimpleAuth \
     src/FastyBird/Core/Core/src/Services/SimpleAuth src/FastyBird/Core/Core/src/Configuration/SimpleAuth \
     src/FastyBird/Core/Core/src/Constants/SimpleAuth src/FastyBird/Core/Core/src/Entities/SimpleAuth \
     src/FastyBird/Core/Core/src/Events/SimpleAuth src/FastyBird/Core/Core/src/Latte/SimpleAuth \
     src/FastyBird/Core/Core/src/Mapping/SimpleAuth src/FastyBird/Core/Core/src/Middleware/SimpleAuth \
     src/FastyBird/Core/Core/src/Persistence/SimpleAuth src/FastyBird/Core/Core/src/Subscribers/SimpleAuth \
     src/FastyBird/Core/Core/src/Types/SimpleAuth src/FastyBird/Core/Core/src/Exceptions/SimpleAuth \
     -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Core\\SimpleAuth\\Access#FastyBird\\Core\\Security\\SimpleAuth\\Access#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Application#FastyBird\\Core\\Presenters\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Entities#FastyBird\\Core\\Entities\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Events#FastyBird\\Core\\Events\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Latte#FastyBird\\Core\\Latte\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Mapping#FastyBird\\Core\\Mapping\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Middleware#FastyBird\\Core\\Middleware\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Models#FastyBird\\Core\\Persistence\\SimpleAuth\\Models#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Queries#FastyBird\\Core\\Persistence\\SimpleAuth\\Queries#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Security#FastyBird\\Core\\Security\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Subscribers#FastyBird\\Core\\Subscribers\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Types#FastyBird\\Core\\Types\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Exceptions#FastyBird\\Core\\Exceptions\\SimpleAuth#g' \
    -e 's#namespace FastyBird\\Core\\SimpleAuth;#namespace FastyBird\\Core\\Services\\SimpleAuth;#g' \
    -e 's#use FastyBird\\Core\\SimpleAuth;#use FastyBird\\Core\\Services\\SimpleAuth;#g' \
    "$f"
done
```

The last two rules are deliberately narrower (`namespace ...;`/`use ...;` exact-line matches, not a bare prefix substitution) because `FastyBird\Core\SimpleAuth` bare (no sub-segment) is ambiguous between the three different top-level files (`Auth.php` → `Services\SimpleAuth`, `Configuration.php` → `Configuration\SimpleAuth`, `Constants.php` → `Constants\SimpleAuth`) that used to share that one bare namespace. Handle each of those three files' own `namespace` line and any file that does `use FastyBird\Core\SimpleAuth;` (meaning "the `Auth` class", since `Configuration.php`/`Constants.php` are referenced via `SimpleAuth\Configuration`/`SimpleAuth\Constants` qualified access in most call sites — grep for `SimpleAuth\Configuration` and `SimpleAuth\Constants` specifically) by hand after the bulk sed: read every file this script touched and confirm each `use` import that referenced the bare `FastyBird\Core\SimpleAuth` namespace now points at the right one of the three (`Services\SimpleAuth\Auth`, `Configuration\SimpleAuth\Configuration`, or `Constants\SimpleAuth\Constants`) rather than a blanket rename.

- [ ] **Step 3: Verify and confirm no stale references**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Security/SimpleAuth src/FastyBird/Core/Core/src/Presenters/SimpleAuth \
  src/FastyBird/Core/Core/src/Services/SimpleAuth src/FastyBird/Core/Core/src/Configuration/SimpleAuth \
  src/FastyBird/Core/Core/src/Constants/SimpleAuth src/FastyBird/Core/Core/src/Entities/SimpleAuth \
  src/FastyBird/Core/Core/src/Events/SimpleAuth src/FastyBird/Core/Core/src/Latte/SimpleAuth \
  src/FastyBird/Core/Core/src/Mapping/SimpleAuth src/FastyBird/Core/Core/src/Middleware/SimpleAuth \
  src/FastyBird/Core/Core/src/Persistence/SimpleAuth src/FastyBird/Core/Core/src/Subscribers/SimpleAuth \
  src/FastyBird/Core/Core/src/Types/SimpleAuth src/FastyBird/Core/Core/src/Exceptions/SimpleAuth -name "*.php"); do
  php -l "$f" || exit 1
done
echo clean
'
grep -rn 'FastyBird\\Core\\SimpleAuth\\' src/FastyBird/Core/Core/src
```

Expected: `clean`, then no grep output (a bare, non-backslash-followed `FastyBird\Core\SimpleAuth` with nothing after it, e.g. inside a `use FastyBird\Core\Services\SimpleAuth;` line, does not match this pattern and is correctly left alone).

- [ ] **Step 4: Commit**

```bash
git add -A src/FastyBird/Core/Core src/FastyBird/Core/SimpleAuth
git commit -m "$(cat <<'EOF'
refactor(core): migrate Core/SimpleAuth content into fastybird/miniserver-core

Access/Security relocate to one Security bucket, Models/Queries relocate to
Persistence, three top-level facade/config/constant files split into new
Services/Configuration/Constants buckets (flagged assumption, not in spec
Appendix A), Auth/ForbiddenAccess/UnauthorizedAccess exceptions stay
domain-tagged per spec section 5, Logical/InvalidMapping merge into the
shared Core\Exceptions classes.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Migrate `Core/Tools` content

**Files:** mapping table (all under `src/FastyBird/Core/Tools/src/` → `src/FastyBird/Core/Core/src/`):

| Old | New | Namespace change |
|---|---|---|
| `Events/{DbTransactionFinished,DbTransactionStarted}.php` | `Events/Tools/{...}.php` | `...Tools\Events` → `FastyBird\Core\Events\Tools` |
| `Formats/{CombinedEnum,CombinedEnumItem,NumberRange,StringEnum}.php` | `Formats/Tools/{...}.php` | `...Tools\Formats` → `FastyBird\Core\Formats\Tools` (Flagged Assumption 6) |
| `Helpers/{Database,Logger,Sentry}.php` | `Helpers/Tools/{...}.php` | `...Tools\Helpers` → `FastyBird\Core\Helpers\Tools` |
| `Schemas/Validator.php` | `Schemas/Tools/Validator.php` | `...Tools\Schemas` → `FastyBird\Core\Schemas\Tools` |
| `Transformers/{DataTypeTransformer,EquationTransformer,HsbTransformer,HsiTransformer,MiredTransformer,RgbTransformer,Transformer}.php` | `Transformers/Tools/{...}.php` | `...Tools\Transformers` → `FastyBird\Core\Transformers\Tools` (Flagged Assumption 6) |
| `Utilities/{DataType,DateTimeProvider,Value}.php` | `Utilities/Tools/{...}.php` | `...Tools\Utilities` → `FastyBird\Core\Utilities\Tools` (Flagged Assumption 6) |
| `Exceptions/{InvalidData,InvalidValue}.php` | `Exceptions/Tools/{...}.php` (stays domain-tagged) | `...Tools\Exceptions` → `FastyBird\Core\Exceptions\Tools` |
| `DI/ToolsExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState,Logic,MalformedInput,Runtime}.php` | *(deleted — merged, Task 2)* | — |

- [ ] **Step 1: Create directories and `git mv`**

```bash
T=src/FastyBird/Core/Tools/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Events/Tools $C/Formats/Tools $C/Helpers/Tools $C/Schemas/Tools $C/Transformers/Tools $C/Utilities/Tools $C/Exceptions/Tools

git mv $T/Events/DbTransactionFinished.php $C/Events/Tools/DbTransactionFinished.php
git mv $T/Events/DbTransactionStarted.php $C/Events/Tools/DbTransactionStarted.php
git mv $T/Formats/CombinedEnum.php $C/Formats/Tools/CombinedEnum.php
git mv $T/Formats/CombinedEnumItem.php $C/Formats/Tools/CombinedEnumItem.php
git mv $T/Formats/NumberRange.php $C/Formats/Tools/NumberRange.php
git mv $T/Formats/StringEnum.php $C/Formats/Tools/StringEnum.php
git mv $T/Helpers/Database.php $C/Helpers/Tools/Database.php
git mv $T/Helpers/Logger.php $C/Helpers/Tools/Logger.php
git mv $T/Helpers/Sentry.php $C/Helpers/Tools/Sentry.php
git mv $T/Schemas/Validator.php $C/Schemas/Tools/Validator.php
git mv $T/Transformers/DataTypeTransformer.php $C/Transformers/Tools/DataTypeTransformer.php
git mv $T/Transformers/EquationTransformer.php $C/Transformers/Tools/EquationTransformer.php
git mv $T/Transformers/HsbTransformer.php $C/Transformers/Tools/HsbTransformer.php
git mv $T/Transformers/HsiTransformer.php $C/Transformers/Tools/HsiTransformer.php
git mv $T/Transformers/MiredTransformer.php $C/Transformers/Tools/MiredTransformer.php
git mv $T/Transformers/RgbTransformer.php $C/Transformers/Tools/RgbTransformer.php
git mv $T/Transformers/Transformer.php $C/Transformers/Tools/Transformer.php
git mv $T/Utilities/DataType.php $C/Utilities/Tools/DataType.php
git mv $T/Utilities/DateTimeProvider.php $C/Utilities/Tools/DateTimeProvider.php
git mv $T/Utilities/Value.php $C/Utilities/Tools/Value.php
git mv $T/Exceptions/InvalidData.php $C/Exceptions/Tools/InvalidData.php
git mv $T/Exceptions/InvalidValue.php $C/Exceptions/Tools/InvalidValue.php

git rm $T/DI/ToolsExtension.php
git rm $T/Exceptions/Exception.php $T/Exceptions/InvalidArgument.php $T/Exceptions/InvalidState.php \
       $T/Exceptions/Logic.php $T/Exceptions/MalformedInput.php $T/Exceptions/Runtime.php

diff src/FastyBird/Core/Tools/tests/cases/unit/BaseTestCase.php src/FastyBird/Core/Core/tests/cases/unit/BaseTestCase.php \
  && git rm src/FastyBird/Core/Tools/tests/cases/unit/BaseTestCase.php \
  || echo "REVIEW: Tools' BaseTestCase.php differs — read both, keep the union"
git rm src/FastyBird/Core/Tools/tests/cases/unit/DI/ToolsExtensionTest.php
git rm src/FastyBird/Core/Tools/tests/common.neon
mkdir -p src/FastyBird/Core/Core/tests/cases/unit/Formats src/FastyBird/Core/Core/tests/cases/unit/Schemas \
  src/FastyBird/Core/Core/tests/cases/unit/Transformers src/FastyBird/Core/Core/tests/cases/unit/Utilities \
  src/FastyBird/Core/Core/tests/fixtures/Schemas
git mv src/FastyBird/Core/Tools/tests/cases/unit/Formats/CombinedEnumFormatTest.php src/FastyBird/Core/Core/tests/cases/unit/Formats/CombinedEnumFormatTest.php
git mv src/FastyBird/Core/Tools/tests/cases/unit/Formats/NumberRangeFormatTest.php src/FastyBird/Core/Core/tests/cases/unit/Formats/NumberRangeFormatTest.php
git mv src/FastyBird/Core/Tools/tests/cases/unit/Formats/StringEnumFormatTest.php src/FastyBird/Core/Core/tests/cases/unit/Formats/StringEnumFormatTest.php
git mv src/FastyBird/Core/Tools/tests/cases/unit/Schemas/ValidatorTest.php src/FastyBird/Core/Core/tests/cases/unit/Schemas/ValidatorTest.php
git mv src/FastyBird/Core/Tools/tests/cases/unit/Transformers/EquationTransformerTest.php src/FastyBird/Core/Core/tests/cases/unit/Transformers/EquationTransformerTest.php
git mv src/FastyBird/Core/Tools/tests/cases/unit/Utilities/ValueTest.php src/FastyBird/Core/Core/tests/cases/unit/Utilities/ValueTest.php
git mv src/FastyBird/Core/Tools/tests/fixtures/Schemas/validator.schema.json src/FastyBird/Core/Core/tests/fixtures/Schemas/validator.schema.json
git rm src/FastyBird/Core/Tools/README.md src/FastyBird/Core/Tools/docs/Home.md
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Events/Tools src/FastyBird/Core/Core/src/Formats/Tools \
     src/FastyBird/Core/Core/src/Helpers/Tools src/FastyBird/Core/Core/src/Schemas/Tools \
     src/FastyBird/Core/Core/src/Transformers/Tools src/FastyBird/Core/Core/src/Utilities/Tools \
     src/FastyBird/Core/Core/src/Exceptions/Tools \
     src/FastyBird/Core/Core/tests/cases/unit/Formats src/FastyBird/Core/Core/tests/cases/unit/Schemas \
     src/FastyBird/Core/Core/tests/cases/unit/Transformers src/FastyBird/Core/Core/tests/cases/unit/Utilities \
     -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Core\\Tools\\Events#FastyBird\\Core\\Events\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Formats#FastyBird\\Core\\Formats\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Helpers#FastyBird\\Core\\Helpers\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Schemas#FastyBird\\Core\\Schemas\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Transformers#FastyBird\\Core\\Transformers\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Utilities#FastyBird\\Core\\Utilities\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Exceptions#FastyBird\\Core\\Exceptions\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Tests#FastyBird\\Core\\Tests#g' \
    "$f"
done
```

- [ ] **Step 3: Verify and confirm no stale references, then commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Events/Tools src/FastyBird/Core/Core/src/Formats/Tools \
  src/FastyBird/Core/Core/src/Helpers/Tools src/FastyBird/Core/Core/src/Schemas/Tools \
  src/FastyBird/Core/Core/src/Transformers/Tools src/FastyBird/Core/Core/src/Utilities/Tools \
  src/FastyBird/Core/Core/src/Exceptions/Tools -name "*.php"); do php -l "$f" || exit 1; done
echo clean
'
grep -rn 'FastyBird\\Core\\Tools\\' src/FastyBird/Core/Core/src src/FastyBird/Core/Core/tests
git add -A src/FastyBird/Core/Core src/FastyBird/Core/Tools
git commit -m "$(cat <<'EOF'
refactor(core): migrate Core/Tools content into fastybird/miniserver-core

Formats/Transformers/Utilities each become their own new type bucket (spec
Appendix A named these folders in section 2.4 but never gave them a table
row); InvalidData/InvalidValue stay domain-tagged, the rest of the
exceptions merge into Core\Exceptions.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Migrate `Library/DateTimeFactory` content

**Files:** the smallest of the 15 — 6 files total.

| Old (`src/FastyBird/Library/DateTimeFactory/src/...`) | New (`src/FastyBird/Core/Core/src/...`) | Namespace change |
|---|---|---|
| `Clock.php` | `Services/DateTimeFactory/Clock.php` | `FastyBird\Library\DateTimeFactory` → `FastyBird\Core\Services\DateTimeFactory` |
| `FrozenClock.php` | `Services/DateTimeFactory/FrozenClock.php` | same |
| `SystemClock.php` | `Services/DateTimeFactory/SystemClock.php` | same |
| `DI/DateTimeFactoryExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument}.php` | *(deleted — merged, Task 2)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |

`Clock.php`, `FrozenClock.php`, `SystemClock.php` are the package's whole reason to exist (a clock abstraction with a real and a frozen-for-tests implementation) — same "package's own facade service" role as `SimpleAuth\Auth.php`/`Phone\Phone.php`, hence the same `Services` bucket (Flagged Assumption 7 extended).

- [ ] **Step 1: `git mv` and delete**

```bash
D=src/FastyBird/Library/DateTimeFactory/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Services/DateTimeFactory
git mv $D/Clock.php $C/Services/DateTimeFactory/Clock.php
git mv $D/FrozenClock.php $C/Services/DateTimeFactory/FrozenClock.php
git mv $D/SystemClock.php $C/Services/DateTimeFactory/SystemClock.php
git rm $D/DI/DateTimeFactoryExtension.php
git rm $D/Exceptions/Exception.php $D/Exceptions/InvalidArgument.php
git rm src/FastyBird/Library/DateTimeFactory/LICENSE.md
```

- [ ] **Step 2: Rewrite namespace (bare, no sub-bucket — all three files declared `namespace FastyBird\Library\DateTimeFactory;` directly)**

```bash
sed -i -E \
  -e 's#namespace FastyBird\\Library\\DateTimeFactory;#namespace FastyBird\\Core\\Services\\DateTimeFactory;#' \
  -e 's#FastyBird\\Library\\DateTimeFactory\\Exceptions#FastyBird\\Core\\Exceptions#g' \
  src/FastyBird/Core/Core/src/Services/DateTimeFactory/Clock.php \
  src/FastyBird/Core/Core/src/Services/DateTimeFactory/FrozenClock.php \
  src/FastyBird/Core/Core/src/Services/DateTimeFactory/SystemClock.php
```

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
php -l src/FastyBird/Core/Core/src/Services/DateTimeFactory/Clock.php
php -l src/FastyBird/Core/Core/src/Services/DateTimeFactory/FrozenClock.php
php -l src/FastyBird/Core/Core/src/Services/DateTimeFactory/SystemClock.php
'
grep -rn 'FastyBird\\Library\\DateTimeFactory' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Library/DateTimeFactory
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/DateTimeFactory content into fastybird/miniserver-core

Clock/FrozenClock/SystemClock move to the Services bucket alongside
SimpleAuth's Auth and Phone's Phone -- the package's own facade classes.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Migrate `Library/DoctrineCrud` content

**Files:** mapping table (all under `src/FastyBird/Library/DoctrineCrud/src/` → `src/FastyBird/Core/Core/src/`):

| Old | New | Namespace change |
|---|---|---|
| `Crud/Create/{EntityCreator,IEntityCreator}.php` | `Persistence/DoctrineCrud/Crud/Create/{...}.php` | `...DoctrineCrud\Crud\Create` → `FastyBird\Core\Persistence\DoctrineCrud\Crud\Create` |
| `Crud/Update/{EntityUpdater,IEntityUpdater}.php` | `Persistence/DoctrineCrud/Crud/Update/{...}.php` | `...DoctrineCrud\Crud\Update` → `FastyBird\Core\Persistence\DoctrineCrud\Crud\Update` |
| `Crud/Delete/{EntityDeleter,IEntityDeleter}.php` | `Persistence/DoctrineCrud/Crud/Delete/{...}.php` | `...DoctrineCrud\Crud\Delete` → `FastyBird\Core\Persistence\DoctrineCrud\Crud\Delete` |
| `Crud/{CrudManager,EntityCrud,EntityCrudFactory,IEntityCrud,IEntityCrudFactory}.php` | `Persistence/DoctrineCrud/Crud/{...}.php` | `...DoctrineCrud\Crud` → `FastyBird\Core\Persistence\DoctrineCrud\Crud` |
| `Entities/IEntity.php` | `Entities/DoctrineCrud/IEntity.php` | `...DoctrineCrud\Entities` → `FastyBird\Core\Entities\DoctrineCrud` |
| `Helpers.php` | `Helpers/DoctrineCrud/Helpers.php` | `FastyBird\Library\DoctrineCrud` → `FastyBird\Core\Helpers\DoctrineCrud` |
| `StringFunctions/DateFormat.php` | `Helpers/DoctrineCrud/StringFunctions/DateFormat.php` | `...DoctrineCrud\StringFunctions` → `FastyBird\Core\Helpers\DoctrineCrud\StringFunctions` |
| `Mapping/Attribute/Crud.php` | `Mapping/DoctrineCrud/Attribute/Crud.php` | `...DoctrineCrud\Mapping\Attribute` → `FastyBird\Core\Mapping\DoctrineCrud\Attribute` |
| `Mapping/{EntityMapper,IEntityMapper}.php` | `Mapping/DoctrineCrud/{...}.php` | `...DoctrineCrud\Mapping` → `FastyBird\Core\Mapping\DoctrineCrud` |
| `Exceptions/{EntityCreation,MissingRequiredField}.php` | `Exceptions/DoctrineCrud/{...}.php` (stays domain-tagged) | `...DoctrineCrud\Exceptions` → `FastyBird\Core\Exceptions\DoctrineCrud` |
| `DI/DoctrineCrudExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState}.php` | *(deleted — merged, Task 2)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |

- [ ] **Step 1: Create directories and `git mv`**

```bash
DC=src/FastyBird/Library/DoctrineCrud/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Persistence/DoctrineCrud/Crud/Create $C/Persistence/DoctrineCrud/Crud/Update $C/Persistence/DoctrineCrud/Crud/Delete
mkdir -p $C/Entities/DoctrineCrud $C/Helpers/DoctrineCrud/StringFunctions $C/Mapping/DoctrineCrud/Attribute $C/Exceptions/DoctrineCrud

git mv $DC/Crud/Create/EntityCreator.php $C/Persistence/DoctrineCrud/Crud/Create/EntityCreator.php
git mv $DC/Crud/Create/IEntityCreator.php $C/Persistence/DoctrineCrud/Crud/Create/IEntityCreator.php
git mv $DC/Crud/Update/EntityUpdater.php $C/Persistence/DoctrineCrud/Crud/Update/EntityUpdater.php
git mv $DC/Crud/Update/IEntityUpdater.php $C/Persistence/DoctrineCrud/Crud/Update/IEntityUpdater.php
git mv $DC/Crud/Delete/EntityDeleter.php $C/Persistence/DoctrineCrud/Crud/Delete/EntityDeleter.php
git mv $DC/Crud/Delete/IEntityDeleter.php $C/Persistence/DoctrineCrud/Crud/Delete/IEntityDeleter.php
git mv $DC/Crud/CrudManager.php $C/Persistence/DoctrineCrud/Crud/CrudManager.php
git mv $DC/Crud/EntityCrud.php $C/Persistence/DoctrineCrud/Crud/EntityCrud.php
git mv $DC/Crud/EntityCrudFactory.php $C/Persistence/DoctrineCrud/Crud/EntityCrudFactory.php
git mv $DC/Crud/IEntityCrud.php $C/Persistence/DoctrineCrud/Crud/IEntityCrud.php
git mv $DC/Crud/IEntityCrudFactory.php $C/Persistence/DoctrineCrud/Crud/IEntityCrudFactory.php
git mv $DC/Entities/IEntity.php $C/Entities/DoctrineCrud/IEntity.php
git mv $DC/Helpers.php $C/Helpers/DoctrineCrud/Helpers.php
git mv $DC/StringFunctions/DateFormat.php $C/Helpers/DoctrineCrud/StringFunctions/DateFormat.php
git mv $DC/Mapping/Attribute/Crud.php $C/Mapping/DoctrineCrud/Attribute/Crud.php
git mv $DC/Mapping/EntityMapper.php $C/Mapping/DoctrineCrud/EntityMapper.php
git mv $DC/Mapping/IEntityMapper.php $C/Mapping/DoctrineCrud/IEntityMapper.php
git mv $DC/Exceptions/EntityCreation.php $C/Exceptions/DoctrineCrud/EntityCreation.php
git mv $DC/Exceptions/MissingRequiredField.php $C/Exceptions/DoctrineCrud/MissingRequiredField.php

git rm $DC/DI/DoctrineCrudExtension.php
git rm $DC/Exceptions/Exception.php $DC/Exceptions/InvalidArgument.php $DC/Exceptions/InvalidState.php
git rm src/FastyBird/Library/DoctrineCrud/LICENSE.md
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Persistence/DoctrineCrud src/FastyBird/Core/Core/src/Entities/DoctrineCrud \
     src/FastyBird/Core/Core/src/Helpers/DoctrineCrud src/FastyBird/Core/Core/src/Mapping/DoctrineCrud \
     src/FastyBird/Core/Core/src/Exceptions/DoctrineCrud -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud\\Create#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Create#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud\\Update#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Update#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud\\Delete#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Delete#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Entities#FastyBird\\Core\\Entities\\DoctrineCrud#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\StringFunctions#FastyBird\\Core\\Helpers\\DoctrineCrud\\StringFunctions#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Mapping\\Attribute#FastyBird\\Core\\Mapping\\DoctrineCrud\\Attribute#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Mapping#FastyBird\\Core\\Mapping\\DoctrineCrud#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Exceptions#FastyBird\\Core\\Exceptions\\DoctrineCrud#g' \
    -e 's#namespace FastyBird\\Library\\DoctrineCrud;#namespace FastyBird\\Core\\Helpers\\DoctrineCrud;#' \
    -e 's#use FastyBird\\Library\\DoctrineCrud;#use FastyBird\\Core\\Helpers\\DoctrineCrud;#' \
    "$f"
done
```

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Persistence/DoctrineCrud src/FastyBird/Core/Core/src/Entities/DoctrineCrud \
  src/FastyBird/Core/Core/src/Helpers/DoctrineCrud src/FastyBird/Core/Core/src/Mapping/DoctrineCrud \
  src/FastyBird/Core/Core/src/Exceptions/DoctrineCrud -name "*.php"); do php -l "$f" || exit 1; done
echo clean
'
grep -rn 'FastyBird\\Library\\DoctrineCrud' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Library/DoctrineCrud
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/DoctrineCrud content into fastybird/miniserver-core

Crud/* relocates to Persistence, StringFunctions and the top-level Helpers.php
both land in one Helpers/DoctrineCrud bucket, EntityCreation/MissingRequiredField
stay domain-tagged per spec section 5.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: Migrate `Library/DoctrineOrmQuery` content

**Files:** 7 files total.

| Old (`src/FastyBird/Library/DoctrineOrmQuery/src/...`) | New (`src/FastyBird/Core/Core/src/...`) | Namespace change |
|---|---|---|
| `QueryObject.php` | `Persistence/DoctrineOrmQuery/QueryObject.php` | `FastyBird\Library\DoctrineOrmQuery` → `FastyBird\Core\Persistence\DoctrineOrmQuery` (Flagged Assumption 5) |
| `ResultSet.php` | `Persistence/DoctrineOrmQuery/ResultSet.php` | same |
| `Exceptions/{NotImplemented,Query}.php` | `Exceptions/DoctrineOrmQuery/{...}.php` (stays domain-tagged; `NotImplemented` is the one confirmed-incompatible-parent case, spec section 5/8.3 — do NOT merge with WebSockets' own `NotImplemented`) | `...DoctrineOrmQuery\Exceptions` → `FastyBird\Core\Exceptions\DoctrineOrmQuery` |
| `Exceptions/{Exception,InvalidArgument,InvalidState}.php` | *(deleted — merged, Task 2)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |

- [ ] **Step 1: `git mv` and delete**

```bash
DQ=src/FastyBird/Library/DoctrineOrmQuery/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Persistence/DoctrineOrmQuery $C/Exceptions/DoctrineOrmQuery
git mv $DQ/QueryObject.php $C/Persistence/DoctrineOrmQuery/QueryObject.php
git mv $DQ/ResultSet.php $C/Persistence/DoctrineOrmQuery/ResultSet.php
git mv $DQ/Exceptions/NotImplemented.php $C/Exceptions/DoctrineOrmQuery/NotImplemented.php
git mv $DQ/Exceptions/Query.php $C/Exceptions/DoctrineOrmQuery/Query.php
git rm $DQ/Exceptions/Exception.php $DQ/Exceptions/InvalidArgument.php $DQ/Exceptions/InvalidState.php
git rm src/FastyBird/Library/DoctrineOrmQuery/LICENSE.md
```

- [ ] **Step 2: Rewrite namespaces, verify, commit**

```bash
sed -i -E \
  -e 's#namespace FastyBird\\Library\\DoctrineOrmQuery;#namespace FastyBird\\Core\\Persistence\\DoctrineOrmQuery;#' \
  -e 's#use FastyBird\\Library\\DoctrineOrmQuery\\Exceptions;#use FastyBird\\Core\\Exceptions\\DoctrineOrmQuery as Exceptions;#' \
  src/FastyBird/Core/Core/src/Persistence/DoctrineOrmQuery/QueryObject.php \
  src/FastyBird/Core/Core/src/Persistence/DoctrineOrmQuery/ResultSet.php

sed -i -E \
  -e 's#namespace FastyBird\\Library\\DoctrineOrmQuery\\Exceptions;#namespace FastyBird\\Core\\Exceptions\\DoctrineOrmQuery;#' \
  src/FastyBird/Core/Core/src/Exceptions/DoctrineOrmQuery/NotImplemented.php \
  src/FastyBird/Core/Core/src/Exceptions/DoctrineOrmQuery/Query.php

docker exec -w /app fastybird-application bash -c '
for f in src/FastyBird/Core/Core/src/Persistence/DoctrineOrmQuery/QueryObject.php \
  src/FastyBird/Core/Core/src/Persistence/DoctrineOrmQuery/ResultSet.php \
  src/FastyBird/Core/Core/src/Exceptions/DoctrineOrmQuery/NotImplemented.php \
  src/FastyBird/Core/Core/src/Exceptions/DoctrineOrmQuery/Query.php; do php -l "$f" || exit 1; done
'
grep -rn 'FastyBird\\Library\\DoctrineOrmQuery' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Library/DoctrineOrmQuery
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/DoctrineOrmQuery content into fastybird/miniserver-core

QueryObject/ResultSet have no Appendix A row -- placed in Persistence
alongside DoctrineCrud (flagged assumption). NotImplemented stays domain-tagged
and unmerged with WebSockets' own NotImplemented (incompatible SPL parents,
spec section 5/8.3).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: Migrate `Library/DoctrineTimestampable` content

**Files:** mapping table (all under `src/FastyBird/Library/DoctrineTimestampable/src/` → `src/FastyBird/Core/Core/src/`):

| Old | New | Namespace change |
|---|---|---|
| `Configuration.php` | `Configuration/DoctrineTimestampable/Configuration.php` | `FastyBird\Library\DoctrineTimestampable` → `FastyBird\Core\Configuration\DoctrineTimestampable` (Flagged Assumption 7) |
| `Entities/{IEntityCreated,IEntityRemoved,IEntityUpdated,TEntityCreated,TEntityRemoved,TEntityUpdated}.php` | `Entities/DoctrineTimestampable/{...}.php` | `...DoctrineTimestampable\Entities` → `FastyBird\Core\Entities\DoctrineTimestampable` |
| `Events/TimestampableSubscriber.php` | `Subscribers/DoctrineTimestampable/TimestampableSubscriber.php` | `...DoctrineTimestampable\Events` → `FastyBird\Core\Subscribers\DoctrineTimestampable` (reclassified by technical role, not origin folder name — this class implements `Doctrine\Common\EventSubscriber`, the same role as `Application\Subscribers\EntityDiscriminator`, not a dispatched event/value-object; the `Subscribers` bucket already exists for exactly this role) |
| `Mapping/Annotation/Timestampable.php` | `Mapping/DoctrineTimestampable/Annotation/Timestampable.php` | `...DoctrineTimestampable\Mapping\Annotation` → `FastyBird\Core\Mapping\DoctrineTimestampable\Annotation` |
| `Mapping/Driver/Timestampable.php` | `Mapping/DoctrineTimestampable/Driver/Timestampable.php` | `...DoctrineTimestampable\Mapping\Driver` → `FastyBird\Core\Mapping\DoctrineTimestampable\Driver` |
| `Providers/DateProvider.php` | `Providers/DoctrineTimestampable/DateProvider.php` | `...DoctrineTimestampable\Providers` → `FastyBird\Core\Providers\DoctrineTimestampable` (explicit Appendix A row) |
| `Types/UTCDateTime.php` | `Types/DoctrineTimestampable/UTCDateTime.php` | `...DoctrineTimestampable\Types` → `FastyBird\Core\Types\DoctrineTimestampable` |
| `DI/DoctrineTimestampableExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState,UnexpectedValue}.php` | *(deleted — merged, Task 2)* | — |
| `Exceptions/InvalidMapping.php` | *(deleted — merged into shared `InvalidMapping`, Task 2, confirmed byte-identical to SimpleAuth's)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |

**Important:** `config/common.neon`'s `nettrineDbal.types.utcdatetime` value (`FastyBird\Library\DoctrineTimestampable\Types\UTCDateTime`, registered as a custom DBAL type) needs updating to `FastyBird\Core\Types\DoctrineTimestampable\UTCDateTime` — this is a raw string reference in a `.neon` `types:` map, not a PHP `use` import, so Task 22's generic sweep must catch it; flagged here so the implementer double-checks it specifically once Task 22 runs (`grep -rn 'DoctrineTimestampable\\\\Types\\\\UTCDateTime' config src/FastyBird` before and after).

- [ ] **Step 1: Create directories and `git mv`**

```bash
DT=src/FastyBird/Library/DoctrineTimestampable/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Configuration/DoctrineTimestampable $C/Entities/DoctrineTimestampable $C/Subscribers/DoctrineTimestampable \
  $C/Mapping/DoctrineTimestampable/Annotation $C/Mapping/DoctrineTimestampable/Driver \
  $C/Providers/DoctrineTimestampable $C/Types/DoctrineTimestampable

git mv $DT/Configuration.php $C/Configuration/DoctrineTimestampable/Configuration.php
git mv $DT/Entities/IEntityCreated.php $C/Entities/DoctrineTimestampable/IEntityCreated.php
git mv $DT/Entities/IEntityRemoved.php $C/Entities/DoctrineTimestampable/IEntityRemoved.php
git mv $DT/Entities/IEntityUpdated.php $C/Entities/DoctrineTimestampable/IEntityUpdated.php
git mv $DT/Entities/TEntityCreated.php $C/Entities/DoctrineTimestampable/TEntityCreated.php
git mv $DT/Entities/TEntityRemoved.php $C/Entities/DoctrineTimestampable/TEntityRemoved.php
git mv $DT/Entities/TEntityUpdated.php $C/Entities/DoctrineTimestampable/TEntityUpdated.php
git mv $DT/Events/TimestampableSubscriber.php $C/Subscribers/DoctrineTimestampable/TimestampableSubscriber.php
git mv $DT/Mapping/Annotation/Timestampable.php $C/Mapping/DoctrineTimestampable/Annotation/Timestampable.php
git mv $DT/Mapping/Driver/Timestampable.php $C/Mapping/DoctrineTimestampable/Driver/Timestampable.php
git mv $DT/Providers/DateProvider.php $C/Providers/DoctrineTimestampable/DateProvider.php
git mv $DT/Types/UTCDateTime.php $C/Types/DoctrineTimestampable/UTCDateTime.php

git rm $DT/DI/DoctrineTimestampableExtension.php
git rm $DT/Exceptions/Exception.php $DT/Exceptions/InvalidArgument.php $DT/Exceptions/InvalidState.php \
       $DT/Exceptions/UnexpectedValue.php $DT/Exceptions/InvalidMapping.php
git rm src/FastyBird/Library/DoctrineTimestampable/LICENSE.md
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Configuration/DoctrineTimestampable src/FastyBird/Core/Core/src/Entities/DoctrineTimestampable \
     src/FastyBird/Core/Core/src/Subscribers/DoctrineTimestampable src/FastyBird/Core/Core/src/Mapping/DoctrineTimestampable \
     src/FastyBird/Core/Core/src/Providers/DoctrineTimestampable src/FastyBird/Core/Core/src/Types/DoctrineTimestampable \
     -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#namespace FastyBird\\Library\\DoctrineTimestampable;#namespace FastyBird\\Core\\Configuration\\DoctrineTimestampable;#' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Entities#FastyBird\\Core\\Entities\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Events#FastyBird\\Core\\Subscribers\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Mapping\\Annotation#FastyBird\\Core\\Mapping\\DoctrineTimestampable\\Annotation#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Mapping\\Driver#FastyBird\\Core\\Mapping\\DoctrineTimestampable\\Driver#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Providers#FastyBird\\Core\\Providers\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Types#FastyBird\\Core\\Types\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    "$f"
done
```

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Configuration/DoctrineTimestampable src/FastyBird/Core/Core/src/Entities/DoctrineTimestampable \
  src/FastyBird/Core/Core/src/Subscribers/DoctrineTimestampable src/FastyBird/Core/Core/src/Mapping/DoctrineTimestampable \
  src/FastyBird/Core/Core/src/Providers/DoctrineTimestampable src/FastyBird/Core/Core/src/Types/DoctrineTimestampable -name "*.php"); do
  php -l "$f" || exit 1
done
echo clean
'
grep -rn 'FastyBird\\Library\\DoctrineTimestampable' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Library/DoctrineTimestampable
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/DoctrineTimestampable content into fastybird/miniserver-core

TimestampableSubscriber reclassified from Events to Subscribers by technical
role (it implements Doctrine\Common\EventSubscriber, not a dispatched event).
InvalidMapping merges into the shared Core\Exceptions\InvalidMapping,
confirmed byte-identical to SimpleAuth's own copy.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: Migrate `Library/JsonApi` content

**Files:** mapping table (all under `src/FastyBird/Library/JsonApi/src/` → `src/FastyBird/Core/Core/src/`). `Builder/`, top-level `Document.php`/`IDocument.php`, and the self-named `JsonApi/` folder (`Encoder.php`/`SchemaContainer.php`) all collapse into the same flat `Encoding/JsonApi/` bucket — confirmed by spec §8 resolution 1 (this spec explicitly corrected an earlier draft's mis-statement that `JsonApi/JsonApi/` held `Document.php`; it actually holds `Encoder.php` + `SchemaContainer.php`, already verified against the real file tree in this plan's own investigation).

| Old | New | Namespace change |
|---|---|---|
| `Builder/Builder.php` | `Encoding/JsonApi/Builder.php` | `...JsonApi\Builder` → `FastyBird\Core\Encoding\JsonApi` |
| `Document.php`, `IDocument.php` | `Encoding/JsonApi/{...}.php` | `FastyBird\Library\JsonApi` (bare) → `FastyBird\Core\Encoding\JsonApi` |
| `JsonApi/Encoder.php`, `JsonApi/SchemaContainer.php` | `Encoding/JsonApi/{...}.php` | `...JsonApi\JsonApi` → `FastyBird\Core\Encoding\JsonApi` |
| `Objects/*.php` (30 files) | `Encoding/JsonApi/Objects/*.php` | `...JsonApi\Objects` → `FastyBird\Core\Encoding\JsonApi\Objects` (internal structure preserved as-is, per Appendix A) |
| `Helpers/CrudReader.php` | `Helpers/JsonApi/CrudReader.php` | `...JsonApi\Helpers` → `FastyBird\Core\Helpers\JsonApi` |
| `Hydrators/Container.php`, `Hydrators/Hydrator.php` | `Persistence/JsonApi/Hydrators/{...}.php` | `...JsonApi\Hydrators` → `FastyBird\Core\Persistence\JsonApi\Hydrators` |
| `Hydrators/Fields/*.php` (9 files) | `Persistence/JsonApi/Hydrators/Fields/*.php` | `...JsonApi\Hydrators\Fields` → `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields` |
| `Middleware/JsonApi.php` | `Middleware/JsonApi/JsonApi.php` | `...JsonApi\Middleware` → `FastyBird\Core\Middleware\JsonApi` |
| `Schemas/JsonApi.php` | `Schemas/JsonApi/JsonApi.php` | `...JsonApi\Schemas` → `FastyBird\Core\Schemas\JsonApi` |
| `Translations/jsonApi.en_US.neon` | `Translations/JsonApi/jsonApi.en_US.neon` | n/a |
| `Exceptions/{JsonApi,JsonApiError,JsonApiMultipleError}.php` | `Exceptions/JsonApi/{...}.php` (stays domain-tagged, spec section 5) | `...JsonApi\Exceptions` → `FastyBird\Core\Exceptions\JsonApi` |
| `DI/JsonApiExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState,Logic,Runtime}.php` | *(deleted — merged, Task 2; `Logic`'s wrong-parent bug already fixed there per D4)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |

- [ ] **Step 1: Create directories and `git mv`**

```bash
J=src/FastyBird/Library/JsonApi/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Encoding/JsonApi/Objects $C/Helpers/JsonApi $C/Persistence/JsonApi/Hydrators/Fields \
  $C/Middleware/JsonApi $C/Schemas/JsonApi $C/Translations/JsonApi $C/Exceptions/JsonApi

git mv $J/Builder/Builder.php $C/Encoding/JsonApi/Builder.php
git mv $J/Document.php $C/Encoding/JsonApi/Document.php
git mv $J/IDocument.php $C/Encoding/JsonApi/IDocument.php
git mv $J/JsonApi/Encoder.php $C/Encoding/JsonApi/Encoder.php
git mv $J/JsonApi/SchemaContainer.php $C/Encoding/JsonApi/SchemaContainer.php
for f in ErrorObject ErrorObjectCollection IErrorObject IErrorObjectCollection ILinkObject ILinkObjectCollection \
         IMetaObject IMetaObjectCollection IRelationshipObject IRelationshipObjectCollection \
         IResourceIdentifierCollection IResourceIdentifierObject IResourceObject IResourceObjectCollection \
         ISourceObject IStandardObject IStandardObjectCollection LinkObject LinkObjectCollection MetaObject \
         MetaObjectCollection Obj RelationshipObject RelationshipObjectCollection ResourceIdentifierCollection \
         ResourceIdentifierObject ResourceObject ResourceObjectCollection SourceObject StandardObject \
         StandardObjectCollection; do
  git mv $J/Objects/$f.php $C/Encoding/JsonApi/Objects/$f.php
done
git mv $J/Helpers/CrudReader.php $C/Helpers/JsonApi/CrudReader.php
git mv $J/Hydrators/Container.php $C/Persistence/JsonApi/Hydrators/Container.php
git mv $J/Hydrators/Hydrator.php $C/Persistence/JsonApi/Hydrators/Hydrator.php
for f in ArrayField BackedEnumField BooleanField CollectionField DateTimeField EntityField Field MixedField \
         NumberField SingleEntityField TextField; do
  git mv $J/Hydrators/Fields/$f.php $C/Persistence/JsonApi/Hydrators/Fields/$f.php
done
git mv $J/Middleware/JsonApi.php $C/Middleware/JsonApi/JsonApi.php
git mv $J/Schemas/JsonApi.php $C/Schemas/JsonApi/JsonApi.php
git mv $J/Translations/jsonApi.en_US.neon $C/Translations/JsonApi/jsonApi.en_US.neon
git mv $J/Exceptions/JsonApi.php $C/Exceptions/JsonApi/JsonApi.php
git mv $J/Exceptions/JsonApiError.php $C/Exceptions/JsonApi/JsonApiError.php
git mv $J/Exceptions/JsonApiMultipleError.php $C/Exceptions/JsonApi/JsonApiMultipleError.php

git rm $J/DI/JsonApiExtension.php
git rm $J/Exceptions/Exception.php $J/Exceptions/InvalidArgument.php $J/Exceptions/InvalidState.php \
       $J/Exceptions/Logic.php $J/Exceptions/Runtime.php
git rm src/FastyBird/Library/JsonApi/LICENSE.md
```

Note: `Hydrators/Fields/SingleEntityField.php` — the actual list in the file tree has 10 field files (`ArrayField, BackedEnumField, BooleanField, CollectionField, DateTimeField, EntityField, Field, MixedField, NumberField, SingleEntityField, TextField` is 11 — re-count against the real listing before running this: the investigation recorded exactly these 11 names under `Hydrators/Fields/`; the loop above lists all 11).

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Encoding/JsonApi src/FastyBird/Core/Core/src/Helpers/JsonApi \
     src/FastyBird/Core/Core/src/Persistence/JsonApi src/FastyBird/Core/Core/src/Middleware/JsonApi \
     src/FastyBird/Core/Core/src/Schemas/JsonApi src/FastyBird/Core/Core/src/Exceptions/JsonApi \
     -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Library\\JsonApi\\Builder#FastyBird\\Core\\Encoding\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\JsonApi#FastyBird\\Core\\Encoding\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Objects#FastyBird\\Core\\Encoding\\JsonApi\\Objects#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Helpers#FastyBird\\Core\\Helpers\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Hydrators\\Fields#FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Hydrators#FastyBird\\Core\\Persistence\\JsonApi\\Hydrators#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Middleware#FastyBird\\Core\\Middleware\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Schemas#FastyBird\\Core\\Schemas\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Exceptions#FastyBird\\Core\\Exceptions\\JsonApi#g' \
    -e 's#namespace FastyBird\\Library\\JsonApi;#namespace FastyBird\\Core\\Encoding\\JsonApi;#' \
    -e 's#use FastyBird\\Library\\JsonApi;#use FastyBird\\Core\\Encoding\\JsonApi;#' \
    "$f"
done
```

`Exceptions/JsonApi/JsonApiError.php` and `JsonApiMultipleError.php` need one more targeted check: they likely `implements Exceptions\JsonApi` (the domain-specific marker, now `FastyBird\Core\Exceptions\JsonApi\JsonApi`) in addition to or instead of the shared `FastyBird\Core\Exceptions\Exception` marker — read both files after the sed and confirm the `implements` clause still resolves; the bulk substitution above handles the namespace but not a possible naming clash between the shared root `Core\Exceptions` namespace and the domain-tagged `Core\Exceptions\JsonApi` namespace both being imported in the same file (disambiguate with an alias, e.g. `use FastyBird\Core\Exceptions as CoreExceptions;`, if both are referenced).

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Encoding/JsonApi src/FastyBird/Core/Core/src/Helpers/JsonApi \
  src/FastyBird/Core/Core/src/Persistence/JsonApi src/FastyBird/Core/Core/src/Middleware/JsonApi \
  src/FastyBird/Core/Core/src/Schemas/JsonApi src/FastyBird/Core/Core/src/Exceptions/JsonApi -name "*.php"); do
  php -l "$f" || exit 1
done
echo clean
'
grep -rn 'FastyBird\\Library\\JsonApi' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Library/JsonApi
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/JsonApi content into fastybird/miniserver-core

Builder, top-level Document/IDocument, and the self-named JsonApi/ folder
(Encoder/SchemaContainer) all collapse into one flat Encoding\JsonApi bucket
per spec section 8 resolution 1. Objects/ keeps its internal structure.
JsonApi/JsonApiError/JsonApiMultipleError stay domain-tagged.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 12: Migrate `Library/Metadata` content

**Files:** mapping table (all under `src/FastyBird/Library/Metadata/src/` → `src/FastyBird/Core/Core/src/`). Metadata has no DI extension and no `Exceptions/` folder at all (confirmed by directory listing) — it is pure value types and constants.

| Old | New | Namespace change |
|---|---|---|
| `Constants.php` | `Constants/Metadata/Constants.php` | `FastyBird\Library\Metadata` → `FastyBird\Core\Constants\Metadata` (Flagged Assumption 7) |
| `Types/{DataType,DataTypeShort}.php` | `Types/Metadata/{...}.php` | `...Metadata\Types` → `FastyBird\Core\Types\Metadata` |
| `Types/Payloads/{Button,Cover,Payload,Switcher}.php` | `Types/Metadata/Payloads/{...}.php` | `...Metadata\Types\Payloads` → `FastyBird\Core\Types\Metadata\Payloads` |
| `Types/Sources/{Addon,Automator,Bridge,Connector,Module,Plugin,Source}.php` | `Types/Metadata/Sources/{...}.php` | `...Metadata\Types\Sources` → `FastyBird\Core\Types\Metadata\Sources` |

Non-`src/`: `tests/cases/unit/Common/ConstantsTest.php` → same relative path; `LICENSE.md` doesn't exist for this package (already relies on the repo-root license); `README.md`/`docs/Home.md` don't exist for this package either (only `README.md` exists — check and delete if present, it was not listed with a `docs/` subfolder in the investigation).

- [ ] **Step 1: Create directories and `git mv`**

```bash
M=src/FastyBird/Library/Metadata/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Constants/Metadata $C/Types/Metadata/Payloads $C/Types/Metadata/Sources

git mv $M/Constants.php $C/Constants/Metadata/Constants.php
git mv $M/Types/DataType.php $C/Types/Metadata/DataType.php
git mv $M/Types/DataTypeShort.php $C/Types/Metadata/DataTypeShort.php
git mv $M/Types/Payloads/Button.php $C/Types/Metadata/Payloads/Button.php
git mv $M/Types/Payloads/Cover.php $C/Types/Metadata/Payloads/Cover.php
git mv $M/Types/Payloads/Payload.php $C/Types/Metadata/Payloads/Payload.php
git mv $M/Types/Payloads/Switcher.php $C/Types/Metadata/Payloads/Switcher.php
git mv $M/Types/Sources/Addon.php $C/Types/Metadata/Sources/Addon.php
git mv $M/Types/Sources/Automator.php $C/Types/Metadata/Sources/Automator.php
git mv $M/Types/Sources/Bridge.php $C/Types/Metadata/Sources/Bridge.php
git mv $M/Types/Sources/Connector.php $C/Types/Metadata/Sources/Connector.php
git mv $M/Types/Sources/Module.php $C/Types/Metadata/Sources/Module.php
git mv $M/Types/Sources/Plugin.php $C/Types/Metadata/Sources/Plugin.php
git mv $M/Types/Sources/Source.php $C/Types/Metadata/Sources/Source.php

mkdir -p src/FastyBird/Core/Core/tests/cases/unit/Common
git mv src/FastyBird/Library/Metadata/tests/cases/unit/Common/ConstantsTest.php src/FastyBird/Core/Core/tests/cases/unit/Common/ConstantsTest.php
git rm src/FastyBird/Library/Metadata/README.md src/FastyBird/Library/Metadata/.gitignore
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Constants/Metadata src/FastyBird/Core/Core/src/Types/Metadata \
     src/FastyBird/Core/Core/tests/cases/unit/Common -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#namespace FastyBird\\Library\\Metadata;#namespace FastyBird\\Core\\Constants\\Metadata;#' \
    -e 's#FastyBird\\Library\\Metadata\\Types\\Payloads#FastyBird\\Core\\Types\\Metadata\\Payloads#g' \
    -e 's#FastyBird\\Library\\Metadata\\Types\\Sources#FastyBird\\Core\\Types\\Metadata\\Sources#g' \
    -e 's#FastyBird\\Library\\Metadata\\Types#FastyBird\\Core\\Types\\Metadata#g' \
    -e 's#FastyBird\\Library\\Metadata\\Tests#FastyBird\\Core\\Tests#g' \
    "$f"
done
```

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Constants/Metadata src/FastyBird/Core/Core/src/Types/Metadata -name "*.php"); do
  php -l "$f" || exit 1
done
echo clean
'
grep -rn 'FastyBird\\Library\\Metadata' src/FastyBird/Core/Core/src src/FastyBird/Core/Core/tests
git add -A src/FastyBird/Core/Core src/FastyBird/Library/Metadata
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/Metadata content into fastybird/miniserver-core

Pure value types and constants, no DI extension, no exceptions of its own.
Constants.php lands in the new Constants bucket alongside SimpleAuth's.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 13: Migrate `Library/Phone` content

**Files:** mapping table (all under `src/FastyBird/Library/Phone/src/` → `src/FastyBird/Core/Core/src/`). Note three different files are all named `Phone.php` in this package, landing at three different final paths — do not conflate them.

| Old | New | Namespace change |
|---|---|---|
| `Entities/Phone.php` | `Entities/Phone/Phone.php` | `...Phone\Entities` → `FastyBird\Core\Entities\Phone` |
| `TPhone.php` | `Entities/Phone/TPhone.php` | `FastyBird\Library\Phone` → `FastyBird\Core\Entities\Phone` (Flagged Assumption 9 — mixin trait grouped with the entity it mixes into) |
| `Phone.php` (top-level facade service) | `Services/Phone/Phone.php` | `FastyBird\Library\Phone` → `FastyBird\Core\Services\Phone` (Flagged Assumption 7) |
| `Types/Phone.php` (DBAL type) | `Types/Phone/Phone.php` | `...Phone\Types` → `FastyBird\Core\Types\Phone` |
| `Events/PhoneObjectSubscriber.php` | `Subscribers/Phone/PhoneObjectSubscriber.php` | `...Phone\Events` → `FastyBird\Core\Subscribers\Phone` (reclassified by role, same reasoning as Task 10's `TimestampableSubscriber` — this implements `Doctrine\Common\EventSubscriber`) |
| `Exceptions/{NoValidCountry,NoValidPhone,NoValidType}.php` | `Exceptions/Phone/{...}.php` (stays domain-tagged; already subclass their own `InvalidArgument` per spec section 5 — repoint that `extends` to the shared `Core\Exceptions\InvalidArgument`) | `...Phone\Exceptions` → `FastyBird\Core\Exceptions\Phone` |
| `DI/{DoctrinePhoneExtension,PhoneExtension}.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument}.php` | *(deleted — merged, Task 2)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |

- [ ] **Step 1: Create directories and `git mv`**

```bash
P=src/FastyBird/Library/Phone/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Entities/Phone $C/Services/Phone $C/Types/Phone $C/Subscribers/Phone $C/Exceptions/Phone

git mv $P/Entities/Phone.php $C/Entities/Phone/Phone.php
git mv $P/TPhone.php $C/Entities/Phone/TPhone.php
git mv $P/Phone.php $C/Services/Phone/Phone.php
git mv $P/Types/Phone.php $C/Types/Phone/Phone.php
git mv $P/Events/PhoneObjectSubscriber.php $C/Subscribers/Phone/PhoneObjectSubscriber.php
git mv $P/Exceptions/NoValidCountry.php $C/Exceptions/Phone/NoValidCountry.php
git mv $P/Exceptions/NoValidPhone.php $C/Exceptions/Phone/NoValidPhone.php
git mv $P/Exceptions/NoValidType.php $C/Exceptions/Phone/NoValidType.php

git rm $P/DI/DoctrinePhoneExtension.php $P/DI/PhoneExtension.php
git rm $P/Exceptions/Exception.php $P/Exceptions/InvalidArgument.php
git rm src/FastyBird/Library/Phone/LICENSE.md
```

- [ ] **Step 2: Rewrite namespaces (the three `Phone.php` files need distinct, exact-line `namespace`/`use` handling, not a shared bulk prefix substitution, for the same ambiguity reason as Task 5's SimpleAuth)**

```bash
sed -i -E \
  -e 's#namespace FastyBird\\Library\\Phone\\Entities;#namespace FastyBird\\Core\\Entities\\Phone;#' \
  -e 's#use FastyBird\\Library\\Phone\\Exceptions#use FastyBird\\Core\\Exceptions#' \
  src/FastyBird/Core/Core/src/Entities/Phone/Phone.php

sed -i -E \
  -e 's#namespace FastyBird\\Library\\Phone;#namespace FastyBird\\Core\\Entities\\Phone;#' \
  src/FastyBird/Core/Core/src/Entities/Phone/TPhone.php

sed -i -E \
  -e 's#namespace FastyBird\\Library\\Phone;#namespace FastyBird\\Core\\Services\\Phone;#' \
  -e 's#use FastyBird\\Library\\Phone\\Exceptions#use FastyBird\\Core\\Exceptions#' \
  -e 's#use FastyBird\\Library\\Phone\\Types#use FastyBird\\Core\\Types\\Phone#' \
  src/FastyBird/Core/Core/src/Services/Phone/Phone.php

sed -i -E \
  -e 's#namespace FastyBird\\Library\\Phone\\Types;#namespace FastyBird\\Core\\Types\\Phone;#' \
  src/FastyBird/Core/Core/src/Types/Phone/Phone.php

sed -i -E \
  -e 's#namespace FastyBird\\Library\\Phone\\Events;#namespace FastyBird\\Core\\Subscribers\\Phone;#' \
  -e 's#use FastyBird\\Library\\Phone\\Types#use FastyBird\\Core\\Types\\Phone#' \
  src/FastyBird/Core/Core/src/Subscribers/Phone/PhoneObjectSubscriber.php

sed -i -E \
  -e 's#namespace FastyBird\\Library\\Phone\\Exceptions;#namespace FastyBird\\Core\\Exceptions\\Phone;#' \
  -e 's#use FastyBird\\Library\\Phone\\Exceptions#use FastyBird\\Core\\Exceptions\\Phone as Exceptions#' \
  src/FastyBird/Core/Core/src/Exceptions/Phone/NoValidCountry.php \
  src/FastyBird/Core/Core/src/Exceptions/Phone/NoValidPhone.php \
  src/FastyBird/Core/Core/src/Exceptions/Phone/NoValidType.php
```

Read each of the three `NoValid*.php` files after this and confirm `extends InvalidArgument` (or `extends Exceptions\InvalidArgument`) now correctly resolves to `FastyBird\Core\Exceptions\InvalidArgument` (the shared class from Task 2), not `FastyBird\Core\Exceptions\Phone\InvalidArgument` (which doesn't exist — `InvalidArgument` was merged, not domain-tagged).

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Entities/Phone src/FastyBird/Core/Core/src/Services/Phone \
  src/FastyBird/Core/Core/src/Types/Phone src/FastyBird/Core/Core/src/Subscribers/Phone \
  src/FastyBird/Core/Core/src/Exceptions/Phone -name "*.php"); do php -l "$f" || exit 1; done
echo clean
'
grep -rn 'FastyBird\\Library\\Phone' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Library/Phone
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/Phone content into fastybird/miniserver-core

Three same-named Phone.php files land at three distinct paths (Entities,
Services, Types) by role. PhoneObjectSubscriber reclassified from Events to
Subscribers, same reasoning as DoctrineTimestampable's TimestampableSubscriber.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 14: Migrate `Library/SlimRouter` content

**Files:** mapping table (all under `src/FastyBird/Library/SlimRouter/src/` → `src/FastyBird/Core/Core/src/`). SlimRouter has no DI extension — its two services (`Http\ResponseFactory`, `Routing\Router`) are registered as raw NEON `factory:` entries in every consumer's `tests/common.neon` (PR #451's pattern, confirmed in this plan's investigation — see Task 22).

| Old | New | Namespace change |
|---|---|---|
| `Controllers/{ControllerResolver,IControllerResolver}.php` | `Controllers/SlimRouter/{...}.php` | `...SlimRouter\Controllers` → `FastyBird\Core\Controllers\SlimRouter` |
| `Http/{Response,ResponseFactory,Stream}.php` | `Http/SlimRouter/{...}.php` | `...SlimRouter\Http` → `FastyBird\Core\Http\SlimRouter` |
| `Middleware/{IMiddlewareDispatcher,MiddlewareDispatcher}.php` | `Middleware/SlimRouter/{...}.php` | `...SlimRouter\Middleware` → `FastyBird\Core\Middleware\SlimRouter` |
| `Routing/*.php` (11 top-level files) | `Routing/SlimRouter/*.php` | `...SlimRouter\Routing` → `FastyBird\Core\Routing\SlimRouter` |
| `Routing/Handlers/*.php` (5 files) | `Routing/SlimRouter/Handlers/*.php` | `...SlimRouter\Routing\Handlers` → `FastyBird\Core\Routing\SlimRouter\Handlers` |
| `Exceptions/{Http,HttpMethodNotAllowed,HttpNotFound,HttpSpecialized,StreamResourceCall}.php` | `Exceptions/SlimRouter/{...}.php` (stays domain-tagged, spec section 5) | `...SlimRouter\Exceptions` → `FastyBird\Core\Exceptions\SlimRouter` |
| `Exceptions/{Exception,InvalidArgument,Runtime}.php` | *(deleted — merged, Task 2)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |

- [ ] **Step 1: Create directories and `git mv`**

```bash
SR=src/FastyBird/Library/SlimRouter/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Controllers/SlimRouter $C/Http/SlimRouter $C/Middleware/SlimRouter $C/Routing/SlimRouter/Handlers $C/Exceptions/SlimRouter

git mv $SR/Controllers/ControllerResolver.php $C/Controllers/SlimRouter/ControllerResolver.php
git mv $SR/Controllers/IControllerResolver.php $C/Controllers/SlimRouter/IControllerResolver.php
git mv $SR/Http/Response.php $C/Http/SlimRouter/Response.php
git mv $SR/Http/ResponseFactory.php $C/Http/SlimRouter/ResponseFactory.php
git mv $SR/Http/Stream.php $C/Http/SlimRouter/Stream.php
git mv $SR/Middleware/IMiddlewareDispatcher.php $C/Middleware/SlimRouter/IMiddlewareDispatcher.php
git mv $SR/Middleware/MiddlewareDispatcher.php $C/Middleware/SlimRouter/MiddlewareDispatcher.php
git mv $SR/Routing/FastRouteDispatcher.php $C/Routing/SlimRouter/FastRouteDispatcher.php
git mv $SR/Routing/IRoute.php $C/Routing/SlimRouter/IRoute.php
git mv $SR/Routing/IRouteCollector.php $C/Routing/SlimRouter/IRouteCollector.php
git mv $SR/Routing/IRouteGroup.php $C/Routing/SlimRouter/IRouteGroup.php
git mv $SR/Routing/IRouteParser.php $C/Routing/SlimRouter/IRouteParser.php
git mv $SR/Routing/IRouter.php $C/Routing/SlimRouter/IRouter.php
git mv $SR/Routing/Route.php $C/Routing/SlimRouter/Route.php
git mv $SR/Routing/RouteCollector.php $C/Routing/SlimRouter/RouteCollector.php
git mv $SR/Routing/RouteGroup.php $C/Routing/SlimRouter/RouteGroup.php
git mv $SR/Routing/RouteHandler.php $C/Routing/SlimRouter/RouteHandler.php
git mv $SR/Routing/RouteParser.php $C/Routing/SlimRouter/RouteParser.php
git mv $SR/Routing/Router.php $C/Routing/SlimRouter/Router.php
git mv $SR/Routing/RoutingResults.php $C/Routing/SlimRouter/RoutingResults.php
git mv $SR/Routing/Handlers/IHandler.php $C/Routing/SlimRouter/Handlers/IHandler.php
git mv $SR/Routing/Handlers/IRequestHandler.php $C/Routing/SlimRouter/Handlers/IRequestHandler.php
git mv $SR/Routing/Handlers/RequestHandler.php $C/Routing/SlimRouter/Handlers/RequestHandler.php
git mv $SR/Routing/Handlers/RequestResponseArgsHandler.php $C/Routing/SlimRouter/Handlers/RequestResponseArgsHandler.php
git mv $SR/Routing/Handlers/RequestResponseHandler.php $C/Routing/SlimRouter/Handlers/RequestResponseHandler.php
git mv $SR/Exceptions/Http.php $C/Exceptions/SlimRouter/Http.php
git mv $SR/Exceptions/HttpMethodNotAllowed.php $C/Exceptions/SlimRouter/HttpMethodNotAllowed.php
git mv $SR/Exceptions/HttpNotFound.php $C/Exceptions/SlimRouter/HttpNotFound.php
git mv $SR/Exceptions/HttpSpecialized.php $C/Exceptions/SlimRouter/HttpSpecialized.php
git mv $SR/Exceptions/StreamResourceCall.php $C/Exceptions/SlimRouter/StreamResourceCall.php

git rm $SR/Exceptions/Exception.php $SR/Exceptions/InvalidArgument.php $SR/Exceptions/Runtime.php
git rm src/FastyBird/Library/SlimRouter/LICENSE.md
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Controllers/SlimRouter src/FastyBird/Core/Core/src/Http/SlimRouter \
     src/FastyBird/Core/Core/src/Middleware/SlimRouter src/FastyBird/Core/Core/src/Routing/SlimRouter \
     src/FastyBird/Core/Core/src/Exceptions/SlimRouter -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Library\\SlimRouter\\Controllers#FastyBird\\Core\\Controllers\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Http#FastyBird\\Core\\Http\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Middleware#FastyBird\\Core\\Middleware\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Routing\\Handlers#FastyBird\\Core\\Routing\\SlimRouter\\Handlers#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Routing#FastyBird\\Core\\Routing\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Exceptions#FastyBird\\Core\\Exceptions\\SlimRouter#g' \
    "$f"
done
```

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Controllers/SlimRouter src/FastyBird/Core/Core/src/Http/SlimRouter \
  src/FastyBird/Core/Core/src/Middleware/SlimRouter src/FastyBird/Core/Core/src/Routing/SlimRouter \
  src/FastyBird/Core/Core/src/Exceptions/SlimRouter -name "*.php"); do php -l "$f" || exit 1; done
echo clean
'
grep -rn 'FastyBird\\Library\\SlimRouter' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Library/SlimRouter
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/SlimRouter content into fastybird/miniserver-core

No DI extension to fold -- its two services stay raw NEON factory: entries,
updated in Task 22 alongside every other consumer NEON rewrite. The whole
Http* exception family stays domain-tagged per spec section 5.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 15: Migrate `Library/WebSockets` content — the WebSockets/WAMP/WsServer split (D7/D8)

This is the architecturally delicate task the spec calls out specifically. Two rules govern every placement below:

- **D7:** the socket-accept runtime (`Server\*`, plus everything that only that runtime touches — its client/topic live-connection state, its lifecycle events, its console logger) gets the `WsServer` domain tag. Everything the routing/controller framework needs that *other extensions also build against directly* (confirmed by this plan's own investigation: `Module/Devices/src/Router/SocketRoutes.php` builds a `WebSockets\Router\RouteList`, `Module/Devices/src/Controllers/ExchangeV1.php` and `Module/Ui`'s own controller both `extend WebSockets\Application\Controller\Controller`) gets the `WebSockets` domain tag instead, reachable by any extension.
- **D8:** the absorbed `Wamp\*` sub-namespace (PR #450) is dropped; its classes fold directly into whichever bucket their *role* matches, at the same flat level as the equivalent base-WebSockets classes — no `Wamp\` segment survives.

**New finding this plan's investigation made that D8 didn't anticipate:** applying "no third nesting level" literally produces *name collisions* between three Wamp classes and their base-WebSockets counterparts once both land in the same bucket, because Wamp's versions are behavioral replacements, not protocol-neutral extras. Flagged Assumption 11 covers the `Clients`/`WsServer` pair; the same investigation found a second pair in the `Controllers`/`WebSockets` bucket: `Wamp\Application\Application.php`/`IApplication.php` are a *second*, separate `Application`/`IApplication` class from base WebSockets' own `Application\Application.php`/`IApplication.php` (registered under a different DI prefix today, `webSocketsWAMP.application` vs the base extension's implicit autowiring). Both are renamed `WampClientFactory`/`WampClient`/`IWampClient`/`WampApplication`/`IWampApplication` on the Wamp side, placed alongside their base counterparts.

**Full mapping** (all under `src/FastyBird/Library/WebSockets/src/` → `src/FastyBird/Core/Core/src/`):

| Old | New | Notes |
|---|---|---|
| `Application/Application.php`, `Application/IApplication.php`, `Application/IRequest.php`, `Application/Reflection.php`, `Application/Request.php` | `Controllers/WebSockets/{...}.php` | The WAMP request-dispatch engine other extensions build controllers against — WebSockets-tagged (D7). Placed in the `Controllers` bucket (already established by SlimRouter) rather than a new ambiguous "Application" type bucket, which would collide in meaning with the `Application` *domain* tag used throughout this plan. |
| `Application/Controller/{Controller,ControllerFactory,IController,IControllerFactory}.php` | `Controllers/WebSockets/Controller/{...}.php` | Same bucket, preserves the `Controller/` subfolder. |
| `Application/Responses/{ErrorResponse,IResponse,MessageResponse,NullResponse}.php` | `Controllers/WebSockets/Responses/{...}.php` | Same bucket, preserves `Responses/`. |
| `Wamp/Application/Application.php`, `Wamp/Application/IApplication.php` | `Controllers/WebSockets/WampApplication.php`, `IWampApplication.php` | **Renamed** — collides with base `Application`/`IApplication` (Flagged Assumption 11 extended). |
| `Clients/{ClientFactory,IClientFactory,IStorage,Storage}.php` | `Clients/WsServer/{...}.php` | Live connection state of the running server process — WsServer-tagged (spec's own resolved note on `WebSockets/Clients`). |
| `Clients/Drivers/IDriver.php`, `Clients/Drivers/InMemory.php` | `Clients/WsServer/Drivers/{...}.php` | Same bucket, preserves `Drivers/`. |
| `Wamp/Clients/ClientFactory.php` | `Clients/WsServer/WampClientFactory.php` | **Renamed** — collides with base `ClientFactory` (Flagged Assumption 11). |
| `Entities/Clients/{Client,IClient}.php` | `Entities/WsServer/{Client,IClient}.php` | Paired with the `Clients/WsServer` runtime state move. |
| `Wamp/Entities/Clients/{Client,IClient}.php` | `Entities/WsServer/{WampClient,IWampClient}.php` | **Renamed** — collides with base (Flagged Assumption 11). |
| `Wamp/Entities/Topics/{ITopic,Topic}.php` | `Entities/WsServer/Topics/{...}.php` | Live topic/subscription state of the running server process, same reasoning as `Clients` — no base-WebSockets counterpart, no rename needed. |
| `Wamp/Topics/{IStorage,Storage}.php` | `Topics/WsServer/{...}.php` | New `Topics` bucket, parallel to `Clients` (both are runtime pub/sub state) — this plan's own extension of the spec's `Clients` reasoning, flagged here since Appendix A has no row for `Wamp/Topics` at all. |
| `Wamp/Topics/Drivers/InMemory.php` | `Topics/WsServer/Drivers/InMemory.php` | Same bucket. |
| `Entities/WebSockets/{IWebSocket,WebSocket}.php` | `Entities/WebSockets/{...}.php` | Protocol-level connection wrapper used by the `Protocols\*` layer — WebSockets-tagged, not runtime-specific. |
| `Wamp/Entities/PushMessages/{IMessage,Message}.php` | `Entities/WebSockets/PushMessages/{...}.php` | Protocol-level value objects, no base counterpart. |
| `Events/Application/{CloseEvent,ErrorEvent,MessageEvent,OpenEvent}.php` | `Events/WebSockets/{...}.php` | Dispatched by the `Controllers/WebSockets` `Application` class, which is WebSockets-tagged — events follow their dispatcher. |
| `Wamp/Events/Application/PushEvent.php` | `Events/WebSockets/PushEvent.php` | Dispatched by `WampApplication`, which is also WebSockets-tagged (not renamed — no collision, base has no `PushEvent`). |
| `Events/Server/{CreateEvent,StartEvent,StopEvent}.php` | `Events/WsServer/{...}.php` | Dispatched by `Server\Server`, which is WsServer-tagged. |
| `Events/Wrapper/{AfterIncommingMessageEvent,ClientConnectEvent,ClientDisconnectEvent,ClientErrorEvent,IncommingMessageEvent}.php` | `Events/WsServer/{...}.php` | Dispatched by `Server\Wrapper`, WsServer-tagged. |
| `Wamp/Subscribers/OnServerStartHandler.php` | `Subscribers/WsServer/OnServerStartHandler.php` | Hooks `Server\Server::$onStart`, runtime-specific. |
| `Commands/ServerCommand.php` | `Commands/WsServer/ServerCommand.php` | The actual `fb:web-server:start`-style command — runtime-specific (D7). |
| `Encoding/{IValidator,Validator}.php` | `Encoding/WebSockets/{...}.php` | Explicit Appendix A row. |
| `Protocols/{HyBi10,IData,IFrame,IMessage,IProtocol,ProtocolProxy,RFC6455}.php` | `Encoding/WebSockets/{...}.php` | Explicit Appendix A row. |
| `Protocols/RFC6455/{Frame,HandshakeVerifier,Message}.php` | `Encoding/WebSockets/RFC6455/{...}.php` | Same bucket, preserves `RFC6455/`. |
| `Wamp/Serializers/PushMessageSerializer.php` | `Encoding/WebSockets/PushMessageSerializer.php` | Wire-format serialization, same conceptual bucket as `Protocols`/`Encoding`. |
| `Http/{IRequest,IResponse,Request,RequestFactory,Response}.php` | `Http/WebSockets/{...}.php` | Explicit Appendix A row (`SlimRouter/Http`, `WebSockets/Http` → `Core\Http\<Domain>`). |
| `Logger/Console.php` | `Helpers/WsServer/Console.php` | Explicit spec-resolved row: console output formatting for `fb:web-server:start`/`fb:ws-server:start`, runtime-specific. |
| `Logger/Formatter/{IFormatter,Symfony}.php` | `Helpers/WsServer/Formatter/{...}.php` | Same bucket, preserves `Formatter/`. |
| `Router/{IRouter,LinkGenerator,Route,RouteList}.php` | `Routing/WebSockets/{...}.php` | Explicit Appendix A row. |
| `Server/{Configuration,FlashWrapper,Handlers,IWrapper,Server,Wrapper}.php` | `Server/WsServer/{...}.php` | The socket-accept runtime itself — D7's core example. New `Server` type bucket (self-descriptive, matches Task 16's identical treatment of `WebServer/Server`). |
| `User.php` | `Compat/User.php` | Vestigial Nette 2.x polyfill, moved as-is (Flagged Assumption 10). |
| `Wamp/PushMessages/{Consumer,ConsumersRegistry,IConsumer,IConsumersRegistry,IPusher,Pusher}.php` | `Messaging/WebSockets/PushMessages/{...}.php` | Same `Messaging` bucket Task 4 created for Exchange's `Consumers`/`Publisher`, tagged `WebSockets` here — these consume Exchange messages and push them out over WAMP. |
| `Exceptions/{Abort,BadRequest,BadResponse,BadSignal,ClientNotFound,ForbiddenRequest,InvalidController,InvalidLink,NotImplemented,Terminate}.php` | `Exceptions/WebSockets/{...}.php` | Stays domain-tagged per spec section 5; `NotImplemented` is the confirmed-incompatible-parent case (extends `Nette\NotImplementedException`, not merged with `DoctrineOrmQuery`'s). |
| `Exceptions/Storage.php` + `Wamp/Exceptions/Storage.php` | `Exceptions/WebSockets/Storage.php` (one file) | **Merged** — byte-identical shape, Flagged Assumption 2. |
| `Wamp/Exceptions/TopicNotFound.php` | `Exceptions/WebSockets/TopicNotFound.php` | No base counterpart, moves unchanged. |
| `DI/WebSocketsExtension.php`, `Wamp/DI/WebSocketsWAMPExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState,Logic,Runtime,UnexpectedValue}.php`, `Wamp/Exceptions/{Exception,InvalidArgument}.php` | *(deleted — merged, Task 2; Wamp's two are redundant zero-behavior subclasses, deleted outright not merged)* | — |
| `LICENSE.md` | *(deleted — Flagged Assumption 1)* | — |
| `assets/*`, `package.json` | *(handled by Task 20 — JS)* | — |

**Interfaces:**
- Consumes: `FastyBird\Core\Exceptions\{Exception,InvalidArgument,InvalidState,Logic,Runtime,UnexpectedValue}` (Task 2).
- Produces: `FastyBird\Core\Controllers\WebSockets\{Application,IApplication,WampApplication,IWampApplication,Controller\Controller,Controller\ControllerFactory}`, `FastyBird\Core\Clients\WsServer\{ClientFactory,WampClientFactory,Storage}`, `FastyBird\Core\Entities\WsServer\{Client,WampClient,Topics\Topic}`, `FastyBird\Core\Server\WsServer\{Server,Wrapper,Configuration}`, `FastyBird\Core\Routing\WebSockets\{RouteList,Route,LinkGenerator}`, `FastyBird\Core\Encoding\WebSockets\{RFC6455,Validator}`, `FastyBird\Core\Commands\WsServer\ServerCommand`. Task 17 (Plugin/WsServer) consumes `FastyBird\Core\Server\WsServer\Wrapper` directly (its `WsServerExtension::beforeCompile()` does `$builder->getByType(WebSockets\Server\Wrapper::class)` today — becomes `$builder->getByType(Server\WsServer\Wrapper::class)`, see Task 18).

- [ ] **Step 1: Create every target directory**

```bash
C=src/FastyBird/Core/Core/src
mkdir -p $C/Controllers/WebSockets/Controller $C/Controllers/WebSockets/Responses
mkdir -p $C/Clients/WsServer/Drivers $C/Entities/WsServer/Topics $C/Topics/WsServer/Drivers
mkdir -p $C/Entities/WebSockets/PushMessages
mkdir -p $C/Events/WebSockets $C/Events/WsServer
mkdir -p $C/Subscribers/WsServer $C/Commands/WsServer
mkdir -p $C/Encoding/WebSockets/RFC6455
mkdir -p $C/Http/WebSockets
mkdir -p $C/Helpers/WsServer/Formatter
mkdir -p $C/Routing/WebSockets
mkdir -p $C/Server/WsServer
mkdir -p $C/Compat
mkdir -p $C/Messaging/WebSockets/PushMessages
mkdir -p $C/Exceptions/WebSockets
```

- [ ] **Step 2: `git mv` the base (non-`Wamp`) files**

```bash
W=src/FastyBird/Library/WebSockets/src
C=src/FastyBird/Core/Core/src

git mv $W/Application/Application.php $C/Controllers/WebSockets/Application.php
git mv $W/Application/IApplication.php $C/Controllers/WebSockets/IApplication.php
git mv $W/Application/IRequest.php $C/Controllers/WebSockets/IRequest.php
git mv $W/Application/Reflection.php $C/Controllers/WebSockets/Reflection.php
git mv $W/Application/Request.php $C/Controllers/WebSockets/Request.php
git mv $W/Application/Controller/Controller.php $C/Controllers/WebSockets/Controller/Controller.php
git mv $W/Application/Controller/ControllerFactory.php $C/Controllers/WebSockets/Controller/ControllerFactory.php
git mv $W/Application/Controller/IController.php $C/Controllers/WebSockets/Controller/IController.php
git mv $W/Application/Controller/IControllerFactory.php $C/Controllers/WebSockets/Controller/IControllerFactory.php
git mv $W/Application/Responses/ErrorResponse.php $C/Controllers/WebSockets/Responses/ErrorResponse.php
git mv $W/Application/Responses/IResponse.php $C/Controllers/WebSockets/Responses/IResponse.php
git mv $W/Application/Responses/MessageResponse.php $C/Controllers/WebSockets/Responses/MessageResponse.php
git mv $W/Application/Responses/NullResponse.php $C/Controllers/WebSockets/Responses/NullResponse.php

git mv $W/Clients/ClientFactory.php $C/Clients/WsServer/ClientFactory.php
git mv $W/Clients/IClientFactory.php $C/Clients/WsServer/IClientFactory.php
git mv $W/Clients/IStorage.php $C/Clients/WsServer/IStorage.php
git mv $W/Clients/Storage.php $C/Clients/WsServer/Storage.php
git mv $W/Clients/Drivers/IDriver.php $C/Clients/WsServer/Drivers/IDriver.php
git mv $W/Clients/Drivers/InMemory.php $C/Clients/WsServer/Drivers/InMemory.php
git mv $W/Entities/Clients/Client.php $C/Entities/WsServer/Client.php
git mv $W/Entities/Clients/IClient.php $C/Entities/WsServer/IClient.php
git mv $W/Entities/WebSockets/IWebSocket.php $C/Entities/WebSockets/IWebSocket.php
git mv $W/Entities/WebSockets/WebSocket.php $C/Entities/WebSockets/WebSocket.php

git mv $W/Events/Application/CloseEvent.php $C/Events/WebSockets/CloseEvent.php
git mv $W/Events/Application/ErrorEvent.php $C/Events/WebSockets/ErrorEvent.php
git mv $W/Events/Application/MessageEvent.php $C/Events/WebSockets/MessageEvent.php
git mv $W/Events/Application/OpenEvent.php $C/Events/WebSockets/OpenEvent.php
git mv $W/Events/Server/CreateEvent.php $C/Events/WsServer/CreateEvent.php
git mv $W/Events/Server/StartEvent.php $C/Events/WsServer/StartEvent.php
git mv $W/Events/Server/StopEvent.php $C/Events/WsServer/StopEvent.php
git mv $W/Events/Wrapper/AfterIncommingMessageEvent.php $C/Events/WsServer/AfterIncommingMessageEvent.php
git mv $W/Events/Wrapper/ClientConnectEvent.php $C/Events/WsServer/ClientConnectEvent.php
git mv $W/Events/Wrapper/ClientDisconnectEvent.php $C/Events/WsServer/ClientDisconnectEvent.php
git mv $W/Events/Wrapper/ClientErrorEvent.php $C/Events/WsServer/ClientErrorEvent.php
git mv $W/Events/Wrapper/IncommingMessageEvent.php $C/Events/WsServer/IncommingMessageEvent.php

git mv $W/Commands/ServerCommand.php $C/Commands/WsServer/ServerCommand.php

git mv $W/Encoding/IValidator.php $C/Encoding/WebSockets/IValidator.php
git mv $W/Encoding/Validator.php $C/Encoding/WebSockets/Validator.php
git mv $W/Protocols/HyBi10.php $C/Encoding/WebSockets/HyBi10.php
git mv $W/Protocols/IData.php $C/Encoding/WebSockets/IData.php
git mv $W/Protocols/IFrame.php $C/Encoding/WebSockets/IFrame.php
git mv $W/Protocols/IMessage.php $C/Encoding/WebSockets/IMessage.php
git mv $W/Protocols/IProtocol.php $C/Encoding/WebSockets/IProtocol.php
git mv $W/Protocols/ProtocolProxy.php $C/Encoding/WebSockets/ProtocolProxy.php
git mv $W/Protocols/RFC6455.php $C/Encoding/WebSockets/RFC6455.php
git mv $W/Protocols/RFC6455/Frame.php $C/Encoding/WebSockets/RFC6455/Frame.php
git mv $W/Protocols/RFC6455/HandshakeVerifier.php $C/Encoding/WebSockets/RFC6455/HandshakeVerifier.php
git mv $W/Protocols/RFC6455/Message.php $C/Encoding/WebSockets/RFC6455/Message.php

git mv $W/Http/IRequest.php $C/Http/WebSockets/IRequest.php
git mv $W/Http/IResponse.php $C/Http/WebSockets/IResponse.php
git mv $W/Http/Request.php $C/Http/WebSockets/Request.php
git mv $W/Http/RequestFactory.php $C/Http/WebSockets/RequestFactory.php
git mv $W/Http/Response.php $C/Http/WebSockets/Response.php

git mv $W/Logger/Console.php $C/Helpers/WsServer/Console.php
git mv $W/Logger/Formatter/IFormatter.php $C/Helpers/WsServer/Formatter/IFormatter.php
git mv $W/Logger/Formatter/Symfony.php $C/Helpers/WsServer/Formatter/Symfony.php

git mv $W/Router/IRouter.php $C/Routing/WebSockets/IRouter.php
git mv $W/Router/LinkGenerator.php $C/Routing/WebSockets/LinkGenerator.php
git mv $W/Router/Route.php $C/Routing/WebSockets/Route.php
git mv $W/Router/RouteList.php $C/Routing/WebSockets/RouteList.php

git mv $W/Server/Configuration.php $C/Server/WsServer/Configuration.php
git mv $W/Server/FlashWrapper.php $C/Server/WsServer/FlashWrapper.php
git mv $W/Server/Handlers.php $C/Server/WsServer/Handlers.php
git mv $W/Server/IWrapper.php $C/Server/WsServer/IWrapper.php
git mv $W/Server/Server.php $C/Server/WsServer/Server.php
git mv $W/Server/Wrapper.php $C/Server/WsServer/Wrapper.php

git mv $W/User.php $C/Compat/User.php

git mv $W/Exceptions/Abort.php $C/Exceptions/WebSockets/Abort.php
git mv $W/Exceptions/BadRequest.php $C/Exceptions/WebSockets/BadRequest.php
git mv $W/Exceptions/BadResponse.php $C/Exceptions/WebSockets/BadResponse.php
git mv $W/Exceptions/BadSignal.php $C/Exceptions/WebSockets/BadSignal.php
git mv $W/Exceptions/ClientNotFound.php $C/Exceptions/WebSockets/ClientNotFound.php
git mv $W/Exceptions/ForbiddenRequest.php $C/Exceptions/WebSockets/ForbiddenRequest.php
git mv $W/Exceptions/InvalidController.php $C/Exceptions/WebSockets/InvalidController.php
git mv $W/Exceptions/InvalidLink.php $C/Exceptions/WebSockets/InvalidLink.php
git mv $W/Exceptions/NotImplemented.php $C/Exceptions/WebSockets/NotImplemented.php
git mv $W/Exceptions/Terminate.php $C/Exceptions/WebSockets/Terminate.php
git mv $W/Exceptions/Storage.php $C/Exceptions/WebSockets/Storage.php

git rm $W/DI/WebSocketsExtension.php
git rm $W/Exceptions/Exception.php $W/Exceptions/InvalidArgument.php $W/Exceptions/InvalidState.php \
       $W/Exceptions/Logic.php $W/Exceptions/Runtime.php $W/Exceptions/UnexpectedValue.php
git rm src/FastyBird/Library/WebSockets/LICENSE.md
```

- [ ] **Step 3: `git mv` the `Wamp/*` files, applying the collision renames**

```bash
WA=src/FastyBird/Library/WebSockets/src/Wamp
C=src/FastyBird/Core/Core/src

git mv $WA/Application/Application.php $C/Controllers/WebSockets/WampApplication.php
git mv $WA/Application/IApplication.php $C/Controllers/WebSockets/IWampApplication.php
git mv $WA/Clients/ClientFactory.php $C/Clients/WsServer/WampClientFactory.php
git mv $WA/Entities/Clients/Client.php $C/Entities/WsServer/WampClient.php
git mv $WA/Entities/Clients/IClient.php $C/Entities/WsServer/IWampClient.php
git mv $WA/Entities/Topics/ITopic.php $C/Entities/WsServer/Topics/ITopic.php
git mv $WA/Entities/Topics/Topic.php $C/Entities/WsServer/Topics/Topic.php
git mv $WA/Entities/PushMessages/IMessage.php $C/Entities/WebSockets/PushMessages/IMessage.php
git mv $WA/Entities/PushMessages/Message.php $C/Entities/WebSockets/PushMessages/Message.php
git mv $WA/Events/Application/PushEvent.php $C/Events/WebSockets/PushEvent.php
git mv $WA/Topics/IStorage.php $C/Topics/WsServer/IStorage.php
git mv $WA/Topics/Storage.php $C/Topics/WsServer/Storage.php
git mv $WA/Topics/Drivers/InMemory.php $C/Topics/WsServer/Drivers/InMemory.php
git mv $WA/Subscribers/OnServerStartHandler.php $C/Subscribers/WsServer/OnServerStartHandler.php
git mv $WA/PushMessages/Consumer.php $C/Messaging/WebSockets/PushMessages/Consumer.php
git mv $WA/PushMessages/ConsumersRegistry.php $C/Messaging/WebSockets/PushMessages/ConsumersRegistry.php
git mv $WA/PushMessages/IConsumer.php $C/Messaging/WebSockets/PushMessages/IConsumer.php
git mv $WA/PushMessages/IConsumersRegistry.php $C/Messaging/WebSockets/PushMessages/IConsumersRegistry.php
git mv $WA/PushMessages/IPusher.php $C/Messaging/WebSockets/PushMessages/IPusher.php
git mv $WA/PushMessages/Pusher.php $C/Messaging/WebSockets/PushMessages/Pusher.php
git mv $WA/Serializers/PushMessageSerializer.php $C/Encoding/WebSockets/PushMessageSerializer.php
git mv $WA/Exceptions/TopicNotFound.php $C/Exceptions/WebSockets/TopicNotFound.php

git rm $WA/DI/WebSocketsWAMPExtension.php
git rm $WA/Exceptions/Exception.php $WA/Exceptions/InvalidArgument.php $WA/Exceptions/Storage.php
```

`Wamp/Exceptions/Storage.php` is `git rm`, not merged with a `git mv`, because `Exceptions/WebSockets/Storage.php` (Step 2) already exists at the target path — the merge *is* keeping the base file and discarding the Wamp copy, confirmed byte-identical by direct read in this plan's own investigation.

- [ ] **Step 4: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Controllers/WebSockets src/FastyBird/Core/Core/src/Clients/WsServer \
     src/FastyBird/Core/Core/src/Entities/WsServer src/FastyBird/Core/Core/src/Entities/WebSockets \
     src/FastyBird/Core/Core/src/Topics/WsServer src/FastyBird/Core/Core/src/Events/WebSockets \
     src/FastyBird/Core/Core/src/Events/WsServer src/FastyBird/Core/Core/src/Subscribers/WsServer \
     src/FastyBird/Core/Core/src/Commands/WsServer src/FastyBird/Core/Core/src/Encoding/WebSockets \
     src/FastyBird/Core/Core/src/Http/WebSockets src/FastyBird/Core/Core/src/Helpers/WsServer \
     src/FastyBird/Core/Core/src/Routing/WebSockets src/FastyBird/Core/Core/src/Server/WsServer \
     src/FastyBird/Core/Core/src/Messaging/WebSockets src/FastyBird/Core/Core/src/Exceptions/WebSockets \
     src/FastyBird/Core/Core/src/Compat \
     -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Application#FastyBird\\Core\\Controllers\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Application\\Controller#FastyBird\\Core\\Controllers\\WebSockets\\Controller#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Application\\Responses#FastyBird\\Core\\Controllers\\WebSockets\\Responses#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Application#FastyBird\\Core\\Controllers\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Clients#FastyBird\\Core\\Clients\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Clients\\Drivers#FastyBird\\Core\\Clients\\WsServer\\Drivers#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Clients#FastyBird\\Core\\Clients\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Entities\\Clients#FastyBird\\Core\\Entities\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Entities\\Topics#FastyBird\\Core\\Entities\\WsServer\\Topics#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Entities\\PushMessages#FastyBird\\Core\\Entities\\WebSockets\\PushMessages#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Entities\\Clients#FastyBird\\Core\\Entities\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Entities\\WebSockets#FastyBird\\Core\\Entities\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Topics\\Drivers#FastyBird\\Core\\Topics\\WsServer\\Drivers#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Topics#FastyBird\\Core\\Topics\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Events\\Application#FastyBird\\Core\\Events\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Events\\Application#FastyBird\\Core\\Events\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Events\\Server#FastyBird\\Core\\Events\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Events\\Wrapper#FastyBird\\Core\\Events\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Subscribers#FastyBird\\Core\\Subscribers\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Commands#FastyBird\\Core\\Commands\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Encoding#FastyBird\\Core\\Encoding\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Protocols\\RFC6455#FastyBird\\Core\\Encoding\\WebSockets\\RFC6455#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Protocols#FastyBird\\Core\\Encoding\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Serializers#FastyBird\\Core\\Encoding\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Http#FastyBird\\Core\\Http\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Logger\\Formatter#FastyBird\\Core\\Helpers\\WsServer\\Formatter#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Logger#FastyBird\\Core\\Helpers\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Router#FastyBird\\Core\\Routing\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Server#FastyBird\\Core\\Server\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\PushMessages#FastyBird\\Core\\Messaging\\WebSockets\\PushMessages#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Exceptions#FastyBird\\Core\\Exceptions\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Exceptions#FastyBird\\Core\\Exceptions\\WebSockets#g' \
    "$f"
done
```

Order matters here — the more specific `Wamp\...` patterns and `...\Application\Controller`/`...\Application\Responses` sub-patterns must run before the shorter `...\Application` and `...\Exceptions` catch-alls, exactly as listed above, otherwise the catch-all rule fires first and the more specific rule never matches anything.

- [ ] **Step 5: By-hand class renames inside the six moved-and-renamed files**

`sed` renamed the *namespace*, not the *class name itself* — `Controllers/WebSockets/WampApplication.php` still declares `class Application` (or `interface Application`/`IApplication`) after Step 4's sed, which is wrong now that it sits next to the base `Application` class of the same namespace. Open each of the six files and rename the declared symbol to match its new filename:

```bash
sed -i -E 's/\bclass Application\b/class WampApplication/' src/FastyBird/Core/Core/src/Controllers/WebSockets/WampApplication.php
sed -i -E 's/\binterface IApplication\b/interface IWampApplication/' src/FastyBird/Core/Core/src/Controllers/WebSockets/IWampApplication.php
sed -i -E 's/\bclass ClientFactory\b/class WampClientFactory/' src/FastyBird/Core/Core/src/Clients/WsServer/WampClientFactory.php
sed -i -E 's/\bclass Client\b/class WampClient/' src/FastyBird/Core/Core/src/Entities/WsServer/WampClient.php
sed -i -E 's/\binterface IClient\b/interface IWampClient/' src/FastyBird/Core/Core/src/Entities/WsServer/IWampClient.php
```

`WampApplication.php` likely also `implements IApplication` (its own, now `IWampApplication`) — read the file and fix the `implements`/`extends` clause too if the class-name sed above didn't already cover it via the namespace rewrite. Same check for `WampClientFactory.php` (likely `implements Clients\IClientFactory` — the *base* interface, unchanged, since Wamp's factory is still a drop-in replacement for the same contract) and `WampClient.php`/`IWampClient.php` (likely `implements Entities\WsServer\IClient` or extends the base `Client` — read and confirm which).

- [ ] **Step 6: Verify and confirm no stale references**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Controllers/WebSockets src/FastyBird/Core/Core/src/Clients/WsServer \
  src/FastyBird/Core/Core/src/Entities/WsServer src/FastyBird/Core/Core/src/Entities/WebSockets \
  src/FastyBird/Core/Core/src/Topics/WsServer src/FastyBird/Core/Core/src/Events/WebSockets \
  src/FastyBird/Core/Core/src/Events/WsServer src/FastyBird/Core/Core/src/Subscribers/WsServer \
  src/FastyBird/Core/Core/src/Commands/WsServer src/FastyBird/Core/Core/src/Encoding/WebSockets \
  src/FastyBird/Core/Core/src/Http/WebSockets src/FastyBird/Core/Core/src/Helpers/WsServer \
  src/FastyBird/Core/Core/src/Routing/WebSockets src/FastyBird/Core/Core/src/Server/WsServer \
  src/FastyBird/Core/Core/src/Messaging/WebSockets src/FastyBird/Core/Core/src/Exceptions/WebSockets \
  src/FastyBird/Core/Core/src/Compat -name "*.php"); do
  php -l "$f" || exit 1
done
echo clean
'
grep -rn 'FastyBird\\Library\\WebSockets' src/FastyBird/Core/Core/src
```

Expected: `clean`, then no grep output.

- [ ] **Step 7: Commit**

```bash
git add -A src/FastyBird/Core/Core src/FastyBird/Library/WebSockets
git commit -m "$(cat <<'EOF'
refactor(core): migrate Library/WebSockets content, splitting WebSockets/WAMP/WsServer

Per spec D7/D8: the socket-accept runtime (Server/*, its live connection and
topic state, its lifecycle events, its console logger) tags WsServer, since
only fb:web-server:start's command instantiates it. The routing/controller
framework and WAMP protocol/message layer that Module/Devices and Module/Ui
build their own endpoints against stays under one WebSockets tag, reachable
by any extension. Wamp's own Application/ClientFactory/Client/IClient classes
collide by name with base WebSockets' equivalents once both land in the same
flat bucket (a gap D8 didn't anticipate) and are renamed Wamp* to coexist.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 16: Migrate `Plugin/WebServer` content — the HttpServer split (D7)

Symmetric with Task 15. Spec §2.6's general statement ("Router, Http, Middleware, Application... stay in Core") is *corrected* by Appendix A's own resolved investigation of `Application.php`'s actual content ("the actual PSR-7 HTTP request-dispatch runtime... Server-runtime code exactly like `WebSockets\Server\Server`... moves to `Core\<Type>\HttpServer\`, alongside `Server`/`Utils`") — Appendix A's row is the more specific, later-verified finding and takes precedence per its own "resolved" framing. So: `Application.php`, `Server/Factory.php`, `Utils/MimeTypesList.php`, the runtime `Commands`/`Events`/`Subscribers` get the **new** `HttpServer` tag; `Router`, `Http`, `Middleware` — the framework other extensions build against (confirmed: `config/common.neon`'s `decorator:` block attaches `@fbJsonApi.middlewares.jsonapi` and `@fbAccountsModule.middlewares.urlFormat` onto `Router\Router` directly) — keep the **original** `WebServer` tag, exactly like WebSockets' non-runtime classes kept the `WebSockets` tag rather than becoming `WsServer`.

**Mapping** (all under `src/FastyBird/Plugin/WebServer/src/` → `src/FastyBird/Core/Core/src/`):

| Old | New | Notes |
|---|---|---|
| `Application/Application.php` | `Server/HttpServer/Application.php` | Appendix A's specific resolution overrides the general §2.6 statement (see above). |
| `Server/Factory.php` | `Server/HttpServer/Factory.php` | Explicit Appendix A row, same `Server` type bucket Task 15 used for `WsServer`. |
| `Utils/MimeTypesList.php` | `Server/HttpServer/MimeTypesList.php` | Folds into the same `Server` bucket per Appendix A's parenthetical ("alongside Server/Utils"), not a separate `Utils` bucket. |
| `Commands/HttpServer.php` | `Commands/HttpServer/HttpServer.php` | Runtime command. |
| `Events/{Error,Request,Response,Startup}.php` | `Events/HttpServer/{...}.php` | Dispatched by the `Server/HttpServer/Application` runtime — events follow their dispatcher, same reasoning as Task 15. |
| `Subscribers/Server.php` | `Subscribers/HttpServer/Server.php` | Runtime lifecycle subscriber. |
| `Http/{Entity,Response,ResponseAttributes,ResponseFactory,ScalarEntity}.php` | `Http/WebServer/{...}.php` | Stays under the original `WebServer` tag — the framework, not the runtime. |
| `Middleware/{Cors,Router,StaticFiles}.php` | `Middleware/WebServer/{...}.php` | Same. |
| `Router/Router.php` | `Routing/WebServer/Router.php` | Same — this is the exact class the root `config/common.neon` decorator attaches to. |
| `Exceptions/FileNotFound.php` | `Exceptions/WebServer/FileNotFound.php` | Stays domain-tagged (spec section 5), original tag — thrown by `Middleware/StaticFiles`, which stays `WebServer`-tagged too. |
| `DI/WebServerExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/{Exception,InvalidArgument,InvalidState}.php` | *(deleted — merged, Task 2)* | — |

- [ ] **Step 1: Create directories and `git mv`**

```bash
WS=src/FastyBird/Plugin/WebServer/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Server/HttpServer $C/Commands/HttpServer $C/Events/HttpServer $C/Subscribers/HttpServer
mkdir -p $C/Http/WebServer $C/Middleware/WebServer $C/Routing/WebServer $C/Exceptions/WebServer

git mv $WS/Application/Application.php $C/Server/HttpServer/Application.php
git mv $WS/Server/Factory.php $C/Server/HttpServer/Factory.php
git mv $WS/Utils/MimeTypesList.php $C/Server/HttpServer/MimeTypesList.php
git mv $WS/Commands/HttpServer.php $C/Commands/HttpServer/HttpServer.php
git mv $WS/Events/Error.php $C/Events/HttpServer/Error.php
git mv $WS/Events/Request.php $C/Events/HttpServer/Request.php
git mv $WS/Events/Response.php $C/Events/HttpServer/Response.php
git mv $WS/Events/Startup.php $C/Events/HttpServer/Startup.php
git mv $WS/Subscribers/Server.php $C/Subscribers/HttpServer/Server.php
git mv $WS/Http/Entity.php $C/Http/WebServer/Entity.php
git mv $WS/Http/Response.php $C/Http/WebServer/Response.php
git mv $WS/Http/ResponseAttributes.php $C/Http/WebServer/ResponseAttributes.php
git mv $WS/Http/ResponseFactory.php $C/Http/WebServer/ResponseFactory.php
git mv $WS/Http/ScalarEntity.php $C/Http/WebServer/ScalarEntity.php
git mv $WS/Middleware/Cors.php $C/Middleware/WebServer/Cors.php
git mv $WS/Middleware/Router.php $C/Middleware/WebServer/Router.php
git mv $WS/Middleware/StaticFiles.php $C/Middleware/WebServer/StaticFiles.php
git mv $WS/Router/Router.php $C/Routing/WebServer/Router.php
git mv $WS/Exceptions/FileNotFound.php $C/Exceptions/WebServer/FileNotFound.php

git rm $WS/DI/WebServerExtension.php
git rm $WS/Exceptions/Exception.php $WS/Exceptions/InvalidArgument.php $WS/Exceptions/InvalidState.php

git rm src/FastyBird/Plugin/WebServer/tests/cases/unit/DI/WebServerExtensionTest.php
git rm src/FastyBird/Plugin/WebServer/tests/common.neon
mkdir -p src/FastyBird/Core/Core/tests/cases/unit/Commands
diff src/FastyBird/Plugin/WebServer/tests/cases/unit/BaseTestCase.php src/FastyBird/Core/Core/tests/cases/unit/BaseTestCase.php \
  && git rm src/FastyBird/Plugin/WebServer/tests/cases/unit/BaseTestCase.php \
  || echo "REVIEW: WebServer's BaseTestCase.php differs — read both, keep the union"
git mv src/FastyBird/Plugin/WebServer/tests/cases/unit/Commands/HttpServerCommandTest.php src/FastyBird/Core/Core/tests/cases/unit/Commands/HttpServerCommandTest.php
git rm src/FastyBird/Plugin/WebServer/README.md src/FastyBird/Plugin/WebServer/docs/Home.md
```

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Server/HttpServer src/FastyBird/Core/Core/src/Commands/HttpServer \
     src/FastyBird/Core/Core/src/Events/HttpServer src/FastyBird/Core/Core/src/Subscribers/HttpServer \
     src/FastyBird/Core/Core/src/Http/WebServer src/FastyBird/Core/Core/src/Middleware/WebServer \
     src/FastyBird/Core/Core/src/Routing/WebServer src/FastyBird/Core/Core/src/Exceptions/WebServer \
     src/FastyBird/Core/Core/tests/cases/unit/Commands \
     -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Plugin\\WebServer\\Application#FastyBird\\Core\\Server\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Server#FastyBird\\Core\\Server\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Utils#FastyBird\\Core\\Server\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Commands#FastyBird\\Core\\Commands\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Events#FastyBird\\Core\\Events\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Subscribers#FastyBird\\Core\\Subscribers\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Http#FastyBird\\Core\\Http\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Middleware#FastyBird\\Core\\Middleware\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Router#FastyBird\\Core\\Routing\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Exceptions#FastyBird\\Core\\Exceptions\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Tests#FastyBird\\Core\\Tests#g' \
    "$f"
done
```

- [ ] **Step 3: Verify and commit**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Server/HttpServer src/FastyBird/Core/Core/src/Commands/HttpServer \
  src/FastyBird/Core/Core/src/Events/HttpServer src/FastyBird/Core/Core/src/Subscribers/HttpServer \
  src/FastyBird/Core/Core/src/Http/WebServer src/FastyBird/Core/Core/src/Middleware/WebServer \
  src/FastyBird/Core/Core/src/Routing/WebServer src/FastyBird/Core/Core/src/Exceptions/WebServer -name "*.php"); do
  php -l "$f" || exit 1
done
echo clean
'
grep -rn 'FastyBird\\Plugin\\WebServer' src/FastyBird/Core/Core/src
git add -A src/FastyBird/Core/Core src/FastyBird/Plugin/WebServer
git commit -m "$(cat <<'EOF'
refactor(core): migrate Plugin/WebServer content, splitting HttpServer from the routing framework

Application.php/Server/Utils/the runtime Commands+Events+Subscribers get the
new HttpServer tag (D7; Appendix A's specific read of Application.php's
content corrects spec section 2.6's general statement). Router/Http/Middleware
-- the framework the root config/common.neon decorator attaches to directly
-- keep the original WebServer tag, symmetric with how WebSockets' non-runtime
classes kept their tag in the prior commit.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 17: Migrate `Plugin/WsServer` content

**Files:** the smallest of the runtime packages — already entirely generic types per Appendix A ("`Plugin/WsServer` adds only `DI`, `Exceptions`, `Commands`, `Subscribers`, `Events` — all already-generic types"). Everything here folds into the `WsServer` domain tag Task 15 already started populating.

| Old (`src/FastyBird/Plugin/WsServer/src/...`) | New (`src/FastyBird/Core/Core/src/...`) | Namespace change |
|---|---|---|
| `Commands/WsServer.php` | `Commands/WsServer/WsServer.php` | `...WsServer\Commands` → `FastyBird\Core\Commands\WsServer` |
| `Constants.php` | `Constants/WsServer/Constants.php` | `FastyBird\Plugin\WsServer` → `FastyBird\Core\Constants\WsServer` (same pattern as SimpleAuth's/Metadata's top-level `Constants.php`) |
| `Events/{ClientConnected,Error,IncomingMessage,Startup}.php` | `Events/WsServer/{...}.php` | `...WsServer\Events` → `FastyBird\Core\Events\WsServer` |
| `Subscribers/Client.php` | `Subscribers/WsServer/Client.php` | `...WsServer\Subscribers` → `FastyBird\Core\Subscribers\WsServer` |
| `DI/WsServerExtension.php` | *(deleted — folded into Task 18)* | — |
| `Exceptions/Exception.php` | *(deleted — merged, Task 2)* | — |
| `Exceptions/Logic.php` | *(deleted — merged into shared `Core\Exceptions\Logic`, Task 2; this was the fifth occurrence Flagged Assumption 4 found)* | — |

- [ ] **Step 1: Create directories and `git mv`**

```bash
WSV=src/FastyBird/Plugin/WsServer/src
C=src/FastyBird/Core/Core/src
mkdir -p $C/Commands/WsServer $C/Constants/WsServer $C/Events/WsServer $C/Subscribers/WsServer

git mv $WSV/Commands/WsServer.php $C/Commands/WsServer/WsServer.php
git mv $WSV/Constants.php $C/Constants/WsServer/Constants.php
git mv $WSV/Events/ClientConnected.php $C/Events/WsServer/ClientConnected.php
git mv $WSV/Events/Error.php $C/Events/WsServer/Error.php
git mv $WSV/Events/IncomingMessage.php $C/Events/WsServer/IncomingMessage.php
git mv $WSV/Events/Startup.php $C/Events/WsServer/Startup.php
git mv $WSV/Subscribers/Client.php $C/Subscribers/WsServer/Client.php

git rm $WSV/DI/WsServerExtension.php
git rm $WSV/Exceptions/Exception.php $WSV/Exceptions/Logic.php

git rm src/FastyBird/Plugin/WsServer/tests/cases/unit/DI/WsServerExtensionTest.php
git rm src/FastyBird/Plugin/WsServer/tests/common.neon
diff src/FastyBird/Plugin/WsServer/tests/cases/unit/BaseTestCase.php src/FastyBird/Core/Core/tests/cases/unit/BaseTestCase.php \
  && git rm src/FastyBird/Plugin/WsServer/tests/cases/unit/BaseTestCase.php \
  || echo "REVIEW: WsServer's BaseTestCase.php differs — read both, keep the union"
git rm src/FastyBird/Plugin/WsServer/README.md src/FastyBird/Plugin/WsServer/docs/Home.md
```

Note `Commands/WsServer.php` moves into a directory also named `WsServer` (`Commands/WsServer/WsServer.php`) — this is correct, not a mistake: the file `WsServer.php` (class name matches, `class WsServer`) sits inside the `Commands` type bucket tagged with the `WsServer` domain, same shape as `Commands/HttpServer/HttpServer.php` in Task 16.

- [ ] **Step 2: Rewrite namespaces**

```bash
find src/FastyBird/Core/Core/src/Commands/WsServer src/FastyBird/Core/Core/src/Constants/WsServer \
     src/FastyBird/Core/Core/src/Events/WsServer src/FastyBird/Core/Core/src/Subscribers/WsServer \
     -name '*.php' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Plugin\\WsServer\\Commands#FastyBird\\Core\\Commands\\WsServer#g' \
    -e 's#namespace FastyBird\\Plugin\\WsServer;#namespace FastyBird\\Core\\Constants\\WsServer;#' \
    -e 's#FastyBird\\Plugin\\WsServer\\Events#FastyBird\\Core\\Events\\WsServer#g' \
    -e 's#FastyBird\\Plugin\\WsServer\\Subscribers#FastyBird\\Core\\Subscribers\\WsServer#g' \
    -e 's#FastyBird\\Plugin\\WsServer\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Server#FastyBird\\Core\\Server\\WsServer#g' \
    -e 's#FastyBird\\Core\\Exchange\\Exchange#FastyBird\\Core\\Messaging\\Exchange#g' \
    "$f"
done
```

The last two rules matter specifically for `Subscribers/WsServer/Client.php` and `Commands/WsServer/WsServer.php`: `WsServerExtension.php` (deleted, folded into Task 18) imported `FastyBird\Library\WebSockets` (base, for `Server\Wrapper`) and `FastyBird\Core\Exchange\Exchange as ExchangeExchange` (for `Factory::class`) — check whether either surviving moved file (not the deleted extension) also references these; if `grep` in Step 3 finds none, both rules are no-ops here, which is fine.

- [ ] **Step 3: Verify and confirm no stale references**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Core/Core/src/Commands/WsServer src/FastyBird/Core/Core/src/Constants/WsServer \
  src/FastyBird/Core/Core/src/Events/WsServer src/FastyBird/Core/Core/src/Subscribers/WsServer -name "*.php"); do
  php -l "$f" || exit 1
done
echo clean
'
grep -rn 'FastyBird\\Plugin\\WsServer' src/FastyBird/Core/Core/src
```

- [ ] **Step 4: Commit**

```bash
git add -A src/FastyBird/Core/Core src/FastyBird/Plugin/WsServer
git commit -m "$(cat <<'EOF'
refactor(core): migrate Plugin/WsServer content into fastybird/miniserver-core

The last of the three WsServer-tagged sources (with Library/WebSockets'
Server/Clients/Topics runtime from two commits ago). Logic merges into the
shared Core\Exceptions\Logic -- the fifth occurrence the spec's own census
missed (flagged assumption 4).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

With this commit, every one of the 15 former packages' `src/` content has moved. `src/FastyBird/{Core/Application,Core/Exchange,Core/SimpleAuth,Core/Tools,Library/DateTimeFactory,Library/DoctrineCrud,Library/DoctrineOrmQuery,Library/DoctrineTimestampable,Library/JsonApi,Library/Metadata,Library/Phone,Library/SlimRouter,Library/WebSockets,Plugin/WebServer,Plugin/WsServer}/src/` should each be an empty tree (only `DI/`, and in a few cases `Application/`/`Exceptions/` directories, may remain with nothing but empty parent directories after `git rm`/`git mv` drained their contents — `git` does not track empty directories, so these vanish from `git status` on their own). Confirm before moving to Task 18:

```bash
find src/FastyBird/Core/Application/src src/FastyBird/Core/Exchange/src src/FastyBird/Core/SimpleAuth/src \
     src/FastyBird/Core/Tools/src src/FastyBird/Library/DateTimeFactory/src src/FastyBird/Library/DoctrineCrud/src \
     src/FastyBird/Library/DoctrineOrmQuery/src src/FastyBird/Library/DoctrineTimestampable/src \
     src/FastyBird/Library/JsonApi/src src/FastyBird/Library/Metadata/src src/FastyBird/Library/Phone/src \
     src/FastyBird/Library/SlimRouter/src src/FastyBird/Library/WebSockets/src src/FastyBird/Plugin/WebServer/src \
     src/FastyBird/Plugin/WsServer/src -type f 2>/dev/null
```

Expected: no output. If any file listed, a bucket got missed above — cross-reference it against the mapping tables in Tasks 3-17 before proceeding.

---

## Task 18: Build the unified `Core\DI\CoreExtension` (D5) and the package's own config files

This is the task spec §6 calls out as "real integration work, not a namespace move." Twelve `CompilerExtension` classes (`ApplicationExtension`, `ExchangeExtension`, `SimpleAuthExtension`, `ToolsExtension`, `DateTimeFactoryExtension`, `DoctrineCrudExtension`, `DoctrineTimestampableExtension`, `JsonApiExtension`, `PhoneExtension`, `DoctrinePhoneExtension`, `WebSocketsExtension`, `WebSocketsWAMPExtension`, `WebServerExtension`, `WsServerExtension` — 14 classes; `DoctrineOrmQuery` and `SlimRouter` never had one) consolidate into one `FastyBird\Core\DI\CoreExtension`.

**Two real collisions found by this plan's own investigation (Flagged Assumption 12), resolved by one uniform rule:** every service definition is keyed `<domainTag>.<originalRelativeKey>` (or `<domainTag>.wamp.<originalRelativeKey>` for what used to be the separate WAMP extension's own registrations), where `<domainTag>` matches the final namespace tag from Tasks 3-17, not the pre-split extension identity. This eliminates the `configuration` collision (`SimpleAuthExtension` vs `DoctrineTimestampableExtension`, now `simpleAuth.configuration` vs `doctrineTimestampable.configuration`), the `subscriber` collision (`DoctrinePhoneExtension` vs `DoctrineTimestampableExtension`, now `phone.doctrinePhone.subscriber` vs `doctrineTimestampable.subscriber`), and the `clients.factory` collision (base `WebSocketsExtension` vs `WebSocketsWAMPExtension`, now `wsServer.clients.factory` vs `wsServer.wamp.clientsFactory`) — applied uniformly, not case-by-case, per D2's own stated principle extended to service keys.

**The config schema resolves two more collisions** (Flagged Assumption 12): base WebSockets' `storage.clients.*` vs WAMP's `storage.topics.*` (both used the bare top-level key `storage`) and base WebSockets' `server.*` (`httpHost`/`port`/`address`/`secured`) vs WebServer's `server.*` (`address`/`port`/`certificate`) — resolved by nesting every former extension's schema under its own domain key.

**Files:**
- Create: `src/FastyBird/Core/Core/src/DI/CoreExtension.php`
- Modify: `src/FastyBird/Core/Core/config/common.neon`
- Modify: `src/FastyBird/Core/Core/config/defaults.neon`
- Delete: `src/FastyBird/Core/Core/tests/cases/unit/DI/ApplicationExtensionTest.php` was already deleted in Task 3 — create its replacement:
- Create: `src/FastyBird/Core/Core/tests/cases/unit/DI/CoreExtensionTest.php`

**Interfaces:**
- Consumes: every namespace Tasks 2-17 produced.
- Produces: `FastyBird\Core\DI\CoreExtension::NAME = 'fbCore'`, `FastyBird\Core\DI\CoreExtension::register(Boot\Configurator $config, string $extensionName = self::NAME)`. Task 19 and every one of the 32 consumers (Task 22) registers this one extension under the key `fbCore` in place of the 14 old extension registrations.

- [ ] **Step 1: Write `src/FastyBird/Core/Core/src/DI/CoreExtension.php`**

```php
<?php declare(strict_types = 1);

/**
 * CoreExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           2026-09-20
 */

namespace FastyBird\Core\DI;

use Casbin;
use Doctrine;
use FastyBird\Core\Boot;
use FastyBird\Core\Clients as WsServerClients;
use FastyBird\Core\Commands as HttpServerCommands;
use FastyBird\Core\Commands as WsServerCommands;
use FastyBird\Core\Configuration as DoctrineTimestampableConfiguration;
use FastyBird\Core\Configuration as SimpleAuthConfiguration;
use FastyBird\Core\Controllers as WebSocketsControllers;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Documents as ExchangeDocuments;
use FastyBird\Core\Encoding as JsonApiEncoding;
use FastyBird\Core\Encoding as WebSocketsEncoding;
use FastyBird\Core\Entities as WsServerEntities;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Events as ApplicationEvents;
use FastyBird\Core\Events as SimpleAuthEvents;
use FastyBird\Core\Events as ToolsEvents;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Helpers as DoctrineCrudHelpers;
use FastyBird\Core\Helpers as JsonApiHelpers;
use FastyBird\Core\Helpers as ToolsHelpers;
use FastyBird\Core\Helpers as WsServerHelpers;
use FastyBird\Core\Http as WebServerHttp;
use FastyBird\Core\Mapping as DoctrineCrudMapping;
use FastyBird\Core\Mapping as SimpleAuthMapping;
use FastyBird\Core\Messaging as ExchangeMessaging;
use FastyBird\Core\Messaging as WebSocketsMessaging;
use FastyBird\Core\Middleware as SimpleAuthMiddleware;
use FastyBird\Core\Middleware as WebServerMiddleware;
use FastyBird\Core\Persistence as DoctrineCrudPersistence;
use FastyBird\Core\Persistence as JsonApiPersistence;
use FastyBird\Core\Providers as DoctrineTimestampableProviders;
use FastyBird\Core\Routing;
use FastyBird\Core\Schemas as JsonApiSchemas;
use FastyBird\Core\Schemas as ToolsSchemas;
use FastyBird\Core\Security as SimpleAuthSecurity;
use FastyBird\Core\Server as HttpServerServer;
use FastyBird\Core\Server as WsServerServer;
use FastyBird\Core\Services as DateTimeFactoryServices;
use FastyBird\Core\Services as PhoneServices;
use FastyBird\Core\Services as SimpleAuthServices;
use FastyBird\Core\Subscribers as ApplicationSubscribers;
use FastyBird\Core\Subscribers as DoctrineTimestampableSubscribers;
use FastyBird\Core\Subscribers as HttpServerSubscribers;
use FastyBird\Core\Subscribers as PhoneSubscribers;
use FastyBird\Core\Subscribers as SimpleAuthSubscribers;
use FastyBird\Core\Subscribers as WsServerSubscribers;
use FastyBird\Core\UI;
use libphonenumber;
use Monolog;
use Nette;
use Nette\Application;
use Nette\Application as NetteApplication;
use Nette\Bootstrap;
use Nette\Caching;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use Psr\EventDispatcher;
use Psr\Log;
use React;
use Sentry;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher;
use Symfony\Contracts\EventDispatcher as SymfonyEventDispatcherContracts;
use stdClass;
use function array_values;
use function assert;
use function class_alias;
use function class_exists;
use function interface_exists;
use function is_bool;
use function is_dir;
use function is_file;
use function is_string;
use function krsort;
use function ksort;
use function lcfirst;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;
use const SORT_NUMERIC;
use const SORT_STRING;

if (!class_exists('Nette\PhpGenerator\Literal')) {
	class_alias('Nette\PhpGenerator\PhpLiteral', 'Nette\PhpGenerator\Literal');
}

/**
 * FastyBird Core -- consolidated DI extension
 *
 * Replaces ApplicationExtension, ExchangeExtension, SimpleAuthExtension, ToolsExtension,
 * DateTimeFactoryExtension, DoctrineCrudExtension, DoctrineTimestampableExtension,
 * JsonApiExtension, PhoneExtension, DoctrinePhoneExtension, WebSocketsExtension,
 * WebSocketsWAMPExtension, WebServerExtension and WsServerExtension. See
 * docs/superpowers/specs/2026-09-20-core-consolidation-design.md section 6.
 *
 * @package        FastyBird:Core!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CoreExtension extends DI\CompilerExtension
{

	public const NAME = 'fbCore';

	public const DRIVER_TAG = 'fastybird.application.attribute.driver';

	public const CONSUMER_STATE = 'consumer_state';

	public const CONSUMER_ROUTING_KEY = 'consumer_routing_key';

	public const TAG_WEBSOCKETS_ROUTES = 'fastybird.core.websockets.routes';

	public static function register(
		Boot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'application' => Schema\Expect::structure([
				'logging' => Schema\Expect::structure([
					'rotatingFile' => Schema\Expect::structure([
						'enabled' => Schema\Expect::bool(true),
						'level' => Schema\Expect::int(Monolog\Level::Info),
						'filename' => Schema\Expect::string('app.log'),
					]),
					'stdOut' => Schema\Expect::structure([
						'enabled' => Schema\Expect::bool(false),
						'level' => Schema\Expect::int(Monolog\Level::Info),
					]),
					'console' => Schema\Expect::structure([
						'enabled' => Schema\Expect::bool(false),
						'level' => Schema\Expect::int(Monolog\Level::Info),
					]),
				]),
				'documents' => Schema\Expect::structure([
					'mapping' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string())->required(),
					'excludePaths' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string()),
				]),
			]),
			'simpleAuth' => Schema\Expect::structure([
				'token' => Schema\Expect::structure([
					'issuer' => Schema\Expect::string(),
					'signature' => Schema\Expect::string()->required(),
				]),
				'enable' => Schema\Expect::structure([
					'middleware' => Schema\Expect::bool(false),
					'doctrine' => Schema\Expect::structure([
						'mapping' => Schema\Expect::bool(false),
						'models' => Schema\Expect::bool(false),
					]),
					'casbin' => Schema\Expect::structure([
						'database' => Schema\Expect::bool(false),
					]),
					'nette' => Schema\Expect::structure([
						'application' => Schema\Expect::bool(false),
					]),
				]),
				'application' => Schema\Expect::structure([
					'signInUrl' => Schema\Expect::string(),
					'homeUrl' => Schema\Expect::string('/'),
				]),
				'services' => Schema\Expect::structure([
					'identity' => Schema\Expect::bool(false),
				]),
				'casbin' => Schema\Expect::structure([
					'model' => Schema\Expect::string(
						// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
						__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'model.conf',
					),
					'policy' => Schema\Expect::string(),
				]),
			]),
			'tools' => Schema\Expect::structure([
				'sentry' => Schema\Expect::structure([
					'dsn' => Schema\Expect::string()->nullable(),
					'level' => Schema\Expect::int(Monolog\Level::Warning),
				]),
			]),
			'dateTimeFactory' => Schema\Expect::structure([
				'timeZone' => Schema\Expect::string('UTC'),
				'system' => Schema\Expect::bool(true),
				'frozen' => Schema\Expect::anyOf(Schema\Expect::float(), Schema\Expect::mixed()),
			]),
			'doctrineTimestampable' => Schema\Expect::structure([
				'lazyAssociation' => Schema\Expect::bool(false),
				'autoMapField' => Schema\Expect::bool(true),
				'dbFieldType' => Schema\Expect::string('datetime_immutable'),
			]),
			'jsonApi' => Schema\Expect::structure([
				'meta' => Schema\Expect::structure([
					'author' => Schema\Expect::anyOf(Schema\Expect::string(), Schema\Expect::array())
						->default('FastyBird team'),
					'copyright' => Schema\Expect::string()->default(null)->nullable(),
				]),
			]),
			'webSockets' => Schema\Expect::structure([
				'storage' => Schema\Expect::structure([
					'clients' => Schema\Expect::structure([
						'driver' => Schema\Expect::string('@wsServer.clients.driver.memory'),
						'ttl' => Schema\Expect::int(0),
					]),
					'topics' => Schema\Expect::structure([
						'driver' => Schema\Expect::string('@wsServer.wamp.topics.driver.memory'),
						'ttl' => Schema\Expect::int(0),
					]),
				]),
				'server' => Schema\Expect::structure([
					'httpHost' => Schema\Expect::string('localhost'),
					'port' => Schema\Expect::int(8_080),
					'address' => Schema\Expect::string('0.0.0.0'),
					'secured' => Schema\Expect::structure([
						'enable' => Schema\Expect::bool(false),
						'sslSettings' => Schema\Expect::array([]),
					]),
				]),
				'routes' => Schema\Expect::array([]),
				'mapping' => Schema\Expect::array([]),
				'loop' => Schema\Expect::anyOf(
					Schema\Expect::string(),
					Schema\Expect::type(DI\Definitions\Statement::class),
				)->nullable(),
			]),
			'httpServer' => Schema\Expect::structure([
				'static' => Schema\Expect::structure([
					'publicRoot' => Schema\Expect::string()->nullable(),
					'enabled' => Schema\Expect::bool(false),
				]),
				'server' => Schema\Expect::structure([
					'address' => Schema\Expect::string('127.0.0.1'),
					'port' => Schema\Expect::int(8_000),
					'certificate' => Schema\Expect::string()->nullable(),
				]),
				'cors' => Schema\Expect::structure([
					'enabled' => Schema\Expect::bool(false),
					'allow' => Schema\Expect::structure([
						'origin' => Schema\Expect::string('*'),
						'methods' => Schema\Expect::arrayOf('string')->default([
							'GET',
							'POST',
							'PATCH',
							'DELETE',
							'OPTIONS',
						]),
						'credentials' => Schema\Expect::bool(true),
						'headers' => Schema\Expect::arrayOf('string')->default([
							'Content-Type',
							'Authorization',
							'X-Requested-With',
						]),
					]),
				]),
			]),
			'wsServer' => Schema\Expect::structure([
				'access' => Schema\Expect::structure([
					'keys' => Schema\Expect::string()->default(null),
					'origins' => Schema\Expect::string()->default(null),
				]),
			]),
		]);
	}
```

Note on `httpServer.cors.allow.methods`' default: the original `WebServerExtension` used `Fig\Http\Message\RequestMethodInterface::METHOD_*` constants (`RequestMethodInterface::METHOD_GET` etc.) — inline the literal string values here since re-adding a `use Fig\Http\Message\RequestMethodInterface;` import purely for five constant string values is unnecessary; the values themselves (`'GET'`, `'POST'`, `'PATCH'`, `'DELETE'`, `'OPTIONS'`) are unchanged.

- [ ] **Step 2: Write `loadConfiguration()`, transplanting all 14 former extensions' logic**

Append to the same file, before the closing `}` of the class:

```php
	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * APPLICATION
		 */

		if ($configuration->application->logging->rotatingFile->enabled === true) {
			$builder->addDefinition(
				$this->prefix('application.logger.handler.rotatingFile'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\RotatingFileHandler::class)
				->setArguments([
					'filename' => FB_LOGS_DIR . DIRECTORY_SEPARATOR . $configuration->application->logging->rotatingFile->filename,
					'maxFiles' => 10,
					'level' => $configuration->application->logging->rotatingFile->level,
				]);
		}

		if ($configuration->application->logging->stdOut->enabled === true) {
			$builder->addDefinition($this->prefix('application.logger.handler.stdOut'), new DI\Definitions\ServiceDefinition())
				->setType(Monolog\Handler\StreamHandler::class)
				->setArguments([
					'stream' => 'php://stdout',
					'level' => $configuration->application->logging->stdOut->level,
				]);
		}

		$consoleHandler = null;

		if ($configuration->application->logging->console->enabled) {
			$consoleHandler = $builder->addDefinition(
				$this->prefix('application.logger.handler.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SymfonyMonolog\Handler\ConsoleHandler::class);
		}

		$builder->addDefinition($this->prefix('application.cache.psr6'), new DI\Definitions\ServiceDefinition())
			->setType(ArrayAdapter::class);

		$builder->addDefinition($this->prefix('application.eventLoop.wrapper'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Application\Wrapper::class);

		$builder->addDefinition($this->prefix('application.eventLoop.status'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Application\Status::class);

		if ($configuration->application->logging->console->enabled) {
			$builder->addDefinition($this->prefix('application.subscribers.console'), new DI\Definitions\ServiceDefinition())
				->setType(ApplicationSubscribers\Application\Console::class)
				->setArguments([
					'handler' => $consoleHandler,
					'level' => $configuration->application->logging->console->level,
				]);
		}

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition(
				$this->prefix('application.subscribers.entityDiscriminator'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(ApplicationSubscribers\Application\EntityDiscriminator::class);
		}

		$builder->addDefinition($this->prefix('application.subscribers.eventLoop'), new DI\Definitions\ServiceDefinition())
			->setType(ApplicationSubscribers\Application\EventLoopLifeCycle::class);

		$builder->addDefinition($this->prefix('application.ui.templateFactory'), new DI\Definitions\ServiceDefinition())
			->setType(UI\Application\TemplateFactory::class);

		$builder->addDefinition($this->prefix('application.ui.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Nette\Application\Routers\RouteList::class);

		$metadataCache = $builder->addDefinition(
			$this->prefix('application.document.cache'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Caching\Cache::class)
			->setArguments(['namespace' => 'metadata_class_metadata'])
			->setAutowired(false);

		$builder->addDefinition('document.factory', new DI\Definitions\ServiceDefinition())
			->setType(ApplicationDocuments\Application\DocumentFactory::class);

		$attributeDriver = $builder->addDefinition(
			'document.mapping.attributeDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(ApplicationDocuments\Application\Mapping\Driver\AttributeDriver::class)
			->setArguments(['paths' => array_values($configuration->application->documents->mapping)])
			->addSetup('addExcludePaths', [$configuration->application->documents->excludePaths])
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriver = $builder->addDefinition(
			'document.mapping.mappingDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(ApplicationDocuments\Application\Mapping\Driver\MappingDriverChain::class);

		$builder->addDefinition('document.mapping.classMetadataFactory', new DI\Definitions\ServiceDefinition())
			->setType(ApplicationDocuments\Application\Mapping\ClassMetadataFactory::class)
			->setArguments(['driver' => $mappingDriver, 'cache' => $metadataCache]);

		foreach ($configuration->application->documents->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new Exceptions\InvalidState(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$mappingDriver->addSetup('addDriver', [$attributeDriver, $namespace]);
		}

		/**
		 * EXCHANGE
		 */

		$builder->addDefinition($this->prefix('exchange.consumer'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeMessaging\Exchange\Consumers\Container::class);

		$builder->addDefinition($this->prefix('exchange.publisher'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeMessaging\Exchange\Publisher\Container::class);

		$builder->addDefinition($this->prefix('exchange.publisher.async'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeMessaging\Exchange\Publisher\Async\Container::class);

		$builder->addDefinition($this->prefix('exchange.entityFactory'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeDocuments\Exchange\DocumentFactory::class);

		/**
		 * SIMPLE AUTH
		 */

		$builder->addDefinition($this->prefix('simpleAuth.auth'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthServices\SimpleAuth\Auth::class);

		$builder->addDefinition($this->prefix('simpleAuth.configuration'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthConfiguration\SimpleAuth\Configuration::class)
			->setArguments([
				'tokenIssuer' => $configuration->simpleAuth->token->issuer,
				'tokenSignature' => $configuration->simpleAuth->token->signature,
				'enableMiddleware' => $configuration->simpleAuth->enable->middleware,
				'enableDoctrineMapping' => $configuration->simpleAuth->enable->doctrine->mapping,
				'enableDoctrineModels' => $configuration->simpleAuth->enable->doctrine->models,
				'enableNetteApplication' => $configuration->simpleAuth->enable->nette->application,
				'applicationSignInUrl' => $configuration->simpleAuth->application->signInUrl,
				'applicationHomeUrl' => $configuration->simpleAuth->application->homeUrl,
			]);

		$builder->addDefinition($this->prefix('simpleAuth.token.builder'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\TokenBuilder::class)
			->setArgument('tokenSignature', $configuration->simpleAuth->token->signature)
			->setArgument('tokenIssuer', $configuration->simpleAuth->token->issuer);

		$builder->addDefinition($this->prefix('simpleAuth.token.reader'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\TokenReader::class);

		$builder->addDefinition($this->prefix('simpleAuth.token.validator'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\TokenValidator::class)
			->setArgument('tokenSignature', $configuration->simpleAuth->token->signature)
			->setArgument('tokenIssuer', $configuration->simpleAuth->token->issuer);

		if ($configuration->simpleAuth->services->identity) {
			$builder->addDefinition($this->prefix('simpleAuth.security.identityFactory'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSecurity\SimpleAuth\IdentityFactory::class);
		}

		$builder->addDefinition($this->prefix('simpleAuth.security.userStorage'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\UserStorage::class);

		$builder->addDefinition($this->prefix('simpleAuth.access.annotationChecker'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\Access\AnnotationChecker::class);

		$builder->addDefinition($this->prefix('simpleAuth.access.latteChecker'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\Access\LatteChecker::class);

		$builder->addDefinition($this->prefix('simpleAuth.access.linkChecker'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\Access\LinkChecker::class);

		if ($configuration->simpleAuth->enable->casbin->database) {
			$adapter = $builder->addDefinition(
				$this->prefix('simpleAuth.casbin.adapter'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(DoctrineCrudPersistence\SimpleAuth\Models\Casbin\Adapter::class);

			$builder->addDefinition($this->prefix('simpleAuth.casbin.subscriber'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSubscribers\SimpleAuth\Policy::class);
		} else {
			$policyFile = $configuration->simpleAuth->casbin->policy;

			if (!is_string($policyFile) || !is_file($policyFile)) {
				throw new Exceptions\Logic('Casbin policy file is not configured');
			}

			$adapter = $builder->addDefinition($this->prefix('simpleAuth.casbin.adapter'), new DI\Definitions\ServiceDefinition())
				->setType(Casbin\Persist\Adapters\FileAdapter::class)
				->setArguments(['filePath' => $policyFile]);
		}

		$modelFile = $configuration->simpleAuth->casbin->model;

		if (!is_string($modelFile) || !is_file($modelFile)) {
			throw new Exceptions\Logic('Casbin model file is not configured');
		}

		$builder->addDefinition($this->prefix('simpleAuth.casbin.enforcerFactory'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuthSecurity\SimpleAuth\EnforcerFactory::class)
			->setArguments(['modelFile' => $modelFile, 'adapter' => $adapter]);

		if ($configuration->simpleAuth->enable->middleware) {
			$builder->addDefinition($this->prefix('simpleAuth.middleware.access'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthMiddleware\SimpleAuth\Authorization::class);

			$builder->addDefinition($this->prefix('simpleAuth.middleware.user'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthMiddleware\SimpleAuth\User::class);
		}

		if ($configuration->simpleAuth->enable->doctrine->mapping) {
			$builder->addDefinition($this->prefix('simpleAuth.doctrine.driver'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthMapping\SimpleAuth\Driver\Owner::class);

			$builder->addDefinition($this->prefix('simpleAuth.doctrine.subscriber'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSubscribers\SimpleAuth\User::class);
		}

		if ($configuration->simpleAuth->enable->doctrine->models) {
			$builder->addDefinition(
				$this->prefix('simpleAuth.doctrine.tokensRepository'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(DoctrineCrudPersistence\SimpleAuth\Models\Tokens\Repository::class);

			$builder->addDefinition($this->prefix('simpleAuth.doctrine.tokensManager'), new DI\Definitions\ServiceDefinition())
				->setType(DoctrineCrudPersistence\SimpleAuth\Models\Tokens\Manager::class);
		}

		if ($configuration->simpleAuth->enable->casbin->database) {
			$builder->addDefinition(
				$this->prefix('simpleAuth.doctrine.policiesRepository'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(DoctrineCrudPersistence\SimpleAuth\Models\Policies\Repository::class);

			$builder->addDefinition($this->prefix('simpleAuth.doctrine.policiesManager'), new DI\Definitions\ServiceDefinition())
				->setType(DoctrineCrudPersistence\SimpleAuth\Models\Policies\Manager::class);
		}

		if ($configuration->simpleAuth->enable->nette->application) {
			$builder->addDefinition($this->prefix('simpleAuth.nette.application'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSubscribers\SimpleAuth\Application::class);
		}

		/**
		 * TOOLS
		 */

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition($this->prefix('tools.helpers.database'), new DI\Definitions\ServiceDefinition())
				->setType(ToolsHelpers\Tools\Database::class);
		}

		$builder->addDefinition($this->prefix('tools.utilities.doctrineDateProvider'), new DI\Definitions\ServiceDefinition())
			->setType(\FastyBird\Core\Utilities\Tools\DateTimeProvider::class);

		$builder->addDefinition($this->prefix('tools.schemas.validator'), new DI\Definitions\ServiceDefinition())
			->setType(ToolsSchemas\Tools\Validator::class);

		if (interface_exists('\Sentry\ClientInterface')) {
			$builder->addDefinition($this->prefix('tools.helpers.sentry'), new DI\Definitions\ServiceDefinition())
				->setType(ToolsHelpers\Tools\Sentry::class);
		}

		if (is_string($configuration->tools->sentry->dsn) && $configuration->tools->sentry->dsn !== '') {
			$builder->addDefinition($this->prefix('tools.sentry.handler'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\Monolog\Handler::class)
				->setArgument('level', $configuration->tools->sentry->level);

			$sentryClientBuilderService = $builder->addDefinition(
				$this->prefix('tools.sentry.clientBuilder'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setFactory('Sentry\ClientBuilder::create')
				->setArguments([['dsn' => $configuration->tools->sentry->dsn]]);

			$builder->addDefinition($this->prefix('tools.sentry.client'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\ClientInterface::class)
				// @phpstan-ignore argument.type (Nette ServiceDefinition::setFactory() accepts a [service, method] callable array at runtime)
				->setFactory([$sentryClientBuilderService, 'getClient']);

			$builder->addDefinition($this->prefix('tools.sentry.hub'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\State\Hub::class);
		}

		/**
		 * DATE TIME FACTORY
		 */

		if (!in_array($configuration->dateTimeFactory->timeZone, \DateTimeZone::listIdentifiers(), true)) {
			throw new Exceptions\InvalidArgument('Timezone have to be valid PHP timezone string');
		}

		if ($configuration->dateTimeFactory->system) {
			$builder->addDefinition($this->prefix('dateTimeFactory.datetime.system'), new DI\Definitions\ServiceDefinition())
				->setType(DateTimeFactoryServices\DateTimeFactory\SystemClock::class)
				->setArgument('timeZone', new \DateTimeZone($configuration->dateTimeFactory->timeZone))
				->setAutowired($configuration->dateTimeFactory->frozen === null);
		}

		if ($configuration->dateTimeFactory->frozen !== null) {
			$builder->addDefinition($this->prefix('dateTimeFactory.datetime.frozen'), new DI\Definitions\ServiceDefinition())
				->setType(DateTimeFactoryServices\DateTimeFactory\FrozenClock::class)
				->setArguments([
					'timestamp' => $configuration->dateTimeFactory->frozen,
					'timeZone' => new \DateTimeZone($configuration->dateTimeFactory->timeZone),
				]);
		}

		/**
		 * DOCTRINE CRUD
		 */

		$builder->addDefinition($this->prefix('doctrineCrud.entity.mapper'))
			->setType(DoctrineCrudMapping\DoctrineCrud\EntityMapper::class)
			->setAutowired(false);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.entity.creator'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\Create\IEntityCreator::class)
			->setAutowired(false)
			->getResultDefinition()
			->setType(DoctrineCrudPersistence\DoctrineCrud\Crud\Create\EntityCreator::class);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.entity.updater'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\Update\IEntityUpdater::class)
			->setAutowired(false)
			->getResultDefinition()
			->setFactory(DoctrineCrudPersistence\DoctrineCrud\Crud\Update\EntityUpdater::class);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.entity.deleter'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\Delete\IEntityDeleter::class)
			->setAutowired(false)
			->getResultDefinition()
			->setFactory(DoctrineCrudPersistence\DoctrineCrud\Crud\Delete\EntityDeleter::class);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.crud'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\IEntityCrudFactory::class)
			->getResultDefinition()
			->setType(DoctrineCrudPersistence\DoctrineCrud\Crud\EntityCrud::class)
			->setArguments([
				new PhpGenerator\Literal('$entityName'),
				'@' . $this->prefix('doctrineCrud.entity.mapper'),
				'@' . $this->prefix('doctrineCrud.entity.creator'),
				'@' . $this->prefix('doctrineCrud.entity.updater'),
				'@' . $this->prefix('doctrineCrud.entity.deleter'),
			]);

		/**
		 * DOCTRINE TIMESTAMPABLE
		 */

		$builder->addDefinition($this->prefix('doctrineTimestampable.configuration'))
			->setType(DoctrineTimestampableConfiguration\DoctrineTimestampable\Configuration::class)
			->setArguments([
				'lazyAssociation' => $configuration->doctrineTimestampable->lazyAssociation,
				'autoMapField' => $configuration->doctrineTimestampable->autoMapField,
				'dbFieldType' => $configuration->doctrineTimestampable->dbFieldType,
			]);

		$builder->addDefinition($this->prefix('doctrineTimestampable.driver'))
			->setType(\FastyBird\Core\Mapping\DoctrineTimestampable\Driver\Timestampable::class);

		$builder->addDefinition($this->prefix('doctrineTimestampable.subscriber'))
			->setType(DoctrineTimestampableSubscribers\DoctrineTimestampable\TimestampableSubscriber::class);

		/**
		 * JSON:API
		 */

		$builder->addDefinition($this->prefix('jsonApi.builder'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApiEncoding\JsonApi\Builder::class)
			->setArgument('metaAuthor', $configuration->jsonApi->meta->author)
			->setArgument('metaCopyright', $configuration->jsonApi->meta->copyright);

		$builder->addDefinition($this->prefix('jsonApi.middlewares.jsonapi'), new DI\Definitions\ServiceDefinition())
			->setType(\FastyBird\Core\Middleware\JsonApi\JsonApi::class);

		$builder->addDefinition($this->prefix('jsonApi.hydrators.container'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApiPersistence\JsonApi\Hydrators\Container::class);

		$builder->addDefinition($this->prefix('jsonApi.schemas.container'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApiEncoding\JsonApi\SchemaContainer::class);

		if (class_exists('\IPub\DoctrineCrud\Mapping\Annotation\Crud')) {
			$builder->addDefinition($this->prefix('jsonApi.helpers.crudReader'), new DI\Definitions\ServiceDefinition())
				->setType(JsonApiHelpers\JsonApi\CrudReader::class);
		}

		/**
		 * PHONE
		 */

		$builder->addDefinition($this->prefix('phone.libphone.utils'))
			->setType(libphonenumber\PhoneNumberUtil::class)
			->setFactory('libphonenumber\PhoneNumberUtil::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.geoCoder'))
			->setType(libphonenumber\geocoding\PhoneNumberOfflineGeocoder::class)
			->setFactory('libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.shortNumber'))
			->setType(libphonenumber\ShortNumberInfo::class)
			->setFactory('libphonenumber\ShortNumberInfo::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.mapper.carrier'))
			->setType(libphonenumber\PhoneNumberToCarrierMapper::class)
			->setFactory('libphonenumber\PhoneNumberToCarrierMapper::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.mapper.timezone'))
			->setType(libphonenumber\PhoneNumberToTimeZonesMapper::class)
			->setFactory('libphonenumber\PhoneNumberToTimeZonesMapper::getInstance');

		$builder->addDefinition($this->prefix('phone.phone'))
			->setType(PhoneServices\Phone\Phone::class);

		$builder->addDefinition($this->prefix('phone.doctrinePhone.subscriber'))
			->setType(PhoneSubscribers\Phone\PhoneObjectSubscriber::class);

		/**
		 * WEBSOCKETS (base + WAMP)
		 */

		$controllerFactory = $builder->addDefinition($this->prefix('webSockets.controllers.factory'))
			->setType(WebSocketsControllers\WebSockets\Controller\IControllerFactory::class)
			->setFactory(WebSocketsControllers\WebSockets\Controller\ControllerFactory::class);

		if ($configuration->webSockets->mapping) {
			$controllerFactory->addSetup('setMapping', [$configuration->webSockets->mapping]);
		}

		if ($builder->getByType(WsServerClients\WsServer\IClientFactory::class) === null) {
			$builder->addDefinition($this->prefix('wsServer.clients.factory'))
				->setType(WsServerClients\WsServer\ClientFactory::class);
		}

		$builder->addDefinition($this->prefix('wsServer.clients.driver.memory'))
			->setType(WsServerClients\WsServer\Drivers\InMemory::class);

		$clientsStorageDriver = $configuration->webSockets->storage->clients->driver === '@wsServer.clients.driver.memory'
			? $builder->getDefinition($this->prefix('wsServer.clients.driver.memory'))
			: $builder->getDefinition($configuration->webSockets->storage->clients->driver);

		$builder->addDefinition($this->prefix('wsServer.clients.storage'))
			->setType(WsServerClients\WsServer\Storage::class)
			->setArguments(['ttl' => $configuration->webSockets->storage->clients->ttl])
			->addSetup('?->setStorageDriver(?)', ['@' . $this->prefix('wsServer.clients.storage'), $clientsStorageDriver]);

		$router = $builder->addDefinition($this->prefix('webSockets.routing.router'))
			->setType(Routing\WebSockets\IRouter::class)
			->setFactory(Routing\WebSockets\RouteList::class);

		foreach ($configuration->webSockets->routes as $mask => $action) {
			$router->addSetup('$service[] = new FastyBird\Core\Routing\WebSockets\Route(?, ?);', [$mask, $action]);
		}

		$builder->addDefinition($this->prefix('webSockets.routing.generator'))
			->setType(Routing\WebSockets\LinkGenerator::class);

		$builder->addDefinition($this->prefix('wsServer.server.wrapper'))
			->setType(WsServerServer\WsServer\Wrapper::class);

		$flashApplication = $builder->addDefinition($this->prefix('wsServer.server.flashWrapper'))
			->setType(WsServerServer\WsServer\FlashWrapper::class);

		$flashApplication->addSetup('?->addAllowedAccess(?, \'80\')', [
			$flashApplication,
			$configuration->webSockets->server->httpHost,
		]);
		$flashApplication->addSetup('?->addAllowedAccess(?, ?)', [
			$flashApplication,
			$configuration->webSockets->server->httpHost,
			strval($configuration->webSockets->server->port),
		]);

		$handlers = $builder->addDefinition($this->prefix('wsServer.server.handlers'))
			->setType(WsServerServer\WsServer\Handlers::class);

		if ($configuration->webSockets->loop === null) {
			$loop = $builder->getByType(React\EventLoop\LoopInterface::class) === null
				? $builder->addDefinition($this->prefix('wsServer.server.loop'))
					->setType(React\EventLoop\LoopInterface::class)
					->setFactory('React\EventLoop\Factory::create')
				: $builder->getDefinitionByType(React\EventLoop\LoopInterface::class);
		} else {
			$loop = is_string($configuration->webSockets->loop)
				? new DI\Definitions\Statement($configuration->webSockets->loop)
				: $configuration->webSockets->loop;
		}

		$serverConfiguration = $builder->addDefinition($this->prefix('wsServer.server.configuration'))
			->setType(WsServerServer\WsServer\Configuration::class)
			->setArguments([
				'port' => $configuration->webSockets->server->port,
				'address' => $configuration->webSockets->server->address,
				'enableSSL' => $configuration->webSockets->server->secured->enable,
				'sslSettings' => $configuration->webSockets->server->secured->sslSettings,
			]);

		if ($builder->findByType(Log\LoggerInterface::class) === []) {
			$builder->addDefinition($this->prefix('wsServer.server.logger'))
				->setType(WsServerHelpers\WsServer\Console::class);
		}

		$builder->addDefinition($this->prefix('wsServer.server.server'))
			->setType(WsServerServer\WsServer\Server::class)
			->setArguments([$handlers, $loop, $serverConfiguration]);

		if (class_exists('Symfony\Component\Console\Command\Command')) {
			$builder->addDefinition($this->prefix('wsServer.commands.server'))
				->setType(WsServerCommands\WsServer\ServerCommand::class);
		}

		$wampStorageDriver = $configuration->webSockets->storage->topics->driver === '@wsServer.wamp.topics.driver.memory'
			? $builder->addDefinition($this->prefix('wsServer.wamp.topics.driver.memory'))
				->setType(\FastyBird\Core\Topics\WsServer\Drivers\InMemory::class)
			: $builder->getDefinition($this->prefix('wsServer.wamp.topics.driver.memory'));

		$builder->addDefinition($this->prefix('wsServer.wamp.topics.storage'))
			->setType(\FastyBird\Core\Topics\WsServer\Storage::class)
			->setArguments(['ttl' => $configuration->webSockets->storage->topics->ttl])
			->addSetup('?->setStorageDriver(?)', ['@' . $this->prefix('wsServer.wamp.topics.storage'), $wampStorageDriver]);

		$builder->addDefinition($this->prefix('webSockets.wamp.application'))
			->setType(WebSocketsControllers\WebSockets\WampApplication::class);

		$builder->addDefinition($this->prefix('webSockets.wamp.serializer'))
			->setType(WebSocketsEncoding\WebSockets\PushMessageSerializer::class);

		$builder->addDefinition($this->prefix('wsServer.wamp.pushRegistry'))
			->setType(WebSocketsMessaging\WebSockets\PushMessages\ConsumersRegistry::class);

		if ($builder->getByType(WsServerClients\WsServer\IClientFactory::class) !== null) {
			$builder->removeDefinition($builder->getByType(WsServerClients\WsServer\IClientFactory::class));
		}

		$builder->addDefinition($this->prefix('wsServer.wamp.clientsFactory'))
			->setType(WsServerClients\WsServer\WampClientFactory::class);

		$builder->addDefinition($this->prefix('wsServer.wamp.subscribers.onServerStart'))
			->setType(WsServerSubscribers\WsServer\OnServerStartHandler::class);

		/**
		 * HTTP SERVER
		 */

		$builder->addDefinition($this->prefix('httpServer.routing.responseFactory'), new DI\Definitions\ServiceDefinition())
			->setType(WebServerHttp\WebServer\ResponseFactory::class);

		$builder->addDefinition($this->prefix('httpServer.routing.router'), new DI\Definitions\ServiceDefinition())
			->setType(Routing\WebServer\Router::class);

		$builder->addDefinition($this->prefix('httpServer.commands.server'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerCommands\HttpServer\HttpServer::class)
			->setArguments([
				'serverAddress' => $configuration->httpServer->server->address,
				'serverPort' => $configuration->httpServer->server->port,
				'serverCertificate' => $configuration->httpServer->server->certificate,
			]);

		$builder->addDefinition($this->prefix('httpServer.middlewares.cors'), new DI\Definitions\ServiceDefinition())
			->setType(WebServerMiddleware\WebServer\Cors::class)
			->setArguments([
				'enabled' => $configuration->httpServer->cors->enabled,
				'allowOrigin' => $configuration->httpServer->cors->allow->origin,
				'allowMethods' => $configuration->httpServer->cors->allow->methods,
				'allowCredentials' => $configuration->httpServer->cors->allow->credentials,
				'allowHeaders' => $configuration->httpServer->cors->allow->headers,
			]);

		$builder->addDefinition($this->prefix('httpServer.middlewares.staticFiles'), new DI\Definitions\ServiceDefinition())
			->setType(WebServerMiddleware\WebServer\StaticFiles::class)
			->setArgument('publicRoot', $configuration->httpServer->static->publicRoot)
			->setArgument('enabled', $configuration->httpServer->static->enabled);

		$builder->addDefinition($this->prefix('httpServer.middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(WebServerMiddleware\WebServer\Router::class);

		$builder->addDefinition($this->prefix('httpServer.application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerServer\HttpServer\Application::class);

		$builder->addDefinition($this->prefix('httpServer.server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerServer\HttpServer\Factory::class);

		$builder->addDefinition($this->prefix('httpServer.subscribers.server'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerSubscribers\HttpServer\Server::class);

		/**
		 * WS SERVER (Plugin/WsServer's own registrations)
		 */

		$builder->addDefinition($this->prefix('wsServer.commands.wsServer'), new DI\Definitions\ServiceDefinition())
			->setType(WsServerCommands\WsServer\WsServer::class)
			->setArguments(['exchangeFactories' => $builder->findByType(ExchangeMessaging\Exchange\Factory::class)]);

		$builder->addDefinition($this->prefix('wsServer.subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(WsServerSubscribers\WsServer\Client::class)
			->setArgument('wsKeys', $configuration->wsServer->access->keys)
			->setArgument('allowedOrigins', $configuration->wsServer->access->origins);
	}
```

Note two name clashes resolved by import aliasing at the top of the file (`use FastyBird\Core\X as YDomain;` pairs): several type buckets (`Configuration`, `Commands`, `Documents`, `Encoding`, `Helpers`, `Mapping`, `Messaging`, `Persistence`, `Schemas`, `Server`, `Services`, `Subscribers`) are used by *more than one* domain in this file (e.g. both `Configuration\SimpleAuth\Configuration` and `Configuration\DoctrineTimestampable\Configuration`), so a bare `use FastyBird\Core\Configuration;` would be ambiguous the moment both are referenced in the same file. The import list at the top of Step 1 already aliases every such case (`SimpleAuthConfiguration`, `DoctrineTimestampableConfiguration`, etc.) — where the code above uses a fully-qualified `\FastyBird\Core\X\Y\Z::class` instead of a short alias (e.g. `\FastyBird\Core\Utilities\Tools\DateTimeProvider::class`), that's because only one domain in this file needs that particular bucket, so no alias was worth adding; leave those as fully-qualified.

- [ ] **Step 3: Write `beforeCompile()` and `afterCompile()`, preserving every guard**

```php
	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 * @throws Exceptions\Logic
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * APPLICATION -- loggers, routes, UI
		 */

		if (
			$configuration->application->logging->rotatingFile->enabled === true
			|| $configuration->application->logging->stdOut->enabled === true
		) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));
			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			if ($configuration->application->logging->rotatingFile->enabled === true) {
				$monologLoggerService->addSetup('?->pushHandler(?)', [
					'@self',
					$builder->getDefinition($this->prefix('application.logger.handler.rotatingFile')),
				]);
			}

			if ($configuration->application->logging->stdOut->enabled === true) {
				$monologLoggerService->addSetup('?->pushHandler(?)', [
					'@self',
					$builder->getDefinition($this->prefix('application.logger.handler.stdOut')),
				]);
			}
		}

		// EntityDiscriminator used to be attached here by hand. nettrine/orm 0.10's EventPass
		// finds every service typed Doctrine\Common\EventSubscriber and registers it on its
		// ContainerEventManager itself, so doing it here too would subscribe it twice.

		$appRouterServiceName = $builder->getByType(Application\Routers\RouteList::class);
		assert(is_string($appRouterServiceName));
		$appRouterService = $builder->getDefinition($appRouterServiceName);
		assert($appRouterService instanceof DI\Definitions\ServiceDefinition);
		$appRouterService->addSetup([Routing\Application\AppRouter::class, 'createRouter'], [$appRouterService]);

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof DI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'App' => 'FastyBird\Core\Presenters\Application\*Presenter',
			]]);
		}

		$templateFactoryService = $builder->getDefinitionByType(UI\Application\TemplateFactory::class);
		assert($templateFactoryService instanceof DI\Definitions\ServiceDefinition);
		$templateFactoryService->addSetup('registerLayout', [
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
			. 'templates' . DIRECTORY_SEPARATOR . '@layout.latte',
		]);

		/**
		 * EXCHANGE -- consumer/publisher proxy assembly
		 */

		$consumerProxyServiceName = $builder->getByType(ExchangeMessaging\Exchange\Consumers\Container::class);

		if ($consumerProxyServiceName !== null) {
			$consumerProxyService = $builder->getDefinition($consumerProxyServiceName);
			assert($consumerProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(ExchangeMessaging\Exchange\Consumers\Consumer::class) as $consumerService) {
				if (
					$consumerService->getType() !== ExchangeMessaging\Exchange\Consumers\Container::class
					&& ($consumerService->getAutowired() === true || !is_bool($consumerService->getAutowired()))
				) {
					$consumerService->setAutowired(false);
					$consumerStatus = $consumerService->getTag(self::CONSUMER_STATE);
					assert(is_bool($consumerStatus) || $consumerStatus === null);
					$consumerRoutingKey = $consumerService->getTag(self::CONSUMER_ROUTING_KEY);
					assert(is_string($consumerRoutingKey) || $consumerRoutingKey === null);

					$consumerProxyService->addSetup('?->register(?, ?, ?)', [
						'@self',
						$consumerService,
						$consumerRoutingKey ?? null,
						$consumerStatus ?? true,
					]);
				}
			}
		}

		$publisherProxyServiceName = $builder->getByType(ExchangeMessaging\Exchange\Publisher\Container::class);

		if ($publisherProxyServiceName !== null) {
			$publisherProxyService = $builder->getDefinition($publisherProxyServiceName);
			assert($publisherProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(ExchangeMessaging\Exchange\Publisher\Publisher::class) as $publisherService) {
				if (
					$publisherService->getType() !== ExchangeMessaging\Exchange\Publisher\Container::class
					&& ($publisherService->getAutowired() === true || !is_bool($publisherService->getAutowired()))
				) {
					$publisherService->setAutowired(false);
					$publisherProxyService->addSetup('?->register(?)', ['@self', $publisherService]);
				}
			}
		}

		$asyncPublisherProxyServiceName = $builder->getByType(ExchangeMessaging\Exchange\Publisher\Async\Container::class);

		if ($asyncPublisherProxyServiceName !== null) {
			$asyncPublisherProxyService = $builder->getDefinition($asyncPublisherProxyServiceName);
			assert($asyncPublisherProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(ExchangeMessaging\Exchange\Publisher\Async\Publisher::class) as $publisherService) {
				if (
					$publisherService->getType() !== ExchangeMessaging\Exchange\Publisher\Async\Container::class
					&& ($publisherService->getAutowired() === true || !is_bool($publisherService->getAutowired()))
				) {
					$publisherService->setAutowired(false);
					$asyncPublisherProxyService->addSetup('?->register(?)', ['@self', $publisherService]);
				}
			}
		}

		/**
		 * SIMPLE AUTH -- user context fallback, Doctrine mapping, Nette Application event bridge
		 */

		$userContextServiceName = $builder->getByType(SimpleAuthSecurity\SimpleAuth\User::class);

		if ($userContextServiceName === null) {
			$builder->addDefinition($this->prefix('simpleAuth.security.user'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSecurity\SimpleAuth\User::class);
		}

		if ($configuration->simpleAuth->enable->doctrine->models || $configuration->simpleAuth->enable->casbin->database) {
			NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
				'default',
				'FastyBird\Core\Entities\SimpleAuth',
				__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR . 'SimpleAuth',
			);
		}

		if ($configuration->simpleAuth->enable->nette->application) {
			if (
				$builder->getByType(SymfonyEventDispatcherContracts\EventDispatcherInterface::class) !== null
				&& $builder->getByType(NetteApplication\Application::class) !== null
			) {
				$dispatcher = $builder->getDefinition(
					$builder->getByType(SymfonyEventDispatcherContracts\EventDispatcherInterface::class),
				);
				$application = $builder->getDefinition($builder->getByType(NetteApplication\Application::class));
				assert($application instanceof DI\Definitions\ServiceDefinition);

				$application->addSetup('?->onRequest[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(SimpleAuthEvents\SimpleAuth\Request::class),
				]);
				$application->addSetup('?->onResponse[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(SimpleAuthEvents\SimpleAuth\Response::class),
				]);
			}
		}

		/**
		 * TOOLS -- Sentry handler wiring
		 */

		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class);

		if ($sentryHandlerServiceName !== null) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));
			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);
			$sentryHandlerService = $builder->getDefinition($this->prefix('tools.sentry.handler'));
			assert($sentryHandlerService instanceof DI\Definitions\ServiceDefinition);
			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
		}

		/**
		 * DOCTRINE CRUD -- custom DATE_FORMAT string function
		 */

		$entityManagerServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class, true);
		$entityManagerService = $builder->getDefinition($entityManagerServiceName);

		if ($entityManagerService instanceof DI\Definitions\ServiceDefinition) {
			$entityManagerService->addSetup('?->getConfiguration()->addCustomStringFunction(?, ?)', [
				'@self',
				'DATE_FORMAT',
				DoctrineCrudHelpers\DoctrineCrud\StringFunctions\DateFormat::class,
			]);
		}

		/**
		 * DOCTRINE TIMESTAMPABLE + DOCTRINE PHONE -- EventManager subscriber wiring
		 */

		$emServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class, true);

		if ($emServiceName !== null) {
			$emService = $builder->getDefinition($emServiceName);
			assert($emService instanceof DI\Definitions\ServiceDefinition);
			$emService->addSetup('?->getEventManager()->addEventSubscriber(?)', [
				'@self',
				$builder->getDefinition($this->prefix('doctrineTimestampable.subscriber')),
			]);
			$emService->addSetup('?->getEventManager()->addEventSubscriber(?)', [
				'@self',
				$builder->getDefinition($this->prefix('phone.doctrinePhone.subscriber')),
			]);
		}

		/**
		 * JSON:API -- schema/hydrator assembly
		 */

		$schemaContainerServiceName = $builder->getByType(JsonApiEncoding\JsonApi\SchemaContainer::class, true);
		$schemaContainerService = $builder->getDefinition($schemaContainerServiceName);
		assert($schemaContainerService instanceof DI\Definitions\ServiceDefinition);

		foreach ($builder->findByType(JsonApiSchemas\JsonApi\JsonApi::class) as $schemasService) {
			$schemaContainerService->addSetup('add', [$schemasService]);
		}

		$hydratorContainerServiceName = $builder->getByType(JsonApiPersistence\JsonApi\Hydrators\Container::class, true);
		$hydratorContainerService = $builder->getDefinition($hydratorContainerServiceName);
		assert($hydratorContainerService instanceof DI\Definitions\ServiceDefinition);

		foreach ($builder->findByType(JsonApiPersistence\JsonApi\Hydrators\Hydrator::class) as $hydratorService) {
			$hydratorContainerService->addSetup('add', [$hydratorService]);
		}

		/**
		 * WEBSOCKETS -- router assembly, controller injection, event bridges
		 *
		 * The Application::class-presence guard below is preserved from WebSocketsExtension
		 * (added in PR #450, this session's ipub/websockets-wamp absorption) -- spec section 6
		 * calls this out by name as logic that must be preserved, not just relocated.
		 */

		$webSocketsRouter = $builder->getDefinition($this->prefix('webSockets.routing.router'));
		$routersFactories = [];

		foreach ($builder->findByTag(self::TAG_WEBSOCKETS_ROUTES) as $tagRouterService => $tagPriority) {
			if (is_bool($tagPriority)) {
				$tagPriority = 100;
			}

			$routersFactories[$tagPriority][$tagRouterService] = $tagRouterService;
		}

		if ($routersFactories !== []) {
			krsort($routersFactories, SORT_NUMERIC);

			foreach ($routersFactories as $priority => $items) {
				ksort($items, SORT_STRING);
				$routersFactories[$priority] = $items;
			}

			foreach ($routersFactories as $items) {
				foreach ($items as $routerService) {
					$webSocketsRouter->addSetup('offsetSet', [
						null,
						new DI\Definitions\Statement(['@' . $routerService, 'createRouter']),
					]);
				}
			}
		}

		$allControllers = [];

		foreach ($builder->findByType(WebSocketsControllers\WebSockets\Controller\IController::class) as $def) {
			$allControllers[$def->getType()] = $def;
		}

		foreach ($allControllers as $def) {
			$def->addTag('nette.inject')->addTag('fastybird.core.websockets.controller', $def->getType());
		}

		if (
			interface_exists('Symfony\Component\EventDispatcher\EventDispatcherInterface')
			&& $builder->getByType(EventDispatcher\EventDispatcherInterface::class) !== null
		) {
			$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

			// Preserved guard (PR #450): the base Application service is genuinely optional --
			// nothing in this extension registers it directly, only whichever extension embeds
			// the WAMP controller-dispatch framework does. Wiring events onto a service that was
			// never defined would be a hard MissingServiceException at compile time.
			$applicationType = $builder->getByType(WebSocketsControllers\WebSockets\Application::class);

			if ($applicationType !== null) {
				$application = $builder->getDefinition($applicationType);
				assert($application instanceof DI\Definitions\ServiceDefinition);

				$application->addSetup('?->onOpen[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WebSockets\OpenEvent::class),
				]);
				$application->addSetup('?->onClose[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WebSockets\CloseEvent::class),
				]);
				$application->addSetup('?->onMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WebSockets\MessageEvent::class),
				]);
				$application->addSetup('?->onError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WebSockets\ErrorEvent::class),
				]);
			}

			$server = $builder->getDefinition($builder->getByType(WsServerServer\WsServer\Server::class));
			assert($server instanceof DI\Definitions\ServiceDefinition);
			$server->addSetup('?->onCreate[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\CreateEvent::class),
			]);
			$server->addSetup('?->onStart[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\StartEvent::class),
			]);
			$server->addSetup('?->onStop[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\StopEvent::class),
			]);

			$serverWrapper = $builder->getDefinition($builder->getByType(WsServerServer\WsServer\Wrapper::class));
			assert($serverWrapper instanceof DI\Definitions\ServiceDefinition);
			$serverWrapper->addSetup('?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\ClientConnectEvent::class),
			]);
			$serverWrapper->addSetup('?->onClientDisconnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\ClientDisconnectEvent::class),
			]);
			$serverWrapper->addSetup('?->onClientError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\ClientErrorEvent::class),
			]);
			$serverWrapper->addSetup('?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\IncommingMessageEvent::class),
			]);
			$serverWrapper->addSetup('?->onAfterIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\AfterIncommingMessageEvent::class),
			]);

			// WAMP's own event bridge -- WampApplication is unconditionally registered by this
			// extension (unlike base Application above), so no presence guard is needed here;
			// preserved from WebSocketsWAMPExtension::beforeCompile().
			$wampApplication = $builder->getDefinition($builder->getByType(WebSocketsControllers\WebSockets\WampApplication::class));
			assert($wampApplication instanceof DI\Definitions\ServiceDefinition);
			$wampApplication->addSetup('?->onPush[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WebSockets\PushEvent::class),
			]);
		}

		$pushRegistry = $builder->getDefinition($builder->getByType(WebSocketsMessaging\WebSockets\PushMessages\ConsumersRegistry::class));

		foreach ($builder->findByType(WebSocketsMessaging\WebSockets\PushMessages\IConsumer::class) as $consumer) {
			$pushRegistry->addSetup('?->addConsumer(?)', [$pushRegistry, $consumer]);
		}

		$wsServerServer = $builder->getDefinitionByType(WsServerServer\WsServer\Server::class);
		$wsServerServer->addSetup('$service->onStart[] = ?', [
			'@' . $this->prefix('wsServer.wamp.subscribers.onServerStart'),
		]);

		/**
		 * WS SERVER PLUGIN -- events bridge (fails loudly if the event dispatcher is missing,
		 * preserved from WsServerExtension::beforeCompile())
		 */

		if ($builder->getByType(EventDispatcher\EventDispatcherInterface::class) === null) {
			throw new Exceptions\Logic(sprintf(
				'Service of type "%s" is needed. Please register it.',
				EventDispatcher\EventDispatcherInterface::class,
			));
		}

		$wsServerDispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));
		$socketWrapperServiceName = $builder->getByType(WsServerServer\WsServer\Wrapper::class);
		assert(is_string($socketWrapperServiceName));
		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup('?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
			'@self', $wsServerDispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\ClientConnected::class),
		]);
		$socketWrapperService->addSetup('?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
			'@self', $wsServerDispatcher, new PhpGenerator\Literal(\FastyBird\Core\Events\WsServer\IncomingMessage::class),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		parent::afterCompile($class);

		// Preserved from DoctrinePhoneExtension::afterCompile() -- registers the 'phone' DBAL
		// type. Entities map columns to it by name (Module/Triggers Entities\Notifications\Sms),
		// so without this every test that loads the Triggers metadata fails.
		$initialize = $class->getMethod('initialize');
		$initialize->addBody(
			'if (!Doctrine\DBAL\Types\Type::hasType(\'' . \FastyBird\Core\Types\Phone\Phone::PHONE . '\')) {'
			. ' Doctrine\DBAL\Types\Type::addType('
			. '\'' . \FastyBird\Core\Types\Phone\Phone::PHONE . '\', \'' . \FastyBird\Core\Types\Phone\Phone::class . '\''
			. '); }',
		);
	}

}
```

Two events collide by *class* name across domains once flattened (`WsServer\ClientConnected`/`IncomingMessage`, moved from `Plugin/WsServer` in Task 17, vs the pre-existing WebSockets wrapper events of similar purpose) — read `Task 17`'s produced files (`Events/WsServer/ClientConnected.php`, `Events/WsServer/IncomingMessage.php`) against `Task 15`'s (`Events/WsServer/ClientConnectEvent.php`, `Events/WsServer/IncommingMessageEvent.php`, note the different spelling/suffix) before wiring both `beforeCompile()` sections above — they are five *different* classes with five different names (`ClientConnectEvent` vs `ClientConnected`, `IncommingMessageEvent` vs `IncomingMessage`), not a collision, but the near-identical names are easy to transpose; double check against the exact file list Tasks 15 and 17 produced before trusting the code above.

- [ ] **Step 4: Write the package's own `config/common.neon`/`config/defaults.neon`**

Task 3 already moved `Core/Application/config/{common,defaults}.neon` verbatim to `src/FastyBird/Core/Core/config/`. Update `common.neon` now that the extension is `fbCore` and the config keys are nested:

```bash
docker exec -w /app fastybird-application true
```

Edit `src/FastyBird/Core/Core/config/common.neon` by hand:

```diff
 extensions:
     contributteConsole      : Contributte\Console\DI\ConsoleExtension(%consoleMode%)
     contributteMonolog      : Contributte\Monolog\DI\MonologExtension
     contributteCacheFactory : Contributte\Cache\DI\CacheFactoryExtension
     orisaiObjectMapper      : OriNette\ObjectMapper\DI\ObjectMapperExtension
-    fbApplication           : FastyBird\Core\Application\DI\ApplicationExtension
+    fbCore                  : FastyBird\Core\DI\CoreExtension

 orisaiObjectMapper:
     debug: %debugMode%
     rules:
-        - FastyBird\Core\Application\ObjectMapper\Rules\UuidRule()
+        - FastyBird\Core\Persistence\Application\Rules\UuidRule()

-fbApplication:
-    logging:
-        rotatingFile:
-            enabled: %logger.rotatingFile.enabled%
-            level: %logger.rotatingFile.level%
-            filename: %logger.rotatingFile.filename%
-        stdOut:
-            enabled: %logger.stdOut.enabled%
-            level: %logger.stdOut.level%
-        console:
-            enabled: %logger.console.enabled%
-            level: %logger.console.level%
+fbCore:
+    application:
+        logging:
+            rotatingFile:
+                enabled: %logger.rotatingFile.enabled%
+                level: %logger.rotatingFile.level%
+                filename: %logger.rotatingFile.filename%
+            stdOut:
+                enabled: %logger.stdOut.enabled%
+                level: %logger.stdOut.level%
+            console:
+                enabled: %logger.console.enabled%
+                level: %logger.console.level%
```

`config/defaults.neon`'s `parameters: logger: ...` block is untouched — it only defines the `%logger.*%` parameters `common.neon` references, no extension name inside it.

- [ ] **Step 5: Write `tests/cases/unit/DI/CoreExtensionTest.php`**

Read the four surviving `*ExtensionTest.php` files before Task 3/4/6/16/17 deleted them (recover via `git show <pre-deletion-commit>:<path>` — e.g. `git show HEAD~14:src/FastyBird/Core/Application/tests/cases/unit/DI/ApplicationExtensionTest.php` from the commit Task 3 made) and combine their assertions into one test class that compiles the merged `CoreExtension` and asserts the union of what each former test asserted (that the container compiles without error, that key services like `document.factory`, `fbCore.simpleAuth.auth`, `fbCore.jsonApi.builder`, `fbCore.webSockets.controllers.factory` resolve). This plan does not reproduce that merged test file's full content here — it is mechanical once the five source test files are recovered from git history, and the exact assertions depend on reading files this plan's own investigation did not open (only the extension classes themselves, not their test suites, were read). Do not skip this step; a `CoreExtensionTest.php` that only asserts "the container compiles" is materially weaker than the five files it replaces.

- [ ] **Step 6: Verify**

```bash
docker exec -w /app fastybird-application php -l src/FastyBird/Core/Core/src/DI/CoreExtension.php
```

Expected: `No syntax errors detected`. Full compile-time verification (does the container actually build, are there really no leftover service-name collisions beyond the three this task fixed) is Task 27's job, once `composer install` has produced a working autoloader for the whole package.

- [ ] **Step 7: Commit**

```bash
git add -A src/FastyBird/Core/Core
git commit -m "$(cat <<'EOF'
feat(core): consolidate 14 DI extensions into Core\DI\CoreExtension

Every service key gets a <domainTag>.<originalKey> prefix, applied uniformly,
resolving three real service-name collisions this plan's investigation found
(configuration, subscriber, clients.factory) and two config-schema collisions
(storage, server) that existed only because the 14 extensions previously had
14 separate top-level namespaces to hide in. Every beforeCompile() guard is
preserved, including the Application::class-presence guard from PR #450 spec
section 6 calls out by name, and the WsServer event-dispatcher-required guard
from WsServerExtension.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 19: Wire `fbCore` into the repository's own `config/common.neon` and `config/defaults.neon`

This is the *application's* wiring (`config/` at repo root), distinct from Task 18 Step 4's package-shipped defaults (`src/FastyBird/Core/Core/config/`) — `Boot\Bootstrap::boot()` loads both, shipped defaults first, per `CLAUDE.md`'s config load order.

**Files:**
- Modify: `config/common.neon`

**Interfaces:**
- Consumes: `FastyBird\Core\DI\CoreExtension` (Task 18).

- [ ] **Step 1: Collapse the 14 `extensions:` entries to one**

```diff
 extensions:
     contributteConsole                              : Contributte\Console\DI\ConsoleExtension(%consoleMode%)
     contributteTranslation                          : Contributte\Translation\DI\TranslationExtension
     contributteEvents                               : Contributte\EventDispatcher\DI\EventDispatcherExtension
     contributteVite                                 : Contributte\Vite\Nette\Extension
     nettrineDbal                                    : Nettrine\DBAL\DI\DbalExtension
     nettrineOrm                                     : Nettrine\ORM\DI\OrmExtension
     nettrineFixtures                                : Nettrine\Fixtures\DI\FixturesExtension
     nettrineMigrations                              : Nettrine\Migrations\DI\MigrationsExtension
-    ipubPhone                                       : FastyBird\Library\Phone\DI\PhoneExtension
-    ipubDoctrinePhone                               : FastyBird\Library\Phone\DI\DoctrinePhoneExtension
-    ipubDoctrineCrud                                : FastyBird\Library\DoctrineCrud\DI\DoctrineCrudExtension
-    ipubDoctrineTimestampable                       : FastyBird\Library\DoctrineTimestampable\DI\DoctrineTimestampableExtension
-    ipubWebsockets                                  : FastyBird\Library\WebSockets\DI\WebSocketsExtension
-    ipubWebsocketsWamp                              : FastyBird\Library\WebSockets\Wamp\DI\WebSocketsWAMPExtension
-    # FastyBird libs
-    fbDateTimeFactory                               : FastyBird\Library\DateTimeFactory\DI\DateTimeFactoryExtension
-    fbSimpleAuth                                    : FastyBird\Core\SimpleAuth\DI\SimpleAuthExtension
-    fbJsonApi                                       : FastyBird\Library\JsonApi\DI\JsonApiExtension
-    # FastyBird app core
-    fbApplication                                   : FastyBird\Core\Application\DI\ApplicationExtension
-    fbTools                                         : FastyBird\Core\Tools\DI\ToolsExtension
-    fbExchange                                       : FastyBird\Core\Exchange\DI\ExchangeExtension
-    # FastyBird app plugins
-    fbWebServerPlugin                               : FastyBird\Plugin\WebServer\DI\WebServerExtension
-    fbWsServerPlugin                                : FastyBird\Plugin\WsServer\DI\WsServerExtension
+    # FastyBird core
+    fbCore                                          : FastyBird\Core\DI\CoreExtension
     # FastyBird modules
     fbAccountsModule                                : FastyBird\Module\Accounts\DI\AccountsExtension
     ...
```

- [ ] **Step 2: Update the decorator block**

```diff
 decorator:
-    FastyBird\Plugin\WebServer\Router\Router:
+    FastyBird\Core\Routing\WebServer\Router:
         setup:
-            - addMiddleware(@fbJsonApi.middlewares.jsonapi)
+            - addMiddleware(@fbCore.jsonApi.middlewares.jsonapi)
             - addMiddleware(@fbAccountsModule.middlewares.urlFormat)
```

- [ ] **Step 3: Collapse the `fbSimpleAuth:`/`fbJsonApi:`/`ipubWebsockets:`/`fbWebServerPlugin:`/`fbApplication:` sections into nested `fbCore:` keys**

```diff
-# Simple authentication
-#######################
-fbSimpleAuth:
-    token:
-        issuer: %security.issuer%
-        signature: %security.signature%
-    enable:
-        middleware: true
-        doctrine:
-            models: true
-            mapping: true
-        casbin:
-            database: true
-        nette:
-            application: true
-    application:
-        signInUrl: Accounts:Sign:in
-        homeUrl: App:Default:default
-
-# JSON:Api support
-##################
-fbJsonApi:
-    meta:
-        copyright: FastyBird s.r.o
-
-# WS server
-###########
-ipubWebsockets:
-    server:
-        address: %sockets.address%
-        port: %sockets.port%
-
-# Web server plugin
-###################
-fbWebServerPlugin:
-    static:
-        publicRoot: %appDir%/public/dist/
-        enabled: true
-    cors:
-        allow:
-            headers:
-                - Content-Type
-                - Authorization
-                - X-Requested-With
-                - X-Api-Key
-    server:
-        address: %server.address%
-        port: %server.port%
-        certificate: %server.certificate%
+# FastyBird Core
+################
+fbCore:
+    simpleAuth:
+        token:
+            issuer: %security.issuer%
+            signature: %security.signature%
+        enable:
+            middleware: true
+            doctrine:
+                models: true
+                mapping: true
+            casbin:
+                database: true
+            nette:
+                application: true
+        application:
+            signInUrl: Accounts:Sign:in
+            homeUrl: App:Default:default
+    jsonApi:
+        meta:
+            copyright: FastyBird s.r.o
+    webSockets:
+        server:
+            address: %sockets.address%
+            port: %sockets.port%
+    httpServer:
+        static:
+            publicRoot: %appDir%/public/dist/
+            enabled: true
+        cors:
+            allow:
+                headers:
+                    - Content-Type
+                    - Authorization
+                    - X-Requested-With
+                    - X-Api-Key
+        server:
+            address: %server.address%
+            port: %server.port%
+            certificate: %server.certificate%
+    application:
+        documents:
+            mapping: []
```

The last block replaces the standalone `fbApplication:` section (`documents: mapping: []`) further down the same file — delete that standalone section once its content is folded in above, don't leave both.

- [ ] **Step 4: Fix the two remaining raw FQCN references**

```diff
 nettrineDbal:
     ...
     types:
         uuid_binary: Ramsey\Uuid\Doctrine\UuidBinaryType
-        utcdatetime: FastyBird\Library\DoctrineTimestampable\Types\UTCDateTime
+        utcdatetime: FastyBird\Core\Types\DoctrineTimestampable\UTCDateTime
```

```diff
 contributteTranslation:
     locales:
         default: en_US
         fallback: [en_US, en]
     localeResolvers: []
     dirs:
-        - %appDir%/vendor/fastybird/json-api-library/src/Translations
+        - %appDir%/vendor/fastybird/miniserver-core/src/Translations/JsonApi
```

- [ ] **Step 5: Verify the NEON parses**

```bash
docker exec -w /app fastybird-application php -r '
$neon = Nette\Neon\Neon::decode(file_get_contents("config/common.neon"));
echo "common.neon parses OK, top-level keys: " . implode(", ", array_keys($neon)) . PHP_EOL;
'
```

Expected: `common.neon parses OK`, and `fbCore` present among the top-level keys, `fbApplication`/`fbSimpleAuth`/`fbJsonApi`/`ipubWebsockets`/`fbWebServerPlugin` absent.

- [ ] **Step 6: Commit**

```bash
git add config/common.neon
git commit -m "$(cat <<'EOF'
refactor(config): wire fbCore into the root application container

Collapses 14 DI extension registrations to one, nests the five former
top-level config sections under fbCore per their new domain keys, and fixes
the two raw FQCN references (the DBAL utcdatetime type, the translations
vendor path) this plan's investigation found outside any PHP use import.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 20: JS/npm consolidation

Per D10, the four `assets/`-bearing packages (`Core/Application`, `Core/Tools`, `Library/Metadata`, `Library/WebSockets`) fold into `@fastybird/miniserver-core`. **Flagged assumption not covered by the spec (D1-D10 say nothing about JS internal structure, only "fold into one package"):** all four trees ship their own `entry.ts`, and `Core/Application`/`Core/Tools` both ship a `composables/index.ts` and `composables/types.ts` with different content, and `Library/Metadata`/`Library/WebSockets` both ship a `types/index.ts` with different content — a flat merge collides on all of these. Resolution: each former package's `assets/` becomes its own subfolder under the merged package's `assets/`, keyed by domain name (mirroring the PHP side's domain-tagging principle, applied to the one place the JS side has an equivalent problem), and one new top-level `assets/entry.ts` re-exports all four.

**Files:**
- Create: `src/FastyBird/Core/Core/assets/entry.ts`
- Move: `src/FastyBird/Core/Application/assets/**` → `src/FastyBird/Core/Core/assets/application/**`
- Move: `src/FastyBird/Core/Tools/assets/**` → `src/FastyBird/Core/Core/assets/tools/**`
- Move: `src/FastyBird/Library/Metadata/assets/**` → `src/FastyBird/Core/Core/assets/metadata/**`
- Move: `src/FastyBird/Library/WebSockets/assets/**` → `src/FastyBird/Core/Core/assets/websockets/**`
- Modify: `tsconfig.json`
- Modify: `src/FastyBird/Core/Core/composer.json` is unaffected; `package.json` was already written in Task 1.

- [ ] **Step 1: `git mv` each subtree whole (directory-level move — every file inside travels together, internal relative imports stay valid since each subtree's own internal structure is unchanged)**

```bash
mkdir -p src/FastyBird/Core/Core/assets
git mv src/FastyBird/Core/Application/assets src/FastyBird/Core/Core/assets/application
git mv src/FastyBird/Core/Tools/assets src/FastyBird/Core/Core/assets/tools
git mv src/FastyBird/Library/Metadata/assets src/FastyBird/Core/Core/assets/metadata
git mv src/FastyBird/Library/WebSockets/assets src/FastyBird/Core/Core/assets/websockets

git rm src/FastyBird/Core/Application/package.json src/FastyBird/Core/Tools/package.json \
       src/FastyBird/Library/Metadata/package.json src/FastyBird/Library/WebSockets/package.json
```

- [ ] **Step 2: Rename each subtree's own `entry.ts` so it isn't shadowed, and write the new top-level `entry.ts`**

Each subtree keeps its own entry point under its own name rather than the generic `entry.ts` (which now only exists once, at the top level):

```bash
git mv src/FastyBird/Core/Core/assets/application/entry.ts src/FastyBird/Core/Core/assets/application/index.ts
git mv src/FastyBird/Core/Core/assets/tools/entry.ts src/FastyBird/Core/Core/assets/tools/index.ts
git mv src/FastyBird/Core/Core/assets/metadata/entry.ts src/FastyBird/Core/Core/assets/metadata/index.ts
git mv src/FastyBird/Core/Core/assets/websockets/entry.ts src/FastyBird/Core/Core/assets/websockets/index.ts
```

Read each renamed `index.ts` (formerly `entry.ts`) — every one of the four is a small re-export barrel file (confirmed by directory listing: each package's `entry.ts` was its sole top-level export point). Read the four files' actual `export` statements before writing `assets/entry.ts` below, since this plan's own investigation read the *file lists* for these packages but not each `entry.ts`'s exact export statements — do not guess the export names.

`src/FastyBird/Core/Core/assets/entry.ts`:

```typescript
export * from './application/index';
export * from './tools/index';
export * from './metadata/index';
export * from './websockets/index';
```

If `pnpm build` (Step 5) reports a duplicate-export error, at least one of the four barrels exports two different things under the same name — resolve by re-exporting that one under an explicit alias (`export { Foo as ApplicationFoo } from './application/index';`) rather than a bare `export *`, and update every external `@fastybird/miniserver-core` import of that name in Task 24.

- [ ] **Step 3: Update `tsconfig.json`**

```diff
     "types": [
-      "@fastybird/tools",
-      "@fastybird/metadata-library",
+      "@fastybird/miniserver-core",
       "@intlify/unplugin-vue-i18n/messages",
       "@types/lodash",
       "@types/md5",
       "node",
       "vite/client",
       "vite-svg-loader",
       "unocss"
     ],
```

- [ ] **Step 4: Confirm `main.ts`'s own imports still resolve**

`src/FastyBird/Core/Core/assets/application/main.ts` (formerly `Core/Application/assets/main.ts`) is the actual Vite app entry point. Read it and confirm every relative import (`./App.vue`, `./router`, `./styles/...` etc.) still resolves from its new location — directory-level moves preserve internal relative paths, but `main.ts` may also import `@fastybird/tools`/`@fastybird/metadata-library`/`@fastybird/websockets-library` by absolute specifier (confirmed by Task 22's investigation: `Core/Application/assets/main.ts` is one of the 58 files importing `@fastybird/application`/`@fastybird/tools`); those become `@fastybird/miniserver-core` in Task 24, not here.

- [ ] **Step 5: Verify**

```bash
docker exec -w /app fastybird-application pnpm --filter @fastybird/miniserver-core exec tsc --noEmit -p ../../../../tsconfig.json 2>&1 | head -50
```

This will report unresolved-import errors for every consumer that still imports the four old npm package names — expected at this point in the plan, since Task 24 hasn't run yet. Confirm the errors are only "module not found" for `@fastybird/application`/`@fastybird/tools`/`@fastybird/metadata-library`/`@fastybird/websockets-library` (old names) and not a genuine syntax or internal-path error inside the moved files themselves.

- [ ] **Step 6: Commit**

```bash
git add -A src/FastyBird/Core/Core src/FastyBird/Core/Application src/FastyBird/Core/Tools \
  src/FastyBird/Library/Metadata src/FastyBird/Library/WebSockets tsconfig.json
git commit -m "$(cat <<'EOF'
refactor(core): consolidate the four assets/ trees into @fastybird/miniserver-core

Application/Tools/Metadata/WebSockets each keep their own subfolder (flagged
assumption -- both Application/Tools ship a composables/index.ts and both
Metadata/WebSockets ship a types/index.ts, colliding on a flat merge) with one
new top-level entry.ts re-exporting all four.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 21: Consumer sweep — `composer.json`

This plan's investigation grepped every `composer.json` under `src/FastyBird` for each of the 15 old package names (excluding the 15 packages' own manifests, which Task 27 deletes). Measured "before" counts, by old package name, counting distinct consumer `composer.json` files: `fastybird/application` 30, `fastybird/metadata-library` 29, `fastybird/tools` 21, `fastybird/exchange` 12, `fastybird/doctrine-orm-query-library` 10, `fastybird/slim-router-library` 10, `fastybird/doctrine-crud-library` 9, `fastybird/simple-auth` 6, `fastybird/json-api-library` 6, `fastybird/datetime-factory-library` 3, `fastybird/doctrine-timestampable-library` 3, `fastybird/phone-library` 2, `fastybird/websockets-library` 2, `fastybird/web-server-plugin` 1, `fastybird/ws-server-plugin` 1 — most consumers declare 2-6 of these at once, exactly the friction spec §2.2 describes.

**Files:** every `composer.json` under `src/FastyBird/{Addon,Automator,Bridge,Connector,Module,Plugin}/*/` that requires one or more of the 15 old package names (32 possible consumers; not all 32 require one of the 15 — `Plugin/ApiKey`, `Plugin/CouchDb`, `Plugin/RabbitMq`, `Plugin/RedisDb` etc. were seen in the greps, confirm the exact set with the discovery command in Step 1).

- [ ] **Step 1: Discover every consumer composer.json referencing any of the 15 old names**

```bash
grep -rlE '"fastybird/(application|exchange|simple-auth|tools|datetime-factory-library|doctrine-crud-library|doctrine-orm-query-library|doctrine-timestampable-library|json-api-library|metadata-library|phone-library|slim-router-library|websockets-library|web-server-plugin|ws-server-plugin)"' \
  --include=composer.json src/FastyBird \
  | grep -vE '/(Core/(Application|Exchange|SimpleAuth|Tools)|Library/(DateTimeFactory|DoctrineCrud|DoctrineOrmQuery|DoctrineTimestampable|JsonApi|Metadata|Phone|SlimRouter|WebSockets)|Plugin/(WebServer|WsServer))/composer\.json$' \
  | sort > /tmp/core-consolidation-composer-consumers.txt
wc -l /tmp/core-consolidation-composer-consumers.txt
```

Expected: a list of consumer `composer.json` paths (no path under the 15 merging packages themselves, filtered out by the second `grep -v`).

- [ ] **Step 2: Run the rewrite script over every discovered file**

```php
<?php declare(strict_types = 1);
// tools/tmp-composer-core-rewrite.php -- run once, delete after Task 21's commit.

$oldNames = [
	'fastybird/application', 'fastybird/exchange', 'fastybird/simple-auth', 'fastybird/tools',
	'fastybird/datetime-factory-library', 'fastybird/doctrine-crud-library',
	'fastybird/doctrine-orm-query-library', 'fastybird/doctrine-timestampable-library',
	'fastybird/json-api-library', 'fastybird/metadata-library', 'fastybird/phone-library',
	'fastybird/slim-router-library', 'fastybird/websockets-library', 'fastybird/web-server-plugin',
	'fastybird/ws-server-plugin',
];

$files = array_filter(explode("\n", trim(file_get_contents('/tmp/core-consolidation-composer-consumers.txt'))));

foreach ($files as $file) {
	$raw = file_get_contents($file);
	$json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
	$changed = false;

	foreach (['require', 'require-dev'] as $block) {
		if (!isset($json[$block])) {
			continue;
		}

		$hadAny = false;

		foreach ($oldNames as $old) {
			if (array_key_exists($old, $json[$block])) {
				unset($json[$block][$old]);
				$hadAny = true;
				$changed = true;
			}
		}

		if ($hadAny && !array_key_exists('fastybird/miniserver-core', $json[$block])) {
			$json[$block]['fastybird/miniserver-core'] = '@dev';
		}

		if (isset($json[$block])) {
			ksort($json[$block]);
		}
	}

	if ($changed) {
		file_put_contents(
			$file,
			json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
		);
		echo "rewrote $file\n";
	}
}
```

Save as `tools/tmp-composer-core-rewrite.php`, run it, then delete it (it is a one-shot migration script, not part of the permanent toolchain):

```bash
docker exec -w /app fastybird-application php tools/tmp-composer-core-rewrite.php
rm tools/tmp-composer-core-rewrite.php
```

- [ ] **Step 3: Also check `suggest` blocks** — `Library/JsonApi/composer.json` (deleted in Task 11, no longer relevant) was the only `suggest` entry referencing another of the 15 (`fastybird/slim-router-library`); confirm no *consumer* (non-merging) package has a `suggest` entry naming one of the 15:

```bash
grep -rlE '"fastybird/(application|exchange|simple-auth|tools|datetime-factory-library|doctrine-crud-library|doctrine-orm-query-library|doctrine-timestampable-library|json-api-library|metadata-library|phone-library|slim-router-library|websockets-library|web-server-plugin|ws-server-plugin)"' \
  --include=composer.json src/FastyBird -A2 -B2 | grep -B3 'suggest'
```

Expected: no output (or only matches already handled in Step 2 if any consumer happens to have a `suggest` block naming one of the 15 — read and fix by hand if found, the script above only touches `require`/`require-dev`).

- [ ] **Step 4: Verify — every consumer now declares exactly one `fastybird/miniserver-core` require, zero old names**

```bash
grep -rlE '"fastybird/(application|exchange|simple-auth|tools|datetime-factory-library|doctrine-crud-library|doctrine-orm-query-library|doctrine-timestampable-library|json-api-library|metadata-library|phone-library|slim-router-library|websockets-library|web-server-plugin|ws-server-plugin)"' \
  --include=composer.json src/FastyBird | wc -l
grep -rl '"fastybird/miniserver-core"' --include=composer.json src/FastyBird | wc -l
```

Expected: first command outputs `0`; second outputs a number equal to or greater than the line count from `/tmp/core-consolidation-composer-consumers.txt` (equal unless a consumer already required `fastybird/miniserver-core` for some other reason, which none should at this point in the plan).

- [ ] **Step 5: Validate every rewritten `composer.json` is still well-formed**

```bash
for f in $(cat /tmp/core-consolidation-composer-consumers.txt); do
  php -r "json_decode(file_get_contents('$f'), false, 512, JSON_THROW_ON_ERROR);" || echo "BROKEN: $f"
done
echo done
```

Expected: no `BROKEN:` lines, `done` printed.

- [ ] **Step 6: Commit**

```bash
git add -A src/FastyBird
git commit -m "$(cat <<'EOF'
refactor(deps): swap 15 old fastybird/* requires for fastybird/miniserver-core

Scripted sweep across every consumer composer.json (discovered via grep,
rewritten via a one-shot JSON-aware PHP script, not hand-edited) -- see
Task 21 of docs/superpowers/plans/2026-09-20-core-consolidation.md for the
exact discovery and rewrite commands.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 22: Consumer sweep — NEON files

This plan's investigation found every consumer's `tests/common.neon` follows the identical shape (confirmed by reading `src/FastyBird/Module/Devices/tests/common.neon` in full): an `extensions:` map with some subset of 14 old DI extension keys, a `services:` list with two raw `factory:` entries for SlimRouter's `Http\ResponseFactory`/`Routing\Router` (no DI extension of its own — PR #451's pattern, per `[[vendor-absorption-progress]]`), and one or more old top-level `fbApplication:`/`fbSimpleAuth:`/`fbJsonApi:`/`fbDateTimeFactory:`/`ipubWebsockets:`/etc. config sections. NEON is indentation-sensitive block structure, not line-oriented text — re-nesting five old top-level sections under one new `fbCore:` key is not safely expressible as `sed`; this task uses a `Nette\Neon\Neon`-based PHP script (the library is already a transitive dependency of every consumer) to decode, transform, and re-encode each file structurally.

**Known limitation, stated up front:** `Nette\Neon\Neon::encode()` does not preserve comments from the decoded structure — every rewritten `tests/common.neon` loses its `#`-comment headers (e.g. `# Doctrine\n##########`). This is acceptable for test fixture configs (unlike Task 19's `config/common.neon`, which this plan edited by hand specifically to preserve its comments) but is a real, visible diff — review each rewritten file's `git diff` before committing, not just the script's exit code.

**Files:** every `tests/common.neon` under `src/FastyBird/{Addon,Automator,Bridge,Connector,Module,Plugin}/*/tests/` that references any of the 14 old NEON extension keys or the two raw SlimRouter factory FQCNs (25 files, confirmed by this plan's own investigation grep) plus the four `config/example.neon` composition-root files under `src/FastyBird/Module/{Accounts,Devices,Triggers,Ui}/config/` (confirmed to register the same old keys, read in full for `Module/Devices/config/example.neon`).

- [ ] **Step 1: Discover every NEON file to rewrite**

```bash
grep -rlE '(ipubPhone|ipubDoctrinePhone|ipubDoctrineCrud|ipubDoctrineTimestampable|ipubWebsockets|ipubWebsocketsWamp|fbDateTimeFactory|fbSimpleAuth|fbJsonApi|fbApplication|fbTools|fbExchange|fbWebServerPlugin|fbWsServerPlugin|FastyBird\\Library\\SlimRouter)' \
  --include="*.neon" src/FastyBird \
  | sort > /tmp/core-consolidation-neon-consumers.txt
wc -l /tmp/core-consolidation-neon-consumers.txt
```

Expected: 29 lines (25 `tests/common.neon` + 4 `config/example.neon`), none under the 15 merging packages themselves (already deleted or drained by Tasks 3-17).

- [ ] **Step 2: Write and run the rewrite script**

```php
<?php declare(strict_types = 1);
// tools/tmp-neon-core-rewrite.php -- run once, delete after Task 22's commit.

use Nette\Neon\Neon;

require __DIR__ . '/../vendor/autoload.php';

$extensionKeyMap = [
	'ipubPhone' => null, 'ipubDoctrinePhone' => null, 'ipubDoctrineCrud' => null,
	'ipubDoctrineTimestampable' => null, 'ipubWebsockets' => null, 'ipubWebsocketsWamp' => null,
	'fbDateTimeFactory' => null, 'fbSimpleAuth' => null, 'fbJsonApi' => null,
	'fbApplication' => null, 'fbTools' => null, 'fbExchange' => null,
	'fbWebServerPlugin' => null, 'fbWsServerPlugin' => null,
];

$sectionKeyMap = [
	'fbApplication' => 'application', 'fbSimpleAuth' => 'simpleAuth', 'fbJsonApi' => 'jsonApi',
	'fbDateTimeFactory' => 'dateTimeFactory', 'ipubWebsockets' => 'webSockets',
	'fbWebServerPlugin' => 'httpServer', 'fbWsServerPlugin' => 'wsServer',
];

$files = array_filter(explode("\n", trim(file_get_contents('/tmp/core-consolidation-neon-consumers.txt'))));

foreach ($files as $file) {
	$raw = file_get_contents($file);
	$data = Neon::decode($raw);
	$changed = false;
	$coreSection = $data['fbCore'] ?? [];

	if (isset($data['extensions'])) {
		$hadOld = false;

		foreach (array_keys($extensionKeyMap) as $oldKey) {
			if (array_key_exists($oldKey, $data['extensions'])) {
				unset($data['extensions'][$oldKey]);
				$hadOld = true;
				$changed = true;
			}
		}

		if ($hadOld && !array_key_exists('fbCore', $data['extensions'])) {
			$data['extensions']['fbCore'] = 'FastyBird\Core\DI\CoreExtension';
		}
	}

	if (isset($data['services']) && is_array($data['services'])) {
		array_walk_recursive($data['services'], static function (&$value) use (&$changed): void {
			if ($value === 'FastyBird\Library\SlimRouter\Http\ResponseFactory') {
				$value = 'FastyBird\Core\Http\SlimRouter\ResponseFactory';
				$changed = true;
			} elseif ($value === 'FastyBird\Library\SlimRouter\Routing\Router') {
				$value = 'FastyBird\Core\Routing\SlimRouter\Router';
				$changed = true;
			} elseif (is_string($value) && str_contains($value, '@fbJsonApi.middlewares.jsonapi')) {
				$value = str_replace('@fbJsonApi.middlewares.jsonapi', '@fbCore.jsonApi.middlewares.jsonapi', $value);
				$changed = true;
			}
		});
	}

	foreach ($sectionKeyMap as $oldKey => $newKey) {
		if (array_key_exists($oldKey, $data)) {
			$coreSection[$newKey] = $data[$oldKey];
			unset($data[$oldKey]);
			$changed = true;
		}
	}

	if ($coreSection !== []) {
		$data['fbCore'] = $coreSection;
	}

	if ($changed) {
		file_put_contents($file, Neon::encode($data, Neon::BLOCK));
		echo "rewrote $file\n";
	}
}
```

```bash
docker exec -w /app fastybird-application php tools/tmp-neon-core-rewrite.php
rm tools/tmp-neon-core-rewrite.php
```

- [ ] **Step 3: Review every rewritten file's diff by hand**

```bash
git diff --stat -- $(cat /tmp/core-consolidation-neon-consumers.txt)
git diff -- $(cat /tmp/core-consolidation-neon-consumers.txt) | less
```

Confirm for each file: the `extensions:` block has exactly one `fbCore` line where it used to have 1-9 old lines; any `services:` factory entries now say `FastyBird\Core\Http\SlimRouter\ResponseFactory`/`FastyBird\Core\Routing\SlimRouter\Router`; every former top-level `fbXxx:`/`ipubXxx:` section is now nested one level deeper under a single `fbCore:` key with its renamed domain key; no file lost a config value (only comments and, potentially, key/list ordering — `Neon::encode` does not guarantee the original key order is preserved, which is cosmetic).

- [ ] **Step 4: Verify no old key or FQCN remains, and every file still parses**

```bash
grep -rlE '(ipubPhone|ipubDoctrinePhone|ipubDoctrineCrud|ipubDoctrineTimestampable|ipubWebsockets|ipubWebsocketsWamp|fbDateTimeFactory|fbSimpleAuth|fbJsonApi|fbApplication|fbTools|fbExchange|fbWebServerPlugin|fbWsServerPlugin|FastyBird\\Library\\SlimRouter)' \
  --include="*.neon" src/FastyBird
docker exec -w /app fastybird-application bash -c '
for f in '"$(cat /tmp/core-consolidation-neon-consumers.txt | tr "\n" " ")"'; do
  php -r "Nette\Neon\Neon::decode(file_get_contents(\"$f\"));" || echo "BROKEN: $f"
done
echo done
'
```

Expected: first command outputs nothing; second outputs no `BROKEN:` lines.

- [ ] **Step 5: Commit**

```bash
git add -A src/FastyBird
git commit -m "$(cat <<'EOF'
refactor(config): collapse 14 NEON extension keys to fbCore across every consumer

Nette\Neon-based scripted rewrite (not sed -- NEON is indentation-sensitive
block structure, not line text) across 25 tests/common.neon files and 4
config/example.neon composition roots. Also repoints the two raw SlimRouter
factory: entries and the one @fbJsonApi.middlewares.jsonapi cross-reference
found repo-wide.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 23: Consumer sweep — PHP `use` imports and inline FQCNs

This plan's investigation counted `use`-import file references (excluding each package's own `src/`) before this task runs: `FastyBird\Core\Application` 806 files, `FastyBird\Core\Tools` 500, `FastyBird\Library\Metadata` 767, `FastyBird\Library\JsonApi` 165, `FastyBird\Library\SlimRouter` 115, `FastyBird\Core\Exchange` 103, `FastyBird\Core\SimpleAuth` 41, `FastyBird\Library\WebSockets` 12 — these are the "before" numbers Step 3's verification compares against. The rewrite rules are the exact same ones Tasks 3-17 already validated against the moved files themselves (every rule below was proven correct there); this task applies the identical table to the other 32 extensions instead.

**Files:** every `.php` file under `src/FastyBird/{Addon,Automator,Bridge,Connector,Module,Plugin}/*/` (`src/` and `tests/`), i.e. everything except `src/FastyBird/Core/Core/` (already correct) and the 15 now-drained former package directories (Task 27 deletes them).

- [ ] **Step 1: Run the combined sed script**

```bash
find src/FastyBird/Addon src/FastyBird/Automator src/FastyBird/Bridge src/FastyBird/Connector \
     src/FastyBird/Module src/FastyBird/Plugin -name '*.php' -o -name '*.phpt' | while read -r f; do
  sed -i -E \
    -e 's#FastyBird\\Core\\Application\\Boot#FastyBird\\Core\\Boot#g' \
    -e 's#FastyBird\\Core\\Application\\Caching#FastyBird\\Core\\Caching\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\Documents\\Mapping\\Driver#FastyBird\\Core\\Documents\\Application\\Mapping\\Driver#g' \
    -e 's#FastyBird\\Core\\Application\\Documents\\Mapping#FastyBird\\Core\\Documents\\Application\\Mapping#g' \
    -e 's#FastyBird\\Core\\Application\\Documents#FastyBird\\Core\\Documents\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\Entities\\Mapping#FastyBird\\Core\\Entities\\Application\\Mapping#g' \
    -e 's#FastyBird\\Core\\Application\\EventLoop#FastyBird\\Core\\EventLoop\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\Events#FastyBird\\Core\\Events\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\ObjectMapper\\Rules#FastyBird\\Core\\Persistence\\Application\\Rules#g' \
    -e 's#FastyBird\\Core\\Application\\Presenters#FastyBird\\Core\\Presenters\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\Router#FastyBird\\Core\\Routing\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\Subscribers#FastyBird\\Core\\Subscribers\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\UI#FastyBird\\Core\\UI\\Application#g' \
    -e 's#FastyBird\\Core\\Application\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    -e 's/ApplicationExceptions\\Mapping\b/ApplicationExceptions\\Logic/g' \
    -e 's#FastyBird\\Core\\Exchange\\Consumers#FastyBird\\Core\\Messaging\\Exchange\\Consumers#g' \
    -e 's#FastyBird\\Core\\Exchange\\Documents\\Mapping\\Driver#FastyBird\\Core\\Documents\\Exchange\\Mapping\\Driver#g' \
    -e 's#FastyBird\\Core\\Exchange\\Documents\\Mapping#FastyBird\\Core\\Documents\\Exchange\\Mapping#g' \
    -e 's#FastyBird\\Core\\Exchange\\Documents#FastyBird\\Core\\Documents\\Exchange#g' \
    -e 's#FastyBird\\Core\\Exchange\\Events#FastyBird\\Core\\Events\\Exchange#g' \
    -e 's#FastyBird\\Core\\Exchange\\Exchange#FastyBird\\Core\\Messaging\\Exchange#g' \
    -e 's#FastyBird\\Core\\Exchange\\Publisher\\Async#FastyBird\\Core\\Messaging\\Exchange\\Publisher\\Async#g' \
    -e 's#FastyBird\\Core\\Exchange\\Publisher#FastyBird\\Core\\Messaging\\Exchange\\Publisher#g' \
    -e 's#FastyBird\\Core\\Exchange\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Access#FastyBird\\Core\\Security\\SimpleAuth\\Access#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Application#FastyBird\\Core\\Presenters\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Entities#FastyBird\\Core\\Entities\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Events#FastyBird\\Core\\Events\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Latte#FastyBird\\Core\\Latte\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Mapping#FastyBird\\Core\\Mapping\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Middleware#FastyBird\\Core\\Middleware\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Models#FastyBird\\Core\\Persistence\\SimpleAuth\\Models#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Queries#FastyBird\\Core\\Persistence\\SimpleAuth\\Queries#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Security#FastyBird\\Core\\Security\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Subscribers#FastyBird\\Core\\Subscribers\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Types#FastyBird\\Core\\Types\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\SimpleAuth\\Exceptions#FastyBird\\Core\\Exceptions\\SimpleAuth#g' \
    -e 's#FastyBird\\Core\\Tools\\Events#FastyBird\\Core\\Events\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Formats#FastyBird\\Core\\Formats\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Helpers#FastyBird\\Core\\Helpers\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Schemas#FastyBird\\Core\\Schemas\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Transformers#FastyBird\\Core\\Transformers\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Utilities#FastyBird\\Core\\Utilities\\Tools#g' \
    -e 's#FastyBird\\Core\\Tools\\Exceptions#FastyBird\\Core\\Exceptions\\Tools#g' \
    -e 's#FastyBird\\Library\\DateTimeFactory\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    -e 's#FastyBird\\Library\\DateTimeFactory#FastyBird\\Core\\Services\\DateTimeFactory#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud\\Create#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Create#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud\\Update#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Update#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud\\Delete#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Delete#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Crud#FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Entities#FastyBird\\Core\\Entities\\DoctrineCrud#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\StringFunctions#FastyBird\\Core\\Helpers\\DoctrineCrud\\StringFunctions#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Mapping\\Attribute#FastyBird\\Core\\Mapping\\DoctrineCrud\\Attribute#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Mapping#FastyBird\\Core\\Mapping\\DoctrineCrud#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud\\Exceptions#FastyBird\\Core\\Exceptions\\DoctrineCrud#g' \
    -e 's#FastyBird\\Library\\DoctrineCrud#FastyBird\\Core\\Helpers\\DoctrineCrud#g' \
    -e 's#FastyBird\\Library\\DoctrineOrmQuery\\Exceptions#FastyBird\\Core\\Exceptions\\DoctrineOrmQuery#g' \
    -e 's#FastyBird\\Library\\DoctrineOrmQuery#FastyBird\\Core\\Persistence\\DoctrineOrmQuery#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Entities#FastyBird\\Core\\Entities\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Events#FastyBird\\Core\\Subscribers\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Mapping\\Annotation#FastyBird\\Core\\Mapping\\DoctrineTimestampable\\Annotation#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Mapping\\Driver#FastyBird\\Core\\Mapping\\DoctrineTimestampable\\Driver#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Providers#FastyBird\\Core\\Providers\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Types#FastyBird\\Core\\Types\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    -e 's#FastyBird\\Library\\DoctrineTimestampable#FastyBird\\Core\\Configuration\\DoctrineTimestampable#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Builder#FastyBird\\Core\\Encoding\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\JsonApi#FastyBird\\Core\\Encoding\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Objects#FastyBird\\Core\\Encoding\\JsonApi\\Objects#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Helpers#FastyBird\\Core\\Helpers\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Hydrators\\Fields#FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Hydrators#FastyBird\\Core\\Persistence\\JsonApi\\Hydrators#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Middleware#FastyBird\\Core\\Middleware\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Schemas#FastyBird\\Core\\Schemas\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi\\Exceptions#FastyBird\\Core\\Exceptions\\JsonApi#g' \
    -e 's#FastyBird\\Library\\JsonApi#FastyBird\\Core\\Encoding\\JsonApi#g' \
    -e 's#FastyBird\\Library\\Metadata\\Types\\Payloads#FastyBird\\Core\\Types\\Metadata\\Payloads#g' \
    -e 's#FastyBird\\Library\\Metadata\\Types\\Sources#FastyBird\\Core\\Types\\Metadata\\Sources#g' \
    -e 's#FastyBird\\Library\\Metadata\\Types#FastyBird\\Core\\Types\\Metadata#g' \
    -e 's#FastyBird\\Library\\Metadata#FastyBird\\Core\\Constants\\Metadata#g' \
    -e 's#FastyBird\\Library\\Phone\\Entities#FastyBird\\Core\\Entities\\Phone#g' \
    -e 's#FastyBird\\Library\\Phone\\Types#FastyBird\\Core\\Types\\Phone#g' \
    -e 's#FastyBird\\Library\\Phone\\Events#FastyBird\\Core\\Subscribers\\Phone#g' \
    -e 's#FastyBird\\Library\\Phone\\Exceptions#FastyBird\\Core\\Exceptions\\Phone#g' \
    -e 's#FastyBird\\Library\\Phone#FastyBird\\Core\\Services\\Phone#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Controllers#FastyBird\\Core\\Controllers\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Http#FastyBird\\Core\\Http\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Middleware#FastyBird\\Core\\Middleware\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Routing\\Handlers#FastyBird\\Core\\Routing\\SlimRouter\\Handlers#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Routing#FastyBird\\Core\\Routing\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\SlimRouter\\Exceptions#FastyBird\\Core\\Exceptions\\SlimRouter#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Application#FastyBird\\Core\\Controllers\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Application\\Controller#FastyBird\\Core\\Controllers\\WebSockets\\Controller#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Application\\Responses#FastyBird\\Core\\Controllers\\WebSockets\\Responses#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Application#FastyBird\\Core\\Controllers\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Clients#FastyBird\\Core\\Clients\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Clients\\Drivers#FastyBird\\Core\\Clients\\WsServer\\Drivers#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Clients#FastyBird\\Core\\Clients\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Entities\\Clients#FastyBird\\Core\\Entities\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Entities\\Topics#FastyBird\\Core\\Entities\\WsServer\\Topics#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Entities\\PushMessages#FastyBird\\Core\\Entities\\WebSockets\\PushMessages#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Entities\\Clients#FastyBird\\Core\\Entities\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Entities\\WebSockets#FastyBird\\Core\\Entities\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Topics\\Drivers#FastyBird\\Core\\Topics\\WsServer\\Drivers#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Topics#FastyBird\\Core\\Topics\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Events\\Application#FastyBird\\Core\\Events\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Events\\Application#FastyBird\\Core\\Events\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Events\\Server#FastyBird\\Core\\Events\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Events\\Wrapper#FastyBird\\Core\\Events\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Subscribers#FastyBird\\Core\\Subscribers\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Commands#FastyBird\\Core\\Commands\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Encoding#FastyBird\\Core\\Encoding\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Protocols\\RFC6455#FastyBird\\Core\\Encoding\\WebSockets\\RFC6455#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Protocols#FastyBird\\Core\\Encoding\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Serializers#FastyBird\\Core\\Encoding\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Http#FastyBird\\Core\\Http\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Logger\\Formatter#FastyBird\\Core\\Helpers\\WsServer\\Formatter#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Logger#FastyBird\\Core\\Helpers\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Router#FastyBird\\Core\\Routing\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Server#FastyBird\\Core\\Server\\WsServer#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\PushMessages#FastyBird\\Core\\Messaging\\WebSockets\\PushMessages#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Wamp\\Exceptions#FastyBird\\Core\\Exceptions\\WebSockets#g' \
    -e 's#FastyBird\\Library\\WebSockets\\Exceptions#FastyBird\\Core\\Exceptions\\WebSockets#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Application#FastyBird\\Core\\Server\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Server#FastyBird\\Core\\Server\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Utils#FastyBird\\Core\\Server\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Commands#FastyBird\\Core\\Commands\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Events#FastyBird\\Core\\Events\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Subscribers#FastyBird\\Core\\Subscribers\\HttpServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Http#FastyBird\\Core\\Http\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Middleware#FastyBird\\Core\\Middleware\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Router#FastyBird\\Core\\Routing\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WebServer\\Exceptions#FastyBird\\Core\\Exceptions\\WebServer#g' \
    -e 's#FastyBird\\Plugin\\WsServer\\Commands#FastyBird\\Core\\Commands\\WsServer#g' \
    -e 's#FastyBird\\Plugin\\WsServer\\Events#FastyBird\\Core\\Events\\WsServer#g' \
    -e 's#FastyBird\\Plugin\\WsServer\\Subscribers#FastyBird\\Core\\Subscribers\\WsServer#g' \
    -e 's#FastyBird\\Plugin\\WsServer\\Exceptions#FastyBird\\Core\\Exceptions#g' \
    -e 's#FastyBird\\Plugin\\WsServer#FastyBird\\Core\\Constants\\WsServer#g' \
    "$f"
done
```

This is the same rule table Tasks 3-17 applied to the 15 packages' own files, run once more here — order matters identically (specific sub-namespace patterns before their shorter parent-namespace catch-alls), and the ordering above preserves that.

- [ ] **Step 2: Handle the `SimpleAuth`/`Phone` bare-namespace ambiguity for consumers too**

Tasks 5 and 13 flagged that `FastyBird\Core\SimpleAuth` (bare) and `FastyBird\Library\Phone` (bare) are ambiguous among three former top-level files each. The bulk sed above already rewrote `FastyBird\Library\Phone` (bare, no sub-segment) to `FastyBird\Core\Services\Phone` as its default — grep for any consumer file that actually meant `FastyBird\Core\Entities\Phone\TPhone` or `FastyBird\Core\Types\Phone\Phone` instead, via what the surrounding code actually uses (a trait `use` inside a class body, or a DBAL type string), and fix by hand:

```bash
grep -rn 'FastyBird\\Core\\Services\\Phone\\TPhone\|FastyBird\\Core\\Services\\Phone\\Phone::PHONE' \
  --include="*.php" src/FastyBird/{Addon,Automator,Bridge,Connector,Module,Plugin}
```

Any hit here is a file where the bulk rule guessed wrong — `TPhone` only ever lived in `Entities\Phone`, and the `PHONE` constant only ever lived in `Types\Phone\Phone`, never in the `Services\Phone\Phone` facade. Fix each hit's `use` statement to the correct bucket. The `SimpleAuth` bare case has no consumer-side equivalent risk: outside the former `Core/SimpleAuth` package itself, nothing bare-imports `FastyBird\Core\SimpleAuth` without a sub-segment (confirmed — every consumer reference found in this plan's investigation qualifies with `\Entities\`, `\Exceptions\`, etc.).

- [ ] **Step 3: Verify — zero old-namespace references remain anywhere outside the 15 (soon-deleted) former package directories**

```bash
grep -rlE 'FastyBird\\(Core\\(Application|Exchange|SimpleAuth|Tools)|Library\\(DateTimeFactory|DoctrineCrud|DoctrineOrmQuery|DoctrineTimestampable|JsonApi|Metadata|Phone|SlimRouter|WebSockets)|Plugin\\(WebServer|WsServer))\\' \
  --include="*.php" --include="*.phpt" src/FastyBird/Addon src/FastyBird/Automator src/FastyBird/Bridge \
  src/FastyBird/Connector src/FastyBird/Module src/FastyBird/Plugin | wc -l
```

Expected: `0`. Before this task ran, the equivalent count (summed across the representative namespaces this plan measured) was well over 2000 file-references (806 + 500 + 767 + 165 + 115 + 103 + 41 + 12, undercounting the remaining 7 namespaces this plan did not individually tally).

- [ ] **Step 4: `php -l` every touched file**

```bash
docker exec -w /app fastybird-application bash -c '
for f in $(find src/FastyBird/Addon src/FastyBird/Automator src/FastyBird/Bridge src/FastyBird/Connector \
  src/FastyBird/Module src/FastyBird/Plugin -name "*.php"); do php -l "$f" > /dev/null || echo "BROKEN: $f"; done
echo done
'
```

Expected: no `BROKEN:` lines.

- [ ] **Step 5: Commit**

```bash
git add -A src/FastyBird/Addon src/FastyBird/Automator src/FastyBird/Bridge src/FastyBird/Connector \
  src/FastyBird/Module src/FastyBird/Plugin
git commit -m "$(cat <<'EOF'
refactor: rewrite every consumer's use FastyBird\Core|Library|Plugin\... import

Same substitution table Tasks 3-17 validated against the moved files
themselves, applied here across the other 32 extensions' src/ and tests/.
Fixes the ApplicationExceptions\Mapping alias's ~150 call sites to Logic and
the Phone/TPhone bare-namespace ambiguity the bulk rule can't disambiguate
on its own.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 24: Consumer sweep — JS/TS import specifiers and `package.json` dependencies

This plan's investigation found the npm side is much smaller than the PHP side: `tsconfig.json`'s `layering.php` note confirms it explicitly ("the frontend couples by `@fastybird/<name>` npm specifier, never by PHP namespace... The frontend boundary is clean today"). Consumers found requiring one of the four old npm names in their own `package.json`: `@fastybird/application` — `Connector/HomeKit`, `Module/Accounts`, `Module/Devices`; `@fastybird/tools` — `Connector/HomeKit`, `Module/Accounts`, `Module/Devices` (root `package.json` too, handled in Task 1); `@fastybird/metadata-library` — `Connector/HomeKit`, `Module/Accounts`, `Module/Devices`, `Module/Ui` (root too, Task 1); `@fastybird/websockets-library` — `Connector/HomeKit`, `Module/Devices`, `Module/Ui` (root too, Task 1). Every one of these four collapses to `@fastybird/miniserver-core`.

**Files:**
- Modify: `src/FastyBird/Connector/HomeKit/package.json`, `src/FastyBird/Module/Accounts/package.json`, `src/FastyBird/Module/Devices/package.json`, `src/FastyBird/Module/Ui/package.json`
- Modify: every `.ts`/`.vue` file under those four packages' `assets/` importing from any of the four old specifiers (roughly 60 files combined, per this plan's own investigation grep).

- [ ] **Step 1: Rewrite the four consumers' `package.json` dependencies**

```bash
for pkg in src/FastyBird/Connector/HomeKit/package.json src/FastyBird/Module/Accounts/package.json \
           src/FastyBird/Module/Devices/package.json src/FastyBird/Module/Ui/package.json; do
  php -r '
    $file = $argv[1];
    $json = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $old = ["@fastybird/application", "@fastybird/tools", "@fastybird/metadata-library", "@fastybird/websockets-library"];
    $hadAny = false;
    foreach (["dependencies", "peerDependencies", "devDependencies"] as $block) {
      if (!isset($json[$block])) { continue; }
      foreach ($old as $name) {
        if (array_key_exists($name, $json[$block])) {
          unset($json[$block][$name]);
          $hadAny = true;
        }
      }
      if ($hadAny && isset($json[$block]) && !array_key_exists("@fastybird/miniserver-core", $json[$block])) {
        $json[$block]["@fastybird/miniserver-core"] = "workspace:*";
      }
      if (isset($json[$block])) { ksort($json[$block]); }
    }
    file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
  ' "$pkg"
done
git diff --stat -- src/FastyBird/Connector/HomeKit/package.json src/FastyBird/Module/Accounts/package.json \
  src/FastyBird/Module/Devices/package.json src/FastyBird/Module/Ui/package.json
```

- [ ] **Step 2: Rewrite every `.ts`/`.vue` import specifier**

```bash
find src/FastyBird/Connector/HomeKit/assets src/FastyBird/Module/Accounts/assets \
     src/FastyBird/Module/Devices/assets src/FastyBird/Module/Ui/assets \
     \( -name '*.ts' -o -name '*.vue' \) | while read -r f; do
  sed -i -E \
    -e "s#from ['\"]@fastybird/application['\"]#from '@fastybird/miniserver-core'#g" \
    -e "s#from ['\"]@fastybird/tools['\"]#from '@fastybird/miniserver-core'#g" \
    -e "s#from ['\"]@fastybird/metadata-library['\"]#from '@fastybird/miniserver-core'#g" \
    -e "s#from ['\"]@fastybird/websockets-library['\"]#from '@fastybird/miniserver-core'#g" \
    "$f"
done
```

A file that imported from *two or more* of the four old specifiers (plausible — `Connector/HomeKit/assets/entry.ts` was found importing both `@fastybird/application` and `@fastybird/tools`-family specifiers) now has two or more separate `import { X } from '@fastybird/miniserver-core';` lines rather than one merged line. This is valid ES module syntax (duplicate import sources are legal and get deduplicated by the bundler) but untidy — consolidate by hand into one `import` statement per file listing every named import together, since `pnpm lint:js`'s `no-duplicate-imports` rule (if enabled — check `.eslintrc`) will otherwise flag every one of these files.

- [ ] **Step 3: Verify**

```bash
grep -rln "from ['\"]@fastybird/\(application\|tools\|metadata-library\|websockets-library\)['\"]" \
  --include="*.ts" --include="*.vue" src/FastyBird
grep -rl '"@fastybird/\(application\|tools\|metadata-library\|websockets-library\)"' --include="package.json" src/FastyBird
```

Expected: both commands output nothing.

- [ ] **Step 4: Commit**

```bash
git add -A src/FastyBird/Connector/HomeKit src/FastyBird/Module/Accounts src/FastyBird/Module/Devices src/FastyBird/Module/Ui
git commit -m "$(cat <<'EOF'
refactor(js): repoint every @fastybird/application|tools|metadata-library|websockets-library import at miniserver-core

Four consumer package.json dependency blocks and roughly 60 .ts/.vue import
specifiers, discovered by grep in this plan's own investigation.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 25: Update `tools/layering.php`

Per Flagged Assumption 14 and the spec's own §7 instruction ("`tools/layering.php`'s existing peer-exception entries that reference now-merged packages... need re-examining"). This file is `tools/check-layering.php`'s data source — a hand-maintained dependency-direction matrix keyed by literal package directory names (`Core/Application`, `Library/JsonApi`, etc.), read in full by this plan's own investigation. Three of its sections reference the 15 merging packages by name and one section (`selfCheck`) has hard-coded floor numbers that assumed 34 packages; this task updates all four.

**Files:**
- Modify: `tools/layering.php`

- [ ] **Step 1: Delete the now-self-referencing exception**

`'packages' => ['Library/JsonApi' => ['Library/SlimRouter'], ...]` (the file's own comment explains this was JsonApi's `class_exists()`-guarded branch formatting SlimRouter's HTTP exceptions). Both `Library/JsonApi` and `Library/SlimRouter` are now the same package (`Core/Core`), and "a package may ALWAYS reference itself... that is not an edge and is never checked" per the file's own stated rule — the entry now suppresses nothing and is pure dead weight:

```diff
 	'packages' => [
 		...
-
-		/*
-		 * The one deliberate exception to "Library is the floor". JsonApi's error-formatting
-		 * middleware has a single class_exists()-guarded branch that gives SlimRouter's
-		 * HttpException (404, 405, ...) its real status code and a JSON:API-formatted error
-		 * body; without it every SlimRouter routing failure in every REST module falls into
-		 * the generic handler and surfaces as a 500. It is not a structural dependency --
-		 * SlimRouter is not required to load JsonApi, and the two libraries solve unrelated
-		 * problems (JSON:API response formatting vs PSR-7 routing) -- so merging them into
-		 * one package the way the phone/websockets absorptions did would just be a stranger
-		 * package boundary for no real gain. Maintainer's decision, 2026-09-19: encode this
-		 * as a rule rather than carry it as a standing exception, so the intent is stated
-		 * where the rules live, same as Automator/DevicesModule above.
-		 */
-		'Library/JsonApi' => ['Library/SlimRouter'],
 	],
```

- [ ] **Step 2: Remove `Library/*` from every type grant, and delete the `Library` type entry entirely**

`src/FastyBird/Library/` has zero packages left after Task 27 deletes the 15 drained former-package directories (all 9 `Library/*` packages were among the 15 merged). Per the file's own anti-rot philosophy ("a listed file that stops making any cross-package reference is a build failure... because it is then... licensing nothing"), a grant to a type with no packages is exactly that kind of dead entry:

```diff
 	'types' => [

-		/*
-		 * Library is the floor. "Code that would work in an application that is not this
-		 * one" cannot, by definition, reach anything in this one. Measured: 0 out-edges of
-		 * any kind. This is the only type whose target list is empty, and that emptiness is
-		 * the strongest single statement in the file -- Library/Metadata has an in-degree
-		 * of 774 and an out-degree of 0.
-		 */
-		'Library' => [],
-
 		/*
-		 * Core is the cornerstone: Core/Application (in-degree 982) is what every other
-		 * package boots on. Core may reach Library and it may reach its two siblings
-		 * (measured Core -> Core 15, all of them Core/Exchange -> Core/Application).
-		 * Core/Application itself has ZERO out-edges -- it is the true bottom alongside
-		 * Library/Metadata. Core reaching a Module is the sharpest possible inversion and
-		 * there is exactly one in the tree; see the exception list.
+		 * Core is the cornerstone -- Core/Core is now the ONLY package of this type, and
+		 * the only package in the whole repository other packages boot on. Library/* no
+		 * longer exists as a type at all (all 9 packages absorbed into Core/Core alongside
+		 * the 6 Core/Plugin packages) -- see docs/superpowers/specs/2026-09-20-core-consolidation-design.md.
 		 */
-		'Core' => ['Core/*', 'Library/*'],
+		'Core' => ['Core/*'],

 		/*
 		 * Plugins are infrastructure the application wires in: redis, web server, sockets,
-		 * api keys. They are consumed by modules, never the other way round. Note there is
-		 * deliberately no `Plugin/*` here: Plugin -> Plugin is sideways and measured zero.
-		 * The Plugin/RedisDb <-> Module coupling that people expect to find lives in two
-		 * BRIDGE packages, which is exactly what bridges are for.
+		 * api keys. They are consumed by modules, never the other way round.
 		 */
-		'Plugin' => ['Core/*', 'Library/*'],
+		'Plugin' => ['Core/*'],

 		/*
-		 * Modules are the application. They sit on Core and Library and on nothing else --
+		 * Modules are the application. They sit on Core and on nothing else --
 		 */
-		'Module' => ['Core/*', 'Library/*'],
+		'Module' => ['Core/*'],

-		'Automator' => ['Core/*', 'Library/*', 'Module/Triggers'],
+		'Automator' => ['Core/*', 'Module/Triggers'],

-		'Connector' => ['Core/*', 'Library/*', 'Module/Devices'],
+		'Connector' => ['Core/*', 'Module/Devices'],

-		'Addon' => ['Core/*', 'Library/*'],
+		'Addon' => ['Core/*'],

-		'Bridge' => ['Core/*', 'Library/*'],
+		'Bridge' => ['Core/*'],
 	],
```

Edit every prose paragraph above each grant to drop its `Library/*` mention rather than leaving stale prose next to a corrected grant list — the diff above shows the `Core`/`Plugin`/`Module` ones in full; apply the same trim to `Automator`, `Connector`, `Addon`, `Bridge`'s comments (each currently repeats "Library" once in its `['Core/*', 'Library/*', ...]` grant's inline comment; none needs a comment rewrite as large as `Core`'s).

- [ ] **Step 3: Leave `externalNamespaces` unchanged**

`'externalNamespaces' => ['JsonApi', 'SimpleAuth', 'DateTimeFactory']` guards against *external* composer packages sharing the bare `FastyBird\JsonApi`/`FastyBird\SimpleAuth`/`FastyBird\DateTimeFactory` namespace roots (no `\Library\` or `\Core\` segment) — unrelated to this repository's own `FastyBird\Core\...`/`FastyBird\Library\...`-prefixed packages and unaffected by this merge. Do not touch this key.

- [ ] **Step 4: Re-measure and update the `compositionRoots` entries**

The four `config/example.neon` files (`Module/{Accounts,Devices,Triggers,Ui}`) were rewritten by Task 22 to register one `fbCore` extension instead of several. Their `compositionRoots` justification was specifically about a `Module -> Plugin` edge (`fbWebServer : FastyBird\Plugin\WebServer\DI\WebServerExtension`) that no longer exists as a separate package — `Module -> Core` is an ordinary allowed edge under every module's own type grant, not a violation needing this exemption. Run the checker itself to find out whether it still reports a genuine cross-type reference in these four files:

```bash
docker exec -w /app fastybird-application php tools/check-layering.php --list-edges 2>&1 | grep -A5 'Module/Accounts/config/example.neon\|Module/Devices/config/example.neon\|Module/Triggers/config/example.neon\|Module/Ui/config/example.neon'
```

If a file no longer produces any cross-package reference (per the tool's own anti-rot check on `compositionRoots`, described in its header comment), remove that file's entry from `'compositionRoots' => [...]`. If it still does (plausible — these example configs may still directly reference other packages' extensions for their own demo purposes), leave the entry as-is; do not remove an entry the tool would then flag as stale for the wrong reason.

- [ ] **Step 5: Re-measure and update every `selfCheck` floor**

```bash
docker exec -w /app fastybird-application php tools/check-layering.php --summary-only 2>&1 | tee /tmp/layering-summary-after.txt
cat /tmp/layering-summary-after.txt
```

Read the "measured" values the summary reports (packages, files, NEON files, JSON files, references, cross-references, package pairs, NEON references, NEON cross-references, tests references, src references, minimum files-per-package). Update every value in `'selfCheck' => [...]` following the file's own stated convention ("floors sit roughly 25-35% below" the measured value) and update every `// measured N` comment to the new number, e.g.:

```diff
 	'selfCheck' => [
-		'minPackages' => 25, // measured 34
+		'minPackages' => 15, // measured 20
```

Do this for all twelve `selfCheck` keys using the real numbers `--summary-only` reports at this point in the plan (after Tasks 3-24, before Task 27 deletes the 15 drained directories) — do not estimate them by hand; the whole point of this floor is that it is measured, not guessed, per the file's own repeated emphasis ("The measured values from the tree this landed on are in the comments").

- [ ] **Step 6: Verify the checker itself still passes**

```bash
docker exec -w /app fastybird-application php tools/check-layering.php 2>&1 | tail -30
echo "exit: $?"
```

Expected: exit `0`, no violation lines, no "self-check FAIL". If this fails because a directory `find` picks up `src/FastyBird/Core/Application` etc. still on disk (Task 27 hasn't deleted them yet at this point if executed out of order) with zero files inside, the tool's own `minFilesPerPackage => 1` floor and "package without src/ is now a hard configuration error" note (line ~106 of the file) mean an empty former-package directory left in place is itself a self-check failure — run this task's Step 6 *after* Task 27's deletions, not before, or accept a red result here as expected-and-temporary and re-run after Task 27.

- [ ] **Step 7: Commit**

```bash
git add tools/layering.php
git commit -m "$(cat <<'EOF'
refactor(tools): update layering.php for the Core consolidation

Removes the now-self-referencing Library/JsonApi -> Library/SlimRouter
exception, removes the dead Library type (zero packages remain under
src/FastyBird/Library after this merge) and every type's Library/* grant,
and re-measures every selfCheck floor against the post-merge package graph.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 26: Update docs and delete the 15 drained former package directories

**Files:**
- Modify: `docs/architecture.md`, `docs/deployment.md` (both reference the 15 old package names, confirmed by grep in this plan's investigation)
- Delete: `src/FastyBird/Core/Application/`, `src/FastyBird/Core/Exchange/`, `src/FastyBird/Core/SimpleAuth/`, `src/FastyBird/Core/Tools/`, `src/FastyBird/Library/DateTimeFactory/`, `src/FastyBird/Library/DoctrineCrud/`, `src/FastyBird/Library/DoctrineOrmQuery/`, `src/FastyBird/Library/DoctrineTimestampable/`, `src/FastyBird/Library/JsonApi/`, `src/FastyBird/Library/Metadata/`, `src/FastyBird/Library/Phone/`, `src/FastyBird/Library/SlimRouter/`, `src/FastyBird/Library/WebSockets/`, `src/FastyBird/Plugin/WebServer/`, `src/FastyBird/Plugin/WsServer/` (whole directories — Tasks 3-17/20 drained their `src/`/`assets/`/`tests/` content but never removed each package's own `composer.json`, and Tasks 3-17 individually `git rm`'d each `LICENSE.md`/`README.md`/`docs/Home.md` but not the now-empty `docs/` directory itself, which `git` doesn't track anyway).

- [ ] **Step 1: Confirm what's actually left in each of the 15 directories before deleting**

```bash
find src/FastyBird/Core/Application src/FastyBird/Core/Exchange src/FastyBird/Core/SimpleAuth \
     src/FastyBird/Core/Tools src/FastyBird/Library/DateTimeFactory src/FastyBird/Library/DoctrineCrud \
     src/FastyBird/Library/DoctrineOrmQuery src/FastyBird/Library/DoctrineTimestampable \
     src/FastyBird/Library/JsonApi src/FastyBird/Library/Metadata src/FastyBird/Library/Phone \
     src/FastyBird/Library/SlimRouter src/FastyBird/Library/WebSockets src/FastyBird/Plugin/WebServer \
     src/FastyBird/Plugin/WsServer -type f
```

Expected: only `composer.json` per directory (every other file was individually `git rm`/`git mv`'d in Tasks 3-17/20). If anything else appears, cross-reference it against the mapping table in whichever task covered that package before deleting — it means something was missed.

- [ ] **Step 2: Delete the 15 directories**

```bash
git rm -r src/FastyBird/Core/Application src/FastyBird/Core/Exchange src/FastyBird/Core/SimpleAuth \
  src/FastyBird/Core/Tools src/FastyBird/Library/DateTimeFactory src/FastyBird/Library/DoctrineCrud \
  src/FastyBird/Library/DoctrineOrmQuery src/FastyBird/Library/DoctrineTimestampable \
  src/FastyBird/Library/JsonApi src/FastyBird/Library/Metadata src/FastyBird/Library/Phone \
  src/FastyBird/Library/SlimRouter src/FastyBird/Library/WebSockets src/FastyBird/Plugin/WebServer \
  src/FastyBird/Plugin/WsServer
```

- [ ] **Step 3: Update `docs/architecture.md` and `docs/deployment.md`**

Read both files in full and replace every reference to the 15 old package names/paths (`Core/Application`, `Core/Exchange`, `Core/SimpleAuth`, `Core/Tools`, `Library/DateTimeFactory`, `Library/DoctrineCrud`, `Library/DoctrineOrmQuery`, `Library/DoctrineTimestampable`, `Library/JsonApi`, `Library/Metadata`, `Library/Phone`, `Library/SlimRouter`, `Library/WebSockets`, `Plugin/WebServer`, `Plugin/WsServer`, and the composer/npm names `fastybird/application` etc./`@fastybird/application` etc.) with `Core/Core` / `fastybird/miniserver-core` / `@fastybird/miniserver-core` as appropriate to each sentence's context, and correct the "34 extensions across 8 types" inventory (now 20 extensions across 7 types — `Library` has zero packages, per Flagged Assumption 14). This plan's own investigation read `tools/layering.php`'s header comment, which independently confirms the "8 types" become 7 for the same reason. Do not guess the exact prose rewording without reading both files first — they were only grepped, not read in full, during this plan's investigation.

- [ ] **Step 4: Verify no stale references remain in tracked docs**

```bash
grep -rlE '(fastybird/(application|exchange|simple-auth|tools|datetime-factory-library|doctrine-crud-library|doctrine-orm-query-library|doctrine-timestampable-library|json-api-library|metadata-library|phone-library|slim-router-library|websockets-library|web-server-plugin|ws-server-plugin)|@fastybird/(application|tools|metadata-library|websockets-library)|Core/Application|Core/Exchange|Core/SimpleAuth|Core/Tools\b|Library/DateTimeFactory|Library/DoctrineCrud|Library/DoctrineOrmQuery|Library/DoctrineTimestampable|Library/JsonApi|Library/Metadata|Library/Phone|Library/SlimRouter|Library/WebSockets|Plugin/WebServer|Plugin/WsServer)' \
  docs/*.md CLAUDE.md 2>/dev/null
```

Expected: no output (or only incidental prose matches unrelated to this merge — read any hit before assuming it's stale).

- [ ] **Step 5: Commit**

```bash
git add -A src/FastyBird docs/architecture.md docs/deployment.md
git commit -m "$(cat <<'EOF'
refactor(core): delete the 15 drained former packages, update architecture docs

Every one of the 15 directories' src/assets/tests/config content moved in
Tasks 3-20; this commit removes what's left (each package's own composer.json)
and corrects docs/architecture.md and docs/deployment.md's package inventory
and dependency-layering description.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 27: Rebuild the vendor mirror and run a whole-repo Reflection-based LSP sweep

Per `CLAUDE.md`'s vendor-mirror-staleness trap and `[[absorption-mirror-staleness-and-lsp-narrowing]]`: `COMPOSER_MIRROR_PATH_REPOS=1` *copies* path repositories rather than symlinking them, so a stale `vendor/fastybird/*` mirror makes production code (console commands, `orm:schema-tool`, everything under `vendor/`) keep running the pre-migration classes while `src/FastyBird/` already has the post-migration ones — a change this size (every one of the 32 consumers, the entire former 15) stales the *whole* mirror, not one package. This task also writes the Reflection-based LSP sweep script the spec's §7 verification bar calls for — `check_lsp.php` was never a committed file in this repository's history (confirmed: `git log --all --oneline -- '*check_lsp*' '*check-lsp*'` returns only this session's own ralph/t3 checkpoint commits, never a real commit) — write it fresh here.

**Files:**
- Create: `tools/check-lsp.php`

- [ ] **Step 1: Rebuild the vendor mirror**

```bash
docker exec -w /app fastybird-application rm -rf vendor/fastybird
docker exec -w /app fastybird-application composer install
```

- [ ] **Step 2: Confirm the mirror actually refreshed, not just "Nothing to install"**

```bash
docker exec -w /app fastybird-application composer install 2>&1 | grep -i "mirroring from src/FastyBird"
```

Expected: a `Mirroring from src/FastyBird/Core/Core` line (and no `Mirroring from src/FastyBird/Core/Application`/etc. lines — those packages no longer exist for Composer to mirror). If this prints nothing at all, the install used a cache instead of actually re-copying; re-run with `composer install --no-cache` per `CLAUDE.md`'s documented failure mode ("it once produced a confident 'Nothing to update'").

- [ ] **Step 3: Write `tools/check-lsp.php`**

```php
<?php declare(strict_types = 1);

/**
 * check-lsp.php
 *
 * Walks every .php file under src/FastyBird, extracts the class/interface/trait/enum it
 * declares from its own source (a lightweight regex parse, not a full PHP parser -- this
 * repository's coding standard guarantees one type declaration per file), and confirms:
 *
 *   1. The type actually autoloads (class_exists()/interface_exists()/trait_exists()/
 *      enum_exists() triggers Composer's autoloader; a failure here is either a genuine
 *      missing class or -- the specific failure mode this script exists to catch -- a
 *      namespace that doesn't match its file's PSR-4 path after a bulk rename).
 *   2. Reflection's own idea of the file the class was loaded from matches the file this
 *      script found it in. A class that autoloads successfully but from the WRONG file
 *      (a stale vendor/fastybird/* mirror serving a pre-migration copy instead of the
 *      real src/ file, see CLAUDE.md's vendor-mirror-staleness trap) is exactly the false
 *      green this script is built to catch, and neither `php -l` nor a partial `composer
 *      install` catches it.
 *   3. Every declared parent class and interface resolves too (ReflectionClass itself
 *      would already have thrown a fatal Error at class-load time if a hard `extends`/
 *      `implements` target were missing, so reaching this line at all proves #3 for the
 *      type's immediate ancestry; this script additionally walks the full ancestor chain
 *      to catch a parent that loaded from a stale file per #2).
 *
 * Usage: php tools/check-lsp.php [path-relative-to-repo-root]
 *   No argument: scans the whole src/FastyBird tree.
 *   With argument: scans only that subtree (for a fast re-check after a single package edit).
 *
 * Exit 0: every discovered type loaded, from the expected file, with its whole ancestry
 *         resolving the same way.
 * Exit 1: at least one type failed one of the three checks above; every failure is printed.
 * Exit 2: configuration error (no vendor/autoload.php -- run `composer install` first).
 */

$repoRoot = dirname(__DIR__);
$autoload = $repoRoot . '/vendor/autoload.php';

if (!is_file($autoload)) {
	fwrite(STDERR, "vendor/autoload.php not found -- run composer install first.\n");
	exit(2);
}

require $autoload;

$scanPath = $repoRoot . '/' . ltrim($argv[1] ?? 'src/FastyBird', '/');

if (!is_dir($scanPath)) {
	fwrite(STDERR, sprintf("Not a directory: %s\n", $scanPath));
	exit(2);
}

/**
 * @return array<int, string> Every .php file under $path, recursively, skipping vendor/
 *                             node_modules/dist/tests fixture directories that are not
 *                             expected to be autoloadable production or test-suite code.
 */
function collectPhpFiles(string $path): array
{
	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
	);

	foreach ($iterator as $fileInfo) {
		/** @var SplFileInfo $fileInfo */
		if ($fileInfo->getExtension() !== 'php') {
			continue;
		}

		$relative = str_replace($fileInfo->getPath() . '/', '', $fileInfo->getPathname());

		if (
			str_contains($fileInfo->getPathname(), '/vendor/')
			|| str_contains($fileInfo->getPathname(), '/node_modules/')
			|| str_contains($fileInfo->getPathname(), '/dist/')
		) {
			continue;
		}

		$files[] = $fileInfo->getPathname();
	}

	sort($files);

	return $files;
}

/**
 * Extracts the fully-qualified class/interface/trait/enum name a file declares, by regex
 * over its own source. Returns null for files with no type declaration (rare under
 * src/FastyBird -- e.g. a pure-constants or bootstrap file) rather than treating that as
 * an error; this script only checks files that actually declare something.
 */
function extractFqcn(string $file): string|null
{
	$source = file_get_contents($file);

	if ($source === false) {
		return null;
	}

	if (!preg_match('/^\s*namespace\s+([^;]+);/m', $source, $namespaceMatch)) {
		$namespace = '';
	} else {
		$namespace = trim($namespaceMatch[1]);
	}

	if (
		!preg_match(
			'/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
			$source,
			$typeMatch,
		)
	) {
		return null;
	}

	return $namespace === '' ? $typeMatch[1] : $namespace . '\\' . $typeMatch[1];
}

/** @return array<int, string> */
function ancestry(string $fqcn): array
{
	$names = [];

	try {
		$reflection = new ReflectionClass($fqcn);
	} catch (Throwable) {
		return $names;
	}

	foreach ($reflection->getInterfaceNames() as $interface) {
		$names[] = $interface;
	}

	$parent = $reflection->getParentClass();

	while ($parent !== false) {
		$names[] = $parent->getName();
		$parent = $parent->getParentClass();
	}

	return $names;
}

$files = collectPhpFiles($scanPath);
$checked = 0;
$failures = [];

foreach ($files as $file) {
	$fqcn = extractFqcn($file);

	if ($fqcn === null) {
		continue;
	}

	$checked++;
	$exists = class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn) || enum_exists($fqcn);

	if (!$exists) {
		$failures[] = sprintf('%s: declares %s, which does not autoload', $file, $fqcn);

		continue;
	}

	try {
		$reflection = new ReflectionClass($fqcn);
	} catch (Throwable $exception) {
		$failures[] = sprintf(
			'%s: declares %s, autoloads, but ReflectionClass failed: %s',
			$file,
			$fqcn,
			$exception->getMessage(),
		);

		continue;
	}

	$loadedFrom = $reflection->getFileName();

	if ($loadedFrom !== false && realpath($loadedFrom) !== realpath($file)) {
		$failures[] = sprintf(
			'%s: declares %s, but it autoloaded from %s instead -- stale vendor mirror?',
			$file,
			$fqcn,
			$loadedFrom,
		);

		continue;
	}

	foreach (ancestry($fqcn) as $ancestorName) {
		if (!class_exists($ancestorName) && !interface_exists($ancestorName) && !trait_exists($ancestorName)) {
			$failures[] = sprintf(
				'%s: %s\'s ancestor %s does not autoload',
				$file,
				$fqcn,
				$ancestorName,
			);
		}
	}
}

printf("Checked %d files with a type declaration under %s.\n", $checked, $scanPath);

if ($failures !== []) {
	printf("%d failure(s):\n\n", count($failures));

	foreach ($failures as $failure) {
		echo ' - ' . $failure . "\n";
	}

	exit(1);
}

echo "All types autoload from the expected file with a fully-resolving ancestry.\n";
exit(0);
```

- [ ] **Step 4: Run it against the whole tree**

```bash
docker exec -w /app fastybird-application php tools/check-lsp.php
```

Expected: `All types autoload from the expected file with a fully-resolving ancestry.`, exit 0. This is the check that would have caught, for example, a `Wamp\Clients\ClientFactory` rename (Flagged Assumption 11) that missed updating the `implements`/`extends` clause inside the file itself (Task 15 Step 5's by-hand check) — `php -l` only proves the file parses, not that its declared ancestry resolves.

- [ ] **Step 5: Sanity-check the harness with a known-positive control before trusting a clean run**

Per `CLAUDE.md`'s "a green verdict is worth only as much as the harness that produced it" — introduce one deliberate, temporary breakage and confirm `check-lsp.php` actually catches it, then revert:

```bash
sed -i 's/namespace FastyBird\\Core\\Exceptions;/namespace FastyBird\\Core\\Exceptions\\Broken;/' src/FastyBird/Core/Core/src/Exceptions/InvalidArgument.php
docker exec -w /app fastybird-application composer reinstall fastybird/miniserver-core
docker exec -w /app fastybird-application php tools/check-lsp.php ; echo "exit: $?"
git checkout -- src/FastyBird/Core/Core/src/Exceptions/InvalidArgument.php
docker exec -w /app fastybird-application composer reinstall fastybird/miniserver-core
```

Expected: the intermediate run reports at least one failure (the shared `InvalidArgument` class moving namespace breaks every class that `extends`/`implements` it — likely dozens of failures, not just one) and exits `1`; after the `git checkout --` revert and mirror refresh, re-run `php tools/check-lsp.php` once more and confirm it's clean again before proceeding.

- [ ] **Step 6: Commit**

```bash
git add tools/check-lsp.php
git commit -m "$(cat <<'EOF'
feat(tools): add a Reflection-based LSP sweep script

Walks every .php file under src/FastyBird, confirms its declared type
autoloads from the expected file (not a stale vendor/fastybird/* mirror
copy) and that its full ancestry resolves. No prior version of this script
exists in git history -- earlier references to "check_lsp.php" this session
were scratch files, never committed.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

## Task 28: Final whole-repo verification

Per spec §7's verification bar: "full `rm -rf vendor/fastybird && composer install` rebuild [Task 27], a Reflection-based LSP sweep across the entire `src/FastyBird` tree [Task 27], two clean `make tests` runs, `pnpm types`/`pnpm build` for the JS side... at a scale 30x larger than any single package absorbed so far." This task is the batch of gates that must all pass before this PR is ready — run every one of them, in this order, and do not stop at the first green result without the second confirming run per `CLAUDE.md`'s "sanity-check any harness with a known-positive control before trusting a batch verdict."

**No files are created or modified by this task** — it is pure verification. If any gate fails, the fix belongs in whichever earlier task's step produced the bug (do not patch around it here).

- [ ] **Step 1: `make layers` — dependency direction is still clean**

```bash
docker exec -w /app fastybird-application make layers
```

Expected: exit 0, self-check PASS, zero violations. This exercises Task 25's `tools/layering.php` edits.

- [ ] **Step 2: `make discriminators`**

```bash
docker exec -w /app fastybird-application make discriminators
```

Expected: exit 0. Confirms every Doctrine inheritance root still declares an explicit `#[ORM\DiscriminatorMap]` after the Entities relocations in Tasks 3-17 (`Core\Entities\Application\Mapping\DiscriminatorEntry`, `Core\Entities\SimpleAuth\...`, etc.).

- [ ] **Step 3: `make composer-validate`**

```bash
docker exec -w /app fastybird-application make composer-validate
```

Expected: exit 0 (not `--strict` — `CLAUDE.md`'s two permanent warnings, `endroid/qr-code` and `mathsolver/mathsolver`, still apply and are out of scope here). This validates the new `src/FastyBird/Core/Core/composer.json` (Task 1) and every rewritten consumer `composer.json` (Task 21) as well-formed, schema-valid Composer manifests, not just valid JSON (Task 21 Step 5 only checked the latter).

- [ ] **Step 4: `php tools/check-lsp.php` — second run, from a cold container**

Task 27 already ran this once as part of building it. Run it again here, standalone, as the "does this whole plan actually hold together" gate, on a container that has seen every task's changes:

```bash
docker exec -w /app fastybird-application php tools/check-lsp.php
```

Expected: `All types autoload from the expected file with a fully-resolving ancestry.`, exit 0.

- [ ] **Step 5: `orm:schema-tool:update --dump-sql` — confirm this is a pure rename, zero schema drift**

This migration renames namespaces; it does not add, remove, or retype any Doctrine-mapped column. If the vendor mirror is correctly rebuilt (Task 27) and every `#[ORM\...]` mapping attribute's class references resolve to the new namespaces, this command should propose zero SQL:

```bash
docker exec -w /app fastybird-application php bin/fb-console.php orm:schema-tool:update --dump-sql
```

Expected: no `ALTER TABLE`/`CREATE TABLE`/`DROP TABLE` statements printed (Doctrine reports "Nothing to update" or an empty SQL list). Per `CLAUDE.md`'s own recorded incident, a stale mirror "once produced a confident 'Nothing to update'" for the wrong reason (it was reading old code, not because nothing changed) — this step only means anything in combination with Step 4 having independently confirmed the mirror is fresh and every class resolves from its real `src/` location.

- [ ] **Step 6: `make tests` — first clean run**

```bash
docker exec -w /app fastybird-application make tests 2>&1 | tee /tmp/core-consolidation-tests-run1.txt
tail -30 /tmp/core-consolidation-tests-run1.txt
```

Expected: all tests pass, `OK` summary line, exit 0. Pipe to a file and read the file's own tail, per `CLAUDE.md`'s "piping a gate through `tail` alone turned a `make: *** Error 255` into a task that recorded success" — the command above pipes to `tee` (which preserves the file) and then separately inspects the file, not `make tests | tail`.

- [ ] **Step 7: `make tests` — second clean run**

```bash
docker exec -w /app fastybird-application make tests 2>&1 | tee /tmp/core-consolidation-tests-run2.txt
tail -30 /tmp/core-consolidation-tests-run2.txt
diff <(grep -oE '[0-9]+ tests?, [0-9]+ assertions?' /tmp/core-consolidation-tests-run1.txt) \
     <(grep -oE '[0-9]+ tests?, [0-9]+ assertions?' /tmp/core-consolidation-tests-run2.txt)
```

Expected: both runs pass, and the test/assertion counts match between runs (a flaky or order-dependent test would show up here as a difference between the two "OK" lines even though both exit 0).

- [ ] **Step 8: `make cs`**

```bash
docker exec -w /app fastybird-application make cs
```

Expected: exit 0, no PHP_CodeSniffer violations across the ~500 moved/rewritten PHP files.

- [ ] **Step 9: `make phpstan`**

```bash
docker exec -w /app fastybird-application make phpstan
```

Expected: exit 0 at level max. This is the step most likely to surface a Task 18 mistake — an import alias that resolves to the wrong bucket, a service key typo in `$this->prefix(...)` vs. its consumer's `getByType()` call — since PHPStan's Nette DI extension actually type-checks service definitions against the classes they reference.

- [ ] **Step 10: `pnpm types`**

```bash
docker exec -w /app fastybird-application pnpm types
```

Expected: exit 0. Runs `vue-tsc --noEmit` across every UI package, catching any remaining `@fastybird/application`/`@fastybird/tools`/`@fastybird/metadata-library`/`@fastybird/websockets-library` import Task 24 missed, and any duplicate-export collision Task 20 flagged as a possible outcome.

- [ ] **Step 11: `pnpm build`**

```bash
docker exec -w /app fastybird-application pnpm build
```

Expected: exit 0. Runs `vue-tsc --noEmit` then the Vite production build for the application shell — this is the step that actually resolves `@fastybird/miniserver-core` through the pnpm workspace and would fail if Task 1's `package.json`/Task 20's `assets/entry.ts` don't line up.

- [ ] **Step 12: `pnpm lint:js`, `pnpm lint:styles`, `pnpm pretty:check`**

```bash
docker exec -w /app fastybird-application pnpm lint:js
docker exec -w /app fastybird-application pnpm lint:styles
docker exec -w /app fastybird-application pnpm pretty:check
```

Expected: exit 0 for all three. `lint:js` is the gate most likely to catch Task 24's Step 2 leftover — two `import` statements from `@fastybird/miniserver-core` in the same file where ESLint's `no-duplicate-imports` rule is enabled.

- [ ] **Step 13: Docker build smoke test**

Per `CLAUDE.md`: "The Docker Build smoke test is the gate that catches" a stray notice breaking `headers_sent()` before the DI container initializes. Build the production image and confirm `GET /api/v1` still returns headers correctly:

```bash
docker build -f docker/prod/php/Dockerfile -t fastybird-core-consolidation-smoketest .
```

Expected: image builds successfully. A full boot-and-curl smoke test is out of this plan's scope (no task here changes `docker/`), but a build failure at this step means something in `src/FastyBird/Core/Core/`'s `bin/`/`config/` paths (Task 1's root `composer.json` `bin` array, Task 3's `bin/` move) broke the production image's expectations about where those files live.

- [ ] **Step 14: Final sanity — grep the whole tree one more time for every old identifier**

```bash
grep -rlE 'FastyBird\\(Core\\(Application|Exchange|SimpleAuth|Tools)|Library\\(DateTimeFactory|DoctrineCrud|DoctrineOrmQuery|DoctrineTimestampable|JsonApi|Metadata|Phone|SlimRouter|WebSockets)|Plugin\\(WebServer|WsServer))' \
  --include="*.php" --include="*.neon" --include="*.ts" --include="*.vue" --include="*.json" .
grep -rl '"fastybird/\(application\|exchange\|simple-auth\|tools\|datetime-factory-library\|doctrine-crud-library\|doctrine-orm-query-library\|doctrine-timestampable-library\|json-api-library\|metadata-library\|phone-library\|slim-router-library\|websockets-library\|web-server-plugin\|ws-server-plugin\)"' .
grep -rl '"@fastybird/\(application\|tools\|metadata-library\|websockets-library\)"' .
```

Expected: all three commands output nothing, across the *entire* repository (not scoped to `src/FastyBird` — this catches anything Tasks 21-26 missed in `docker/`, `bin/`, `public/`, or elsewhere).

- [ ] **Step 15: Report**

This plan's deliverable is complete once every step above is green on the same commit. Do not create the pull request until Steps 6/7's two `make tests` runs both pass with matching counts — a single green run is not sufficient evidence at this scale, per the plan's own Global Constraints.

---
