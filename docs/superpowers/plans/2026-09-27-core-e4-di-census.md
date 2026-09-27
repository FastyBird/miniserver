# E4.1 — Census of `CoreExtension`: DI allocation, rename tables and order analysis

Subtask of Epic E4 (#459), orchestration issue #562. Satisfies the checklist in #553. **Merging
this PR is the maintainer's approval of every individual name below (services, configuration
keys, tags, translation domain) and of the order analysis.** Phase 1 (#555–#559) and #560
execute these tables verbatim; a name, key or tag that is not in them is an escalation (#459
§14).

Measured on `origin/main` at `3ffaae15e` (`refactor(cross): alias the remaining colliding imports
and empty the naming baseline (#548)`), the Technical Implementation Plan's own base. Every number
comes from a script run against that tree in the `fb-e2-app:latest` application image, with
`vendor/` installed by `COMPOSER_MIRROR_PATH_REPOS=1 composer install` (29 mirrors, all copies, no
symlinks, `diff -rq` of Core's `src` against its mirror empty). PHP was parsed with the vendored
`nikic/php-parser` 5.9.0, never with a regex. The configuration schema was walked from the real
`Nette\Schema` objects. The DI graph was read from compiled containers. "How to reproduce" at the
end lists every script.

## Decisions already taken (maintainer, 2026-09-27)

These are recorded here, not reopened (#459 comment of 2026-09-27, plan §3.2, §3.4, §3.6, §16):

1. **Extension placement:** capability-owned, `FastyBird\Core\<Capability>\DI\<Capability>Extension`
   (§3.2).
2. **`Configuration` stays one root type** in E4, registered by the composite from the `security` and
   `persistence.timestampable` subtrees. Its split is handed to #460 (§3.6).
3. **Full rename tables are in scope** (§3.4): all 9 `fbCore` configuration sections, the service
   names, the DI tags and the translation domain. This census writes every row. Approving it
   approves the individual names, not the scope.

**Decided while the census was reviewed (orchestrator, 2026-09-27, through the plan's §3.5
route).** A relative-order change is allowed when it comes with a proof that it is inert, and
§5.4 is that proof. The snapshot criteria and the 13 allowed global-order moves are recorded in
§5.5. **Merging this census approves those 13 moves and the two named hooks
(`loadTimestampable()`, `loadServerProcess()`).**

## Headline numbers, and where the plan's differ

| Item | Plan (#459) | Measured | Which is right, and why |
|---|---|---|---|
| `CoreExtension.php` | 1,596 lines | 1,596 | same |
| Service definitions | 105 = 101 prefixed + 4 `document.*`, "in `loadConfiguration()` 326–1071" | **105 distinct names from 106 `addDefinition`/`addFactoryDefinition` call sites**; 101 prefixed + 4 literal. **103 names are registered in `loadConfiguration()`, 2 in `beforeCompile()`** (`application.eventDispatcher`, `simpleAuth.security.user`). `simpleAuth.casbin.adapter` has two call sites (the `if`/`else` of `casbin.database`) | The count is right; the placement is not. Both `beforeCompile()` definitions are in the §1.1 per-capability table (root 8, Security 22), so the table is consistent with 105 across both methods |
| Services per owner | Security 22, WebSockets 22, Persistence 11, Http 9, Logging 9, root 8, Phone 7, Documents 5, Api 5, Exchange 4, Clock 2, Values 1 | identical | same |
| `beforeCompile()` blocks | 14 | **11 comment-headed blocks**. The plan's 14 counts `APPLICATION` as its 4 concerns (loggers, `AppRouter`, presenter mapping, template layout). One of the 14 (entity-manager subscriber wiring) has **two owners**, so there are **15 allocation units** | 14 is right at concern granularity; the allocation needs 15 |
| Symfony event subscribers in Core | 5 | **6**: `Symfony\Bridge\Monolog\Handler\ConsoleHandler` (`application.logger.handler.console`, debug mode only) is an `EventSubscriberInterface` too | measured |
| Doctrine subscribers in Core | 6; `loadClassMetadata` 4, `onFlush` 2 | 6; `loadClassMetadata` 4, `onFlush` 2 (as subscribers); **listener entries 6 and 3** in production, because of D2 (#564) | same, plus D2 |
| Module `loadConfiguration()` lookups of Core services | `DevicesExtension.php:926`, `DevicesModuleUiModuleExtension.php:160` | **3 extensions, 6 lookups**: Devices `:926-927`, **Ui `:492-493`**, DevicesModuleUiModule bridge `:160-161` (`LinkGenerator` + `Topics\IStorage` each) | measured |
| "`fbCore` is registered first, so every Core registration precedes every module `loadConfiguration()`" (§1.6) | holds | **Holds in production only.** In all 28 package test containers the package's own extension is registered by `<Ext>::register()` through `Configurator::onCompile`, which puts it **before** every NEON `extensions:` entry, so it runs `loadConfiguration()` **before** `fbCore` | measured; see finding F1. The composite does not change it |
| Base test containers | 29 | 29 `tests/common.neon`; **28 compile. `Plugin/CouchDb`'s does not compile on `main`**, and no test extends its `BaseTestCase` | measured; see F2 |
| Per-test NEON overlays | "E4.1 enumerates" | **6** (`registerNeonConfigurationFile()` ×5, `createContainer($file)` ×1) | — |
| Production | 1 | 1 configuration. It compiles only with a signature **and** a Vite manifest. 4 compilable variants were measured (§7) | — |
| DI-identifier denylist | 12 words + scoped `application` | `FB_NAMESPACE_DENYLIST` has 16 entries. The plan's list omits `slimRouter`, `doctrineOrmQuery` and `jsonApiDocument`, and `FB_TYPE_DENYLIST` adds `iPublikuj` | Use the full list (§10). The counts on `main` are identical either way |
| Guard violations on `main` | — | **134** = 61 service names + 71 schema key paths + 2 tag strings | §10 |
| Translation domain | 22 lookups in 5 Api files; `HydratorFieldsTest` | 22 lookups in 5 files, 11 distinct keys; `HydratorFieldsTest` has 2 `'//jsonApi.hydrator'` strings; catalogue has 10 keys. **4 of the 22 lookups name keys the catalogue does not have** | measured; see F3 (#567) |
| `@fbCore.jsonApi.middlewares.jsonapi` in NEON | 25 files | 25 | same |
| Service names looked up by string in tests | 7 (`CoreExtensionTest`) | 7 | same |
| Tag sites | 21 `findByTag(DRIVER_TAG)`, 15 `addTag(CONSUMER_STATE)` | 21 (21 files, all in `beforeCompile()`), 15 (13 files, all in `loadConfiguration()`). **10 of the 15 have no effect** (F4) | same count; F4 is new |
| Example rename `fbCore.wsServer.server.server` | → `fbCore.webSockets.server` | → **`fbCore.webSockets.server.runtime`** | see §2: the plan's form would also be the prefix of six sibling names |
| Order preservation | "at most a couple of named hooks" | **2 named hooks** (Persistence, WebSockets), **0 relative-order changes** in any collection, in all 38 compiled containers | §5 |
| Global definition order | recorded by the snapshot | **cannot be reproduced** under the one-hook rule: 13 of 103 `loadConfiguration()` definitions move. Exact reproduction needs 6 extra hooks | §5.5. **Resolved:** the 13 moves are proven inert (§5.4) and approved by merging this census; the snapshot allows exactly these (`--allow-moves`) |

## 1. Allocation

**Rule (#553):** the owner is the capability of the registered type's namespace. For vendor types
it is the block that configures them. Named exceptions: `Http\Routing\LinkGenerator` → WebSockets
(Epic §1.7), `Documents\RoutingDocumentFactory` registered in the Exchange block → Exchange,
`Configuration` → root. Root means the composite `CoreExtension` itself: event loop, Nette UI and
route list, PSR-6 array cache, event-dispatcher fallback, `Configuration`, presenter mapping.

Four vendor types sit in the mixed `APPLICATION` block, so the rule needs their configuring
concern. `ArrayAdapter` is the root PSR-6 cache. Nette `RouteList` is configured by the root UI
block (`AppRouter::createRouter`). Nette `Caching\Cache` is the `cache` argument of
`document.mapping.classMetadataFactory`, so it goes to Documents. The three Monolog handlers are
pushed by the Logging `beforeCompile()` block. `TOOLS` vendor types (Sentry) → Logging; `PHONE`
(libphonenumber) → Phone; `SIMPLE AUTH` (Casbin `FileAdapter`) → Security; `WEBSOCKETS` (React
loop) → WebSockets. No service belongs to two capabilities without a rule to break the tie.

### 1.1 Service definitions (106 call sites, 105 names)

"Lands in" is the E4.3/E4.4 target. Root groups A/B/C and the two named hooks are defined in §5.3.

| # | Line | Service (today) | Registered type | Owner | Lands in | Registered only when |
|---|---|---|---|---|---|---|
| 1 | 337 | `fbCore.application.logger.handler.rotatingFile` | `Monolog\Handler\RotatingFileHandler` | Logging (vendor, by block) | `LoggingExtension::loadConfiguration()` | application.logging.rotatingFile.enabled === true |
| 2 | 350 | `fbCore.application.logger.handler.stdOut` | `Monolog\Handler\StreamHandler` | Logging (vendor, by block) | `LoggingExtension::loadConfiguration()` | application.logging.stdOut.enabled === true |
| 3 | 364 | `fbCore.application.logger.handler.console` | `Symfony\Bridge\Monolog\Handler\ConsoleHandler` | Logging (vendor, by block) | `LoggingExtension::loadConfiguration()` | application.logging.console.enabled |
| 4 | 371 | `fbCore.application.cache.psr6` | `Symfony\Component\Cache\Adapter\ArrayAdapter` | root (vendor, by block) | `CoreExtension` root group A | always |
| 5 | 374 | `fbCore.application.eventLoop.wrapper` | `FastyBird\Core\EventLoop\Wrapper` | root | `CoreExtension` root group A | always |
| 6 | 377 | `fbCore.application.eventLoop.status` | `FastyBird\Core\EventLoop\Status` | root | `CoreExtension` root group A | always |
| 7 | 381 | `fbCore.application.subscribers.console` | `FastyBird\Core\Logging\Subscribers\Console` | Logging | `LoggingExtension::loadConfiguration()` | application.logging.console.enabled |
| 8 | 393 | `fbCore.application.subscribers.entityDiscriminator` | `FastyBird\Core\Persistence\Subscribers\EntityDiscriminator` | Persistence | `PersistenceExtension::loadConfiguration()` | Doctrine DBAL and ORM installed |
| 9 | 400 | `fbCore.application.subscribers.eventLoop` | `FastyBird\Core\EventLoop\Subscribers\EventLoopLifeCycle` | root | `CoreExtension` root group B | always |
| 10 | 406 | `fbCore.application.ui.templateFactory` | `FastyBird\Core\UI\TemplateFactory` | root | `CoreExtension` root group B | always |
| 11 | 409 | `fbCore.application.ui.routes` | `Nette\Application\Routers\RouteList` | root (vendor, by block) | `CoreExtension` root group B | always |
| 12 | 412 | `fbCore.application.document.cache` | `Nette\Caching\Cache` | Documents (vendor, by block) | `DocumentsExtension::loadConfiguration()` | always |
| 13 | 420 | `document.factory` | `FastyBird\Core\Documents\DocumentFactory` | Documents | `DocumentsExtension::loadConfiguration()` | always |
| 14 | 423 | `document.mapping.attributeDriver` | `FastyBird\Core\Documents\Mapping\Driver\AttributeDriver` | Documents | `DocumentsExtension::loadConfiguration()` | always |
| 15 | 433 | `document.mapping.mappingDriver` | `FastyBird\Core\Documents\Mapping\Driver\MappingDriverChain` | Documents | `DocumentsExtension::loadConfiguration()` | always |
| 16 | 439 | `document.mapping.classMetadataFactory` | `FastyBird\Core\Documents\Mapping\ClassMetadataFactory` | Documents | `DocumentsExtension::loadConfiguration()` | always |
| 17 | 455 | `fbCore.exchange.consumer` | `FastyBird\Core\Exchange\Consumers\Container` | Exchange | `ExchangeExtension::loadConfiguration()` | always |
| 18 | 458 | `fbCore.exchange.publisher` | `FastyBird\Core\Exchange\Publisher\Container` | Exchange | `ExchangeExtension::loadConfiguration()` | always |
| 19 | 461 | `fbCore.exchange.publisher.async` | `FastyBird\Core\Exchange\Publisher\Async\Container` | Exchange | `ExchangeExtension::loadConfiguration()` | always |
| 20 | 464 | `fbCore.exchange.entityFactory` | `FastyBird\Core\Documents\RoutingDocumentFactory` | Exchange (exception) | `ExchangeExtension::loadConfiguration()` | always |
| 21 | 472 | `fbCore.simpleAuth.auth` | `FastyBird\Core\Security\Services\Auth` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 22 | 475 | `fbCore.simpleAuth.token.builder` | `FastyBird\Core\Security\Identity\TokenBuilder` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 23 | 480 | `fbCore.simpleAuth.token.reader` | `FastyBird\Core\Security\Identity\TokenReader` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 24 | 483 | `fbCore.simpleAuth.token.validator` | `FastyBird\Core\Security\Identity\TokenValidator` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 25 | 489 | `fbCore.simpleAuth.security.identityFactory` | `FastyBird\Core\Security\Identity\IdentityFactory` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.services.identity |
| 26 | 496 | `fbCore.simpleAuth.security.userStorage` | `FastyBird\Core\Security\Identity\UserStorage` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 27 | 502 | `fbCore.simpleAuth.access.annotationChecker` | `FastyBird\Core\Security\Access\AnnotationChecker` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 28 | 508 | `fbCore.simpleAuth.access.latteChecker` | `FastyBird\Core\Security\Access\LatteChecker` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 29 | 514 | `fbCore.simpleAuth.access.linkChecker` | `FastyBird\Core\Security\Access\LinkChecker` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 30 | 521 | `fbCore.simpleAuth.casbin.adapter` | `FastyBird\Core\Security\Models\Casbin\Adapter` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.casbin.database |
| 31 | 543 | `fbCore.simpleAuth.casbin.subscriber` | `FastyBird\Core\Security\Subscribers\Policy` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.casbin.database |
| 32 | 555 | `fbCore.simpleAuth.casbin.adapter` | `Casbin\Persist\Adapters\FileAdapter` | Security (vendor, by block) | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND else of if (simpleAuth.enable.casbin.database) |
| 33 | 569 | `fbCore.simpleAuth.casbin.enforcerFactory` | `FastyBird\Core\Security\Identity\EnforcerFactory` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' |
| 34 | 577 | `fbCore.simpleAuth.middleware.access` | `FastyBird\Core\Security\Middleware\Authorization` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.middleware |
| 35 | 583 | `fbCore.simpleAuth.middleware.user` | `FastyBird\Core\Security\Middleware\User` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.middleware |
| 36 | 591 | `fbCore.simpleAuth.doctrine.driver` | `FastyBird\Core\Security\Mapping\Driver\Owner` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.doctrine.mapping |
| 37 | 597 | `fbCore.simpleAuth.doctrine.subscriber` | `FastyBird\Core\Security\Subscribers\User` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.doctrine.mapping |
| 38 | 605 | `fbCore.simpleAuth.doctrine.tokensRepository` | `FastyBird\Core\Security\Models\Tokens\Repository` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.doctrine.models |
| 39 | 611 | `fbCore.simpleAuth.doctrine.tokensManager` | `FastyBird\Core\Security\Models\Tokens\Manager` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.doctrine.models |
| 40 | 619 | `fbCore.simpleAuth.doctrine.policiesRepository` | `FastyBird\Core\Security\Models\Policies\Repository` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.casbin.database |
| 41 | 625 | `fbCore.simpleAuth.doctrine.policiesManager` | `FastyBird\Core\Security\Models\Policies\Manager` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.casbin.database |
| 42 | 633 | `fbCore.simpleAuth.nette.application` | `FastyBird\Core\Security\Subscribers\Application` | Security | `SecurityExtension::loadConfiguration()` | simpleAuth.token.signature !== '' AND simpleAuth.enable.nette.application |
| 43 | 646 | `fbCore.tools.helpers.database` | `FastyBird\Core\Persistence\Helpers\Database` | Persistence | `PersistenceExtension::loadConfiguration()` | Doctrine DBAL and ORM installed |
| 44 | 650 | `fbCore.tools.utilities.doctrineDateProvider` | `FastyBird\Core\Persistence\Utilities\DateTimeProvider` | Persistence | `PersistenceExtension::loadConfiguration()` | always |
| 45 | 656 | `fbCore.tools.schemas.validator` | `FastyBird\Core\Values\Schemas\Validator` | Values | `ValuesExtension::loadConfiguration()` | always |
| 46 | 660 | `fbCore.tools.helpers.sentry` | `FastyBird\Core\Logging\Sentry` | Logging | `LoggingExtension::loadConfiguration()` | sentry/sentry installed |
| 47 | 687 | `fbCore.tools.sentry.handler` | `Sentry\Monolog\Handler` | Logging (vendor, by block) | `LoggingExtension::loadConfiguration()` | a Sentry DSN (env or tools.sentry.dsn) |
| 48 | 691 | `fbCore.tools.sentry.clientBuilder` | `factory Sentry\ClientBuilder::create` | Logging (vendor, by block) | `LoggingExtension::loadConfiguration()` | a Sentry DSN (env or tools.sentry.dsn) |
| 49 | 698 | `fbCore.tools.sentry.client` | `Sentry\ClientInterface` | Logging (vendor, by block) | `LoggingExtension::loadConfiguration()` | a Sentry DSN (env or tools.sentry.dsn) |
| 50 | 703 | `fbCore.tools.sentry.hub` | `Sentry\State\Hub` | Logging (vendor, by block) | `LoggingExtension::loadConfiguration()` | a Sentry DSN (env or tools.sentry.dsn) |
| 51 | 716 | `fbCore.dateTimeFactory.datetime.system` | `FastyBird\Core\Clock\SystemClock` | Clock | `ClockExtension::loadConfiguration()` | dateTimeFactory.system |
| 52 | 726 | `fbCore.dateTimeFactory.datetime.frozen` | `FastyBird\Core\Clock\FrozenClock` | Clock | `ClockExtension::loadConfiguration()` | dateTimeFactory.frozen !== null |
| 53 | 741 | `fbCore.doctrineCrud.entity.mapper` | `FastyBird\Core\Persistence\Mapping\EntityMapper` | Persistence | `PersistenceExtension::loadConfiguration()` | always |
| 54 | 745 | `fbCore.doctrineCrud.entity.creator` | `FastyBird\Core\Persistence\Crud\Create\EntityCreatorFactory (result FastyBird\Core\Persistence\Crud\Create\EntityCreator)` | Persistence | `PersistenceExtension::loadConfiguration()` | always |
| 55 | 751 | `fbCore.doctrineCrud.entity.updater` | `FastyBird\Core\Persistence\Crud\Update\EntityUpdaterFactory (result FastyBird\Core\Persistence\Crud\Update\EntityUpdater)` | Persistence | `PersistenceExtension::loadConfiguration()` | always |
| 56 | 757 | `fbCore.doctrineCrud.entity.deleter` | `FastyBird\Core\Persistence\Crud\Delete\EntityDeleterFactory (result FastyBird\Core\Persistence\Crud\Delete\EntityDeleter)` | Persistence | `PersistenceExtension::loadConfiguration()` | always |
| 57 | 763 | `fbCore.doctrineCrud.crud` | `FastyBird\Core\Persistence\Crud\CrudFactory (result FastyBird\Core\Persistence\Crud\EntityCrud)` | Persistence | `PersistenceExtension::loadConfiguration()` | always |
| 58 | 782 | `fbCore.configuration` | `FastyBird\Core\Configuration` | root (exception) | `CoreExtension` root group C | always |
| 59 | 802 | `fbCore.doctrineTimestampable.driver` | `FastyBird\Core\Persistence\Mapping\Driver\Timestampable` | Persistence | `PersistenceExtension` named hook | always |
| 60 | 805 | `fbCore.doctrineTimestampable.subscriber` | `FastyBird\Core\Persistence\Subscribers\TimestampableSubscriber` | Persistence | `PersistenceExtension` named hook | always |
| 61 | 824 | `fbCore.doctrineMigrations.subscriber` | `FastyBird\Core\Persistence\Subscribers\SchemaSubscriber` | Persistence | `PersistenceExtension` named hook | nettrineMigrations registered |
| 62 | 832 | `fbCore.jsonApi.builder` | `FastyBird\Core\Api\Encoding\Builder` | Api | `ApiExtension::loadConfiguration()` | always |
| 63 | 837 | `fbCore.jsonApi.middlewares.jsonapi` | `FastyBird\Core\Api\Middleware\JsonApiMiddleware` | Api | `ApiExtension::loadConfiguration()` | always |
| 64 | 840 | `fbCore.jsonApi.hydrators.container` | `FastyBird\Core\Api\Hydrators\Container` | Api | `ApiExtension::loadConfiguration()` | always |
| 65 | 843 | `fbCore.jsonApi.schemas.container` | `FastyBird\Core\Api\Encoding\SchemaContainer` | Api | `ApiExtension::loadConfiguration()` | always |
| 66 | 847 | `fbCore.jsonApi.helpers.crudReader` | `FastyBird\Core\Api\Helpers\CrudReader` | Api | `ApiExtension::loadConfiguration()` | class_exists('IPub\DoctrineCrud\...\Crud') (never true, D1) |
| 67 | 855 | `fbCore.phone.libphone.utils` | `libphonenumber\PhoneNumberUtil` | Phone (vendor, by block) | `PhoneExtension::loadConfiguration()` | always |
| 68 | 859 | `fbCore.phone.libphone.geoCoder` | `libphonenumber\geocoding\PhoneNumberOfflineGeocoder` | Phone (vendor, by block) | `PhoneExtension::loadConfiguration()` | always |
| 69 | 863 | `fbCore.phone.libphone.shortNumber` | `libphonenumber\ShortNumberInfo` | Phone (vendor, by block) | `PhoneExtension::loadConfiguration()` | always |
| 70 | 867 | `fbCore.phone.libphone.mapper.carrier` | `libphonenumber\PhoneNumberToCarrierMapper` | Phone (vendor, by block) | `PhoneExtension::loadConfiguration()` | always |
| 71 | 871 | `fbCore.phone.libphone.mapper.timezone` | `libphonenumber\PhoneNumberToTimeZonesMapper` | Phone (vendor, by block) | `PhoneExtension::loadConfiguration()` | always |
| 72 | 875 | `fbCore.phone.phone` | `FastyBird\Core\Phone\Services\PhoneNumberHelper` | Phone | `PhoneExtension::loadConfiguration()` | always |
| 73 | 878 | `fbCore.phone.doctrinePhone.subscriber` | `FastyBird\Core\Phone\Subscribers\PhoneObjectSubscriber` | Phone | `PhoneExtension::loadConfiguration()` | always |
| 74 | 885 | `fbCore.webSockets.controllers.factory` | `FastyBird\Core\WebSockets\Controllers\IControllerFactory` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 75 | 894 | `fbCore.wsServer.clients.factory` | `FastyBird\Core\WebSockets\Clients\ClientFactory` | WebSockets | `WebSocketsExtension::loadConfiguration()` | no ClientProvider yet |
| 76 | 898 | `fbCore.wsServer.clients.driver.memory` | `FastyBird\Core\WebSockets\Clients\Drivers\InMemory` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 77 | 905 | `fbCore.wsServer.clients.storage` | `FastyBird\Core\WebSockets\Clients\Storage` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 78 | 913 | `fbCore.webSockets.routing.router` | `FastyBird\Core\WebSockets\Wamp\WampRouter` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 79 | 924 | `fbCore.webSockets.routing.generator` | `FastyBird\Core\Http\Routing\LinkGenerator` | WebSockets (exception) | `WebSocketsExtension::loadConfiguration()` | always |
| 80 | 927 | `fbCore.wsServer.server.wrapper` | `FastyBird\Core\WebSockets\Server\Wrapper` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 81 | 930 | `fbCore.wsServer.server.flashWrapper` | `FastyBird\Core\WebSockets\Server\FlashWrapper` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 82 | 943 | `fbCore.wsServer.server.handlers` | `FastyBird\Core\WebSockets\Server\Handlers` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 83 | 948 | `fbCore.wsServer.server.loop` | `React\EventLoop\LoopInterface` | WebSockets (vendor, by block) | `WebSocketsExtension::loadConfiguration()` | webSockets.loop === null AND no LoopInterface registered |
| 84 | 958 | `fbCore.wsServer.server.configuration` | `FastyBird\Core\WebSockets\Server\Configuration` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 85 | 968 | `fbCore.wsServer.server.logger` | `FastyBird\Core\WebSockets\Helpers\Console` | WebSockets | `WebSocketsExtension::loadConfiguration()` | no LoggerInterface registered |
| 86 | 972 | `fbCore.wsServer.server.server` | `FastyBird\Core\WebSockets\Server\ServerRuntime` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 87 | 977 | `fbCore.wsServer.wamp.topics.driver.memory` | `FastyBird\Core\WebSockets\Topics\Drivers\InMemory` | WebSockets | `WebSocketsExtension::loadConfiguration()` | topics.driver is the default sentinel (D3) |
| 88 | 981 | `fbCore.wsServer.wamp.topics.storage` | `FastyBird\Core\WebSockets\Topics\Storage` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 89 | 989 | `fbCore.webSockets.wamp.application` | `FastyBird\Core\WebSockets\Controllers\WampApplication` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 90 | 992 | `fbCore.webSockets.wamp.serializer` | `FastyBird\Core\WebSockets\Encoding\PushMessageSerializer` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 91 | 995 | `fbCore.webSockets.wamp.pushRegistry` | `FastyBird\Core\WebSockets\PushMessages\ConsumersRegistry` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 92 | 1002 | `fbCore.wsServer.wamp.clientsFactory` | `FastyBird\Core\WebSockets\Clients\WampClientFactory` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 93 | 1005 | `fbCore.wsServer.wamp.subscribers.onServerStart` | `FastyBird\Core\WebSockets\Subscribers\OnServerStartHandler` | WebSockets | `WebSocketsExtension::loadConfiguration()` | always |
| 94 | 1012 | `fbCore.httpServer.routing.responseFactory` | `FastyBird\Core\Http\ServerResponseFactory` | Http | `HttpExtension::loadConfiguration()` | always |
| 95 | 1018 | `fbCore.httpServer.routing.router` | `FastyBird\Core\Http\Routing\ServerRouter` | Http | `HttpExtension::loadConfiguration()` | always |
| 96 | 1021 | `fbCore.httpServer.commands.server` | `FastyBird\Core\Http\Commands\HttpServer` | Http | `HttpExtension::loadConfiguration()` | always |
| 97 | 1029 | `fbCore.httpServer.middlewares.cors` | `FastyBird\Core\Http\Middleware\Cors` | Http | `HttpExtension::loadConfiguration()` | always |
| 98 | 1039 | `fbCore.httpServer.middlewares.staticFiles` | `FastyBird\Core\Http\Middleware\StaticFiles` | Http | `HttpExtension::loadConfiguration()` | always |
| 99 | 1047 | `fbCore.httpServer.middlewares.router` | `FastyBird\Core\Http\Middleware\Router` | Http | `HttpExtension::loadConfiguration()` | always |
| 100 | 1050 | `fbCore.httpServer.application.classic` | `FastyBird\Core\Http\Server\Application` | Http | `HttpExtension::loadConfiguration()` | always |
| 101 | 1053 | `fbCore.httpServer.server.factory` | `FastyBird\Core\Http\Server\Factory` | Http | `HttpExtension::loadConfiguration()` | always |
| 102 | 1056 | `fbCore.httpServer.subscribers.server` | `FastyBird\Core\Http\Subscribers\Server` | Http | `HttpExtension::loadConfiguration()` | always |
| 103 | 1063 | `fbCore.wsServer.commands.wsServer` | `FastyBird\Core\WebSockets\Commands\WsServer` | WebSockets | `WebSocketsExtension` named hook | always |
| 104 | 1067 | `fbCore.wsServer.subscribers.client` | `FastyBird\Core\WebSockets\Subscribers\Client` | WebSockets | `WebSocketsExtension` named hook | always |
| 105 | 1107 | `fbCore.application.eventDispatcher` | `Symfony\Component\EventDispatcher\EventDispatcher` | root (vendor, by block) | root `beforeCompile()` | no PSR event dispatcher |
| 106 | 1273 | `fbCore.simpleAuth.security.user` | `FastyBird\Core\Security\Identity\User` | Security | `SecurityExtension::beforeCompile()` | no Identity\User service yet AND simpleAuth.token.signature !== '' |

### 1.2 `beforeCompile()` (14 blocks as the plan counts them, 15 allocation units)

| # | Lines | Block (comment header) | What it does | Owner | Lands in |
|---|---|---|---|---|---|
| 1 | 1086–1109 | EVENT DISPATCHER — default fallback | registers `application.eventDispatcher` if no PSR-14 dispatcher exists | root | `CoreExtension::beforeCompile()`, first |
| 2 | 1111–1136 | DOCTRINE CRUD — entity CRUD services | removes the 5 `doctrineCrud.*` definitions when no `ManagerRegistry` exists | Persistence | `PersistenceExtension::beforeCompile()` |
| 3 | 1138–1164 | APPLICATION — loggers | `pushHandler` rotating-file, then stdOut, on the Monolog logger | Logging | `LoggingExtension::beforeCompile()` |
| 4 | 1170–1174 | APPLICATION — routes | `AppRouter::createRouter` setup on the Nette `RouteList` | root | `CoreExtension::beforeCompile()` |
| 5 | 1176–1182 | APPLICATION — presenter mapping | `setMapping(['App' => …])` on the presenter factory | root | `CoreExtension::beforeCompile()` |
| 6 | 1184–1189 | APPLICATION — UI | `registerLayout` on `UI\TemplateFactory` | root | `CoreExtension::beforeCompile()` |
| 7 | 1191–1258 | EXCHANGE — consumer/publisher proxy assembly | `findByType(Consumer / MessagePublisher / Async\MessagePublisher)`, `setAutowired(false)`, `register()` setups, `CONSUMER_STATE`/`CONSUMER_ROUTING_KEY` tags read | Exchange | `ExchangeExtension::beforeCompile()` |
| 8 | 1260–1310 | SIMPLE AUTH — user fallback, Doctrine mapping, Nette Application bridge | registers `simpleAuth.security.user`; `MappingHelper::of($this)->addAttribute(…Security\Entities…)`; `onRequest`/`onResponse` on Nette `Application` | Security | `SecurityExtension::beforeCompile()` |
| 9 | 1312–1326 | TOOLS — Sentry handler wiring | `pushHandler` Sentry on the Monolog logger | Logging | `LoggingExtension::beforeCompile()` (after block 3's two) |
| 10 | 1328–1350 | DOCTRINE CRUD — custom DATE_FORMAT | `addCustomStringFunction('DATE_FORMAT')` on the entity manager | Persistence | `PersistenceExtension::beforeCompile()` |
| 11a | 1364–1367 | DOCTRINE TIMESTAMPABLE + DOCTRINE PHONE (first half) | `addEventSubscriber(timestampable)` on the entity manager | Persistence | `PersistenceExtension::beforeCompile()` (verbatim, D2 #564) |
| 11b | 1368–1371 | DOCTRINE TIMESTAMPABLE + DOCTRINE PHONE (second half) | `addEventSubscriber(phone)` on the entity manager | Phone | `PhoneExtension::beforeCompile()` (verbatim, D2 #564) |
| 12 | 1374–1395 | JSON:API — schema/hydrator assembly | `add()` every `JsonApiSchema` / `Hydrator` into the two containers | Api | `ApiExtension::beforeCompile()` |
| 13 | 1397–1544 | WEBSOCKETS — router, controllers, event bridges | `findByTag(TAG_WEBSOCKETS_ROUTES)` → `offsetSet`; tags every `RequestController` with `nette.inject` + `ipub.websockets.controller`; event bridges on `Controllers\Application`, `ServerRuntime`, `Wrapper`, `WampApplication`; push registry; `onStart[]` | WebSockets | `WebSocketsExtension::beforeCompile()` |
| 14 | 1546–1577 | WS SERVER PLUGIN — events bridge | throws without a PSR-14 dispatcher; `ClientConnected`/`IncomingMessage` bridges on `Wrapper` | WebSockets | `WebSocketsExtension::beforeCompile()` (after block 13) |

Lines 1166–1168 are a comment with no code: `EntityDiscriminator` is no longer attached by hand.

### 1.3 `afterCompile()`

| Lines | What | Owner | Lands in |
|---|---|---|---|
| 1580–1594 | appends `Type::addType('phone', PhoneType::class)` to the generated `initialize()` | Phone | `PhoneExtension::afterCompile()` |

Measured: this is the **only** statement `fbCore` contributes to `initialize()`. It is written
directly into the method, not through `getInitialization()`, so it has no `// fbCore.` header. In
the Core test container it is the last statement of `initialize()`. The composite must keep
writing it from `afterCompile()` at `fbCore`'s slot. Moving it into a child's `getInitialization()`
would wrap it in a closure and change the generated `initialize()` text.

### 1.4 Other members of the class

`NAME`, the four tag constants (§4), the unused static `register()`, and the dead
`class_alias('Nette\PhpGenerator\PhpLiteral', …)` guard (lines 113–115; E4.3 removes it with its
`PSR1.Files.SideEffects` exclusion).

## 2. Service map (`tools/di-maps/06-services.php`)

**Rule:** `fbCore.<capability>.<role>`. The library segment (`application`, `tools`,
`simpleAuth`, `dateTimeFactory`, `doctrineCrud`, `doctrineTimestampable`, `doctrineMigrations`,
`jsonApi`, `wsServer`, `httpServer`, unprefixed `document`) is replaced by the owning capability,
in the camelCase used by the config keys. The role path after it is **kept as it is** unless it
stutters, merely restates the capability, or carries a library word. Kept vendor words name real
third-party libraries, not merged ones: `casbin`, `sentry`, `libphone`, `doctrine`. Root services
are named by the root namespace their type lives in (`eventLoop`, `ui`), or by bare role
(`cache.psr6`, `eventDispatcher`, `configuration`). `application` never sits directly under
`fbCore`.

105 rows; 89 change, 16 are identity rows. Every target is unique, and every target passes both the denylist and the
positive pattern of §10.

**Departure from the plan's example:** `fbCore.wsServer.server.server` → `fbCore.webSockets.server.runtime`,
not `fbCore.webSockets.server`. The plan's form removes the stutter, but it would make one service
name the literal prefix of six sibling names (`fbCore.webSockets.server.wrapper`, `.flashWrapper`,
`.handlers`, `.loop`, `.configuration`, `.logger`). A reader could not tell the service from the
group. `runtime` is the type's role (`ServerRuntime`).

| # | Service today | Service target | Owner | Rule |
|---|---|---|---|---|
| 1 | `fbCore.application.logger.handler.rotatingFile` | `fbCore.logging.handler.rotatingFile` | Logging | library segment `application` -> capability; `logger` restated the capability |
| 2 | `fbCore.application.logger.handler.stdOut` | `fbCore.logging.handler.stdOut` | Logging | library segment `application` -> capability; `logger` restated the capability |
| 3 | `fbCore.application.logger.handler.console` | `fbCore.logging.handler.console` | Logging | library segment `application` -> capability; `logger` restated the capability |
| 4 | `fbCore.application.cache.psr6` | `fbCore.cache.psr6` | root | root service; `application` directly under fbCore is banned |
| 5 | `fbCore.application.eventLoop.wrapper` | `fbCore.eventLoop.wrapper` | root | root service named by its root namespace EventLoop |
| 6 | `fbCore.application.eventLoop.status` | `fbCore.eventLoop.status` | root | root service named by its root namespace EventLoop |
| 7 | `fbCore.application.subscribers.console` | `fbCore.logging.subscribers.console` | Logging | library segment -> capability |
| 8 | `fbCore.application.subscribers.entityDiscriminator` | `fbCore.persistence.subscribers.entityDiscriminator` | Persistence | library segment -> capability |
| 9 | `fbCore.application.subscribers.eventLoop` | `fbCore.eventLoop.subscribers.lifeCycle` | root | root namespace EventLoop; `eventLoop` would stutter, role from EventLoopLifeCycle |
| 10 | `fbCore.application.ui.templateFactory` | `fbCore.ui.templateFactory` | root | root service named by its root namespace UI |
| 11 | `fbCore.application.ui.routes` | `fbCore.ui.routes` | root | root service named by its root namespace UI |
| 12 | `fbCore.application.document.cache` | `fbCore.documents.cache` | Documents | library segment -> capability; `document` restated it |
| 13 | `document.factory` | `fbCore.documents.factory` | Documents | unprefixed -> prefixed (plan §3.4 example) |
| 14 | `document.mapping.attributeDriver` | `fbCore.documents.mapping.attributeDriver` | Documents | unprefixed -> prefixed |
| 15 | `document.mapping.mappingDriver` | `fbCore.documents.mapping.driverChain` | Documents | unprefixed -> prefixed; `mapping.mappingDriver` stuttered, role from MappingDriverChain |
| 16 | `document.mapping.classMetadataFactory` | `fbCore.documents.mapping.classMetadataFactory` | Documents | unprefixed -> prefixed |
| 17 | `fbCore.exchange.consumer` | *(unchanged)* | Exchange | unchanged: already `fbCore.<capability>.<role>` |
| 18 | `fbCore.exchange.publisher` | *(unchanged)* | Exchange | unchanged: already `fbCore.<capability>.<role>` |
| 19 | `fbCore.exchange.publisher.async` | *(unchanged)* | Exchange | unchanged: already `fbCore.<capability>.<role>` |
| 20 | `fbCore.exchange.entityFactory` | *(unchanged)* | Exchange | unchanged: already `fbCore.<capability>.<role>` |
| 21 | `fbCore.simpleAuth.auth` | `fbCore.security.auth` | Security | library segment `simpleAuth` -> capability |
| 22 | `fbCore.simpleAuth.token.builder` | `fbCore.security.token.builder` | Security | library segment `simpleAuth` -> capability |
| 23 | `fbCore.simpleAuth.token.reader` | `fbCore.security.token.reader` | Security | library segment `simpleAuth` -> capability |
| 24 | `fbCore.simpleAuth.token.validator` | `fbCore.security.token.validator` | Security | library segment `simpleAuth` -> capability |
| 25 | `fbCore.simpleAuth.security.identityFactory` | `fbCore.security.identityFactory` | Security | library segment -> capability; `security.security` would stutter |
| 26 | `fbCore.simpleAuth.security.userStorage` | `fbCore.security.userStorage` | Security | library segment -> capability; `security.security` would stutter |
| 27 | `fbCore.simpleAuth.access.annotationChecker` | `fbCore.security.access.annotationChecker` | Security | library segment `simpleAuth` -> capability |
| 28 | `fbCore.simpleAuth.access.latteChecker` | `fbCore.security.access.latteChecker` | Security | library segment `simpleAuth` -> capability |
| 29 | `fbCore.simpleAuth.access.linkChecker` | `fbCore.security.access.linkChecker` | Security | library segment `simpleAuth` -> capability |
| 30 | `fbCore.simpleAuth.casbin.adapter` | `fbCore.security.casbin.adapter` | Security | library segment `simpleAuth` -> capability |
| 31 | `fbCore.simpleAuth.casbin.subscriber` | `fbCore.security.casbin.subscriber` | Security | library segment `simpleAuth` -> capability |
| 32 | `fbCore.simpleAuth.casbin.enforcerFactory` | `fbCore.security.casbin.enforcerFactory` | Security | library segment `simpleAuth` -> capability |
| 33 | `fbCore.simpleAuth.middleware.access` | `fbCore.security.middleware.access` | Security | library segment `simpleAuth` -> capability |
| 34 | `fbCore.simpleAuth.middleware.user` | `fbCore.security.middleware.user` | Security | library segment `simpleAuth` -> capability |
| 35 | `fbCore.simpleAuth.doctrine.driver` | `fbCore.security.doctrine.driver` | Security | library segment `simpleAuth` -> capability |
| 36 | `fbCore.simpleAuth.doctrine.subscriber` | `fbCore.security.doctrine.subscriber` | Security | library segment `simpleAuth` -> capability |
| 37 | `fbCore.simpleAuth.doctrine.tokensRepository` | `fbCore.security.doctrine.tokensRepository` | Security | library segment `simpleAuth` -> capability |
| 38 | `fbCore.simpleAuth.doctrine.tokensManager` | `fbCore.security.doctrine.tokensManager` | Security | library segment `simpleAuth` -> capability |
| 39 | `fbCore.simpleAuth.doctrine.policiesRepository` | `fbCore.security.doctrine.policiesRepository` | Security | library segment `simpleAuth` -> capability |
| 40 | `fbCore.simpleAuth.doctrine.policiesManager` | `fbCore.security.doctrine.policiesManager` | Security | library segment `simpleAuth` -> capability |
| 41 | `fbCore.simpleAuth.nette.application` | `fbCore.security.nette.application` | Security | library segment `simpleAuth` -> capability |
| 42 | `fbCore.tools.helpers.database` | `fbCore.persistence.helpers.database` | Persistence | library segment `tools` -> capability |
| 43 | `fbCore.tools.utilities.doctrineDateProvider` | `fbCore.persistence.utilities.doctrineDateProvider` | Persistence | library segment `tools` -> capability |
| 44 | `fbCore.tools.schemas.validator` | `fbCore.values.schemas.validator` | Values | library segment `tools` -> capability |
| 45 | `fbCore.tools.helpers.sentry` | `fbCore.logging.helpers.sentry` | Logging | library segment `tools` -> capability |
| 46 | `fbCore.tools.sentry.handler` | `fbCore.logging.sentry.handler` | Logging | library segment `tools` -> capability |
| 47 | `fbCore.tools.sentry.clientBuilder` | `fbCore.logging.sentry.clientBuilder` | Logging | library segment `tools` -> capability |
| 48 | `fbCore.tools.sentry.client` | `fbCore.logging.sentry.client` | Logging | library segment `tools` -> capability |
| 49 | `fbCore.tools.sentry.hub` | `fbCore.logging.sentry.hub` | Logging | library segment `tools` -> capability |
| 50 | `fbCore.dateTimeFactory.datetime.system` | `fbCore.clock.system` | Clock | library segment -> capability; `datetime` restated the library, role from SystemClock |
| 51 | `fbCore.dateTimeFactory.datetime.frozen` | `fbCore.clock.frozen` | Clock | library segment -> capability; role from FrozenClock |
| 52 | `fbCore.doctrineCrud.entity.mapper` | `fbCore.persistence.entity.mapper` | Persistence | library segment `doctrineCrud` -> capability |
| 53 | `fbCore.doctrineCrud.entity.creator` | `fbCore.persistence.entity.creator` | Persistence | library segment `doctrineCrud` -> capability |
| 54 | `fbCore.doctrineCrud.entity.updater` | `fbCore.persistence.entity.updater` | Persistence | library segment `doctrineCrud` -> capability |
| 55 | `fbCore.doctrineCrud.entity.deleter` | `fbCore.persistence.entity.deleter` | Persistence | library segment `doctrineCrud` -> capability |
| 56 | `fbCore.doctrineCrud.crud` | `fbCore.persistence.crud` | Persistence | library segment `doctrineCrud` -> capability |
| 57 | `fbCore.configuration` | *(unchanged)* | root | unchanged (decided, §3.4) |
| 58 | `fbCore.doctrineTimestampable.driver` | `fbCore.persistence.timestampable.driver` | Persistence | library segment -> capability + concern (matches key persistence.timestampable) |
| 59 | `fbCore.doctrineTimestampable.subscriber` | `fbCore.persistence.timestampable.subscriber` | Persistence | library segment -> capability + concern |
| 60 | `fbCore.doctrineMigrations.subscriber` | `fbCore.persistence.migrations.subscriber` | Persistence | library-named segment -> capability + concern |
| 61 | `fbCore.jsonApi.builder` | `fbCore.api.builder` | Api | library segment `jsonApi` -> capability |
| 62 | `fbCore.jsonApi.middlewares.jsonapi` | `fbCore.api.middleware` | Api | Epic: no stutter (plan §3.4 example) |
| 63 | `fbCore.jsonApi.hydrators.container` | `fbCore.api.hydrators.container` | Api | library segment -> capability |
| 64 | `fbCore.jsonApi.schemas.container` | `fbCore.api.schemas.container` | Api | library segment -> capability |
| 65 | `fbCore.jsonApi.helpers.crudReader` | `fbCore.api.helpers.crudReader` | Api | library segment -> capability (registration stays dead, D1/#552) |
| 66 | `fbCore.phone.libphone.utils` | *(unchanged)* | Phone | unchanged: libphone names the vendor library it wraps, like casbin/sentry |
| 67 | `fbCore.phone.libphone.geoCoder` | *(unchanged)* | Phone | unchanged: libphone names the vendor library it wraps, like casbin/sentry |
| 68 | `fbCore.phone.libphone.shortNumber` | *(unchanged)* | Phone | unchanged: libphone names the vendor library it wraps, like casbin/sentry |
| 69 | `fbCore.phone.libphone.mapper.carrier` | *(unchanged)* | Phone | unchanged: libphone names the vendor library it wraps, like casbin/sentry |
| 70 | `fbCore.phone.libphone.mapper.timezone` | *(unchanged)* | Phone | unchanged: libphone names the vendor library it wraps, like casbin/sentry |
| 71 | `fbCore.phone.phone` | `fbCore.phone.helper` | Phone | stutter; role from PhoneNumberHelper |
| 72 | `fbCore.phone.doctrinePhone.subscriber` | `fbCore.phone.doctrine.subscriber` | Phone | `doctrinePhone` is a denylisted library name; `doctrine` as in security.doctrine.* |
| 73 | `fbCore.webSockets.controllers.factory` | *(unchanged)* | WebSockets | unchanged: already `fbCore.<capability>.<role>` |
| 74 | `fbCore.wsServer.clients.factory` | `fbCore.webSockets.clients.factory` | WebSockets | library segment `wsServer` -> capability |
| 75 | `fbCore.wsServer.clients.driver.memory` | `fbCore.webSockets.clients.driver.memory` | WebSockets | library segment `wsServer` -> capability |
| 76 | `fbCore.wsServer.clients.storage` | `fbCore.webSockets.clients.storage` | WebSockets | library segment `wsServer` -> capability |
| 77 | `fbCore.webSockets.routing.router` | *(unchanged)* | WebSockets | unchanged: already `fbCore.<capability>.<role>` |
| 78 | `fbCore.webSockets.routing.generator` | *(unchanged)* | WebSockets | unchanged: already `fbCore.<capability>.<role>` |
| 79 | `fbCore.wsServer.server.wrapper` | `fbCore.webSockets.server.wrapper` | WebSockets | library segment `wsServer` -> capability |
| 80 | `fbCore.wsServer.server.flashWrapper` | `fbCore.webSockets.server.flashWrapper` | WebSockets | library segment `wsServer` -> capability |
| 81 | `fbCore.wsServer.server.handlers` | `fbCore.webSockets.server.handlers` | WebSockets | library segment `wsServer` -> capability |
| 82 | `fbCore.wsServer.server.loop` | `fbCore.webSockets.server.loop` | WebSockets | library segment `wsServer` -> capability |
| 83 | `fbCore.wsServer.server.configuration` | `fbCore.webSockets.server.configuration` | WebSockets | library segment `wsServer` -> capability |
| 84 | `fbCore.wsServer.server.logger` | `fbCore.webSockets.server.logger` | WebSockets | library segment `wsServer` -> capability |
| 85 | `fbCore.wsServer.server.server` | `fbCore.webSockets.server.runtime` | WebSockets | stutter; role from ServerRuntime (departs from the plan example, see text) |
| 86 | `fbCore.wsServer.wamp.topics.driver.memory` | `fbCore.webSockets.wamp.topics.driver.memory` | WebSockets | library segment `wsServer` -> capability |
| 87 | `fbCore.wsServer.wamp.topics.storage` | `fbCore.webSockets.wamp.topics.storage` | WebSockets | library segment `wsServer` -> capability |
| 88 | `fbCore.webSockets.wamp.application` | *(unchanged)* | WebSockets | unchanged: already `fbCore.<capability>.<role>` |
| 89 | `fbCore.webSockets.wamp.serializer` | *(unchanged)* | WebSockets | unchanged: already `fbCore.<capability>.<role>` |
| 90 | `fbCore.webSockets.wamp.pushRegistry` | *(unchanged)* | WebSockets | unchanged: already `fbCore.<capability>.<role>` |
| 91 | `fbCore.wsServer.wamp.clientsFactory` | `fbCore.webSockets.wamp.clientsFactory` | WebSockets | library segment `wsServer` -> capability |
| 92 | `fbCore.wsServer.wamp.subscribers.onServerStart` | `fbCore.webSockets.wamp.subscribers.onServerStart` | WebSockets | library segment `wsServer` -> capability |
| 93 | `fbCore.httpServer.routing.responseFactory` | `fbCore.http.routing.responseFactory` | Http | library segment `httpServer` -> capability |
| 94 | `fbCore.httpServer.routing.router` | `fbCore.http.routing.router` | Http | library segment `httpServer` -> capability |
| 95 | `fbCore.httpServer.commands.server` | `fbCore.http.commands.server` | Http | library segment `httpServer` -> capability |
| 96 | `fbCore.httpServer.middlewares.cors` | `fbCore.http.middlewares.cors` | Http | library segment `httpServer` -> capability |
| 97 | `fbCore.httpServer.middlewares.staticFiles` | `fbCore.http.middlewares.staticFiles` | Http | library segment `httpServer` -> capability |
| 98 | `fbCore.httpServer.middlewares.router` | `fbCore.http.middlewares.router` | Http | library segment `httpServer` -> capability |
| 99 | `fbCore.httpServer.application.classic` | `fbCore.http.application.classic` | Http | library segment `httpServer` -> capability |
| 100 | `fbCore.httpServer.server.factory` | `fbCore.http.server.factory` | Http | library segment `httpServer` -> capability |
| 101 | `fbCore.httpServer.subscribers.server` | `fbCore.http.subscribers.server` | Http | library segment `httpServer` -> capability |
| 102 | `fbCore.wsServer.commands.wsServer` | `fbCore.webSockets.commands.server` | WebSockets | library word `wsServer` in the role; the command starts the server |
| 103 | `fbCore.wsServer.subscribers.client` | `fbCore.webSockets.subscribers.client` | WebSockets | library segment `wsServer` -> capability |
| 104 | `fbCore.application.eventDispatcher` | `fbCore.eventDispatcher` | root | root fallback; `application` directly under fbCore is banned |
| 105 | `fbCore.simpleAuth.security.user` | `fbCore.security.user` | Security | library segment -> capability; `security.security` would stutter |

```php
<?php declare(strict_types = 1);

/**
 * E4.6 (#558) service-name map, from the E4.1 census (#553): today => target.
 * Total: all 105 service names CoreExtension can register; identity rows are kept so the map is total.
 */
return [
	'fbCore.application.logger.handler.rotatingFile' => 'fbCore.logging.handler.rotatingFile',
	'fbCore.application.logger.handler.stdOut' => 'fbCore.logging.handler.stdOut',
	'fbCore.application.logger.handler.console' => 'fbCore.logging.handler.console',
	'fbCore.application.cache.psr6' => 'fbCore.cache.psr6',
	'fbCore.application.eventLoop.wrapper' => 'fbCore.eventLoop.wrapper',
	'fbCore.application.eventLoop.status' => 'fbCore.eventLoop.status',
	'fbCore.application.subscribers.console' => 'fbCore.logging.subscribers.console',
	'fbCore.application.subscribers.entityDiscriminator' => 'fbCore.persistence.subscribers.entityDiscriminator',
	'fbCore.application.subscribers.eventLoop' => 'fbCore.eventLoop.subscribers.lifeCycle',
	'fbCore.application.ui.templateFactory' => 'fbCore.ui.templateFactory',
	'fbCore.application.ui.routes' => 'fbCore.ui.routes',
	'fbCore.application.document.cache' => 'fbCore.documents.cache',
	'document.factory' => 'fbCore.documents.factory',
	'document.mapping.attributeDriver' => 'fbCore.documents.mapping.attributeDriver',
	'document.mapping.mappingDriver' => 'fbCore.documents.mapping.driverChain',
	'document.mapping.classMetadataFactory' => 'fbCore.documents.mapping.classMetadataFactory',
	'fbCore.exchange.consumer' => 'fbCore.exchange.consumer',
	'fbCore.exchange.publisher' => 'fbCore.exchange.publisher',
	'fbCore.exchange.publisher.async' => 'fbCore.exchange.publisher.async',
	'fbCore.exchange.entityFactory' => 'fbCore.exchange.entityFactory',
	'fbCore.simpleAuth.auth' => 'fbCore.security.auth',
	'fbCore.simpleAuth.token.builder' => 'fbCore.security.token.builder',
	'fbCore.simpleAuth.token.reader' => 'fbCore.security.token.reader',
	'fbCore.simpleAuth.token.validator' => 'fbCore.security.token.validator',
	'fbCore.simpleAuth.security.identityFactory' => 'fbCore.security.identityFactory',
	'fbCore.simpleAuth.security.userStorage' => 'fbCore.security.userStorage',
	'fbCore.simpleAuth.access.annotationChecker' => 'fbCore.security.access.annotationChecker',
	'fbCore.simpleAuth.access.latteChecker' => 'fbCore.security.access.latteChecker',
	'fbCore.simpleAuth.access.linkChecker' => 'fbCore.security.access.linkChecker',
	'fbCore.simpleAuth.casbin.adapter' => 'fbCore.security.casbin.adapter',
	'fbCore.simpleAuth.casbin.subscriber' => 'fbCore.security.casbin.subscriber',
	'fbCore.simpleAuth.casbin.enforcerFactory' => 'fbCore.security.casbin.enforcerFactory',
	'fbCore.simpleAuth.middleware.access' => 'fbCore.security.middleware.access',
	'fbCore.simpleAuth.middleware.user' => 'fbCore.security.middleware.user',
	'fbCore.simpleAuth.doctrine.driver' => 'fbCore.security.doctrine.driver',
	'fbCore.simpleAuth.doctrine.subscriber' => 'fbCore.security.doctrine.subscriber',
	'fbCore.simpleAuth.doctrine.tokensRepository' => 'fbCore.security.doctrine.tokensRepository',
	'fbCore.simpleAuth.doctrine.tokensManager' => 'fbCore.security.doctrine.tokensManager',
	'fbCore.simpleAuth.doctrine.policiesRepository' => 'fbCore.security.doctrine.policiesRepository',
	'fbCore.simpleAuth.doctrine.policiesManager' => 'fbCore.security.doctrine.policiesManager',
	'fbCore.simpleAuth.nette.application' => 'fbCore.security.nette.application',
	'fbCore.tools.helpers.database' => 'fbCore.persistence.helpers.database',
	'fbCore.tools.utilities.doctrineDateProvider' => 'fbCore.persistence.utilities.doctrineDateProvider',
	'fbCore.tools.schemas.validator' => 'fbCore.values.schemas.validator',
	'fbCore.tools.helpers.sentry' => 'fbCore.logging.helpers.sentry',
	'fbCore.tools.sentry.handler' => 'fbCore.logging.sentry.handler',
	'fbCore.tools.sentry.clientBuilder' => 'fbCore.logging.sentry.clientBuilder',
	'fbCore.tools.sentry.client' => 'fbCore.logging.sentry.client',
	'fbCore.tools.sentry.hub' => 'fbCore.logging.sentry.hub',
	'fbCore.dateTimeFactory.datetime.system' => 'fbCore.clock.system',
	'fbCore.dateTimeFactory.datetime.frozen' => 'fbCore.clock.frozen',
	'fbCore.doctrineCrud.entity.mapper' => 'fbCore.persistence.entity.mapper',
	'fbCore.doctrineCrud.entity.creator' => 'fbCore.persistence.entity.creator',
	'fbCore.doctrineCrud.entity.updater' => 'fbCore.persistence.entity.updater',
	'fbCore.doctrineCrud.entity.deleter' => 'fbCore.persistence.entity.deleter',
	'fbCore.doctrineCrud.crud' => 'fbCore.persistence.crud',
	'fbCore.configuration' => 'fbCore.configuration',
	'fbCore.doctrineTimestampable.driver' => 'fbCore.persistence.timestampable.driver',
	'fbCore.doctrineTimestampable.subscriber' => 'fbCore.persistence.timestampable.subscriber',
	'fbCore.doctrineMigrations.subscriber' => 'fbCore.persistence.migrations.subscriber',
	'fbCore.jsonApi.builder' => 'fbCore.api.builder',
	'fbCore.jsonApi.middlewares.jsonapi' => 'fbCore.api.middleware',
	'fbCore.jsonApi.hydrators.container' => 'fbCore.api.hydrators.container',
	'fbCore.jsonApi.schemas.container' => 'fbCore.api.schemas.container',
	'fbCore.jsonApi.helpers.crudReader' => 'fbCore.api.helpers.crudReader',
	'fbCore.phone.libphone.utils' => 'fbCore.phone.libphone.utils',
	'fbCore.phone.libphone.geoCoder' => 'fbCore.phone.libphone.geoCoder',
	'fbCore.phone.libphone.shortNumber' => 'fbCore.phone.libphone.shortNumber',
	'fbCore.phone.libphone.mapper.carrier' => 'fbCore.phone.libphone.mapper.carrier',
	'fbCore.phone.libphone.mapper.timezone' => 'fbCore.phone.libphone.mapper.timezone',
	'fbCore.phone.phone' => 'fbCore.phone.helper',
	'fbCore.phone.doctrinePhone.subscriber' => 'fbCore.phone.doctrine.subscriber',
	'fbCore.webSockets.controllers.factory' => 'fbCore.webSockets.controllers.factory',
	'fbCore.wsServer.clients.factory' => 'fbCore.webSockets.clients.factory',
	'fbCore.wsServer.clients.driver.memory' => 'fbCore.webSockets.clients.driver.memory',
	'fbCore.wsServer.clients.storage' => 'fbCore.webSockets.clients.storage',
	'fbCore.webSockets.routing.router' => 'fbCore.webSockets.routing.router',
	'fbCore.webSockets.routing.generator' => 'fbCore.webSockets.routing.generator',
	'fbCore.wsServer.server.wrapper' => 'fbCore.webSockets.server.wrapper',
	'fbCore.wsServer.server.flashWrapper' => 'fbCore.webSockets.server.flashWrapper',
	'fbCore.wsServer.server.handlers' => 'fbCore.webSockets.server.handlers',
	'fbCore.wsServer.server.loop' => 'fbCore.webSockets.server.loop',
	'fbCore.wsServer.server.configuration' => 'fbCore.webSockets.server.configuration',
	'fbCore.wsServer.server.logger' => 'fbCore.webSockets.server.logger',
	'fbCore.wsServer.server.server' => 'fbCore.webSockets.server.runtime',
	'fbCore.wsServer.wamp.topics.driver.memory' => 'fbCore.webSockets.wamp.topics.driver.memory',
	'fbCore.wsServer.wamp.topics.storage' => 'fbCore.webSockets.wamp.topics.storage',
	'fbCore.webSockets.wamp.application' => 'fbCore.webSockets.wamp.application',
	'fbCore.webSockets.wamp.serializer' => 'fbCore.webSockets.wamp.serializer',
	'fbCore.webSockets.wamp.pushRegistry' => 'fbCore.webSockets.wamp.pushRegistry',
	'fbCore.wsServer.wamp.clientsFactory' => 'fbCore.webSockets.wamp.clientsFactory',
	'fbCore.wsServer.wamp.subscribers.onServerStart' => 'fbCore.webSockets.wamp.subscribers.onServerStart',
	'fbCore.httpServer.routing.responseFactory' => 'fbCore.http.routing.responseFactory',
	'fbCore.httpServer.routing.router' => 'fbCore.http.routing.router',
	'fbCore.httpServer.commands.server' => 'fbCore.http.commands.server',
	'fbCore.httpServer.middlewares.cors' => 'fbCore.http.middlewares.cors',
	'fbCore.httpServer.middlewares.staticFiles' => 'fbCore.http.middlewares.staticFiles',
	'fbCore.httpServer.middlewares.router' => 'fbCore.http.middlewares.router',
	'fbCore.httpServer.application.classic' => 'fbCore.http.application.classic',
	'fbCore.httpServer.server.factory' => 'fbCore.http.server.factory',
	'fbCore.httpServer.subscribers.server' => 'fbCore.http.subscribers.server',
	'fbCore.wsServer.commands.wsServer' => 'fbCore.webSockets.commands.server',
	'fbCore.wsServer.subscribers.client' => 'fbCore.webSockets.subscribers.client',
	'fbCore.application.eventDispatcher' => 'fbCore.eventDispatcher',
	'fbCore.simpleAuth.security.user' => 'fbCore.security.user',
];
```

**Not service names, but renamed with them (E4.6, together, D3 #565):** the two schema defaults and
the two literal comparisons `'@wsServer.clients.driver.memory'` (`CoreExtension.php:255`, `:901`)
and `'@wsServer.wamp.topics.driver.memory'` (`:259`, `:976`) become
`'@fbCore.webSockets.clients.driver.memory'` and `'@fbCore.webSockets.wamp.topics.driver.memory'`,
the real names of the two memory drivers. They are sentinels today and stay sentinels: default and
comparison are rewritten in the same commit, and D3 is not fixed. The string list in the
`beforeCompile()` CRUD-removal block (`:1128-1132`) follows the map.

## 3. Configuration key map

**Rule (§3.4):** only the top-level segment changes, leaf keys stay as they are.

```php
return [
	'application.logging' => 'logging',
	'tools.sentry' => 'logging.sentry',
	'application.documents' => 'documents',
	'simpleAuth' => 'security',
	'dateTimeFactory' => 'clock',
	'doctrineTimestampable' => 'persistence.timestampable',
	'jsonApi' => 'api',
	'webSockets' => 'webSockets',
	'wsServer.access' => 'webSockets.access',
	'httpServer' => 'http',
];
```

89 schema nodes (55 leaves, 34 sections, 9 of them top-level). 86 map to a new path and 3 dissolve; every target path is unique. The three that dissolve are the pure containers `application`, `tools` and
`wsServer`. Three new containers appear: `logging`, `documents` and `persistence`, plus the new
section `webSockets.access`.

**The `wsServer.access` → `webSockets.access` merge has no leaf-name clash.** The existing
`webSockets` structure has exactly five children: `storage`, `server`, `routes`, `mapping`,
`loop`. None is named `access`, so the merged section adds `webSockets.access.keys` and
`webSockets.access.origins` beside them. `routes` and `mapping` are free-form `Expect::array()`
values, so nothing a user puts in them is a schema key. The other two merges have no clash either:
`application.logging` has no `sentry` child, and `persistence` is new.

**One subtree per child, after E4.5.** Today Logging reads two top-level sections
(`application.logging`, `tools.sentry`) and so does WebSockets (`webSockets`, `wsServer.access`).
After the rename each child reads exactly one subtree: `logging`, `documents`, `security`,
`clock`, `persistence`, `api`, `webSockets`, `http`. Exchange, Values and Phone read no
configuration. The root reads `security` and `persistence.timestampable` for `Configuration`.
During E4.3/E4.4 (names frozen, §3.7) Logging and WebSockets therefore need the composite's whole
configuration object, or both subtrees handed in.

| Key path today | Key path target | Kind | Type | Default |
|---|---|---|---|---|
| `application` | *(dissolves: container only, its children move)* | section |  |  |
| `application.logging` | `logging` | section |  |  |
| `application.logging.rotatingFile` | `logging.rotatingFile` | section |  |  |
| `application.logging.rotatingFile.enabled` | `logging.rotatingFile.enabled` | leaf | `bool` | `true` |
| `application.logging.rotatingFile.level` | `logging.rotatingFile.level` | leaf | `int` | `Monolog\Level::Info` |
| `application.logging.rotatingFile.filename` | `logging.rotatingFile.filename` | leaf | `string` | `"app.log"` |
| `application.logging.stdOut` | `logging.stdOut` | section |  |  |
| `application.logging.stdOut.enabled` | `logging.stdOut.enabled` | leaf | `bool` | `false` |
| `application.logging.stdOut.level` | `logging.stdOut.level` | leaf | `int` | `Monolog\Level::Info` |
| `application.logging.console` | `logging.console` | section |  |  |
| `application.logging.console.enabled` | `logging.console.enabled` | leaf | `bool` | `false` |
| `application.logging.console.level` | `logging.console.level` | leaf | `int` | `Monolog\Level::Info` |
| `application.documents` | `documents` | section |  |  |
| `application.documents.mapping` | `documents.mapping` | leaf | `array<string, string>` | `[]` |
| `application.documents.excludePaths` | `documents.excludePaths` | leaf | `array<string, string>` | `[]` |
| `simpleAuth` | `security` | section |  |  |
| `simpleAuth.token` | `security.token` | section |  |  |
| `simpleAuth.token.issuer` | `security.token.issuer` | leaf | `string` | `null` |
| `simpleAuth.token.signature` | `security.token.signature` | leaf | `string` | `""` |
| `simpleAuth.enable` | `security.enable` | section |  |  |
| `simpleAuth.enable.middleware` | `security.enable.middleware` | leaf | `bool` | `false` |
| `simpleAuth.enable.doctrine` | `security.enable.doctrine` | section |  |  |
| `simpleAuth.enable.doctrine.mapping` | `security.enable.doctrine.mapping` | leaf | `bool` | `false` |
| `simpleAuth.enable.doctrine.models` | `security.enable.doctrine.models` | leaf | `bool` | `false` |
| `simpleAuth.enable.casbin` | `security.enable.casbin` | section |  |  |
| `simpleAuth.enable.casbin.database` | `security.enable.casbin.database` | leaf | `bool` | `false` |
| `simpleAuth.enable.nette` | `security.enable.nette` | section |  |  |
| `simpleAuth.enable.nette.application` | `security.enable.nette.application` | leaf | `bool` | `false` |
| `simpleAuth.application` | `security.application` | section |  |  |
| `simpleAuth.application.signInUrl` | `security.application.signInUrl` | leaf | `string` | `null` |
| `simpleAuth.application.homeUrl` | `security.application.homeUrl` | leaf | `string` | `"/"` |
| `simpleAuth.services` | `security.services` | section |  |  |
| `simpleAuth.services.identity` | `security.services.identity` | leaf | `bool` | `false` |
| `simpleAuth.casbin` | `security.casbin` | section |  |  |
| `simpleAuth.casbin.model` | `security.casbin.model` | leaf | `string` | `__DIR__`-relative `resources/model.conf` |
| `simpleAuth.casbin.policy` | `security.casbin.policy` | leaf | `string` | `null` |
| `tools` | *(dissolves: container only, its children move)* | section |  |  |
| `tools.sentry` | `logging.sentry` | section |  |  |
| `tools.sentry.dsn` | `logging.sentry.dsn` | leaf | `null\|string` | `null` |
| `tools.sentry.level` | `logging.sentry.level` | leaf | `int` | `Monolog\Level::Warning` |
| `dateTimeFactory` | `clock` | section |  |  |
| `dateTimeFactory.timeZone` | `clock.timeZone` | leaf | `string` | `"UTC"` |
| `dateTimeFactory.system` | `clock.system` | leaf | `bool` | `true` |
| `dateTimeFactory.frozen` | `clock.frozen` | leaf | `anyOf(float\|mixed)` | `null` |
| `doctrineTimestampable` | `persistence.timestampable` | section |  |  |
| `doctrineTimestampable.lazyAssociation` | `persistence.timestampable.lazyAssociation` | leaf | `bool` | `false` |
| `doctrineTimestampable.autoMapField` | `persistence.timestampable.autoMapField` | leaf | `bool` | `true` |
| `doctrineTimestampable.dbFieldType` | `persistence.timestampable.dbFieldType` | leaf | `string` | `"datetime_immutable"` |
| `jsonApi` | `api` | section |  |  |
| `jsonApi.meta` | `api.meta` | section |  |  |
| `jsonApi.meta.author` | `api.meta.author` | leaf | `anyOf(string\|array)` | `"FastyBird team"` |
| `jsonApi.meta.copyright` | `api.meta.copyright` | leaf | `null\|string` | `null` |
| `webSockets` | `webSockets` | section |  |  |
| `webSockets.storage` | `webSockets.storage` | section |  |  |
| `webSockets.storage.clients` | `webSockets.storage.clients` | section |  |  |
| `webSockets.storage.clients.driver` | `webSockets.storage.clients.driver` | leaf | `string` | `"@wsServer.clients.driver.memory"` |
| `webSockets.storage.clients.ttl` | `webSockets.storage.clients.ttl` | leaf | `int` | `0` |
| `webSockets.storage.topics` | `webSockets.storage.topics` | section |  |  |
| `webSockets.storage.topics.driver` | `webSockets.storage.topics.driver` | leaf | `string` | `"@wsServer.wamp.topics.driver.memory"` |
| `webSockets.storage.topics.ttl` | `webSockets.storage.topics.ttl` | leaf | `int` | `0` |
| `webSockets.server` | `webSockets.server` | section |  |  |
| `webSockets.server.httpHost` | `webSockets.server.httpHost` | leaf | `string` | `"localhost"` |
| `webSockets.server.port` | `webSockets.server.port` | leaf | `int` | `8080` |
| `webSockets.server.address` | `webSockets.server.address` | leaf | `string` | `"0.0.0.0"` |
| `webSockets.server.secured` | `webSockets.server.secured` | section |  |  |
| `webSockets.server.secured.enable` | `webSockets.server.secured.enable` | leaf | `bool` | `false` |
| `webSockets.server.secured.sslSettings` | `webSockets.server.secured.sslSettings` | leaf | `array` | `[]` |
| `webSockets.routes` | `webSockets.routes` | leaf | `array` | `[]` |
| `webSockets.mapping` | `webSockets.mapping` | leaf | `array` | `[]` |
| `webSockets.loop` | `webSockets.loop` | leaf | `anyOf(string\|Nette\DI\Definitions\Statement\|null)` | `null` |
| `httpServer` | `http` | section |  |  |
| `httpServer.static` | `http.static` | section |  |  |
| `httpServer.static.publicRoot` | `http.static.publicRoot` | leaf | `null\|string` | `null` |
| `httpServer.static.enabled` | `http.static.enabled` | leaf | `bool` | `false` |
| `httpServer.server` | `http.server` | section |  |  |
| `httpServer.server.address` | `http.server.address` | leaf | `string` | `"127.0.0.1"` |
| `httpServer.server.port` | `http.server.port` | leaf | `int` | `8000` |
| `httpServer.server.certificate` | `http.server.certificate` | leaf | `null\|string` | `null` |
| `httpServer.cors` | `http.cors` | section |  |  |
| `httpServer.cors.enabled` | `http.cors.enabled` | leaf | `bool` | `false` |
| `httpServer.cors.allow` | `http.cors.allow` | section |  |  |
| `httpServer.cors.allow.origin` | `http.cors.allow.origin` | leaf | `string` | `"*"` |
| `httpServer.cors.allow.methods` | `http.cors.allow.methods` | leaf | `array<string>` | `["GET", "POST", "PATCH", "DELETE", "OPTIONS"]` |
| `httpServer.cors.allow.credentials` | `http.cors.allow.credentials` | leaf | `bool` | `true` |
| `httpServer.cors.allow.headers` | `http.cors.allow.headers` | leaf | `array<string>` | `["Content-Type", "Authorization", "X-Requested-With"]` |
| `wsServer` | *(dissolves: container only, its children move)* | section |  |  |
| `wsServer.access` | `webSockets.access` | section |  |  |
| `wsServer.access.keys` | `webSockets.access.keys` | leaf | `string` | `null` |
| `wsServer.access.origins` | `webSockets.access.origins` | leaf | `string` | `null` |

## 4. Tag map and translation domain

**Rule (§3.4):** `fastybird.core.<capability>.<role>`. The constants move to the owning extension.

| Tag today | Tag target | Constant today | Constant after E4.7 |
|---|---|---|---|
| `fastybird.application.attribute.driver` | `fastybird.core.documents.attributeDriver` | `CoreExtension::DRIVER_TAG` | `DocumentsExtension::DRIVER_TAG` |
| `consumer_state` | `fastybird.core.exchange.consumerState` | `CoreExtension::CONSUMER_STATE` | `ExchangeExtension::CONSUMER_STATE` |
| `consumer_routing_key` | `fastybird.core.exchange.consumerRoutingKey` | `CoreExtension::CONSUMER_ROUTING_KEY` | `ExchangeExtension::CONSUMER_ROUTING_KEY` |
| `ipub.websockets.routes` | `fastybird.core.webSockets.routes` | `CoreExtension::TAG_WEBSOCKETS_ROUTES` (the Devices producer is a literal) | `WebSocketsExtension::ROUTES_TAG` |
| `ipub.websockets.controller` | `fastybird.core.webSockets.controller` | none; two literals (E4.2 adds `CoreExtension::TAG_WEBSOCKETS_CONTROLLER`) | `WebSocketsExtension::CONTROLLER_TAG` |

`nette.inject` is Nette's and stays. No tracked NEON file sets any of the five tags (every
`tags:` section in all tracked `.neon` was parsed).

Every producer and consumer, parsed:

- **`fastybird.application.attribute.driver`** → `fastybird.core.documents.attributeDriver`. Producers (1): `Core/Core/src/DI/CoreExtension.php:430`. Consumers (21): `Addon/VirtualThermostat/src/DI/VirtualThermostatExtension.php:202`, `Automator/DateTime/src/DI/DateTimeExtension.php:99`, `Automator/DevicesModule/src/DI/DevicesModuleExtension.php:148`, `Bridge/DevicesModuleUiModule/src/DI/DevicesModuleUiModuleExtension.php:201`, `Bridge/ShellyConnectorHomeKitConnector/src/DI/ShellyConnectorHomeKitConnectorExtension.php:264`, `Bridge/VieraConnectorHomeKitConnector/src/DI/VieraConnectorHomeKitConnectorExtension.php:225`, `Bridge/VirtualThermostatAddonHomeKitConnector/src/DI/VirtualThermostatAddonHomeKitConnectorExtension.php:190`, `Connector/FbMqtt/src/DI/FbMqttExtension.php:320`, `Connector/HomeKit/src/DI/HomeKitExtension.php:461`, `Connector/Modbus/src/DI/ModbusExtension.php:301`, `Connector/NsPanel/src/DI/NsPanelExtension.php:1080`, `Connector/Shelly/src/DI/ShellyExtension.php:361`, `Connector/Sonoff/src/DI/SonoffExtension.php:371`, `Connector/Tuya/src/DI/TuyaExtension.php:369`, `Connector/Viera/src/DI/VieraExtension.php:341`, `Connector/Virtual/src/DI/VirtualExtension.php:280`, `Connector/Zigbee2Mqtt/src/DI/Zigbee2MqttExtension.php:406`, `Module/Accounts/src/DI/AccountsExtension.php:260`, `Module/Devices/src/DI/DevicesExtension.php:995`, `Module/Triggers/src/DI/TriggersExtension.php:262`, `Module/Ui/src/DI/UiExtension.php:533`.
- **`consumer_state`** → `fastybird.core.exchange.consumerState`. Producers (15): `Bridge/DevicesModuleUiModule/src/DI/DevicesModuleUiModuleExtension.php:171`, `Connector/FbMqtt/src/DI/FbMqttExtension.php:98`, `Connector/HomeKit/src/DI/HomeKitExtension.php:104`, `Connector/Modbus/src/DI/ModbusExtension.php:99`, `Connector/NsPanel/src/DI/NsPanelExtension.php:106`, `Connector/Shelly/src/DI/ShellyExtension.php:99`, `Connector/Sonoff/src/DI/SonoffExtension.php:99`, `Connector/Tuya/src/DI/TuyaExtension.php:99`, `Connector/Viera/src/DI/VieraExtension.php:99`, `Connector/Virtual/src/DI/VirtualExtension.php:99`, `Connector/Zigbee2Mqtt/src/DI/Zigbee2MqttExtension.php:99`, `Module/Devices/src/DI/DevicesExtension.php:913`, `Module/Devices/src/DI/DevicesExtension.php:923`, `Module/Devices/src/DI/DevicesExtension.php:937`, `Module/Ui/src/DI/UiExtension.php:503`. Consumers (1): `Core/Core/src/DI/CoreExtension.php:1207`.
- **`consumer_routing_key`** → `fastybird.core.exchange.consumerRoutingKey`. Producers (0): none. Consumers (1): `Core/Core/src/DI/CoreExtension.php:1209`.
- **`ipub.websockets.routes`** → `fastybird.core.webSockets.routes`. Producers (1): `Module/Devices/src/DI/DevicesExtension.php:178`. Consumers (1): `Core/Core/src/DI/CoreExtension.php:1408`.
- **`ipub.websockets.controller`** → `fastybird.core.webSockets.controller`. Producers (1): `Core/Core/src/DI/CoreExtension.php:1444`. Consumers (1): `Core/Core/src/WebSockets/Controllers/ControllerFactory.php:53`.

```php
<?php declare(strict_types = 1);

/**
 * E4.7 (#559) DI tag map, from the E4.1 census (#553): today => target.
 */
return [
	'fastybird.application.attribute.driver' => 'fastybird.core.documents.attributeDriver',
	'consumer_state' => 'fastybird.core.exchange.consumerState',
	'consumer_routing_key' => 'fastybird.core.exchange.consumerRoutingKey',
	'ipub.websockets.routes' => 'fastybird.core.webSockets.routes',
	'ipub.websockets.controller' => 'fastybird.core.webSockets.controller',
];
```

**Translation domain `jsonApi` → `api`** (E4.8, #560):

| What | Measured |
|---|---|
| Lookups `'//jsonApi.…'` in production code | **22** in 5 files: `Api/Hydrators/Hydrator.php` 14, `Api/Hydrators/Fields/ArrayField.php` 2, `BackedEnumField.php` 2, `BooleanField.php` 2, `NumberField.php` 2; 11 distinct keys |
| In tests | `HydratorFieldsTest.php:154-155`: 2 `assertStringNotContainsString('//jsonApi.hydrator', …)` |
| Catalogue | 1 file, `src/FastyBird/Core/Core/src/Api/Translations/jsonApi.en_US.neon` → `api.en_US.neon`; **10** leaf keys (5 messages × `heading`/`message`) |
| Directory | unchanged; `contributteTranslation.dirs` entries stay as they are |
| Keys looked up but missing from the catalogue | **4 lookups, 2 keys × 2**: `hydrator.resourceInvalid.{heading,message}` (`Hydrator.php:153-154`) and `hydrator.identifierInvalid.{heading,message}` (`Hydrator.php:203-204`). The catalogue has `invalidResource.*` and `invalidIdentifier.*`, which nothing looks up. F3 |

## 5. Order analysis (§3.5)

### 5.1 What makes definition order observable

`ContainerBuilder` keeps definitions in insertion order. Order becomes behaviour only in these
places:

- **compile-time collections:** a later extension's `findByType()`/`findByTag()` loop turns the
  order into a sequence of setups or an array argument (nettrine `EventPass`, contributte
  dispatcher and console, Core's own loops, autowired `array` parameters);
- **setups `fbCore` adds to shared services** in `beforeCompile()`, in call order;
- **runtime collections:** the generated container's `$wiring` (per type) and `$tags` (per tag)
  lists, in definition order, which back `Container::findByType()`/`findByTag()`;
- **`loadConfiguration()`-time lookups** whose result depends on what is already registered.

**Measured in all 38 compiled containers: `fbCore`'s `loadConfiguration()` definitions form one
contiguous block** in the global order. The neighbours are always `orisaiObjectMapper.processor`
before and the next extension's first definition after. Its two `beforeCompile()` definitions sit
where `fbCore`'s `beforeCompile()` runs. Since the composite still runs as one extension at the
same slot, **no Core-versus-non-Core relative order can change**. Only the relative order of two
Core definitions, or of two Core `beforeCompile()` units, is at stake.

### 5.2 Order-sensitive collections, their Core members, current relative order

Read from the compiled containers (production `prod:dev`/`prod:sentry` for the richest
membership, and the test containers). Arrows are the current order.

| Collection | Built by | Core members, current order | Notes |
|---|---|---|---|
| **Doctrine listeners** (`nettrineOrm.eventManager` setups) | nettrine `EventPass::beforePassCompile()`: `findByType(Doctrine\Common\EventSubscriber)` → `addEventListener(events, 'name')` | `entityDiscriminator` (loadClassMetadata) → `casbin.subscriber` (postPersist/postUpdate/postRemove) → `doctrine.subscriber` = Security `User` (loadClassMetadata, onFlush) → `doctrineTimestampable.subscriber` (loadClassMetadata, onFlush) → `doctrineMigrations.subscriber` (postGenerateSchema) → `phone.doctrinePhone.subscriber` (loadClassMetadata); then 30 module subscribers | 6 Core members; production dispatch order per event in D2 |
| **Entity-manager setups** | `fbCore` `beforeCompile()` blocks 10, 11a, 11b | `DATE_FORMAT` → `addEventSubscriber(timestampable)` → `addEventSubscriber(phone)` | the only service two Core owners touch (Persistence, then Phone) |
| **Symfony subscribers** (`contributteEvents.dispatcher` `addListener` setups; production only) | contributte `EventDispatcherExtension`: `findByType(EventSubscriberInterface)` | `logger.handler.console` (`console.command` 255, `console.terminate` −255) → `subscribers.console` (`console.command` 0) → `subscribers.eventLoop` (3 EventLoop events) → `simpleAuth.nette.application` (`PresenterRequest`) → `httpServer.subscribers.server` (3 HttpServer events) → `wsServer.subscribers.client` (`ClientConnected`, `IncomingMessage`); then 24 module listeners | 6 Core services, 12 Core listeners. Their events are pairwise disjoint except `console.command`, where both listeners are Logging's and have different priorities |
| **Console commands** (`contributteConsole.application` `add()`, `lazy: false`) | contributte `ConsoleExtension`: `findByType(Command)` | `httpServer.commands.server` → `wsServer.commands.wsServer`; then 62 others | |
| **Monolog handler stack** (`contributteMonolog.logger.default` `pushHandler`) | `fbCore` `beforeCompile()` blocks 3 and 9 | `rotatingFile` → `stdOut` → `sentry.handler` | all Logging; block 9 only with a Sentry DSN (`prod:sentry`) |
| **`ServerRouter` middlewares** | NEON `decorator:` (DecoratorExtension, which runs before `fbCore`) | `@fbCore.jsonApi.middlewares.jsonapi` → `@fbAccountsModule.middlewares.urlFormat` | order comes from NEON, not from definitions. Security's `middleware.access`/`.user` are single constructor arguments of each module's `ApiRoutes`, not a collection |
| **Core's own loops** (exchange proxies, `jsonApi` schema/hydrator containers, WAMP router tag, controller tag, push registry, WebSockets event bridges) | `fbCore` `beforeCompile()` blocks 7, 12, 13 | **no Core members.** Members are module services, e.g. 119 schemas and 113 hydrators in production | order is module definition order, which is unaffected |
| **Runtime lookups** | `ControllerFactory.php:53` `findByTag('ipub.websockets.controller')`; every other runtime lookup in `src/` is a single-service `getByType()` | no Core members | |
| **Runtime wiring lists with ≥2 Core members** | generated `$wiring` | `Doctrine\Common\EventSubscriber` (6, as above); `EventSubscriberInterface` (6, as above); `Command` (2); `Psr\Http\Server\MiddlewareInterface`: `middleware.access` → `middleware.user` → `jsonApi.middlewares.jsonapi`; `Monolog\Handler\HandlerInterface`: `rotatingFile` → `stdOut` → `console` (→ `sentry.handler`); and generic types | all compared in §5.4 |
| **Module `loadConfiguration()`-time lookups of Core services** | `DevicesExtension.php:926-927`, `UiExtension.php:492-493`, `DevicesModuleUiModuleExtension.php:160-161`: `findByType(LinkGenerator)`, `findByType(Topics\IStorage)` | a presence test, not an order | production: `fbCore` runs first, so both are found and the `socketsBridge` consumers exist. Test containers: F1 |

**Dependencies inside `fbCore`'s own `loadConfiguration()`** that fix the relative position of
children, all measured:

- The WebSockets block's `getByType(React\EventLoop\LoopInterface)` finds root group A's
  `EventLoop\Wrapper` (a `LoopInterface`). That is why `wsServer.server.loop` never exists and
  `ServerRuntime` and the `WsServer` command get `@fbCore.application.eventLoop.wrapper`.
  **Root group A must run before WebSockets**, or a new `wsServer.server.loop` definition
  appears.
- `findByType(Psr\Log\LoggerInterface)` finds `contributteMonolog`, which Core's
  `config/common.neon` registers before `fbCore`, so `wsServer.server.logger` never exists.
  Independent of child order.
- `getByType(Clients\ClientProvider)`: nothing provides one before `fbCore` in any container. So
  `wsServer.clients.factory` is registered at line 894 and removed again at line 999 in every
  container. Independent of child order, as long as the WebSockets block stays one unit.
- `findByType(Exchange\Factory)` for the `WsServer` command (D4): Core registers no
  `Exchange\Factory`, so the result is the same wherever the WebSockets child runs inside `fbCore`.

**Dependencies inside `fbCore`'s `beforeCompile()`:** block 1's fallback dispatcher must exist
before blocks 8, 13 and 14 read a dispatcher. Block 7's `setAutowired(false)` is read by no later
Core block. The entity manager is the only service two owners touch (§5.2). The Monolog logger is
touched only by Logging.

### 5.3 Composite invocation order

`loadConfiguration()`: the composite first gives every child the compiler, then calls:

| # | Call | Registers (today's names) |
|---|---|---|
| 1 | `LoggingExtension::loadConfiguration()` | 3 handlers, `subscribers.console`, `tools.helpers.sentry`, 4 `tools.sentry.*` |
| 2 | root group A | `cache.psr6`, `eventLoop.wrapper`, `eventLoop.status` |
| 3 | `PersistenceExtension::loadConfiguration()` | `subscribers.entityDiscriminator`, `tools.helpers.database`, `tools.utilities.doctrineDateProvider`, 5 `doctrineCrud.*` |
| 4 | root group B | `subscribers.eventLoop`, `ui.templateFactory`, `ui.routes` |
| 5 | `DocumentsExtension::loadConfiguration()` | `application.document.cache`, 4 `document.*` |
| 6 | `ExchangeExtension::loadConfiguration()` | 4 `exchange.*` (incl. `RoutingDocumentFactory`) |
| 7 | `SecurityExtension::loadConfiguration()` | 20 `simpleAuth.*` (21 call sites) |
| 8 | `ValuesExtension::loadConfiguration()` | `tools.schemas.validator` |
| 9 | `ClockExtension::loadConfiguration()` | 2 `dateTimeFactory.datetime.*` |
| 10 | root group C | `configuration` |
| 11 | **`PersistenceExtension` named hook**, suggested `loadTimestampable()` | `doctrineTimestampable.driver`, `doctrineTimestampable.subscriber`, `doctrineMigrations.subscriber` |
| 12 | `ApiExtension::loadConfiguration()` | 5 `jsonApi.*` |
| 13 | `PhoneExtension::loadConfiguration()` | 7 `phone.*` |
| 14 | `WebSocketsExtension::loadConfiguration()` | 20 `webSockets.*`/`wsServer.*` incl. `LinkGenerator`, and the `ClientProvider` probe and removal |
| 15 | `HttpExtension::loadConfiguration()` | 9 `httpServer.*` |
| 16 | **`WebSocketsExtension` named hook**, suggested `loadServerProcess()` | `wsServer.commands.wsServer`, `wsServer.subscribers.client` |

`beforeCompile()`: root block 1 → `PersistenceExtension::beforeCompile()` (blocks 2, 10, 11a) →
`LoggingExtension::beforeCompile()` (blocks 3, 9) → root blocks 4–6 →
`ExchangeExtension::beforeCompile()` (7) → `SecurityExtension::beforeCompile()` (8) →
`PhoneExtension::beforeCompile()` (11b) → `ApiExtension::beforeCompile()` (12) →
`WebSocketsExtension::beforeCompile()` (13, then 14). Clock, Values, Documents and Http have none.

`afterCompile()`: `PhoneExtension::afterCompile()`.

**Why the two hooks.** *Persistence:* its Doctrine subscribers sit on both sides of Security's.
`EntityDiscriminator` precedes Security `User` on `loadClassMetadata`, while `Timestampable`
follows `User` on `loadClassMetadata` and on `onFlush`. One call cannot keep both. The hook
carries the `DoctrineTimestampable` and `DoctrineMigrations` blocks. *WebSockets:* the `WsServer`
command follows the Http command in the console collection, and the `Client` subscriber follows
Http's `Server` subscriber in the Symfony collection. The hook carries the former `WS SERVER`
block. Both method names are suggestions: §14 lets E4.3/E4.4 name the hook, stated in the PR.

**Why no `beforeCompile()` hook.** Persistence's blocks 10 and 11a now run before blocks 3–9, and
Logging's block 9 before blocks 4–8. Neither changes an observable order. The only shared services
are the entity manager (Persistence still before Phone) and the Monolog logger (Logging only). No
moved unit reads state a skipped unit writes: `getByType(EntityManagerInterface)`,
`getByType(Monolog\Logger)` and `getByType(Sentry\Monolog\Handler)` are untouched by blocks 4–8.

### 5.4 Result: no relative-order change

`analyze.py` models the composite as a permutation of the 103 `loadConfiguration()` names. In
each of the 38 compiled containers, it takes every pair of Core definitions whose relative order
the permutation flips (113–429 pairs per container, mostly unobservable) and looks for it in every
collection the container actually has: setup sequences (with bare service-name strings as nettrine
and contributte use them), array arguments after `complete()`, every `$wiring` list and every
`$tags` list.

**Result: 0 collections contain a flipped pair, in all 38 containers.**

**Known-positive control:** the same check with the hooks removed (`CONTROL=nohooks`) reports
exactly the collections the hooks exist for: `nettrineOrm.eventManager` setups and
`wiring[Doctrine\Common\EventSubscriber]` (timestampable/migrations against Security's
subscribers), `contributteConsole.application` setups and `wiring[Command]`, and
`contributteEvents.dispatcher` setups and `wiring[EventSubscriberInterface]`. So the check can see
a flip.

### 5.5 The global definition order (resolved: 13 approved inert moves)

The snapshot is specified to record the global definition order (§3.3), and E4.3/E4.4 must show
an **empty** diff. **The composite, as §3.5 specifies it (one call at the first block, at most one
named hook per child), cannot reproduce the global order.** In `loadConfiguration()` source order,
the owners form 20 runs: Logging 3, Persistence 4, WebSockets 2, root 3, every other capability 1.
Root is the composite itself and may split freely. Exact reproduction therefore needs **6 extra
hooks: Logging 2, Persistence 3, WebSockets 1.**

Under the proposed composite (2 hooks), **13 of the 103 definitions change global position**. All
13 are in none of the collections of §5.2 (§5.4 proves it):

| Definition | Position today | Position after |
|---|---|---|
| `fbCore.application.subscribers.console` | 6 | 3 |
| `fbCore.tools.helpers.database` | 41 | 13 |
| `fbCore.tools.utilities.doctrineDateProvider` | 42 | 14 |
| `fbCore.tools.helpers.sentry` | 44 | 4 |
| `fbCore.tools.sentry.handler` | 45 | 5 |
| `fbCore.tools.sentry.clientBuilder` | 46 | 6 |
| `fbCore.tools.sentry.client` | 47 | 7 |
| `fbCore.tools.sentry.hub` | 48 | 8 |
| `fbCore.doctrineCrud.entity.mapper` | 51 | 15 |
| `fbCore.doctrineCrud.entity.creator` | 52 | 16 |
| `fbCore.doctrineCrud.entity.updater` | 53 | 17 |
| `fbCore.doctrineCrud.entity.deleter` | 54 | 18 |
| `fbCore.doctrineCrud.crud` | 55 | 19 |

The best placement allowed by the rule adds a third hook (Logging, for the 5 Sentry definitions)
and still leaves **4** moves. `variants.py` enumerates every placement, and none reaches 0.

**Decision (orchestrator, 2026-09-27, through the plan's §3.5 route).** §3.5 allows a
relative-order change when it comes with a proof that it is inert, and §5.4 is that proof for
these 13 moves. The snapshot (#554) therefore works as follows:

1. **Hard criteria, compared strictly:**
   - per-definition content: name, type, creator, arguments (after `complete()`), setups **in
     order**, tags, autowiring, `lazy`, `implement`. This covers every compile-time collection,
     because each ends as a setup sequence or an array argument;
   - the generated per-type `$wiring` lists and per-tag `$tags` lists, **in order**. This covers
     every runtime `findByType()`/`findByTag()`;
   - the generated `initialize()` body;
   - the extension order.

   #554's tool records and compares all of these.
2. **The raw global definition order stays strict, with one exception.** `--allow-moves <file>`
   names the definitions that may change position. **For E4.3 and E4.4 that list is exactly the 13
   definitions in the table above**, under the composite of §5.3 with its two named hooks
   (`loadTimestampable()`, `loadServerProcess()`). A move of any other definition fails.
3. **E4.5, E4.6 and E4.7 keep the global order identical** under their maps. Those PRs change no
   position.

Measured: under the §5.3 composite, all hard criteria in item 1 give an empty diff in all 38
containers (§5.4). The only raw-order differences are the 13 listed moves.

**Merging this census approves the 13 moves and the two named hooks.** A different hook placement,
or any other moved definition, is an escalation (#459 §14).

<details><summary>Full composite order of the 103 <code>loadConfiguration()</code> names (static, every branch)</summary>

```
  0  fbCore.application.logger.handler.rotatingFile
  1  fbCore.application.logger.handler.stdOut
  2  fbCore.application.logger.handler.console
  3  fbCore.application.subscribers.console
  4  fbCore.tools.helpers.sentry
  5  fbCore.tools.sentry.handler
  6  fbCore.tools.sentry.clientBuilder
  7  fbCore.tools.sentry.client
  8  fbCore.tools.sentry.hub
  9  fbCore.application.cache.psr6
 10  fbCore.application.eventLoop.wrapper
 11  fbCore.application.eventLoop.status
 12  fbCore.application.subscribers.entityDiscriminator
 13  fbCore.tools.helpers.database
 14  fbCore.tools.utilities.doctrineDateProvider
 15  fbCore.doctrineCrud.entity.mapper
 16  fbCore.doctrineCrud.entity.creator
 17  fbCore.doctrineCrud.entity.updater
 18  fbCore.doctrineCrud.entity.deleter
 19  fbCore.doctrineCrud.crud
 20  fbCore.application.subscribers.eventLoop
 21  fbCore.application.ui.templateFactory
 22  fbCore.application.ui.routes
 23  fbCore.application.document.cache
 24  document.factory
 25  document.mapping.attributeDriver
 26  document.mapping.mappingDriver
 27  document.mapping.classMetadataFactory
 28  fbCore.exchange.consumer
 29  fbCore.exchange.publisher
 30  fbCore.exchange.publisher.async
 31  fbCore.exchange.entityFactory
 32  fbCore.simpleAuth.auth
 33  fbCore.simpleAuth.token.builder
 34  fbCore.simpleAuth.token.reader
 35  fbCore.simpleAuth.token.validator
 36  fbCore.simpleAuth.security.identityFactory
 37  fbCore.simpleAuth.security.userStorage
 38  fbCore.simpleAuth.access.annotationChecker
 39  fbCore.simpleAuth.access.latteChecker
 40  fbCore.simpleAuth.access.linkChecker
 41  fbCore.simpleAuth.casbin.adapter
 42  fbCore.simpleAuth.casbin.subscriber
 43  fbCore.simpleAuth.casbin.enforcerFactory
 44  fbCore.simpleAuth.middleware.access
 45  fbCore.simpleAuth.middleware.user
 46  fbCore.simpleAuth.doctrine.driver
 47  fbCore.simpleAuth.doctrine.subscriber
 48  fbCore.simpleAuth.doctrine.tokensRepository
 49  fbCore.simpleAuth.doctrine.tokensManager
 50  fbCore.simpleAuth.doctrine.policiesRepository
 51  fbCore.simpleAuth.doctrine.policiesManager
 52  fbCore.simpleAuth.nette.application
 53  fbCore.tools.schemas.validator
 54  fbCore.dateTimeFactory.datetime.system
 55  fbCore.dateTimeFactory.datetime.frozen
 56  fbCore.configuration
 57  fbCore.doctrineTimestampable.driver
 58  fbCore.doctrineTimestampable.subscriber
 59  fbCore.doctrineMigrations.subscriber
 60  fbCore.jsonApi.builder
 61  fbCore.jsonApi.middlewares.jsonapi
 62  fbCore.jsonApi.hydrators.container
 63  fbCore.jsonApi.schemas.container
 64  fbCore.jsonApi.helpers.crudReader
 65  fbCore.phone.libphone.utils
 66  fbCore.phone.libphone.geoCoder
 67  fbCore.phone.libphone.shortNumber
 68  fbCore.phone.libphone.mapper.carrier
 69  fbCore.phone.libphone.mapper.timezone
 70  fbCore.phone.phone
 71  fbCore.phone.doctrinePhone.subscriber
 72  fbCore.webSockets.controllers.factory
 73  fbCore.wsServer.clients.factory
 74  fbCore.wsServer.clients.driver.memory
 75  fbCore.wsServer.clients.storage
 76  fbCore.webSockets.routing.router
 77  fbCore.webSockets.routing.generator
 78  fbCore.wsServer.server.wrapper
 79  fbCore.wsServer.server.flashWrapper
 80  fbCore.wsServer.server.handlers
 81  fbCore.wsServer.server.loop
 82  fbCore.wsServer.server.configuration
 83  fbCore.wsServer.server.logger
 84  fbCore.wsServer.server.server
 85  fbCore.wsServer.wamp.topics.driver.memory
 86  fbCore.wsServer.wamp.topics.storage
 87  fbCore.webSockets.wamp.application
 88  fbCore.webSockets.wamp.serializer
 89  fbCore.webSockets.wamp.pushRegistry
 90  fbCore.wsServer.wamp.clientsFactory
 91  fbCore.wsServer.wamp.subscribers.onServerStart
 92  fbCore.httpServer.routing.responseFactory
 93  fbCore.httpServer.routing.router
 94  fbCore.httpServer.commands.server
 95  fbCore.httpServer.middlewares.cors
 96  fbCore.httpServer.middlewares.staticFiles
 97  fbCore.httpServer.middlewares.router
 98  fbCore.httpServer.application.classic
 99  fbCore.httpServer.server.factory
100  fbCore.httpServer.subscribers.server
101  fbCore.wsServer.commands.wsServer
102  fbCore.wsServer.subscribers.client
```
</details>

### 5.6 Composite contract, additional points found while measuring

- **Dependencies.** `Compiler::processBeforeCompile()` (vendor `nette/di` 3.2.7, `Compiler.php:275-277`)
  adds the class file of every *registered* extension to the container's dependencies. Children are
  not registered, so their files are not recorded. In debug mode (`APP_ENV=dev`,
  `ContainerLoader` auto-rebuild) editing a child extension would not rebuild the container. The
  composite should call `$compiler->addDependencies([...child class files])`.
- **Initialization.** Measured: nothing in Core uses `getInitialization()` today (§1.3). The
  §3.1 merge is future-proofing; the Phone DBAL type stays in `afterCompile()`.
- **`MappingHelper::of($child)`** needs only `getContainerBuilder()`, so a child that has received
  `setCompiler()` works. Security's `beforeCompile()` always finds nettrine's mapping-driver tag,
  because `nettrineOrm` runs `loadConfiguration()` before any `beforeCompile()`.
- The `DOCTRINE MIGRATIONS` block uses `$this->compiler->getExtensions(...)`, so the Persistence
  child needs the compiler before its hook runs. That already holds, since the composite passes it
  in `loadConfiguration()`.

## 6. Cross-capability couplings (§1.7) and how each is routed

| Coupling | Routed as |
|---|---|
| `Http\Routing\LinkGenerator` is a WebSockets class | registered by `WebSocketsExtension::loadConfiguration()` (§1.1 row 79, today's `webSockets.routing.generator`); class stays in `Http\Routing`; move handed to #460 |
| `Configuration` mixes Security and Persistence settings | registered by the root (group C) from the `security` and `persistence.timestampable` subtrees (today `simpleAuth`, `doctrineTimestampable`); split handed to #460 |
| `Documents\RoutingDocumentFactory` in the Exchange block | registered by `ExchangeExtension::loadConfiguration()` (`exchange.entityFactory` keeps its name) |
| WebSockets probes other capabilities at `loadConfiguration()` time | root group A (`LoopInterface`) before WebSockets; `LoggerInterface` from `contributteMonolog` (outside Core); `Exchange\Factory` (D4, timing kept); `ClientProvider` (own) |
| Security's ORM mapping | `MappingHelper::of($this)` moves into `SecurityExtension::beforeCompile()` with `$this` = the child |
| Entity manager shared by Persistence and Phone | `PersistenceExtension::beforeCompile()` runs before `PhoneExtension::beforeCompile()` (§5.3) |
| Fallback event dispatcher read by Security and WebSockets | root `beforeCompile()` block 1 runs first |
| Logging and WebSockets read two config sections each until E4.5 | children get the composite's configuration object, or both subtrees, until E4.5 (§3) |

## 7. Container inventory for the snapshot

How every test harness builds its container, all 47 `BaseTestCase`/`DbTestCase` files parsed:
`Bootstrap::boot()` loads Core's `config/common.neon` + `config/defaults.neon`, then the harness
adds the package's `tests/common.neon`, then its overlays, then `<Ext>::register($config)` (all
except Core). `BaseTestCase` and `DbTestCase` of one package register the same extension and add
the same files, so they compile the same container. Other ways a container is built, parsed across
every tracked test file: only `tests/cases/application/bootstrap-production-scope.php`
(`EntityMappingTest`'s child), which is production plus an array overlay. Nothing else calls
`Compiler`, `ContainerLoader` or `Configurator` directly.

| # | Container | Kind | Configuration | `onCompile` extension (loads **before** `fbCore`) | Core definitions | Compiles on `main` |
|---|---|---|---|---|---|---|
| 1 | `test:Addon/VirtualThermostat` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Addon/VirtualThermostat/tests/common.neon` | `VirtualThermostatExtension` | 86 | yes |
| 2 | `test:Automator/DateTime` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Automator/DateTime/tests/common.neon` | `DateTimeExtension` | 86 | yes |
| 3 | `test:Automator/DevicesModule` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Automator/DevicesModule/tests/common.neon` | `DevicesModuleExtension` | 86 | yes |
| 4 | `test:Bridge/DevicesModuleUiModule` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Bridge/DevicesModuleUiModule/tests/common.neon` | `DevicesModuleUiModuleExtension` | 86 | yes |
| 5 | `test:Bridge/RedisDbPluginDevicesModule` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Bridge/RedisDbPluginDevicesModule/tests/common.neon` | `RedisDbPluginDevicesModuleExtension` | 86 | yes |
| 6 | `test:Bridge/RedisDbPluginTriggersModule` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Bridge/RedisDbPluginTriggersModule/tests/common.neon` | `RedisDbPluginTriggersModuleExtension` | 86 | yes |
| 7 | `test:Bridge/ShellyConnectorHomeKitConnector` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Bridge/ShellyConnectorHomeKitConnector/tests/common.neon` | `ShellyConnectorHomeKitConnectorExtension` | 86 | yes |
| 8 | `test:Bridge/VieraConnectorHomeKitConnector` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Bridge/VieraConnectorHomeKitConnector/tests/common.neon` | `VieraConnectorHomeKitConnectorExtension` | 86 | yes |
| 9 | `test:Bridge/VirtualThermostatAddonHomeKitConnector` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Bridge/VirtualThermostatAddonHomeKitConnector/tests/common.neon` | `VirtualThermostatAddonHomeKitConnectorExtension` | 86 | yes |
| 10 | `test:Connector/FbMqtt` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/FbMqtt/tests/common.neon` | `FbMqttExtension` | 86 | yes |
| 11 | `test:Connector/HomeKit` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/HomeKit/tests/common.neon` | `HomeKitExtension` | 86 | yes |
| 12 | `test:Connector/Modbus` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/Modbus/tests/common.neon` | `ModbusExtension` | 86 | yes |
| 13 | `test:Connector/NsPanel` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/NsPanel/tests/common.neon` | `NsPanelExtension` | 86 | yes |
| 14 | `test:Connector/Shelly` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/Shelly/tests/common.neon` | `ShellyExtension` | 86 | yes |
| 15 | `test:Connector/Sonoff` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/Sonoff/tests/common.neon` | `SonoffExtension` | 86 | yes |
| 16 | `test:Connector/Tuya` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/Tuya/tests/common.neon` | `TuyaExtension` | 86 | yes |
| 17 | `test:Connector/Viera` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/Viera/tests/common.neon` | `VieraExtension` | 86 | yes |
| 18 | `test:Connector/Virtual` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/Virtual/tests/common.neon` | `VirtualExtension` | 86 | yes |
| 19 | `test:Connector/Zigbee2Mqtt` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Connector/Zigbee2Mqtt/tests/common.neon` | `Zigbee2MqttExtension` | 86 | yes |
| 20 | `test:Core/Core` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Core/Core/tests/common.neon` | - | 82 | yes |
| 21 | `test:Module/Accounts` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Accounts/tests/common.neon` | `AccountsExtension` | 91 | yes |
| 22 | `test:Module/Devices` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Devices/tests/common.neon` | `DevicesExtension` | 86 | yes |
| 23 | `test:Module/Triggers` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Triggers/tests/common.neon` | `TriggersExtension` | 86 | yes |
| 24 | `test:Module/Ui` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Ui/tests/common.neon` | `UiExtension` | 86 | yes |
| 25 | `test:Plugin/ApiKey` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Plugin/ApiKey/tests/common.neon` | `ApiKeyExtension` | 70 | yes |
| 26 | `test:Plugin/CouchDb` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Plugin/CouchDb/tests/common.neon` | `CouchDbExtension` | 66 | no: `ServiceCreationException` Service 'fbCouchDbPlugin.model.statesManager' (type of FastyBird\Plugin\CouchDb\Models\Sta... |
| 27 | `test:Plugin/RabbitMq` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Plugin/RabbitMq/tests/common.neon` | `RabbitMqExtension` | 66 | yes |
| 28 | `test:Plugin/RedisDb` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Plugin/RedisDb/tests/common.neon` | `RedisDbExtension` | 66 | yes |
| 29 | `test:Plugin/RedisDbCache` | base-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Plugin/RedisDbCache/tests/common.neon` | `RedisDbCacheExtension` | 66 | yes |
| 30 | `overlay:Module/Accounts:Router/prefixedRoutes.neon` | overlay-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Accounts/tests/common.neon` + `src/FastyBird/Module/Accounts/tests/cases/unit/Router/prefixedRoutes.neon` | `AccountsExtension` | 91 | yes |
| 31 | `overlay:Module/Devices:Controllers/exchange.neon` | overlay-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Devices/tests/common.neon` + `src/FastyBird/Module/Devices/tests/cases/unit/Controllers/exchange.neon` | `DevicesExtension` | 86 | yes |
| 32 | `overlay:Module/Devices:Router/prefixedRoutes.neon` | overlay-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Devices/tests/common.neon` + `src/FastyBird/Module/Devices/tests/cases/unit/Router/prefixedRoutes.neon` | `DevicesExtension` | 86 | yes |
| 33 | `overlay:Module/Triggers:Router/prefixedRoutes.neon` | overlay-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Triggers/tests/common.neon` + `src/FastyBird/Module/Triggers/tests/cases/unit/Router/prefixedRoutes.neon` | `TriggersExtension` | 86 | yes |
| 34 | `overlay:Module/Ui:Router/prefixedRoutes.neon` | overlay-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Module/Ui/tests/common.neon` + `src/FastyBird/Module/Ui/tests/cases/unit/Router/prefixedRoutes.neon` | `UiExtension` | 86 | yes |
| 35 | `overlay:Plugin/RabbitMq:Connections/customConnection.neon` | overlay-test | Core `config/{common,defaults}.neon` + `src/FastyBird/Plugin/RabbitMq/tests/common.neon` + `src/FastyBird/Plugin/RabbitMq/tests/fixtures/Connections/customConnection.neon` | `RabbitMqExtension` | 66 | yes |
| 36 | `prod:cli` | production | `src/FastyBird/Core/Core/config/{common,defaults}.neon` + `config/{common,defaults}.neon` | - | - | no: `InvalidConfigurationException` The item 'fbCore › simpleAuth › token › signature' expects to be string, null given.... |
| 37 | `prod:cli-signed` | production | `src/FastyBird/Core/Core/config/{common,defaults}.neon` + `config/{common,defaults}.neon`; `FB_APP_PARAMETER__SECURITY_SIGNATURE` | - | - | no: `LogicalException` Define path to manifest.json, because automatic search found nothing in "/app/public".... |
| 38 | `prod:dev` | production | `src/FastyBird/Core/Core/config/{common,defaults}.neon` + `config/{common,defaults}.neon`; `FB_APP_PARAMETER__SECURITY_SIGNATURE`, `APP_ENV`; Vite-manifest overlay | - | 93 | yes |
| 39 | `prod:fpm` | production | `src/FastyBird/Core/Core/config/{common,defaults}.neon` + `config/{common,defaults}.neon`; `FB_APP_PARAMETER__SECURITY_SIGNATURE`; `consoleMode: false`; Vite-manifest overlay | - | 90 | yes |
| 40 | `prod:sentry` | production | `src/FastyBird/Core/Core/config/{common,defaults}.neon` + `config/{common,defaults}.neon`; `FB_APP_PARAMETER__SECURITY_SIGNATURE`, `FB_APP_PARAMETER__SENTRY_DSN`; Vite-manifest overlay | - | 94 | yes |
| 41 | `prod:entity-mapping-test` | production | `src/FastyBird/Core/Core/config/{common,defaults}.neon` + `config/{common,defaults}.neon`; `FB_APP_PARAMETER__SECURITY_SIGNATURE`; Vite-manifest overlay | - | 90 | yes |

**Counts:** 29 base test configurations (28 compile), 6 per-test overlays, 1 production
configuration. Together: **38 containers compile on `main`** (28 + 6 + 4 production variants).

**Recommended snapshot set for #554:**
- the 28 compiling base containers;
- the 6 overlays;
- production as `EntityMappingTest` builds it (`prod:entity-mapping-test`), the only production
  container the suite compiles;
- `prod:dev`, which adds the 3 debug-mode Logging definitions, including the `ConsoleHandler`
  subscriber;
- `prod:sentry`, the only way the 4 Sentry definitions and the Sentry `pushHandler` compile.

`prod:fpm` is optional: its Core definitions are identical to CLI, in the same order. `CouchDb` is
either recorded as an expected compile failure or skipped with that reason; it is not something
E4 can make green. `prod:cli` and `prod:cli-signed` fail by design, for lack of a signature and a
manifest.

Four names never compile in any container: `jsonApi.helpers.crudReader` (D1),
`wsServer.clients.factory` (always removed again), `wsServer.server.loop` and
`wsServer.server.logger` (their conditions are never true, §5.2). No snapshot can see them. The
service map covers them statically.

## 8. Silent-path coverage (E4.2's to-do list)

| Silent path | Tested today? | Evidence |
|---|---|---|
| `DRIVER_TAG`: a module's document path | **Indirectly only, not mutation-proven** | No test references the tag or its string. Devices `Documents/ChannelPropertyDocumentTest` and `ChannelPropertyActionDocumentTest`, and Ui `Documents/WidgetDocumentTest`, create their module's documents through the container's `DocumentFactory`; those namespaces reach the mapping chain only through the `DRIVER_TAG` blocks (`DevicesExtension.php:995-1017`, `UiExtension.php:533`). No other test calls the container's `DocumentFactory` directly. Whether one of the other 19 consumers is exercised through a service that builds documents is not established without a mutation run. |
| `CONSUMER_STATE = false` leaves a consumer disabled | **Untested** | `Messaging/ExchangeContainerTest` builds `Consumers\Container` by hand (`register($consumer, null)`); no test compiles a tagged consumer and reads its state. The test must use one of the 5 tags on a service definition (e.g. Devices `exchange.consumer.statesActions`); the 10 connector tags never reach the proxy (F4) |
| `CONSUMER_ROUTING_KEY` | **Untested**; no producer exists | only the `?? null` default is ever exercised |
| `ipub.websockets.routes`: Devices WAMP routes reach the router | **Untested** | Devices and Ui `Controllers/ExchangeV1Test` call `Router\SocketRoutes::createRouter()` directly |
| `ipub.websockets.controller`: `ControllerFactory` resolves a tagged controller | **Untested** | Core `ApplicationTest`, `WampApplicationTest`, `ControllerTest` and both `ExchangeV1Test` mock or hand-build `IControllerFactory` |
| D2 (#564) listener count | **Untested** | no test reads `getListeners()` |

## 9. D1–D4

| # | Verdict | Evidence (reproduce with the scripts in the last section) |
|---|---|---|
| D1 `CrudReader` never registered | **Confirmed**; the decision is #552 | `class_exists('\IPub\DoctrineCrud\Mapping\Annotation\Crud')` is `false` in the image; `git log -S 'namespace IPub\DoctrineCrud\Mapping\Annotation'` finds no commit; `fbCore.jsonApi.helpers.crudReader` is absent from all 38 compiled containers. E4 keeps the guard verbatim in the Api extension |
| D2 Timestampable and Phone subscribed twice | **Confirmed; filed as #564** | table below |
| D3 non-default WebSockets storage driver cannot work | **Confirmed; filed as #565**. Fails loudly at compile time; not live (no tracked NEON sets these keys) | table below |
| D4 `WsServer` command's `exchangeFactories` resolved too early | **Confirmed; filed as #566.** Live in the documented RedisDb and RabbitMQ configurations (`docs/configuration.md`); not live in the shipped default, which has no `Exchange\Factory`. Not an E4 blocker: E4 keeps the timing verbatim (§12) | table below |

**D2 (#564).** Each container was instantiated (no database connection is made) and nettrine's
`ContainerEventManager::$listeners` was read, then resolved through `getListeners()`:

| Container | Event | Listener entries | Distinct objects | Invoked twice | Dispatch order (`(obj)` = the `addEventSubscriber()` entry) |
|---|---|---|---|---|---|
| `prod:entity-mapping-test` | `loadClassMetadata` | 6 | 4 | TimestampableSubscriber, PhoneObjectSubscriber | `EntityDiscriminator`, `User`, `TimestampableSubscriber`, `PhoneObjectSubscriber`, `TimestampableSubscriber`(obj), `PhoneObjectSubscriber`(obj) |
| `prod:entity-mapping-test` | `onFlush` | 6 | 5 | TimestampableSubscriber | `User`, `TimestampableSubscriber`, `AccountEntity`, `EmailEntity`, `NotificationEntity`, `TimestampableSubscriber`(obj) |
| `test:Core/Core` | `loadClassMetadata` | 5 | 3 | TimestampableSubscriber, PhoneObjectSubscriber | `EntityDiscriminator`, `TimestampableSubscriber`, `PhoneObjectSubscriber`, `TimestampableSubscriber`(obj), `PhoneObjectSubscriber`(obj) |
| `test:Core/Core` | `onFlush` | 2 | 1 | TimestampableSubscriber | `TimestampableSubscriber`, `TimestampableSubscriber`(obj) |
| `test:Module/Devices` | `loadClassMetadata` | 5 | 3 | TimestampableSubscriber, PhoneObjectSubscriber | `EntityDiscriminator`, `TimestampableSubscriber`, `PhoneObjectSubscriber`, `TimestampableSubscriber`(obj), `PhoneObjectSubscriber`(obj) |
| `test:Module/Devices` | `onFlush` | 2 | 1 | TimestampableSubscriber | `TimestampableSubscriber`, `TimestampableSubscriber`(obj) |

Mechanism: `EventPass` adds each subscriber by service name, keyed `service@<name>`. The
entity-manager setups at `CoreExtension.php:1364-1371` add the same object again, keyed by
`spl_object_hash()`. `ContainerEventManager::dispatchEvent()` calls every entry, so
`TimestampableSubscriber::loadClassMetadata/onFlush` and `PhoneObjectSubscriber::loadClassMetadata`
run twice per event. **In production the second `onFlush` run of Timestampable comes after the
Accounts and Triggers `onFlush` subscribers.** Removing the duplicate is therefore a behaviour
change, not dead-code removal. E4 keeps the wiring verbatim. E4.2's characterization test should
pin these counts.

**D3 (#565).** The Core test container compiled with one overlay each:

| Overlay (`fbCore.webSockets.storage…`) | Result |
|---|---|
| `clients.driver: "@fbCore.wsServer.clients.driver.memory"` (an existing service, `@` form like the default) | `MissingServiceException`: `getDefinition()` does not strip `@` (`CoreExtension.php:903`) |
| `clients.driver: "@myClientsDriver"` with `myClientsDriver` in `services:` | `MissingServiceException` (same line) |
| `clients.driver: "myClientsDriver"` (bare, in `services:`) | `MissingServiceException`: `services:` is processed after every extension's `loadConfiguration()` |
| `clients.driver: "fbCore.wsServer.clients.driver.memory"` (bare, registered by Core) | compiles; the only working non-default form |
| `topics.driver: "@myTopicsDriver"` | `MissingServiceException` for `fbCore.wsServer.wamp.topics.driver.memory`: the non-default branch (`:979`) fetches the memory driver it declined to register |

**D4 (#566).** Production (`prod:entity-mapping-test`) compiled with the `config/local.neon` from
`docs/configuration.md`, verbatim:

| Container | `Exchange\Factory` services | `fbCore.wsServer.commands.wsServer` `exchangeFactories` | Devices `commands.exchange` / `commands.connector` (collected in `beforeCompile()`) |
|---|---|---|---|
| production + RedisDb `local.neon` | `fbRedisDbPlugin.exchange.factory` | `[]` | `{fbRedisDbPlugin.exchange.factory}` |
| production + RabbitMq `local.neon` | `fbRabbitMqPlugin.channels.async.factory` | `[]` | `{fbRabbitMqPlugin.channels.async.factory}` |
| `test:Plugin/RedisDb` (plugin via `onCompile`, **before** `fbCore`) | `fbRedisDbPlugin.exchange.factory` | `{fbRedisDbPlugin.exchange.factory}` | — |
| `test:Plugin/RabbitMq` (same) | `fbRabbitMqPlugin.channels.async.factory` | `{fbRabbitMqPlugin.channels.async.factory}` | — |

`WsServer.php:93-95` is the only place the `fb:ws-server:start` process calls `create()` on an
exchange. RedisDb/RabbitMq have no other start path: no subscriber on `WsServerStartup` or
`EventLoopStarted`. The test containers register the plugin before `fbCore`, so the suite cannot
see the defect. E4 keeps the timing (plan §1.8). The composite does not change what the lookup
sees, because Core registers no `Exchange\Factory`.

## 10. DI-identifier denylist for the E4.2 guard

**Words** (the camelCase form of every `FB_NAMESPACE_DENYLIST` entry, plus `iPublikuj` from
`FB_TYPE_DENYLIST`): `simpleAuth`, `slimRouter`, `doctrineCrud`, `doctrineOrmQuery`,
`doctrineTimestampable`, `doctrinePhone`, `jsonApi`, `jsonApiDocument`, `metadata`, `tools`,
`dateTimeFactory`, `webServer`, `wsServer`, `httpServer`, `ipub`, `iPublikuj`.

**Scoped: `application`** violates only as the top-level configuration key or as the service-name
segment directly under `fbCore`. It is allowed deeper: `simpleAuth.enable.nette.application`,
`fbCore.httpServer.application.classic` and `fbCore.security.nette.application` name Nette's or
the HTTP server's `Application`.

**Matching:** split the identifier on `.`. A segment violates if it equals a word
case-insensitively, so `jsonapi` in `middlewares.jsonapi` is caught. Count per identifier.

**Measured on `main`** (`guard.py`; the §4 list and the full list give identical numbers):

| Kind | Checked | Violations |
|---|---|---|
| Service names in the Core test container (`fbCore.*` and `document.*`) | 82 | **61** |
| Schema key paths (every node) | 89 | **71** (43 of the 55 leaves, if leaves only) |
| Tag strings (the 5 Core tags) | 5 | **2** (`ipub.websockets.routes`, `ipub.websockets.controller`) |
| **Total, the guard's starting list** | | **134** |

For the static set of all 105 service names the count is 83. After the maps of §2–§4 the count is
**0** for services, keys and tags.

**The denylist cannot see three of the five tag renames.** `fastybird.application.attribute.driver`
(`application` is not under `fbCore` and not a config key), `consumer_state` and
`consumer_routing_key` pass it. It also cannot see the four unprefixed `document.*` names. The guard
should add two **positive** rules (the reasoning `docs/conventions.md` gives for import aliases):

- every Core service name matches `^fbCore\.(api|clock|documents|exchange|http|logging|persistence|phone|security|values|webSockets)\.<role>$` or is one of the root forms `fbCore.(eventLoop|ui|cache).<role>`, `fbCore.eventDispatcher` or `fbCore.configuration`. On `main`, 64 of 82 fail;
- every Core tag matches `^fastybird\.core\.<capability>\.<role>$`. On `main`, 5 of 5 fail.

The two kinds of rule catch different defects. The denylist catches `fbCore.phone.doctrinePhone.subscriber`,
which the pattern accepts; the pattern catches `document.*`, which the denylist accepts. Every
target in §2 and §4 passes both.

**Adopted by #554 (orchestrator, 2026-09-27):** the guard uses the full word list above, checks
every schema node's key path (not only leaves), and adds both positive rules.

## 11. Other findings (not D-items)

- **F1: package test containers load the package before `fbCore`.** Measured in all 28:
  `<Ext>::register()` adds the extension through `onCompile`, before `ExtensionsExtension` adds the
  NEON ones. Consequences today: the Devices and Ui containers never compile their own
  `socketsBridge` consumer, because their `loadConfiguration()` lookups of Core services find
  nothing; D4 is invisible to the suite; and the global order puts the package's definitions
  before Core's. The composite preserves all of it. Not an E4 change.
- **F2: `Plugin/CouchDb`'s test container does not compile on `main`**
  (`fbCouchDbPlugin.model.statesManager` needs a `CouchDb\States\StateFactory` nobody registers).
  Reproduced without the census harness (`couchdb_plain.php`). No test extends its `BaseTestCase`.
- **F3: four JSON:API error texts are untranslated; filed as #567.** Through the real translator,
  `//jsonApi.hydrator.resourceInvalid.{heading,message}` and `…identifierInvalid.{heading,message}`
  return the raw key, while the control key `…invalidAttribute.heading` returns "Invalid attribute".
  So a 422 for a missing resource or an invalid identifier carries the key as its title and detail.
  E4.8 renames only the domain prefix on those lines. Fixing the keys is a translation-key change
  (#459 §14) and belongs to #567.
- **F4: 10 of the 15 `CONSUMER_STATE` tags have no effect; not a bug.** The 10 connector extensions
  (FbMqtt, HomeKit, Modbus, NsPanel, Shelly, Sonoff, Tuya, Viera, Virtual, Zigbee2Mqtt; e.g.
  `ShellyExtension.php:99`) put the tag on `addFactoryDefinition(...)->getResultDefinition()`.
  Core's proxy assembly iterates `findByType(Consumers\Consumer)`, which sees service definitions
  only, never a factory's result definition.

  Evidence, from the production compile (`prod:entity-mapping-test`, `f4.py`): the tag sits on 5
  service definitions, and all 5 appear as `register()` setups on `fbCore.exchange.consumer`
  (Devices `statesActions`, `moduleEntities`, `socketsBridge`; Ui `socketsBridge`; bridge
  `stateEntities`). The tag also sits on the result definition of 10
  `<connector>.writers.exchange` `FactoryDefinition`s, and none of those is registered. The
  generated `$wiring[Consumers\Consumer]` lists the proxy plus those same 5.

  The writers register themselves disabled at runtime: all 10 `Connector/*/src/Writers/Exchange.php`
  call `register($this, null, false)`, e.g. Shelly `:80`. So the tag changes nothing. E4.7 still
  renames these 10 tag uses like any other.

## 12. Escalation items

**None open.** The items this census raised are filed or decided:

| Item | Status |
|---|---|
| D1 `CrudReader` guard | #552, the maintainer's decision. E4 keeps the guard verbatim |
| D2 double Doctrine subscription | filed as #564. E4 keeps the wiring verbatim |
| D3 non-default WebSockets storage drivers | filed as #565. E4.6 renames the sentinels together, and nothing more |
| D4 `exchangeFactories` timing (live with RedisDb/RabbitMQ as documented) | filed as #566. **Not an E4 blocker:** E4 keeps the timing verbatim (plan §1.8, §3.8). Whether to fix it separately is the maintainer's call |
| F3 untranslated JSON:API errors | filed as #567. E4.8 renames only the domain prefix on those lines |
| F4 inert connector `CONSUMER_STATE` tags | no bug. E4.7 renames them with the rest |
| Global definition order (§5.5) | **resolved (orchestrator, 2026-09-27):** the hard snapshot criteria of §5.5, and `--allow-moves` with exactly the 13 listed definitions for E4.3/E4.4. Merging this census approves the 13 moves and the two named hooks |

No service belongs to two capabilities without a rule to break the tie. No rename touches a route,
topic, payload, table, discriminator or `@Secured` line: none of the 124 `@Secured` lines mentions
a DI identifier, and the hash is still `dc9fe022…ec83b`.

## How this was produced / how to reproduce

The scripts are local scratch, not part of the repository. They live in `~/.cache/e4/census/` on
the machine this census was produced on. As in the E3 census, **the tables above are the
authoritative artifact**; E4.3–E4.8 execute them, not the scripts. Each script is described below
well enough to rebuild it.

```bash
# vendor/ for the measured tree (git-ignored), copies not symlinks:
docker run --rm -v "$PWD":/app -w /app -e COMPOSER_MIRROR_PATH_REPOS=1 -e XDEBUG_MODE=off \
  -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC fb-e2-app:latest composer install --no-interaction
find vendor/fastybird -maxdepth 1 -type l          # prints nothing
# every PHP script ran as (repository read-only, the test ini on the scan dir as `make tests` does):
docker run --rm -v "$PWD":/app:ro -v ~/.cache/e4/census:/census -w /app \
  -e XDEBUG_MODE=off -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC -e PHP_INI_SCAN_DIR=:/app/tools/php.d \
  fb-e2-app:latest php /census/<script>.php [args]
```

| Script | What it does | Produces |
|---|---|---|
| `static.php` | parses `CoreExtension.php` (php-parser + `NameResolver`): every `addDefinition`/`addFactoryDefinition`/`addSetup`/`findByType`/… call with method, comment block, enclosing conditions and the registered type from the fluent chain | §1 (106 sites, 105 names) |
| `schema.php` | instantiates `CoreExtension`, walks `getConfigSchema()`'s `Structure` items by reflection | §3 (89 nodes) |
| `containers.php` | parses every package's `BaseTestCase`/`DbTestCase` for `<Ext>::register()` and every tracked test file for `registerNeonConfigurationFile()`/`createContainer()`/`addConfig()` | §7 inventory |
| `compile.php <id> [--d2] [--overlay=<neon>]` | builds one container exactly as its harness does (same constants as `tools/phpunit-bootstrap.php`, `DG\BypassFinals`, same `addConfig` order). Three probe extensions are added through `onCompile`, and a reflection reorder of `Compiler::$extensions` makes them run `beforeCompile()` right before `fbCore`, right after it, and last. Records every definition at each point, the completed builder, and the generated `$wiring`/`$tags`/`initialize()`. `--d2` instantiates and reads the Doctrine listeners | every compiled fact |
| `scan.php` | parses every tracked `.php` and `.neon`: tag constants and literals with the enclosing call, `//jsonApi.` lookups, catalogues, service-name strings, DI and runtime container lookups | §4, §2 notes, §5.2 lookups |
| `translation.php`, `translate_check.php` | lookup keys against the catalogue; the real translator on the four missing keys | §4, F3 |
| `couchdb_plain.php` | CouchDb container without the probes | F2 |
| `f4.py` | `CONSUMER_STATE` tags on service definitions versus factory result definitions, against the proxy's `register()` setups | F4 |
| `analyze.py` (`model.py` holds the allocation rule, the three maps, the composite and the denylists) | allocation counts; map totality and uniqueness; the pair-flip check across every collection of every container; `CONTROL=nohooks` for the positive control | §1, §2, §5.4 |
| `globalorder.py`, `variants.py` | owner runs, the 13 moved definitions, every hook placement | §5.5 |
| `contiguous.py` | contiguity of `fbCore`'s block in each container | §5.1 |
| `collections.py <id>` | prints each order-sensitive collection of one container | §5.2 |
| `guard.py` | denylist and positive-rule counts | §10 |
| `d3.sh`, `d4.sh` with `d3/*.neon`, `d4/*.neon` | the D3 and D4 compiles | §9 |
| `render.py`, `assemble.py` | turn the measurements into the tables of this document | this file |
