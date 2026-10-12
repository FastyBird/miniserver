# Architecture

## Extension layout

Every extension lives under `src/FastyBird/<Type>/<Name>/`. Each has its own `src/`, `tests/` (`tests/cases/unit`, and `tests/support` for its test helpers), `docs/`, `README.md` and `composer.json`. An extension with a Vue 3 UI also has `assets/` and a `package.json`; some have a `config/` directory of their own. The PHP namespace is `FastyBird\<Type>\<Name>`, except for `Core/Core`, whose namespace is `FastyBird\Core\…` with no repeated `\Core\Core\` segment.

There are 29 extensions across 7 types:

| Type | Count | Extensions |
|---|---|---|
| Addon | 1 | VirtualThermostat |
| Automator | 2 | DateTime, DevicesModule |
| Bridge | 6 | DevicesModuleUiModule, RedisDbPluginDevicesModule, RedisDbPluginTriggersModule, ShellyConnectorHomeKitConnector, VieraConnectorHomeKitConnector, VirtualThermostatAddonHomeKitConnector |
| Connector | 10 | FbMqtt, HomeKit, Modbus, NsPanel, Shelly, Sonoff, Tuya, Viera, Virtual, Zigbee2Mqtt |
| Core | 1 | Core |
| Module | 4 | Accounts, Devices, Triggers, Ui |
| Plugin | 5 | ApiKey, CouchDb, RabbitMq, RedisDb, RedisDbCache |

Six extensions have a frontend: Core, the four modules and the HomeKit connector. Every extension is a path repository of this application (composer `fastybird/<name>`, npm `@fastybird/<name>`), not a separately released package.

How the repository and Core came to this shape is recorded in the specs and plans under [`docs/superpowers/`](./superpowers/), which is an archive, not a description of the current system.

## Core

`Core/Core` (composer `fastybird/miniserver-core`, npm `@fastybird/miniserver-core`) is the one package every other extension builds on. Its own [README](../src/FastyBird/Core/Core/README.md) lists what it provides, and its [guide](../src/FastyBird/Core/Core/docs/Home.md) describes each capability.

### Capability layout

Core is organised by capability, not by layer. `src/FastyBird/Core/Core/src` has 17 top-level namespaces under `FastyBird\Core\`:

- **11 capabilities:** `Api`, `Clock`, `Documents`, `Exchange`, `Http`, `Logging`, `Persistence`, `Phone`, `Security`, `Values` and `WebSockets`;
- **`Exceptions`**, a shared root and not a capability: only the exceptions that belong to no single capability (`Exception`, `InvalidArgument`, `InvalidController`, `InvalidLink`, `InvalidState`, `Logic`, `Runtime`, `UnexpectedValue`);
- **5 runtime namespaces**, kept at the root because they are unrelated to each other: `Boot` (the bootstrap and the container configurator), `DI` (the composite extension), `EventLoop` (the React event loop wrapper and its lifecycle events), `Presenters` (the Nette presenters and the `/` route) and `UI` (the Latte template factory).

Two structural rules keep it that way:

1. **Exceptions and events live inside their capability:** `Security\Exceptions\UnauthorizedAccess`, `Exchange\Events\BeforeMessagePublished`. Only the cross-cutting exceptions above sit at the root.
2. **Layer names appear only inside a capability.** `Middleware`, `Subscribers`, `Entities`, `Controllers`, `Helpers` and the like are never top-level namespaces.

[conventions.md](./conventions.md#namespace-layout) states the naming rules, and `make naming` enforces the identity part of them.

### The composite DI extension `fbCore`

Every container registers exactly one Core extension: `FastyBird\Core\DI\CoreExtension`, under the name `fbCore`. It is a composite. Each capability has a child extension, `FastyBird\Core\<Capability>\DI\<Capability>Extension`, which the composite constructs, names `fbCore.<capability>`, hands its own configuration subtree, and calls at the right point of each compiler phase. The children are never registered with the compiler themselves, because nette/di cannot add an extension while it compiles. `CoreExtension` keeps only the composition and the root runtime: the event loop, the Nette UI and route list, the presenter mapping, the PSR-6 cache and a fallback event dispatcher.

| Child extension | Configuration section |
|---|---|
| `Api\DI\ApiExtension` | `fbCore.api` |
| `Clock\DI\ClockExtension` | `fbCore.clock` |
| `Documents\DI\DocumentsExtension` | `fbCore.documents` |
| `Exchange\DI\ExchangeExtension` | none |
| `Http\DI\HttpExtension` | `fbCore.http` |
| `Logging\DI\LoggingExtension` | `fbCore.logging` |
| `Persistence\DI\PersistenceExtension` | `fbCore.persistence` |
| `Phone\DI\PhoneExtension` | none |
| `Security\DI\SecurityExtension` | `fbCore.security` |
| `Values\DI\ValuesExtension` | none |
| `WebSockets\DI\WebSocketsExtension` | `fbCore.webSockets` |

Services are named `fbCore.<capability>.<role>`, tags `fastybird.core.<capability>.<role>`, and each configuration section is read through a typed `final readonly` class. [configuration.md](./configuration.md#core-fbcore) documents every key, and [conventions.md](./conventions.md#di) the rules for services, keys and tags.

### JS assets

`src/FastyBird/Core/Core/assets` follows the same capability layout. `entry.ts` is the package's single export (`"."` in `package.json`) and re-exports the capability barrels. `main.ts` is the Vite entry of the application shell.

- **Capabilities:** `api/` (the JSON:API backend client and its plugin), `eventBus/`, `extensions/` (the plugin that installs every registered extension), `security/` (the account manager contract), `stores/` (the Pinia store manager), `values/` (`types/`, the data types, payloads and sources mirrored from PHP's `Values`, and `utilities/`) and `websockets/` (the WAMP v1 client).
- **The shell, at the root:** `main.ts`, `App.vue`, `App.scss`, `vite-env.d.ts`, `icons.generated.ts`, `components/`, `composables/`, `images/`, `layouts/`, `locales/`, `router/`, `styles/` and `views/`.

`make naming` refuses an `assets/` directory named after a former package (`application`, `metadata`, `tools` and the rest of its namespace denylist) in every extension.

## Dependency layering

`tools/check-layering.php` (`make layers`) measures the dependency direction between packages from the code itself, every `use`, class reference and `.neon` service, rather than from the composer manifests. The rules are the `'types'` table of `tools/layering.php`:

- **Core** depends on nothing else in the repository, and is the only package the others may all depend on.
- **Plugins** and **modules** depend only on Core. A plugin never depends on another plugin, and a module never on another module or a plugin.
- **Automators** depend on Core and `Module/Triggers`. `Automator/DevicesModule` also depends on `Module/Devices`.
- **Connectors** depend on Core and `Module/Devices` only, never on another connector.
- **Addons** and **bridges** depend on Core plus the packages each one names in `tools/layering.php`'s `'packages'` table: a bridge is defined by the pair it joins (for example `Bridge/RedisDbPluginDevicesModule` on `Plugin/RedisDb` and `Module/Devices`). The bridges are the only packages that reach a plugin or an addon.

## Request routing

`public/index.php` is the single entry point for both the API and the UI. It compares the start of `$_SERVER['REQUEST_URI']` with `'/' . FastyBird\Core\Http\Routing\Router::API_PREFIX` (`/api`). A match goes to the API stack, everything else to the Nette application.

**The API stack.** `FastyBird\Core\Http\Server\Application` dispatches the request through the HTTP router, `fbCore.http.routing.router` (`Http\Routing\ServerRouter`). Each module registers its routes on it in `beforeCompile()`, under `/api`, then `/<module prefix>` while the module's `apiPrefix` option is `true` (the application sets it from `%api.prefixed.modules%`, `true` by default), then `/v1`: for example `GET /api/devices-module/v1/devices`. The module's route group carries Security's `Authorization` and `User` middlewares, so an anonymous request is refused with 403 and an invalid token with 401. The application's `config/common.neon` adds two router-wide middlewares: `fbCore.api.middleware` (JSON:API error responses) and the Accounts module's URL-format middleware. Under `fb:web-server:start` the same router runs inside a ReactPHP server, behind the CORS and static-file middlewares, the services `fbCore.http.middlewares.cors` (`Http\Middleware\Cors`) and `fbCore.http.middlewares.staticFiles` (`Http\Middleware\StaticFiles`), configured by the `fbCore.http.cors` and `fbCore.http.static` sections.

**The Nette application.** Its router is `fbCore.ui.routes`, a Nette `RouteList`. `CoreExtension` adds `FastyBird\Core\Presenters\AppRouter`, which maps `/` to `App:Default:default`, and the Accounts and Devices modules add their own presenter routes (`/sign/<action>`, `/account`, `/devices[/<id>]`, `/connectors[/<id>]` and others). The layout template, `src/FastyBird/Core/Core/templates/@layout.latte`, emits the Vue shell's script through contributte/vite into the `#app` mount point that `assets/main.ts` expects.

**`GET /` returns 404 on Linux.** The presenters look their templates up by the capitalised presenter name (`templates/presenters/Default.latte`), and the files are lowercase (`default.latte`, Accounts' `sign.latte`). On a case-sensitive filesystem, which includes the production image, a presenter that renders a template ends in 404 `Missing template`: `GET /` and `GET /sign/in` among them. A route that needs a signed-in user redirects to `/sign/in` before it renders. On a case-insensitive filesystem, such as a macOS bind mount, the same requests return 200, so a local result does not predict the production one. Do not document or assume that `GET /` returns 200. `docker/prod/Dockerfile`'s `HEALTHCHECK` uses `/favicon.ico` for this reason, and `.github/workflows/ci-tests.yaml`'s `GET /` step asserts only that the Nette application answered.

## Configuration load order

`FastyBird\Core\Boot\Bootstrap::boot()` defines `FB_APP_DIR`, `FB_PUBLIC_DIR`, `FB_RESOURCES_DIR`, `FB_TEMP_DIR`, `FB_LOGS_DIR` and `FB_CONFIG_DIR` from `$_ENV`/`getenv()`. `FB_CONFIG_DIR` defaults to `<FB_APP_DIR>/config` when not set explicitly, but it is a genuinely independent, overridable setting -- point it at, for example, `/var/mini-server/config` (the production image sets it to `/data/config`) to load operator overrides from somewhere other than the checked-in `config/` directory.

Config resolution is **three layers**, read in this order, via `Bootstrap::resolveConfigFiles()`:

1. **Core's own defaults** -- `src/FastyBird/Core/Core/config/common.neon`, then `.../defaults.neon`.
2. **The application's own config** -- `<FB_APP_DIR>/config/common.neon`, then `.../defaults.neon`. This is the wiring checked into the root `config/` directory.
3. **The operator override directory, `FB_CONFIG_DIR`** -- `common.neon`, `defaults.neon`, then `local.neon`, in that order.

Every file is skipped if it does not exist, and `resolveConfigFiles()` also **dedupes by resolved real path** (`realpath()`), skipping any file already loaded by an earlier layer. Since `FB_CONFIG_DIR` defaults to the *same directory* as layer 2, the common case is that layer 3's `common.neon`/`defaults.neon` resolve to files already loaded in layer 2 and are silently skipped -- only `local.neon` actually adds anything. Point `FB_CONFIG_DIR` at a genuinely different directory and all three of its files load in addition to layers 1 and 2, because none of them share a real path with anything loaded so far.

Environment variables named `FB_APP_PARAMETER__<SECTION>_<KEY>` become container parameters: the prefix ends in a double underscore, and every further underscore starts a nested key, all lowercased. `FB_APP_PARAMETER__DATABASE_HOST` becomes `%database.host%`, and `FB_APP_PARAMETER__API_PREFIXED_MODULES` becomes `%api.prefixed.modules%`. A canonical integer becomes an integer, and `true`/`false` a boolean; credentials are never typed (see [Environment variable typing](./configuration.md#environment-variable-typing)). `APP_ENV=dev` enables debug mode. Tracy is enabled unless the process runs under PHPUnit. See [configuration.md](./configuration.md) for how to add extension-specific wiring through `local.neon`.

## Runtime processes

HTTP: nginx in front of php-fpm, every request handled by `public/index.php`. The ReactPHP server behind `php bin/fb-console.php fb:web-server:start` remains available for local runs without nginx.

Long-running workers under supervisord: `fb:ws-server:start` (the WAMP v1 WebSocket server, port 8888 from `%sockets.port%`) and `fb:devices-module:exchange`. Connector processes run as `fb:devices-module:connector <identifier>`, one process per configured connector. The processes exchange state through Core's `Exchange` capability; the RedisDb and RabbitMq plugins carry it between processes when they are registered. See [deployment.md](./deployment.md) for the supervisor programs of the production image, the development Compose stack, and a gap in the connector wiring worth knowing about before you rely on it.

## Frontend

`config/extensions.ts` registers which extensions' UI the Vite build includes; today that is `accounts-module`, `devices-module`, `homekit-connector` and `ui-module` (`triggers-module` has a UI but is not registered). Each entry's module is the extension's npm package, whose single export is its `assets/entry.ts`. Its default export must be a Vue plugin object whose `install(app, options)` takes the `IExtensionOptions` the shell passes; the shell's `extensions` plugin skips, with a console error, any module that does not export one. `src/FastyBird/Core/Core/assets/main.ts` is the Vite entry: it installs the shell's plugins and the registered extensions, and `vite.config.ts` injects the application version and description from the root `package.json` at build time.

## Database schema

Schema changes ship as Doctrine migrations in `migrations/`, the one directory `config/common.neon` registers. It holds two versions:

- `Version20261011210849`: a baseline of the whole schema of the default registration (the extensions `config/common.neon` registered when it was generated), 34 tables plus Doctrine Migrations' own `doctrine_migrations`.
- `Version20261012172407`: the two Automators' 14 nullable single-table-inheritance columns, 5 on `fb_triggers_module_actions` and 9 on `fb_triggers_module_conditions`. `config/common.neon` registers the Automators since this version (#745).

A schema change adds a new version on top of them, generated with `migrations:diff` after `rm -rf var/temp/cache`: outside debug mode the compiled container is never rebuilt, and a stale one can make `migrations:diff` report no changes.

- **Character set.** The DBAL connection uses `charset: utf8mb4`, and its `defaultTableOptions` (`charset: utf8mb4`, `collation: utf8mb4_general_ci`) are the only place table options are set. Every generated `CREATE TABLE` carries them explicitly, so the schema does not depend on the server's default collation. Entities declare no table options of their own.
- **The drift gate.** The production entrypoint runs `migrations:migrate --no-interaction --allow-no-migration` at every start. CI's `Docker Build` job starts the image on an empty database and then runs `orm:validate-schema`, which exits 0 only when the migrated schema equals the entity mapping, so a mapping change without a migration fails CI. `Persistence\Subscribers\SchemaSubscriber` adds the `doctrine_migrations` table to the schema the ORM generates, so neither the gate nor `orm:schema-tool` proposes dropping it.
- **Tests do not migrate.** The test suites build their schema from the mapping with Doctrine's `SchemaTool`.
- **Optional extensions ship no schema.** No migration creates what an extension outside the default registration maps. The ApiKey plugin adds a table, so registering it leaves the schema out of sync with its mapping (#730). The two Automators are part of the default registration, and `Version20261012172407` creates their columns.

[deployment.md](./deployment.md) describes what to do with a database created before the baseline.
