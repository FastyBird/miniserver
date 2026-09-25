# Architecture

## Extension layout

Every extension lives under `src/FastyBird/<Type>/<Name>/` with its own `src/` (PHP, namespace `FastyBird\<Type>\<Name>`, except `Core/Core`, whose namespace is the flat `FastyBird\Core\...` with no repeated `\Core\Core\...` segment), `tests/cases/unit`, optional `assets/` (Vue 3 UI), optional `config/`, `docs/`, `README.md`, `composer.json` and, when `assets/` exists, `package.json`. There are 29 extensions across 7 types:

| Type | Count | Extensions |
|---|---|---|
| Addon | 1 | VirtualThermostat |
| Automator | 2 | DateTime, DevicesModule |
| Bridge | 6 | DevicesModuleUiModule, RedisDbPluginDevicesModule, RedisDbPluginTriggersModule, ShellyConnectorHomeKitConnector, VieraConnectorHomeKitConnector, VirtualThermostatAddonHomeKitConnector |
| Connector | 10 | FbMqtt, HomeKit, Modbus, NsPanel, Shelly, Sonoff, Tuya, Viera, Virtual, Zigbee2Mqtt |
| Core | 1 | Core |
| Module | 4 | Accounts, Devices, Triggers, Ui |
| Plugin | 5 | ApiKey, CouchDb, RabbitMq, RedisDb, RedisDbCache |

`Library` is no longer a type: it had a single member, `Library/Metadata`, and that merged into `Core/Core` (composer `fastybird/miniserver-core`, npm `@fastybird/miniserver-core`) along with eight other library-only packages (`DateTimeFactory`, `DoctrineCrud`, `DoctrineOrmQuery`, `DoctrineTimestampable`, `JsonApi`, `Phone`, `SlimRouter`, `WebSockets`) and the former `Core/Application`, `Core/Exchange`, `Core/SimpleAuth`, `Core/Tools`, `Plugin/WebServer` and `Plugin/WsServer` -- fifteen packages in total, none of which ever had their own DI extension and so were never part of the 34-extension count except for the six that did (`Core/Application`, `Core/Exchange`, `Core/Tools`, `Library/Metadata`, `Plugin/WebServer`, `Plugin/WsServer`); the new `Core/Core` replaces those six, hence 34 - 6 + 1 = 29. `src/FastyBird/Library/` still exists on disk (its `README.md` is the only file left there) but ships nothing. See [docs/superpowers/specs/2026-09-20-core-consolidation-design.md](./superpowers/specs/2026-09-20-core-consolidation-design.md) for the merge rationale, and `tools/layering.php`'s `'types'` table for the current dependency rules described below in code form.

Dependency layering, measured by `tools/check-layering.php` rather than composer manifests (the manifests were found to describe 117 undeclared edges against 149 declared and are no longer trusted for this): `Core/Core` is the cornerstone -- the only package every other package may depend on, and the only one that boots. Plugins depend only on `Core/*` (never on each other -- `Plugin -> Plugin` is sideways and measured zero; the `Plugin/RedisDb`-to-module coupling people expect lives in two Bridge packages instead). Modules depend only on `Core/*` (no `Module -> Module`, no `Module -> Plugin`). Automators depend on `Core/*` and `Module/Triggers` only. Connectors depend on `Core/*` and `Module/Devices` only -- ten connectors, 1201 references, not one touching another module. Addons and Bridges depend on `Core/*` plus the specific packages they bridge, declared per package since "bridges two things" says nothing about which two.

## Request routing

`public/index.php` is the single entry point for both the API and the UI. It inspects `$_SERVER['REQUEST_URI']` against `FastyBird\Core\Constants\Constants::ROUTER_API_PREFIX`: a match is routed to the ReactPHP-based `FastyBird\Core\Http\Server\Application`; everything else goes to the Nette application.

The Nette application's layout template (`Core/Core/templates/@layout.latte`) does wire up the Vue SPA shell -- it emits `<script src="{='index.html'|vite}">` into a `#app` mount point, matching what `Core/Core/assets/application/main.ts` expects. But reaching that template requires a route, and none is registered: `Core/Core/src/Routing/Application/AppRouter.php` defines a route for `/`, yet nothing calls it (no `services: router:` entry and no `application: mapping:` in any shipped `.neon`). The Nette application therefore has **no active routes today, and `GET /` 404s by design** -- this is pre-existing, frozen-repository behaviour, not something the merge introduced, and wiring the router is a behavioural change out of scope here. Only `/api/v1` (and the rest of the JSON:API surface) responds. Do not document or assume `GET /` returns 200; see `docker/prod/Dockerfile`'s `HEALTHCHECK` comment and `.github/workflows/ci-tests.yaml`'s `GET /` step for the same conclusion reached independently.

## Configuration load order

`FastyBird\Core\Boot\Bootstrap::boot()` defines `FB_APP_DIR`, `FB_PUBLIC_DIR`, `FB_RESOURCES_DIR`, `FB_TEMP_DIR`, `FB_LOGS_DIR` and `FB_CONFIG_DIR` from `$_ENV`/`getenv()`. `FB_CONFIG_DIR` defaults to `<FB_APP_DIR>/config` when not set explicitly, but it is a genuinely independent, overridable setting -- point it at, for example, `/var/mini-server/config` (the production image sets it to `/data/config`) to load operator overrides from somewhere other than the checked-in `config/` directory.

Config resolution is **three layers**, read in this order, via `Bootstrap::resolveConfigFiles()`:

1. **This extension's own defaults** -- `src/FastyBird/Core/Core/config/common.neon`, then `.../defaults.neon`.
2. **The application's own config** -- `<FB_APP_DIR>/config/common.neon`, then `.../defaults.neon`. This is the wiring checked into the root `config/` directory.
3. **The operator override directory, `FB_CONFIG_DIR`** -- `common.neon`, `defaults.neon`, then `local.neon`, in that order.

Every file is skipped if it does not exist, and `resolveConfigFiles()` also **dedupes by resolved real path** (`realpath()`), skipping any file already loaded by an earlier layer. Since `FB_CONFIG_DIR` defaults to the *same directory* as layer 2, the common case is that layer 3's `common.neon`/`defaults.neon` resolve to files already loaded in layer 2 and are silently skipped -- only `local.neon` actually adds anything. Point `FB_CONFIG_DIR` at a genuinely different directory and all three of its files load in addition to layers 1 and 2, because none of them share a real path with anything loaded so far.

Environment variables named `FB_APP_PARAMETER__<SECTION>_<KEY>` become container parameters (double underscore after the prefix, single underscore between section and key -- e.g. `FB_APP_PARAMETER__DATABASE_HOST` becomes `%database.host%`). `APP_ENV=dev` enables debug mode. See [configuration.md](./configuration.md) for how to add extension-specific wiring through `local.neon`.

## Runtime processes

HTTP: nginx in front of php-fpm, every request handled by `public/index.php`. The ReactPHP server behind `php bin/fb-console.php fb:web-server:start` remains available for local runs without nginx.

Long-running workers under supervisord: `fb:ws-server:start` (WebSocket server, port 8888) and `fb:devices-module:exchange`. Connector processes run as `fb:devices-module:connector <identifier>`, one process per configured connector -- see [deployment.md](./deployment.md) for how (and where) that is currently wired, and for a gap in that wiring worth knowing about before you rely on it.

## Frontend

`config/extensions.ts` registers which extensions' `assets/entry.ts` the Vite build includes; today that is `accounts-module`, `devices-module` and `homekit-connector` (`triggers-module` and `ui-module` have a UI but are not registered -- registering them is a functional change, out of scope for this merge). `src/FastyBird/Core/Core/assets/application/main.ts` reads that registry and the application version/description from the root `package.json` at build time.
