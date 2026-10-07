# E5.1 — Census of Core's public API: interfaces, dead code, hooks, constants and accessors

Subtask of Epic E5 (#460), orchestration issue #646. It satisfies the checklist in #633.
**Merging this PR approves the following:**

- T1: the decisions and role names;
- T2: the deletions, including the DI services they remove;
- T4: the event names and the listener priorities in T3;
- T5: the constant destinations;
- T9: the replacement values;
- T10: the type names.

It also decides the escalations in §0.3. Each one has a default that applies unless the reviewer
picks another option.

Every later E5 PR implements these rows as written. A name, deletion or destination that is not
in these tables is an escalation (#460 §15).

**Base commit.** `origin/main` at `5206bfc86` (`fix(module): emit canonical links from scoped
Devices routes (#632)`), the Epic's own base.

**Harness.** Every number was measured against that tree in the `fb-e2-app:latest` application
image. `vendor/` was installed with `COMPOSER_MIRROR_PATH_REPOS=1 composer install`, which gives
29 `vendor/fastybird/*` mirrors. All 29 are copies: `find vendor/fastybird -maxdepth 1 -type l`
prints nothing, and `diff -rq` of Core's `src` against its mirror is empty. `var/temp/cache` was
cleared first.

**How the code was read.**

- **PHP code.** Parsed with the vendored `nikic/php-parser` 5.9.0 and its `NameResolver`, so every
  reference is counted by FQCN, through `use` imports and aliases. That is how
  `Clients\Drivers\IDriver` and `Topics\Drivers\IDriver`, and the two `IStorage` and `IMessage`
  pairs, are told apart. Class members (callback arrays, constant values, accessor pairs) were
  read with PHP Reflection through the real autoloader, and method bodies with
  `PhpToken::tokenize()`.
- **Compiled DI graph.** Read from all 47 containers recorded by `tools/di-snapshot.php`. 46 of
  them compile; `test/Plugin/CouchDb` still does not, as on every main since E4.
- **Grep.** Used only to locate strings in NEON, Latte, XML, JSON and YAML, and to cross-check
  counts. Every place it was used says so.

Every script is committed under `tools/census/e5/`, and "How to reproduce" at the end gives the
exact commands.

---

## 0. Read this first

### 0.1 Where the census disagrees with Epic §1

| # | Epic §1 says | Measured | Which is right, and why |
|---|---|---|---|
| D1 | §1.3: "**24** public callback arrays", "**15 live**, all in WebSockets", "9 dead" | **22** arrays: 13 in WebSockets and 9 dead (`members.php callbacks`, Reflection over every Core class) | The measurement. The 15 are the **bridge setups**, not the arrays: `onClientConnected` and `onIncomingMessage` are bridged twice. 13 + 9 = 22. |
| D2 | §1.3: "15 live" | Of the 13 WebSockets arrays, **only 10 ever fire in production**. `ServerRuntime::run()` and `stop()`, which fire `onStart` and `onStop`, are called only by `ServerTest`. `WsServer::execute()` calls `create()` and then `$eventLoop->run()` directly. `WampApplication::handlePush()`, which fires `onPush`, is called only by `WampApplicationTest` (`calls.php run,stop,handlePush`). | The measurement. It changes T3/T4 and escalation **X2**. |
| D3 | §1.3: the second bridge of `onClientConnected`/`onIncomingMessage` is in "`loadServerProcess`" | It is in **`WebSocketsExtension::beforeCompile()`**, lines 445–464, in the "WS SERVER PLUGIN" block after the push-registry loop. `loadServerProcess()` only registers `commands.server` and `subscribers.client`. | The code. The bridge is the last thing appended to `Wrapper`'s setups (T3). |
| D4 | §1.2/§1.16-1: "Every one of them has at most one production implementer" | **`IRouter` has 4** production implementers: `Router`, `ServerRouter`, `Connector\HomeKit\Router\Router` and `Connector\NsPanel\Router\Router`, all through `extends Router`. **`IProtocol` has 2**: `RFC6455` and `HyBi10 extends RFC6455` (`t1.py`). | The measurement. Literally, K1/K2 holds for both, so see escalation **X3**. |
| D5 | §1.2: `IRouter` "referenced in **98** files outside Core" | **99** by FQCN. The 98 under `src/FastyBird/` agree with `git grep -lw IRouter`. The 99th is `tests/cases/application/bootstrap-routes.php` (`q.py`). | Both are right. The Epic counted `src/FastyBird` only, and the root `tests/` tree must be rewritten too. |
| D6 | §1.7: `Constants` "referenced by **94** files outside Core" | **76** files outside `src/FastyBird/Core/` fetch a `FastyBird\Core\Constants` constant, 48 of them tests (`consts.php`, the FQCN of each `ClassConstFetch` resolved). The class-reference index gives the identical set of 76. | The measurement. A short-name `Constants::` grep also catches each extension's own `Constants`. |
| D7 | §1.7: "**Unreferenced:** `ROLE_VISITOR` and `TOKEN_URI_NAME`" | **Only `TOKEN_URI_NAME`** is unreferenced. `ROLE_VISITOR` is used at `Module/Accounts/src/Constants.php:95`. | The measurement. #635 deletes `TOKEN_URI_NAME` only. |
| D8 | §1.7: `EXCHANGE_CHANNEL_NAME`, `MESSAGE_BUS_PREFIX_KEY`, `NOT_SPECIFIED_SOURCE`: "RedisDb, RabbitMq, Core" | `NOT_SPECIFIED_SOURCE` is used **only as the `NOT_SPECIFIED` case value of the 6 `Sources` enums**, so it is C1. `EXCHANGE_CHANNEL_NAME` is used **only by RedisDb** (C2). `MESSAGE_BUS_PREFIX_KEY` is used by **4 module `Constants` plus RabbitMq** (C4). | The measurement (T5). |
| D9 | §1.7: prefixes are used by their own extension, and `MODULE_DEVICES_PREFIX` also by the 3 HomeKit bridges' tests | Also **`MODULE_UI_PREFIX`** in `Bridge/DevicesModuleUiModule/tests/.../DataSourcesV1Test.php` | The measurement (T5). |
| D10 | §1.9: `LinkGenerator`: "**13** files outside Core" | **6** by FQCN: the 3 `SocketsBridge` consumers (Devices, Ui, DevicesModuleUiModule) and the 3 DI extensions. Inside Core: `Controller`, `WebSocketsExtension`, `Pusher` and `ControllerTest`. | The measurement. A short-name count also hits `Nette\Application\LinkGenerator`. |
| D11 | §1.4: `Clock` "referenced by 87 files outside Core and 7 inside, **162** `getNow()` calls outside Core" | `Clock\Clock` is referenced by FQCN in **84** files outside Core and **5** Core files (`q.py`). There are **158** `->getNow()` calls in **73** files (`t8.php`, parsed): **154 in 70 files outside Core**. A `git grep -c -- '->getNow()'` gives the same 158/73. | The measurement. |
| D12 | §1.11: 39 pairs, "Documents 6, **on orisai `MappedObject`s**" | **41** genuine get/set pairs (`members.php accessors`). **None of the 6 Documents pairs is a `MappedObject`**: they are on `Events\PreLoad` (a Symfony event), `Mapping\ClassMetadata` (×3), `Mapping\Driver\AttributeDriver` and `Mapping\Driver\MappingDriverChain`, all plain classes. 41 − 2 pairs on T2-deleted types (`Pusher::$connected`, `TEntityRemoved::$deletedAt`) = **39**. | The measurement. P4's "exclude 6 `MappedObject` pairs" excludes nothing (§0.4). |
| D13 | §3.10: "`Route::setName()` … **fluent** setter or builder" | `Route::setName()` returns **`void`**. It is not fluent. Outside Core it is called **147×** as `$route->setName(...)` after the route is built. | It stays unchanged as builder **usage**, not because it is fluent (T6). |
| D14 | §1.12 lists 14 dead types | All 14 confirmed. **Transitively dead as well:** `Caching\MemoryStorage` (only `MemoryAdapterStorage` uses it); `Security\Latte\Nodes\{IfAllowedNode, NElseAllowedNode, AllowedHrefNode}` (only `AccessExtension` uses them); and **the whole server-push pipeline**, beyond `IConsumer`: `ConsumersRegistry`, `IConsumersRegistry`, `Subscribers\OnServerStartHandler`, `Encoding\PushMessageSerializer`, `Entities\PushMessages\{IMessage, Message}`, `Controllers\IWampApplication` and `WampApplication::handlePush()` with `$onPush` and `PushEvent`. | The measurement (T2 and escalation **X1**). |
| D15 | §1.15: "`tools/di-snapshot.php` covers **38** containers" | **47** recorded, 46 of which compile. The per-test overlays added since E4 account for the difference. | The measurement. |
| D16 | §16: "`git grep -i ipub -- src/FastyBird/Core` prints nothing" | **This cannot hold.** `IdentifierGuardTest.php:86-87` holds `'ipub'` and `'iPublikuj'` **as the guard's denylist**, and they must stay. The other hits are a code comment (`WebSocketsExtension.php:283`), `Core/Core/docs/Home.md`, and the 4 T9 sites. | The criterion needs a path exclusion (escalation **X8**). |
| D17 | #640 lists `WebSocketsExtension` among the `Configuration` consumers | `Configuration` is referenced only by `DI/CoreExtension.php`, `Persistence/Mapping/Driver/Timestampable.php`, `Security/Presenters/HasAuthorization.php` and `CoreExtensionTest`. **6 of its 10 public getters are never called** (`getTokenIssuer`, `getTokenSignature` and the four `isEnable*()`). | The measurement (T10). |
| D18 | §1.5: three locators. "If E5.1's graph analysis shows a cycle, … lazy" | **A real cycle exists for all three** in 32 of the 46 compiled containers, production included. `SchemaContainer` reaches each module schema, then `fbCore.http.routing.router`, then the router's setups (the `JsonApiMiddleware` middleware, and the module route services, then controllers, then `Builder`/`Hydrators\Container`). | The measurement (T7). `lazy` is required, not optional. |
| D19 | §1.6/§1.12/§3.6: "`Nette\Security\User` … the security identity Devices' and Ui's role enforcement relies on (#543)"; the Compat shim "never declares anything, because nette/security is installed" | **nette/security is not installed** (absent from `composer.lock`; the class does not resolve in the application image), and the Compat shim is never autoloaded (T2 row 12). `Nette\Security\User` is therefore an **undefined class**, named in 4 type positions: `Controllers\Controller::getUser()`, `Entities\Client::$user`, `Entities\ConnectedClient::setUser()`/`getUser()`, and an `instanceof` in `Clients\Storage:78`. Devices' and Ui's `ExchangeV1` enforce roles through `ConnectedClient::getRoles()` against `Constants::ROLE_MANAGER`/`ROLE_ADMINISTRATOR`, and **neither calls `getUser()`** (`git grep`). `ControllerTest`'s own docblock records that no `Nette\Security\User` service exists. | The measurement. §3.6's "`Nette\Security\User` stays" stands, since nothing is replaced, but the reason given is wrong. See escalation **X10**. |
| D20 | §3.5 and #635: "`Controller::$context` and its **constructor** parameter" | The constructor takes no arguments. `$context` is the first parameter of **`injectPrimary()`**, which `nette.inject` calls with `@container` (compiled setup, T2 row 25). | The code. #635 removes an `injectPrimary()` parameter, and `ControllerTest` calls it positionally. |
| D21 | §1.14 (the test net's "§14.2 production-image checks: … WAMP subscribe round trip") and §14.2 ("A WAMP subscribe round trip receives an exchange message pushed through `SocketsBridge`") assume the round trip **works today** | **It cannot work today.** `Wamp\RouteList::$cachedRoutes` is declared `private array $cachedRoutes;` (`RouteList.php:24`), typed and never initialised. `constructUrl()` reads it with `$this->cachedRoutes === null` (`RouteList.php:63`) before `warmupCache()` ever assigns it (`:135`). Reading an uninitialised typed property throws `Error`, so every `LinkGenerator::link()` that reaches the router's `constructUrl()` throws. All three `SocketsBridge`s call `link()` inside a `try … catch (Throwable)`: Devices `SocketsBridge.php:114/142`, Ui `:115/143`, DevicesModuleUiModule `:179/207`. So they log and **never broadcast**. Separately, Ui's `SocketsBridge` links to `'DevicesModule:Exchange:'` (`:115`), not to a Ui destination. The DevicesModuleUiModule bridge links to `'UiModule:Exchange:'`. #648's characterization tests established this, and the code confirms it. | The code. This is **not an E5 design decision**, so there is no X item. It is a pre-existing defect, tracked on #625 ([root cause](https://github.com/FastyBird/miniserver/issues/625#issuecomment-5977634701)), which the orchestrator schedules after #648 and **before #637's §14.2 check**. Until it is fixed, the §14.2 WAMP round trip cannot pass on any E5 PR, and an E5 PR must not "fix" it in passing. T7 notes it; T12 row 10 and the X4 note depend on it. |
| D22 | §1.4: "`FrozenClock` returns `clone` of the value it was constructed with, so it is mutable when it was built from a `DateTime`" | `FrozenClock`'s constructor converts a `DateTime` with `DateTimeImmutable::createFromMutable()` and stores a `DateTimeImmutable $dt` (`Clock/FrozenClock.php`). `getNow()` returns `clone $this->dt`, which is always immutable. #648's T12-21 test pins it. | The code. #641's `FrozenClock` change is a type change only (`now(): DateTimeImmutable`), with no behaviour change. |

Everything else in §1 that T1–T12 touches was re-measured and **agrees**:

- the 48 + 2 prefixed types;
- `Arrays::invoke` 21×;
- 64 constants, of which the 32 identities;
- the five Api `Objects` counts (32/35/35/31/9);
- `IRouteCollector` and `IRouteGroup` in 7 files, `IEntityCrud` in 25;
- PHP 8.5 blocked by exactly `lcobucci/clock` and 9 `orisai/*` packages;
- `Controller::$context` never read.

### 0.2 Proposed decisions P1–P7 (Epic §17), restated with the evidence

| ID | Decision (as proposed) | Evidence for | Evidence against / caveat |
|---|---|---|---|
| P1 | `ArrayHash` stays out of E5 | Not re-measured, because no E5 table depends on it. | None. |
| P2 | No PSR-11 adapter. The 3 locators become injection, `lazy` where needed. `ControllerFactory` keeps the Nette container. | `Nette\DI\Container` is not PSR-11. `ControllerFactory` uses `findByTag`/`createInstance`/`callInjects`. | None against. **`lazy` is needed for all three** (D18). One lazy definition, `fbCore.api.schemas.container`, breaks all three cycles (T7). Whether nette/di 3.2.7 runs `setup` on a lazy service is Epic assumption 3, and E5.7 must prove it. |
| P3 | PHP 8.5 = PHPStan 8.4–8.5 + a `PHP 8.5 (Core)` job + **`lcobucci/jwt` 5** | **`lcobucci/jwt` 5.6.0** (2025-10-17) requires `php: ~8.2.0 \|\| ~8.3.0 \|\| ~8.4.0 \|\| ~8.5.0` and `psr/clock: ^1.0`. `lcobucci/clock` becomes a dev-only dependency (T11). | The upgrade **silently breaks `TokenBuilder`**: the v5 `Builder` is immutable, and `TokenBuilder` discards the return value of every builder call. **`setValidationConstraints()` is deprecated since 5.5**, and `phpstan-deprecation-rules` is installed (T11). Both are mechanical to fix, and are listed so #643 cannot miss them. |
| P4 | Accessors: the census's get/set pairs, minus entities, mapped objects and builders | 41 pairs measured (T6). | **"6 `MappedObject` pairs" is empty** (D12). The pairs that actually need excluding are entity **traits** (`HasEntityCreated`, `HasEntityUpdated`, `HasOwner`), a third-party contract (`Casbin\Adapter::isFiltered`, required by `FilteredAdapter`), and builder usage (`Route::setName`). T6 marks each one. |
| P5 | Agent string `FastyBird/WebSockets/1.0.0` | No module, frontend or test reads `ServerRuntime::VERSION` or `X-Powered-By` (`git grep`). | None. |
| P6 | Typed configuration objects are in scope | 8 configurable children, 32 structures, 55 leaves (`schema.php`). | None. The scale is 32 classes (T10). |
| P7 | Dead hooks are deleted, not turned into events | The 9 arrays are assigned nowhere: no assignment outside the declaring file in the repository, by `git grep` plus the `calls.php` survey. | **Three more hooks are wired but never fire in production** (D2). P7 as written does not cover them. Escalation **X2** decides them. |

### 0.3 Escalations: decided by merging, default first

Each item below is a place where the evidence contradicts or extends an Epic §3 decision. The
**default** is what the later PRs implement if the maintainer merges without comment. The
maintainer can override it in the review.

- **X1. The server-push pipeline is dead, not only `IConsumer`** (§3.1 "delete `IConsumer`'s
  collection point").
  - **Evidence.**
    - `PushMessages\Consumer` and `Pusher` are `abstract` and have no subclass anywhere.
    - No container has a service of type `IConsumer` (`di.py services`).
    - So `ConsumersRegistry` is always empty, and `OnServerStartHandler` iterates nothing.
    - `IWampApplication`'s only member, `handlePush()`, is called only by `WampApplicationTest`.
      So `$onPush` never fires and `PushEvent` is never dispatched in production.
    - `PushMessageSerializer` and `Entities\PushMessages\Message` are used only by `Pusher`.
    - The WAMP round trip that production relies on goes through each module's `SocketsBridge`,
      then `Topics\Storage`, then the topic's broadcast. It touches none of this.
  - **Option A (default).** #635 deletes the whole pipeline. That is the 9 types in T2 rows
    15–23, `WampApplication::handlePush()`/`$onPush`/`PushEvent`, the DI services
    `fbCore.webSockets.wamp.pushRegistry`, `.wamp.serializer` and
    `.wamp.subscribers.onServerStart`, and the 2 setups that wire them. That removes 1 test,
    `WampApplicationTest::testOnPushFiresRegisteredHandlerWithMessageProviderAndTopic`.
  - **Option B.** Delete only `Consumer`, `Pusher`, `IConsumer`, `IPusher` and the `findByType`
    loop. Keep the registry, the handler and `handlePush`, and collapse
    `IConsumersRegistry`/`IWampApplication`/`PushMessages\IMessage` in #637.
- **X2. `onStart`/`onStop` fire only when somebody calls `ServerRuntime::run()`/`stop()`**, and
  production never does (D2).
  - **Default.** They become the events `ServerStarted`/`ServerStopped`. `run()`/`stop()` are
    public API, and the hooks are live code on those paths. Under X1-A nothing listens to
    `ServerStarted` any more.
  - **Option.** Delete `run()`, `stop()` and both hooks (P7-style). `ServerTest` loses 2 tests.
- **X3. K1/K2 satisfied only through subclasses of the sole concrete class** (`IRouter`,
  `IProtocol`; D4).
  - **Evidence.**
    - Every extra implementer `extends` the concrete: `ServerRouter`, HomeKit's and NsPanel's
      `Router` extend `Router`; `HyBi10` extends `RFC6455`. None implements the interface
      independently.
    - `IRouter` is not a DI substitution point. It has exactly **1 autowired candidate**,
      `fbCore.http.routing.router`, in all 46 compiled containers. HomeKit's and NsPanel's
      routers are registered **non-autowired** under their own type (`$wiring[IRouter][2]`).
  - **Proposed refinement R1 (default).** "An implementation that extends the sole other
    implementation does not count towards K1/K2." Both collapse into their concrete class, which
    stays non-final because it is extended.
  - **Option.** Keep both as role-named interfaces: `IRouter` → `RequestRouter`,
    `IProtocol` → `Protocol`.
- **X4. The hook order differs between production and the package test containers** (T3).
  - **Evidence.**
    - In production, `fbCore`'s `beforeCompile()` runs first. So `ServerRuntime::onCreate` holds,
      in order: the `CreateEvent` dispatch, then the Devices, Ui and DevicesModuleUiModule
      `SocketsBridge` enablers.
    - In 6 package test containers, the package's own extension compiles before `fbCore` (E4
      finding F1), so its enabler comes **first**.
    - `Exchange\Consumers\Container::enable()` moves the consumer to the end of its
      `SplObjectStorage`, so the **enable order is the consume order**. It is observable.
    - **In the same 6 containers the first hook throws today.** The package's own
      `SocketsBridge` service is **never registered**, but its `onCreate[]` enabler is.
      `python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base enablers` lists the
      containers:
      - `test/Module/Devices` and its 2 overlays;
      - `test/Module/Ui` and its 1 overlay;
      - `test/Bridge/DevicesModuleUiModule`, for the bridge's own `SocketsBridge` only (Devices'
        and Ui's are registered there).

      The cause is the same E4 finding F1. Each module registers its bridge in
      `loadConfiguration()` only when `findByType(LinkGenerator)` and `findByType(Topics\IStorage)`
      are non-empty, as at `DevicesModuleUiModuleExtension.php:160-168`. That runs before `fbCore`
      has registered either. The enabler is added in `beforeCompile()`, unconditionally. It is
      the first `onCreate` setup in those containers, so `ServerRuntime::create()` throws
      `Exceptions\InvalidArgument` ("Provided consumer is not registered in container and can
      not be enabled") on its first hook there. In the other 40 compiled containers, production
      included, every consumer that is enabled is registered: 19 have enablers and 21 have none.
  - **Default.** The listener priorities in T3 reproduce **production's** order everywhere. The
    test containers' order then changes. The only container where the relative order of two
    enablers changes is `test/Bridge/DevicesModuleUiModule`: today DevicesModuleUiModule,
    Devices, Ui; afterwards Devices, Ui, DevicesModuleUiModule. #638 declares that change.
  - **#638 must also declare the 6 containers above.** A module's `ServerCreated` listener
    that enables an unregistered consumer throws exactly as the `onCreate[]` closure does today.
    The behaviour is preserved: #634 pins the throw, and #638 does not fix it. Registering the
    listener only when its `SocketsBridge` is registered would be a behaviour change, which is
    an escalation, not part of #638.
- **X5. WAMP closure routes cannot dispatch today** (T9).
  - **Evidence.**
    - `WampRoute` gives a closure route the controller `'IPub:WebSocket'`.
    - `Application::processMessage()` first calls
      `ControllerFactory::getControllerClass('IPub:WebSocket')`. That maps the name through its
      `'*'` mask to `IPubModule\WebSocketController`, a class that exists nowhere. So
      `getControllerClass()` throws **`Exceptions\InvalidController`** ("Cannot load controller
      …, class … was not found", `ControllerFactory.php:112-116`).
    - So the `is_subclass_of(…, RequestController)` check that would throw `BadRequest` is
      **never reached**. #648's characterization test established this, and the code confirms it.
    - No configuration in the repository defines a closure route.
  - **Default.** T9 renames the strings only. #634's closure-route test pins today's outcome
    (the `InvalidController`) and labels it as a known defect.
  - **Option.** Delete closure-route support in #637.
- **X6. Typed configuration needs 32 classes, not 11** (T10). The default is the naming rule in
  T10.
- **X7. `UTCDateTime` deletion edits `config/common.neon`** (T2 row 13). §7 says
  `config/*.neon` is unchanged, but §6 anticipates deleting it "unless T2 proves it dead and no
  mapping uses it", and T2 proves exactly that. **Default:** delete it, together with the 6 NEON
  `utcdatetime` registrations listed in T2.
- **X8. DoD `git grep -i ipub -- src/FastyBird/Core`** (D16). **Default:** the check becomes
  `git grep -i ipub -- src/FastyBird/Core ':!**/IdentifierGuardTest.php'`. #637 fixes the
  comment at `WebSocketsExtension.php:283` and `Core/Core/docs/Home.md`.
- **X9. Deleting `IFormatter` touches `Helpers\Console`** (T2 row 3).
  - **Evidence.** `Console::$formatter` is a typed property that nothing ever sets:
    `setFormatter()` has no caller. Every log method reads it, so `Console` would throw
    `Error: must not be accessed before initialization` on its first use.
  - `Console` is registered only when no `LoggerInterface` exists, which is true of none of the
    46 containers.
  - **Default.** #635 removes `setFormatter()`, the property and the `if ($this->formatter)`
    branches. That leaves the existing `echo` fallback, which fixes a latent fatal on a path no
    container compiles.
- **X10. `Nette\Security\User` is an undefined class** (D19).
  - **Evidence.** `Controller::getUser()` always throws `InvalidState`, because no such service
    can exist. `ConnectedClient::setUser()` has no caller. `Clients\Storage:78`'s
    `instanceof Nette\Security\User` is always false.
  - **Default.** E5 leaves these 4 signatures unchanged (Epic §3.6). T6 excludes pair 35. Only
    the never-loaded shim is deleted.
  - **Option.** #635 also deletes `Controller::getUser()`, `ConnectedClient::setUser()`/
    `getUser()`, `Client::$user` and the dead `Storage` branch. That is dead API on a type that
    does not exist; `ControllerTest::testGetUserThrowsInvalidStateWhenNoUserServiceWasInjected`
    would be removed with it.
  - **Resolved by #650: option D + fail-closed guard.** The default was overridden. E5.3a (#652)
    deletes the shim and the dead API together: `Controller::$user`, the `injectPrimary()`
    parameter `$user` and `getUser()`, `ConnectedClient::setUser()`/`getUser()`, `Client::$user`
    with `setUser()`/`getUser()`, and the `Storage` log key and branch. `checkRequirements()` stays
    and throws `CoreExceptions\InvalidState` itself for `@User(loggedIn)`, so the annotation still
    fails closed with the same exception class; every other `@User` value stays a no-op.

---

## T1. The 50 prefixed types

**Columns.**

- **Impl. (prod / test):** transitive implementers by FQCN; `[ext]` means non-final and extended.
- **Files (Core src / Core tests / outside src / outside tests):** distinct files that reference
  the FQCN, in code or in a docblock type, outside the declaring file.
- **K-evidence:** the K1–K4 checks. **cfg** means selected by configuration.
- **DI:** `getByType`/`findByType`/`setType`/`setImplement` uses outside tests, plus the
  compiled `$wiring`.

`setImplement` is used for none of the 50, and no type is named in any NEON, Latte, XML, JSON or
YAML file (`t1.py`, a grep over those formats).

**Measured by.** `python3 tools/census/e5/t1.py /tmp/e633/refs.json` and `t1rows.py`, `q.py`,
and `di.py wiring` for the DI column.

**Decision rule.** §3.1 K1–K4, plus R1 (X3). Under R1 every type collapses into its concrete
class, except the two config-selected drivers. Every concrete class that receives a collapse is
**already `final`**, except `Router` and `RFC6455`, which stay non-final because they are
extended. **No class becomes `final`, and none loses `final`.**

### T1.a Api (16): all collapse (#636)

All 16 have one production implementer and no test implementer. None has a K reason (no
`findByType`, no tag, not config-selected, no third-party contract), and none has a DI wiring
entry: these are value objects, built with `new`.

| Interface (`FastyBird\Core\Api\Encoding\…`) | Collapse into | Files C-src / C-test / out-src / out-test |
|---|---|---|
| `IDocument` | `Document` | 11 / 2 / 8 / 1 |
| `Objects\IErrorObject` | `Objects\ErrorObject` | 4 / 0 / 0 / 0 |
| `Objects\IErrorObjectCollection` | `Objects\ErrorObjectCollection` | 3 / 0 / 0 / 0 |
| `Objects\ILinkObject` | `Objects\LinkObject` | 5 / 0 / 0 / 0 |
| `Objects\ILinkObjectCollection` | `Objects\LinkObjectCollection` | 9 / 0 / 0 / 0 |
| `Objects\IMetaObject` | `Objects\MetaObject` | 6 / 0 / 0 / 0 |
| `Objects\IMetaObjectCollection` | `Objects\MetaObjectCollection` | 11 / 0 / 0 / 0 |
| `Objects\IRelationshipObject` | `Objects\RelationshipObject` | 4 / 0 / 35 / 0 |
| `Objects\IRelationshipObjectCollection` | `Objects\RelationshipObjectCollection` | 4 / 0 / 0 / 0 |
| `Objects\IResourceIdentifierCollection` | `Objects\ResourceIdentifierCollection` | 3 / 0 / 1 / 0 |
| `Objects\IResourceIdentifierObject` | `Objects\ResourceIdentifierObject` | 7 / 0 / 31 / 0 |
| `Objects\IResourceObject` | `Objects\ResourceObject` | 6 / 0 / 0 / 0 |
| `Objects\IResourceObjectCollection` | `Objects\ResourceObjectCollection` | 4 / 0 / 35 / 0 |
| `Objects\ISourceObject` | `Objects\SourceObject` | 3 / 0 / 0 / 0 |
| `Objects\IStandardObject` | `Objects\StandardObject` | 30 / 1 / 30 / 2 |
| `Objects\IStandardObjectCollection` | `Objects\StandardObjectCollection` | 3 / 0 / 0 / 0 |

The collections' and `IStandardObject`'s parent interfaces (`IteratorAggregate`, `Countable`,
`Traversable`, `JsonSerializable`) move onto the concrete class's `implements`. `StandardObject`
must keep `IteratorAggregate`, because `Traversable` cannot be implemented directly.

### T1.b Http (8): all collapse (#636), `IRouter` under R1

| Interface (`FastyBird\Core\Http\…`) | Impl. prod | K-evidence | DI / outside | Decision |
|---|---|---|---|---|
| `Controllers\IControllerResolver` | `ControllerResolver` | none | — / 0 | collapse into `Controllers\ControllerResolver` |
| `Middleware\IMiddlewareDispatcher` | `MiddlewareDispatcher` | none. It extends PSR-15 `RequestHandlerInterface`, which is K4 for **that** interface only, and that stays on the concrete. | — / 0 | collapse into `Middleware\MiddlewareDispatcher` (keeps `implements RequestHandlerInterface`) |
| `Routing\Handlers\IRequestHandler` | `RequestHandler` | none; it extends the role-named `Handlers\Handler` | — / 0 | collapse into `Routing\Handlers\RequestHandler` (keeps `implements Handler`) |
| `Routing\IRoute` | `Route` | none | — / 0 | collapse into `Routing\Route` |
| `Routing\IRouteCollector` | `RouteCollector` | none | — / 7 src | collapse into `Routing\RouteCollector` |
| `Routing\IRouteGroup` | `RouteGroup` | none | — / 7 src | collapse into `Routing\RouteGroup` |
| `Routing\IRouteParser` | `RouteParser` | none | — / 0 | collapse into `Routing\RouteParser` |
| `Routing\IRouter` | `Router` [ext], `ServerRouter` (final, extends `Router`), HomeKit and NsPanel `Router\Router` (non-final, extend `Router`) | K1/K2 only through subclasses (X3). `$wiring[IRouter]` is `[0 => [fbCore.http.routing.router]]` in all 46 containers; HomeKit/NsPanel routers appear only in `[2]` (not autowired). | `getByType(IRouter)` in the 4 module `Router\Validator`s (Accounts:62, Devices:63, Triggers:63, Ui:63) and in `tests/cases/application/bootstrap-routes.php:137`; 50 outside src / 49 outside tests | **collapse into `Routing\Router`** (R1). `Router` stays non-final. *If R1 is rejected:* keep as **`RequestRouter`**. |

### T1.c Persistence (3 interfaces + 1 trait)

| Type (`FastyBird\Core\Persistence\…`) | Impl. | Evidence | Decision |
|---|---|---|---|
| `Crud\IEntityCrud` | `EntityCrud` (final) | no K. Outside: 25 src files. | collapse into `Crud\EntityCrud` (#636) |
| `Mapping\IEntityMapper` | `EntityMapper` (final) | no K. `$wiring[IEntityMapper] = [2 => [fbCore.persistence.entity.mapper]]` (not autowired, findByType only) in 42 containers. | collapse into `Mapping\EntityMapper` (#636) |
| `Entities\IEntityRemoved` | none | 0 references | **delete** (T2, #635) |
| `Entities\TEntityRemoved` | none | 0 users | **delete** (T2, #635) |

### T1.d Phone (1 trait)

| Type | Evidence | Decision |
|---|---|---|
| `FastyBird\Core\Phone\Entities\TPhone` | 0 users | **delete** (T2, #635) |

### T1.e Security (1)

| Type | Impl. | Evidence | Decision |
|---|---|---|---|
| `FastyBird\Core\Security\Identity\IUserStorage` | `UserStorage` (final) | No K. It is referenced by `Identity\User`, `Subscribers\User` and `AnnotationCheckerTest`. `$wiring[IUserStorage] = [[fbCore.security.userStorage]]`, the same single service as `$wiring[UserStorage]`, in 41 containers. No NEON or extension registers another storage, and nothing is configured. **It is not a substitution point.** | collapse into `Identity\UserStorage` (#636) |

### T1.f WebSockets (20)

| Type (`FastyBird\Core\WebSockets\…`) | Impl. prod / test | K-evidence and DI | Outside files src/tests | Decision |
|---|---|---|---|---|
| `Clients\Drivers\IDriver` | `InMemory` / `DummyClientsDriver` | **K3, config-selected**: `fbCore.webSockets.storage.clients.driver` (#565). `$wiring` holds `[memory, myClientsDriver]` in 3 overlay containers. | 0 / 0 | **keep as `Clients\Drivers\Driver`** (#637) |
| `Topics\Drivers\IDriver` | `InMemory` / `DummyTopicsDriver` | **K3, config-selected**: `…storage.topics.driver`. `$wiring` holds `[myTopicsDriver]` in 2 overlays. | 0 / 0 | **keep as `Topics\Drivers\Driver`** (#637) |
| `Clients\IStorage` | `Storage` (final) | none | 0 / 2 | collapse into `Clients\Storage` |
| `Topics\IStorage` | `Storage` (final) | `findByType(Topics\IStorage)` is an **existence check**, not a collection, in `DevicesExtension.php:924`, `UiExtension.php:499` and `DevicesModuleUiModuleExtension.php:162`. It becomes `findByType(Topics\Storage::class)`. | 6 / 2 | collapse into `Topics\Storage` |
| `Controllers\IControllerFactory` | `ControllerFactory` (final) / **an anonymous class in `WampApplicationTest.php:52`** | Registered `setType(IControllerFactory)`, `WebSocketsExtension.php:120`. The modules call `getDefinitionByType(IControllerFactory)` at `DevicesExtension.php:1083` and `UiExtension.php:585`; grep-located, because the `::class` sits on its own line, which `t1.py`'s same-line DI pattern misses. One autowired candidate. **The test seam is a test double, so not K** (§3.1); `bypass-finals` lets the test mock `ControllerFactory`. | 2 / 3 | collapse into `Controllers\ControllerFactory`. The service type changes from `IControllerFactory` to `ControllerFactory`; the name is unchanged. |
| `Controllers\IWampApplication` | `WampApplication` (final) | Its only member is `handlePush()`. It is used only by the push pipeline (X1). | 0 / 0 | **X1-A: delete** (#635). *X1-B: collapse into `Controllers\WampApplication`.* |
| `Encoding\IFrame` | `RFC6455\Frame` (final) | none | 0 / 0 | collapse into `Encoding\RFC6455\Frame` (keeps `implements FrameData`) |
| `Encoding\IMessage` | `RFC6455\Message` (final) | none | 0 / 0 | collapse into `Encoding\RFC6455\Message` (keeps `implements FrameData`) |
| `Encoding\IProtocol` | `RFC6455` [ext], `HyBi10` (final, extends `RFC6455`) | K1 only through a subclass (X3) | 0 / 0 | **collapse into `Encoding\RFC6455`** (R1). *If R1 is rejected: keep as **`Encoding\Protocol`**.* |
| `Encoding\IValidator` | `Validator` (final) | none | 0 / 0 | collapse into `Encoding\Validator` |
| `Entities\IWampClient` | `WampClient` (final) | none; it extends the role-named `ConnectedClient` | 0 / 0 | collapse into `Entities\WampClient` |
| `Entities\IWebSocket` | `WebSocket` (final) | none | 0 / 0 | collapse into `Entities\WebSocket` |
| `Entities\PushMessages\IMessage` | `PushMessages\Message` (final) | push pipeline only (X1) | 0 / 0 | **X1-A: delete** (#635). *X1-B: collapse.* |
| `Entities\Topics\ITopic` | `Topic` (final) | none | 2 / 0 | collapse into `Entities\Topics\Topic` |
| `Handshake\IRequest` | `Request` (final) | It extends `Nette\Http\IRequest`. That is K4 for **Nette's** interface, which stays on `Request`. `IRequest` declares no constant of its own; `IRequest::GET` and the like are inherited from Nette's, so they resolve on `Request` unchanged. | 0 / 0 | collapse into `Handshake\Request` |
| `Handshake\IResponse` | `WampResponse` (final) | none. Its typed status constants (the `S…` codes used by `Wrapper`, `S101_SWITCHING_PROTOCOLS`, `S413_REQUEST_ENTITY_TOO_LARGE`, `S500_INTERNAL_SERVER_ERROR`, …) move to `WampResponse` with their values. | 0 / 0 | collapse into `Handshake\WampResponse` |
| `Helpers\Formatter\IFormatter` | `Formatter\Symfony` (dead) | none | 0 / 0 | **delete** (T2, X9) |
| `PushMessages\IConsumer` | `Consumer` (abstract, no subclass) | `findByType(IConsumer)` (`WebSocketsExtension.php:417`) collects **0 services in all 46 containers** | 0 / 0 | **delete, with its collection loop** (T2, #635) |
| `PushMessages\IConsumersRegistry` | `ConsumersRegistry` | always empty (X1) | 0 / 0 | **X1-A: delete** (#635). *X1-B: collapse.* |
| `PushMessages\IPusher` | `Pusher` (abstract, no subclass) | none | 0 / 0 | **delete** (T2, #635) |

### T1 totals

| Outcome | Count | Where |
|---|---|---|
| collapse | **39** | 16 Api, 8 Http, 2 Persistence, 1 Security in #636 (= 27, as the Epic expected); 12 WebSockets in #637 |
| keep, role-named | **2** | `Clients\Drivers\Driver`, `Topics\Drivers\Driver` (#637) |
| delete | **9** | `IEntityRemoved`, `TEntityRemoved`, `TPhone`, `IFormatter`, `IConsumer`, `IPusher`, `IConsumersRegistry`, `IWampApplication`, `PushMessages\IMessage` (#635, X1-A) |

**About the names.** `Driver` follows the precedent of the role-named interfaces E3 kept:
`Exchange\Consumers\Consumer`, `Http\Routing\Handlers\Handler` and `Security\Access\Checker`.
Neither namespace declares a concrete class named `Driver` (`decls`). The one file that imports
both namespaces, `WebSocketsExtension`, already aliases them as `ClientsDrivers` and
`TopicsDrivers`. No `I`, no suffix, no clash.

---

## T2. Dead code

**Measured by.**

- **References:** `python3 tools/census/e5/t2.py /tmp/e633/refs.json`. That finds every Core
  type with no FQCN reference outside its own file, in code or docblock, `tools/` excluded. It
  also finds PHP string literals naming the type, and a grep for the FQCN in tracked
  NEON/Latte/XML/JSON/YAML.
- **DI services:** `di.py services`.
- **Latte:** **0 `.latte` files are tracked** (`git ls-files '*.latte'`).

**Result.** The search over all 422 Core types finds exactly **14** with no reference. That is
the Epic §1.12 list. The measurement adds the transitive and pipeline rows below.

| # | Type / member | Evidence | DI services removed | Decision (#635) |
|---|---|---|---|---|
| 1 | `Caching\MemoryAdapterStorage` | 0 PHP refs, 0 strings, 0 NEON | none | **delete** |
| 2 | `Caching\MemoryStorage` | Referenced only by row 1 | none | **delete** (transitive) |
| 3 | `WebSockets\Helpers\Formatter\Symfony` + `IFormatter` | `Symfony`: 0 refs. `IFormatter`: referenced only by `Symfony` and by `Helpers\Console::$formatter`/`setFormatter()`, and `setFormatter()` has **no caller** (`calls.php setFormatter`). | none | **delete** both. Also remove `Console::setFormatter()`, `$formatter` and its branches (X9). |
| 4 | `WebSockets\Exceptions\WampNotImplemented` | 0 refs | none | **delete** |
| 5 | `Http\ScalarEntity` | 0 refs | none | **delete** |
| 6 | `Http\Routing\Handlers\RequestResponseArgsHandler` | 0 refs; no route strategy names it | none | **delete** |
| 7 | `Values\Transformers\DataTypeTransformer` | 0 refs | none | **delete** |
| 8 | `Security\Latte\AccessExtension` | **Confirmed dead.** 0 PHP refs, 0 strings, 0 NEON (no `latte: extensions:`), and no `addExtension` setup in any compiled container. **0 Latte templates exist**, so nothing uses its tags (`{ifAllowed}`, `n:allowedHref`, `n:elseAllowed`). | none | **delete** |
| 9 | `Security\Latte\Nodes\{IfAllowedNode, NElseAllowedNode, AllowedHrefNode}` | Referenced only by row 8 (and each other) | none | **delete** (transitive) |
| 10 | `Persistence\Crud\EntityCrudFactory` | **Confirmed dead.** 0 PHP refs and 0 strings. **No container has a service of that type** (`di.py services`). DI registers `Crud\CrudFactory` (`fbCore.persistence.crud`), a different class. | none | **delete** |
| 11 | `Presenters\DefaultPresenter` | **Refuted: it is live by string.** `CoreExtension.php:412-413` maps `'App' => 'FastyBird\Core\Presenters\*Presenter'`, and `Presenters\AppRouter::createRouter()` routes `/` to presenter `Default`. `BasePresenterTest` uses it. Deleting it would touch the deliberate `GET /` gap (CLAUDE.md trap). | — | **keep** |
| 12 | `WebSockets/Compat/User.php` (declares `Nette\Security\User` under `if (!class_exists(...))`) | **The file is never loaded.** Core's only autoload rule is PSR-4 `FastyBird\Core\` → `src/`, which can never resolve `Nette\Security\User`, and no classmap or `files` entry names it. **nette/security is not installed either** (not in `composer.lock`; `new ReflectionClass('Nette\Security\User')` throws in the application image). The Epic's reason, "nette/security is installed", is wrong (D19); the conclusion, dead, is right. | none | **deleted in E5.3a (#650 option D)**, with the API typed against it (X10) |
| 13 | `Persistence\Types\UTCDateTime` | **Confirmed dead as a type.** 0 PHP refs. The literal `'utcdatetime'` occurs only in its own `UTC_DATETIME` constant. No entity, attribute, XML mapping or migration uses the type: `git grep -i utcdatetime` hits only the class, NEON, and docs/plans. It is live **only as a registration**, in these 6 NEON places: `config/common.neon:160`, `tests/config/dbal-test-connection.neon:40` and the 4 module `config/example.neon` (Accounts:44-45, Devices:43-44, Triggers:53-54, Ui:43-44). | none (a DBAL type map entry, not a service) | **delete the class and the 6 registrations** (X7). `orm:schema-tool:update --dump-sql` must stay empty. |
| 14 | `Persistence\Entities\IEntityRemoved`, `TEntityRemoved` | 0 refs / 0 users | none | **delete** |
| 15 | `Phone\Entities\TPhone` | 0 users | none | **delete** |
| 16 | `WebSockets\PushMessages\Consumer` (abstract) | 0 refs, 0 subclasses | none | **delete** |
| 17 | `WebSockets\PushMessages\Pusher` (abstract) | 0 refs, 0 subclasses | none | **delete** |
| 18 | `WebSockets\PushMessages\IConsumer` + the `findByType(IConsumer)` loop (`WebSocketsExtension.php:413-419`) | The only implementer is row 16. The loop adds 0 setups in all 46 containers. | none (the loop only adds setups) | **delete** (X1, either option) |
| 19 | `WebSockets\PushMessages\IPusher` | the only implementer is row 17 | none | **delete** |
| 20 | `WebSockets\PushMessages\ConsumersRegistry` + `IConsumersRegistry` | always empty | `fbCore.webSockets.wamp.pushRegistry` (46 containers) | **X1-A: delete** |
| 21 | `WebSockets\Subscribers\OnServerStartHandler` + its setup `$service->onStart[] = @…onServerStart` (`WebSocketsExtension.php:421-424`) | iterates the always-empty registry; also never invoked in production (D2) | `fbCore.webSockets.wamp.subscribers.onServerStart` (46) | **X1-A: delete** |
| 22 | `WebSockets\Encoding\PushMessageSerializer` | used only by row 17 | `fbCore.webSockets.wamp.serializer` (46) | **X1-A: delete** |
| 23 | `WebSockets\Entities\PushMessages\{IMessage, Message}`, `Controllers\IWampApplication`, `WampApplication::handlePush()` + `$onPush` + `Events\PushEvent` + the `onPush` bridge setup (`WebSocketsExtension.php:404-410`) | `handlePush()` is called only by `WampApplicationTest` | none (one setup) | **X1-A: delete**. Removes 1 test (X1). |
| 24 | The 9 dead callback arrays (T3 rows 14–22) and their 7 `Arrays::invoke()` calls | assigned nowhere in the repository | none | **delete** (P7) |
| 25 | `WebSockets\Controllers\Controller::$context` and the `$context` parameter of `injectPrimary()` | **Confirmed never read.** `private`, assigned once (`Controller.php:110`, inside `injectPrimary()`, **not the constructor**, which takes no arguments), read nowhere in the class, and a private property cannot be read elsewhere. Devices' and Ui's `ExchangeV1` do not override `injectPrimary()` (`git grep`). The compiled `nette.inject` setup passes it `@container` today: `fbUiModule.controllers.exchange` and `fbDevicesModule.controllers.exchange` both have `injectPrimary(@container, @…controllers.factory, @…routing.router, @…routing.generator)`. | none (one setup argument per controller) | **delete**. #635's text says "constructor parameter"; it is the first `injectPrimary()` parameter, and `ControllerTest::testInjectPrimarySucceedsOnceAndRejectsASecondCall` calls it positionally. |
| 26 | `Constants::TOKEN_URI_NAME` | 0 fetches (`consts.php`) | none | **delete** (C5). **Not `ROLE_VISITOR`** (D7). |

**DI change #635 must declare (X1-A):**

- 3 service removals in all 46 containers: `wamp.pushRegistry`, `wamp.serializer` and
  `wamp.subscribers.onServerStart`.
- `ServerRuntime` loses its `onStart[] = @…onServerStart` setup.
- `WampApplication` loses its `onPush` bridge setup.
- Every `Controller` service's `injectPrimary` setup loses its first argument, `@container`.

Nothing else changes.

---

## T3. The callback arrays and today's invocation order

**Measured by.**

- **The arrays:** `members.php callbacks`, Reflection over every class declared under Core's
  `src`. **22 arrays.**
- **Who fires them:** `calls.php run,stop,create,handlePush,handleOpen,…`.
- **The order:** read from **compiled containers**, not inferred:
  `python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base hookgroups`, the setup lists
  in compiled order.

### T3.1 Today's order, from the compiled containers

| Service | Containers | Setups in compiled order (the order closures are appended, which is the order `Arrays::invoke` calls them) |
|---|---|---|
| `fbCore.webSockets.server.runtime` (`ServerRuntime`) | **production, production:dev, production:sentry** | 1 `onCreate` ← dispatch `CreateEvent` · 2 `onStart` ← dispatch `StartEvent` · 3 `onStop` ← dispatch `StopEvent` · 4 `onStart` ← `OnServerStartHandler` · 5 `onCreate` ← enable Devices `SocketsBridge` · 6 `onCreate` ← enable Ui `SocketsBridge` · 7 `onCreate` ← enable DevicesModuleUiModule `SocketsBridge` |
| same | 16 (Addon, Automator/DevicesModule, 4 Bridges, 10 Connectors) | 1–4 as above · 5 enable Devices |
| same | 21 (Core and its 8 overlays, Accounts + 2, Triggers + 1, Automator/DateTime, RedisDbPluginTriggersModule, ApiKey, RabbitMq + 1, RedisDb, RedisDbCache) | 1–4 only |
| same | `test/Module/Devices` (+2 overlays) | 1 enable Devices · 2–5 = dispatch/dispatch/dispatch/handler. **The Devices `SocketsBridge` is not registered here, so setup 1 throws on `create()`** (X4). |
| same | `test/Module/Ui` (+1) | 1 enable Ui · 2–5. **The Ui `SocketsBridge` is not registered, so setup 1 throws** (X4). |
| same | `test/Bridge/DevicesModuleUiModule` | 1 enable DevicesModuleUiModule · 2–5 · 6 enable Devices · 7 enable Ui. **The bridge's own `SocketsBridge` is not registered, so setup 1 throws, and setups 2–7 never run** (X4). |
| `fbCore.webSockets.server.wrapper` (`Wrapper`), all 46 | | 1 `onClientConnected` ← `ClientConnectEvent` · 2 `onClientDisconnected` ← `ClientDisconnectEvent` · 3 `onClientError` ← `ClientErrorEvent` · 4 `onIncomingMessage` ← `IncommingMessageEvent` · 5 `onAfterIncomingMessage` ← `AfterIncommingMessageEvent` · **6 `onClientConnected` ← `ClientConnected`** · **7 `onIncomingMessage` ← `IncomingMessage`** (D3) |
| `fbCore.webSockets.wamp.application` (`WampApplication`, found by `getByType(Controllers\Application)`), all 46 | | 1 `onOpen` ← `OpenEvent` · 2 `onClose` ← `CloseEvent` · 3 `onMessage` ← `MessageEvent` · 4 `onError` ← `ErrorEvent` · 5 `onPush` ← `PushEvent` |

**Where each setup comes from.**

- Setups 1–3 on the runtime, 1–5 on the wrapper, and all 5 on the application come from the
  `WEBSOCKETS` block of `WebSocketsExtension::beforeCompile()`, lines 327–411.
- Runtime setup 4 comes from line 421.
- Wrapper setups 6–7 come from the `WS SERVER PLUGIN` block, lines 445–464.
- The enablers come from each module's `beforeCompile()`: `DevicesExtension.php:1103`,
  `UiExtension.php:605` and `DevicesModuleUiModuleExtension.php:241` (the setup string's line).
- Their position follows the compiler's extension order:
  - production: `fbCore` first, then Devices, Ui, DevicesModuleUiModule, per `config/common.neon`;
  - package test containers: the package's own extension first (E4 finding F1).

**Who listens today** (`git grep 'WebSockets\\Events'` plus `getSubscribedEvents`): **only Core's
`Subscribers\Client`**, on `ClientConnected` and `IncomingMessage`. So in each double-bridged
hook, the event with no listener is dispatched first, then the one `Client` handles.

### T3.2 The 22 arrays

"Fires in production" means a production code path invokes it: `WsServer::execute()` calls
`ServerRuntime::create()`, then `$eventLoop->run()`.

| # | Array | Payload (`Arrays::invoke` arguments, in order) | Assigned by | Fires in production? | Target event (T4) | Listener priorities that reproduce production's order |
|---|---|---|---|---|---|---|
| 1 | `WebSockets\Server\ServerRuntime::$onCreate` | `ServerRuntime $server` | bridge + 3 module enablers | **yes** (`create()`) | `ServerCreated` | Devices enabler **−10**, Ui enabler **−20**, DevicesModuleUiModule enabler **−30**. They run after any default-priority (0) listener, as today's `CreateEvent` listeners would, and in production's enable order (X4). |
| 2 | `ServerRuntime::$onStart` | `LoopInterface $loop, ServerRuntime $server` | bridge + `OnServerStartHandler` | **no**: `run()` is called only by `ServerTest` (X2) | `ServerStarted` | X1-A: no listener. X1-B: `OnServerStartHandler` at **−10**. |
| 3 | `ServerRuntime::$onStop` | `LoopInterface $loop, ServerRuntime $server` | bridge | **no**: `stop()` is called only by `ServerTest` (X2) | `ServerStopped` | — |
| 4 | `Server\Wrapper::$onClientConnected` | `ConnectedClient $client, IRequest $request` | 2 bridges (`ClientConnectEvent`, then `ClientConnected`) | **yes**: `attemptUpgrade()` | `ClientConnected` | `Subscribers\Client::clientConnected` at **−10**, after any default listener, as today's `ClientConnectEvent` listeners ran first. |
| 5 | `Wrapper::$onClientDisconnected` | `ConnectedClient, IRequest` | bridge | **yes**: `connectionClose()` | `ClientDisconnected` | — |
| 6 | `Wrapper::$onClientError` | `ConnectedClient, IRequest`. **No exception is passed.** | bridge | **yes**: `connectionError()` | `ClientFailed` | — |
| 7 | `Wrapper::$onIncomingMessage` | `ConnectedClient, IRequest, string $message` | 2 bridges (`IncommingMessageEvent`, then `IncomingMessage`) | **yes**: `connectionMessage()` | `MessageReceived` | `Subscribers\Client::incomingMessage` at **−10** (same reason as row 4) |
| 8 | `Wrapper::$onAfterIncomingMessage` | `ConnectedClient, IRequest` | bridge | **yes** | `MessageProcessed` | — |
| 9 | `Controllers\Application::$onOpen` | `Application $application, ConnectedClient, IRequest` | bridge on `WampApplication` | **yes**: `WampApplication::handleOpen()`, then `parent::` | `ConnectionOpened` | — |
| 10 | `Application::$onClose` | `Application, ConnectedClient, IRequest` | bridge | **yes** | `ConnectionClosed` | — |
| 11 | `Application::$onMessage` | `Application, ConnectedClient $from, IRequest, string $message` | bridge | **yes** | `ApplicationMessageReceived` | — |
| 12 | `Application::$onError` | `Application, ConnectedClient, IRequest, Throwable $ex` | bridge | **yes**: `Wrapper::connectionError()` | `ApplicationFailed` | — |
| 13 | `Controllers\WampApplication::$onPush` | `IMessage $message, string $provider, ITopic $topic` | bridge | **no**: `handlePush()` is called only by `WampApplicationTest` (X1) | X1-A: **deleted**; X1-B: `TopicPushed` | — |
| 14 | `Security\Identity\User::$onLoggedIn` | `User` | **nobody** | invoked by `login()`, with an empty array | — (P7: **delete**) | — |
| 15 | `User::$onLoggedOut` | `User` | nobody | as above, from `logout()` | — (delete) | — |
| 16 | `Persistence\Crud\Create\EntityCreator::$beforeAction` | `entity, values` | nobody | empty array | — (delete) | — |
| 17 | `EntityCreator::$afterAction` | `entity, values` | nobody | empty array | — (delete) | — |
| 18 | `Persistence\Crud\Update\EntityUpdater::$beforeAction` | `entity, values` | nobody | empty array | — (delete) | — |
| 19 | `EntityUpdater::$afterAction` | `entity, values` | nobody | empty array | — (delete) | — |
| 20 | `Persistence\Crud\Delete\EntityDeleter::$beforeAction` | `entity` | nobody | empty array | — (delete) | — |
| 21 | `EntityDeleter::$afterAction` | none | nobody | empty array | — (delete) | — |
| 22 | `Persistence\Query\QueryObject::$onPostFetch` | — | nobody | **never invoked** | — (delete) | — |

**How listeners register (#638).**

- **Core.** `Subscribers\Client` keeps `getSubscribedEvents()`, with
  `[ClientConnected::class => ['clientConnected', -10], MessageReceived::class => ['incomingMessage', -10]]`.
- **Each module** -- *amended by escalation #658*
  ([resolution](https://github.com/FastyBird/miniserver/issues/658#issuecomment-6048479996)).
  - The census proposed a new `EventSubscriberInterface` service in each module, collected by
    contributte/event-dispatcher by type. contributte is registered only in the 3 production
    containers. Every package test container has Core's fallback dispatcher, which collects nothing,
    so the enablers would stop running in the 19 test containers that run them today, and X4's throw
    would disappear.
  - Instead, Devices, Ui and DevicesModuleUiModule each register, in `loadConfiguration()` and
    unconditionally, one `final` invokable listener, `Subscribers\EnableSocketsBridge`, whose
    `__invoke(ServerCreated)` calls `Exchange\Consumers\Container::enable(<its SocketsBridge>)`.
    It is tagged `WebSocketsExtension::SERVER_CREATED_LISTENER_TAG`
    (`fastybird.core.webSockets.serverCreatedListener`), with the priority of row 1 as the tag
    value, and it does **not** implement `EventSubscriberInterface`.
  - `WebSocketsExtension::beforeCompile()` adds, for each tagged service, an
    `addListener(ServerCreated::class, LazyListener(<service>, '__invoke', <container>), <priority>)`
    setup on the autowired Symfony dispatcher, contributte's in production and Core's fallback
    elsewhere. These are the "listener services and tags" in #638's DI declaration.
- **Why the priorities are explicit.** The Symfony dispatcher runs higher priorities first and
  breaks ties by registration order, and registration order is definition order, which differs
  between containers (X4). Only explicit priorities make the order container-independent.

**Order-dependence that #634's test must pin.** `Container::enable()` re-inserts the consumer at
the end of the `SplObjectStorage`, so the enable order is the order in which the three
`SocketsBridge`s consume each exchange message. In production that is Devices, Ui,
DevicesModuleUiModule.

---

## T4. The 17 event classes and their final names

**Measured by.** `git grep -n 'WebSockets\\Events'`: only `WsServer`, `WebSocketsExtension`,
`Subscribers\Client` and `ClientAuthenticationTest` reference them. The constructors were read
from the classes.

**Naming rule** (conventions → Naming, Epic §3.4): past tense, no `Event` suffix, no clash in
`FastyBird\Core\WebSockets\Events`. `ClientConnected` already exists there and is the merge
target.

**Shape.** Each class follows `Exchange\Events\*`: `final class X extends
Symfony\Contracts\EventDispatcher\Event`, with readonly promoted constructor properties in the
current argument order. That makes today's two non-Symfony classes, `ClientConnected` and
`IncomingMessage` (both `final readonly class` with no base class), stoppable. No listener stops
propagation, so behaviour is unchanged. A `readonly class` cannot extend `Event`, which is why
these lose class-level `readonly`.

| Today | Dispatched by | Payload today | **Final name** | Payload after (typed, current order) |
|---|---|---|---|---|
| `CreateEvent` | `onCreate` bridge | `ServerRuntime $server` | **`ServerCreated`** | `ServerRuntime $server` |
| `StartEvent` | `onStart` bridge | `LoopInterface $eventLoop, ServerRuntime $server` | **`ServerStarted`** | same |
| `StopEvent` | `onStop` bridge | same | **`ServerStopped`** | same |
| `ClientConnectEvent` + `ClientConnected` | `onClientConnected`, bridged twice | both `ConnectedClient $client, IRequest $httpRequest` (identical) | **`ClientConnected`** (one class) | `ConnectedClient $client, Handshake\Request $httpRequest` (`IRequest` collapsed by #637) |
| `ClientDisconnectEvent` | `onClientDisconnected` | `ConnectedClient, IRequest` | **`ClientDisconnected`** | same |
| `ClientErrorEvent` | `onClientError` | `ConnectedClient, IRequest` | **`ClientFailed`** | same |
| `IncommingMessageEvent` + `IncomingMessage` | `onIncomingMessage`, bridged twice | `IncommingMessageEvent`: client, request, `string $message`. `IncomingMessage`: client, request only; **the 3rd `func_get_args()` argument is silently dropped**. | **`MessageReceived`** (one class) | `ConnectedClient, Request, string $message`. That is a superset of both, so `Client::incomingMessage()` is unaffected. |
| `AfterIncommingMessageEvent` | `onAfterIncomingMessage` | `ConnectedClient, IRequest` | **`MessageProcessed`** | same |
| `OpenEvent` | `onOpen` | `Dispatcher $application, ConnectedClient, IRequest` | **`ConnectionOpened`** | same |
| `CloseEvent` | `onClose` | same | **`ConnectionClosed`** | same |
| `MessageEvent` | `onMessage` | `Dispatcher, ConnectedClient, IRequest, string $message` | **`ApplicationMessageReceived`** | same |
| `ErrorEvent` | `onError` | `Dispatcher, ConnectedClient, IRequest, Throwable` | **`ApplicationFailed`** | same |
| `PushEvent` | `onPush` | `IMessage, string $provider, ITopic` | X1-A: **deleted**. X1-B: **`TopicPushed`** | X1-B: `PushMessages\Message, string, Topics\Topic` |
| `WsServerStartup` | `Commands\WsServer::execute()`, before the socket is created | none | **`ServerLaunched`** | none |
| `WsServerError` | the socket's `error` handler in `WsServer::execute()` | `Throwable $ex` | **`ServerFailed`** | `Throwable $ex` |

**Are `WsServerStartup` and `WsServerError` distinct?** Yes, both are kept.

- `WsServerStartup` is the **only** start event that fires in production. It fires at a
  different moment from `StartEvent`, which comes from `ServerRuntime::run()` and is never called
  in production (D2), and it carries no payload.
- `WsServerError` carries the socket-level `Throwable`. `ClientErrorEvent` is per client, with no
  exception.
- Neither has a listener today.

**Totals.** 17 classes become **14** under X1-A, or 15 under X1-B: the two merges, plus `PushEvent`
under X1-A.

---

## T5. The 64 constants of `FastyBird\Core\Constants`

**Measured by.**

- **Values:** `members.php constants`, Reflection; 64 constants.
- **Consumers:** `consts.php /e633/files-php.txt FastyBird\\Core\\Constants`, every
  `ClassConstFetch` resolved to FQCN; 1,293 fetches; and `t5.py`.
- **No NEON/Latte/JSON/XML line** names `Constants::` (`git grep`).

**Rules** (Epic §3.8, applied in order C1 to C5). Destination constants are **typed**
(`public const string`). Values are unchanged. Every C2 name is kept as-is, so only the class
changes. C3/C4 names are new, on the owning type.

**Counts:** C1 **33**, C2 **11**, C3 **8**, C4 **11**, C5 **1** (= 64).

| # | Constant | Value | Fetches / files | Consumers (by extension; `(t)` = tests) | Rule | **Destination** |
|---|---|---|---|---|---|---|
| 1 | `NOT_SPECIFIED_SOURCE` | `'*'` | 6 / 6 | Core `Values\Types\Sources\{Addon,Automator,Bridge,Connector,Module,Plugin}` (case `NOT_SPECIFIED`) | C1 | literal `'*'` in each of the 6 `NOT_SPECIFIED` cases |
| 2 | `MODULE_ACCOUNTS_SOURCE` | `'com.fastybird.accounts-module'` | 1 / 1 | `Sources\Module::ACCOUNTS` | C1 | literal in `Sources\Module::ACCOUNTS` |
| 3 | `MODULE_DEVICES_SOURCE` | `'com.fastybird.devices-module'` | 1 / 1 | `Sources\Module::DEVICES` | C1 | literal in `Sources\Module::DEVICES` |
| 4 | `MODULE_TRIGGERS_SOURCE` | `'com.fastybird.triggers-module'` | 1 / 1 | `Sources\Module::TRIGGERS` | C1 | `Sources\Module::TRIGGERS` |
| 5 | `MODULE_UI_SOURCE` | `'com.fastybird.ui-module'` | 1 / 1 | `Sources\Module::UI` | C1 | `Sources\Module::UI` |
| 6 | `PLUGIN_COUCHDB_SOURCE` | `'com.fastybird.couchdb-plugin'` | 1 / 1 | `Sources\Plugin::COUCHDB` | C1 | `Sources\Plugin::COUCHDB` |
| 7 | `PLUGIN_RABBITMQ_SOURCE` | `'com.fastybird.rabbitmq-plugin'` | 1 / 1 | `Sources\Plugin::RABBITMQ` | C1 | `Sources\Plugin::RABBITMQ` |
| 8 | `PLUGIN_REDISDB_SOURCE` | `'com.fastybird.redisdb-plugin'` | 1 / 1 | `Sources\Plugin::REDISDB` | C1 | `Sources\Plugin::REDISDB` |
| 9 | `PLUGIN_REDISDB_CACHE_SOURCE` | `'com.fastybird.redisdb-cache-plugin'` | 1 / 1 | `Sources\Plugin::REDISDB_CACHE` | C1 | `Sources\Plugin::REDISDB_CACHE` |
| 10 | `PLUGIN_WS_SERVER_SOURCE` | `'com.fastybird.ws-server-plugin'` | 1 / 1 | `Sources\Plugin::WS_SERVER` | C1 | `Sources\Plugin::WS_SERVER` |
| 11 | `PLUGIN_WEB_SERVER_SOURCE` | `'com.fastybird.web-server-plugin'` | 1 / 1 | `Sources\Plugin::WEB_SERVER` | C1 | `Sources\Plugin::WEB_SERVER` |
| 12 | `PLUGIN_API_KEY` | `'com.fastybird.api-key-plugin'` | 1 / 1 | `Sources\Plugin::API_KEY` | C1 | `Sources\Plugin::API_KEY` |
| 13 | `CONNECTOR_FB_BUS_SOURCE` | `'com.fastybird.fb-bus-connector'` | 1 / 1 | `Sources\Connector::FB_BUS` | C1 | `Sources\Connector::FB_BUS` |
| 14 | `CONNECTOR_FB_MQTT_SOURCE` | `'com.fastybird.fb-mqtt-connector'` | 1 / 1 | `Sources\Connector::FB_MQTT` | C1 | `Sources\Connector::FB_MQTT` |
| 15 | `CONNECTOR_SHELLY_SOURCE` | `'com.fastybird.shelly-connector'` | 1 / 1 | `Sources\Connector::SHELLY` | C1 | `Sources\Connector::SHELLY` |
| 16 | `CONNECTOR_TUYA_SOURCE` | `'com.fastybird.tuya-connector'` | 1 / 1 | `Sources\Connector::TUYA` | C1 | `Sources\Connector::TUYA` |
| 17 | `CONNECTOR_SONOFF_SOURCE` | `'com.fastybird.sonoff-connector'` | 1 / 1 | `Sources\Connector::SONOFF` | C1 | `Sources\Connector::SONOFF` |
| 18 | `CONNECTOR_MODBUS_SOURCE` | `'com.fastybird.modbus-connector'` | 1 / 1 | `Sources\Connector::MODBUS` | C1 | `Sources\Connector::MODBUS` |
| 19 | `CONNECTOR_HOMEKIT_SOURCE` | `'com.fastybird.homekit-connector'` | 1 / 1 | `Sources\Connector::HOMEKIT` | C1 | `Sources\Connector::HOMEKIT` |
| 20 | `CONNECTOR_VIRTUAL_SOURCE` | `'com.fastybird.virtual-connector'` | 1 / 1 | `Sources\Connector::VIRTUAL` | C1 | `Sources\Connector::VIRTUAL` |
| 21 | `CONNECTOR_TERMINAL_SOURCE` | `'com.fastybird.terminal-connector'` | 1 / 1 | `Sources\Connector::TERMINAL` | C1 | `Sources\Connector::TERMINAL` |
| 22 | `CONNECTOR_VIERA_SOURCE` | `'com.fastybird.viera-connector'` | 1 / 1 | `Sources\Connector::VIERA` | C1 | `Sources\Connector::VIERA` |
| 23 | `CONNECTOR_NS_PANEL_SOURCE` | `'com.fastybird.ns-panel-connector'` | 1 / 1 | `Sources\Connector::NS_PANEL` | C1 | `Sources\Connector::NS_PANEL` |
| 24 | `CONNECTOR_ZIGBEE2MQTT_SOURCE` | `'com.fastybird.zigbee2mqtt-connector'` | 1 / 1 | `Sources\Connector::ZIGBEE2MQTT` | C1 | `Sources\Connector::ZIGBEE2MQTT` |
| 25 | `AUTOMATOR_DEVICE_MODULE_SOURCE` | `'com.fastybird.device-module-automator'` | 1 / 1 | `Sources\Automator::DEVICE_MODULE` | C1 | `Sources\Automator::DEVICE_MODULE` |
| 26 | `AUTOMATOR_DATE_TIME_SOURCE` | `'com.fastybird.date-time-automator'` | 1 / 1 | `Sources\Automator::DATE_TIME` | C1 | `Sources\Automator::DATE_TIME` |
| 27 | `ADDON_VIRTUAL_THERMOSTAT_SOURCE` | `'com.fastybird.virtual-thermostat-addon'` | 1 / 1 | `Sources\Addon::VIRTUAL_THERMOSTAT` | C1 | `Sources\Addon::VIRTUAL_THERMOSTAT` |
| 28 | `BRIDGE_DEVICES_MODULE_UI_MODULE_SOURCE` | `'com.fastybird.devices-module-ui-module-bridge'` | 1 / 1 | `Sources\Bridge::DEVICES_MODULE_UI_MODULE` | C1 | `Sources\Bridge::DEVICES_MODULE_UI_MODULE` |
| 29 | `BRIDGE_REDISDB_PLUGIN_DEVICES_MODULE_SOURCE` | `'com.fastybird.redisdb-plugin-devices-module-bridge'` | 1 / 1 | `Sources\Bridge::REDISDB_PLUGIN_DEVICES_MODULE` | C1 | `Sources\Bridge::REDISDB_PLUGIN_DEVICES_MODULE` |
| 30 | `BRIDGE_REDISDB_PLUGIN_TRIGGERS_MODULE_SOURCE` | `'com.fastybird.redisdb-plugin-triggers-module-bridge'` | 1 / 1 | `Sources\Bridge::REDISDB_PLUGIN_TRIGGERS_MODULE` | C1 | `Sources\Bridge::REDISDB_PLUGIN_TRIGGERS_MODULE` |
| 31 | `BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE` | `'com.fastybird.shelly-connector-homekit-connector-bridge'` | 1 / 1 | `Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR` | C1 | `Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR` |
| 32 | `BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE` | `'com.fastybird.viera-connector-homekit-connector-bridge'` | 1 / 1 | `Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR` | C1 | `Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR` |
| 33 | `BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_SOURCE` | `'com.fastybird.virtual-thermostat-addon-homekit-connector-bridge'` | 1 / 1 | `Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR` | C1 | `Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR` |
| 34 | `MODULE_ACCOUNTS_PREFIX` | `'accounts-module'` | 312 / 14 | Accounts 2, Accounts(t) 12 | C2 | `FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX` |
| 35 | `MODULE_DEVICES_PREFIX` | `'devices-module'` | 338 / 23 | Devices 2, Devices(t) 18, 3 HomeKit bridges (t) 1 each | C2 | `FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX`. The bridge tests refer to Devices'. |
| 36 | `MODULE_TRIGGERS_PREFIX` | `'triggers-module'` | 213 / 7 | Triggers 1, Triggers(t) 6 | C2 | `FastyBird\Module\Triggers\Constants::MODULE_TRIGGERS_PREFIX` |
| 37 | `MODULE_UI_PREFIX` | `'ui-module'` | 124 / 10 | Ui 2, Ui(t) 7, **DevicesModuleUiModule(t) 1** | C2 | `FastyBird\Module\Ui\Constants::MODULE_UI_PREFIX`. The bridge test refers to Ui's. |
| 38 | `BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX` | `'shelly-connector-homekit-connector-bridge'` | 37 / 2 | that bridge, plus its test | C2 | `FastyBird\Bridge\ShellyConnectorHomeKitConnector\Constants::BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX` |
| 39 | `BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX` | `'viera-connector-homekit-connector-bridge'` | 37 / 2 | that bridge, plus its test | C2 | `FastyBird\Bridge\VieraConnectorHomeKitConnector\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX` |
| 40 | `BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX` | `'virtual-thermostat-addon-homekit-connector-bridge'` | 37 / 2 | that bridge, plus its test | C2 | `FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX` |
| 41 | `EXCHANGE_CHANNEL_NAME` | `'fb_exchange'` | 1 / 1 | RedisDb (`DI/RedisDbExtension.php:69`, the schema default) | C2 | `FastyBird\Plugin\RedisDb\DI\RedisDbExtension::EXCHANGE_CHANNEL_NAME` (`private const string`). RedisDb has no `Constants` class, and its only use is in this file. |
| 42 | `VALUE_FORMAT_NUMBER_RANGE` | the regexp at `Constants.php:128` | 16 / 4 | Devices 3, Core `Common/ConstantsTest` (t) | C2 | `FastyBird\Module\Devices\Constants::VALUE_FORMAT_NUMBER_RANGE`. Its `ConstantsTest` case moves to a Devices test. |
| 43 | `VALUE_FORMAT_STRING_ENUM` | the regexp at `Constants.php:130` | 12 / 4 | Devices 3, Core (t) | C2 | `FastyBird\Module\Devices\Constants::VALUE_FORMAT_STRING_ENUM` (as above) |
| 44 | `VALUE_FORMAT_COMBINED_ENUM` | the regexp at `Constants.php:132` | 10 / 4 | Devices 3, Core (t) | C2 | `FastyBird\Module\Devices\Constants::VALUE_FORMAT_COMBINED_ENUM` (as above) |
| 45 | `TOKEN_HEADER_NAME` | `'authorization'` | 5 / 2 | Core Security (`TokenReader`), Security (t) | C3 | `FastyBird\Core\Security\Identity\TokenReader::HEADER_NAME` |
| 46 | `TOKEN_HEADER_REGEXP` | `'/Bearer\s+(.*)$/i'` | 1 / 1 | `TokenReader` | C3 | `TokenReader::HEADER_PATTERN` |
| 47 | `TOKEN_CLAIM_USER` | `'user'` | 11 / 5 | `IdentityFactory`, `TokenBuilder`, `TokenValidator`; `TokenTest`, `ClientAuthenticationTest` | C3 | `FastyBird\Core\Security\Identity\TokenBuilder::CLAIM_USER` |
| 48 | `TOKEN_CLAIM_ROLES` | `'roles'` | 7 / 4 | the same three; `TokenTest` | C3 | `TokenBuilder::CLAIM_ROLES` |
| 49 | `PERMISSIONS_DELIMITER` | `':'` | 2 / 2 | `Access\AnnotationChecker`, `Access\LatteChecker` | C3 | `FastyBird\Core\Security\Access\Checker::PERMISSIONS_DELIMITER` (the interface all three checkers implement) |
| 50 | `WS_HEADER_AUTHORIZATION` | `'authorization'` | 1 / 1 | `WebSockets\Subscribers\Client` | C3 | `FastyBird\Core\WebSockets\Subscribers\Client::HEADER_AUTHORIZATION` |
| 51 | `WS_HEADER_WS_KEY` | `'x-ws-key'` | 1 / 1 | `Subscribers\Client` | C3 | `Client::HEADER_WS_KEY` |
| 52 | `WS_HEADER_ORIGIN` | `'origin'` | 1 / 1 | `Subscribers\Client` | C3 | `Client::HEADER_ORIGIN` |
| 53 | `ROUTER_API_PREFIX` | `'api'` | 10 / 9 | Accounts 2, Devices, Triggers, Ui, 3 HomeKit bridges, `public/index.php` | C4 | `FastyBird\Core\Http\Routing\Router::API_PREFIX` |
| 54 | `VALUE_NOT_SET` | `'N/A'` | 29 / 4 | Devices 3, DevicesModuleUiModule 1 | C4 | `FastyBird\Core\Values\Utilities\Value::NOT_SET` |
| 55 | `VALUE_EQUATION_TRANSFORMER` | the regexp at `Constants.php:138` | 11 / 4 | Core `Values\Transformers\EquationTransformer`, Devices 2, Core (t) | C4 | `FastyBird\Core\Values\Transformers\EquationTransformer::PATTERN` |
| 56 | `MESSAGE_BUS_PREFIX_KEY` | `'fb.exchange'` | 13 / 5 | Accounts, Devices, Triggers and Ui `Constants`; RabbitMq `Channels\Factory` | C4 | `FastyBird\Core\Exchange\Publisher\MessagePublisher::ROUTING_KEY_PREFIX` |
| 57 | `ACCESS_TOKEN_COOKIE` | `'token'` | 2 / 2 | Core `Security\Subscribers\Application`, `WebSockets\Subscribers\Client` | C4 | `FastyBird\Core\Security\Identity\TokenReader::COOKIE_NAME` |
| 58 | `ROLE_ANONYMOUS` | `'guest'` | 2 / 2 | Core `Identity\User`, Accounts | C4 | `FastyBird\Core\Security\Identity\User::ROLE_ANONYMOUS` |
| 59 | `ROLE_VISITOR` | `'visitor'` | 1 / 1 | **Accounts `Constants.php:95`** (D7) | C4 | `User::ROLE_VISITOR` |
| 60 | `ROLE_USER` | `'user'` | 5 / 3 | Accounts 2, Accounts(t) 1 | C4 | `User::ROLE_USER` |
| 61 | `ROLE_MANAGER` | `'manager'` | 4 / 3 | Accounts, Devices `ExchangeV1`, Ui `ExchangeV1` | C4 | `User::ROLE_MANAGER` |
| 62 | `ROLE_ADMINISTRATOR` | `'administrator'` | 7 / 5 | Accounts 3, Devices, Ui | C4 | `User::ROLE_ADMINISTRATOR` |
| 63 | `USER_ANONYMOUS` | `'guest'` | 5 / 2 | Core `Identity\User`, Accounts `SessionV1` | C4 | `User::ANONYMOUS_ID` |
| 64 | `TOKEN_URI_NAME` | `'authorization'` | **0 / 0** | — | C5 | **deleted** (#635) |

**Notes on the rules.**

- **`Identity\User` as the role owner.** It owns roles: `getRoles()`, `isInRole()`, and the
  anonymous fallback that already reads `ROLE_ANONYMOUS`/`USER_ANONYMOUS`. Accounts'
  `Security\User extends Identity\User`, so it inherits them. No new type is introduced.
- **`VALUE_FORMAT_*` is C2, not C4.** The rule order puts it there: the one extension is Devices,
  and the other consumer is a Core **test**. A reviewer who prefers `Values` should say so, and
  the alternative is `Values\Formats\…`.

---

## T6. The 41 accessor pairs

**Measured by.** `members.php accessors` (Reflection plus `PhpToken::tokenize()`). A pair is a
getter whose whole body is `return $this->p;`, plus a `set<X>($v)` on the same property whose
body is exactly `$this->p = $v;`, optionally followed by `return $this;`.

Call sites come from `calls.php @accnames.txt`, by method name. The receivers of every hit
outside Core were read, and only calls on the listed type are counted. #644 rewrites them
type-aware (Rector or PHPStan types), because the names are ambiguous: `getName` has 833 calls
outside Core, `getData` 232.

**§3.10 cases.**

- **A:** a typed public property (trivial get plus a public set, called from another class).
- **B:** `public private(set)` or `protected(set)` (the setter is called only inside its class
  hierarchy, or never).
- **H:** a property hook or interface property (the accessor is declared on an interface).
- **X:** excluded, with the reason.

| # | Class (`FastyBird\Core\…`) | Property | Getter / setter | Setter called from | Case | Call sites outside Core |
|---|---|---|---|---|---|---|
| 1 | `Documents\Events\PreLoad` | `data` | `getData()` / `setData()` | `DevicesModuleUiModule\Subscribers\DocumentsMapper:172`, `DocumentTest` | A | 1 setter (DocumentsMapper); getter: its subscribers |
| 2 | `Documents\Mapping\ClassMetadata` | `owningEntity` | `getOwningEntity()` / `setOwningEntity()` | `AttributeDriver:258` | A | 0 |
| 3 | `ClassMetadata` | `isMappedSuperclass` | `isMappedSuperclass()` / `setIsMappedSuperclass()` | `AttributeDriver:261` | A | 0 |
| 4 | `ClassMetadata` | `inheritanceType` | `getInheritanceType()` / `setInheritanceType()` | `ClassMetadataFactory:167`, `AttributeDriver:291` | A | 0 |
| 5 | `Documents\Mapping\Driver\AttributeDriver` | `fileExtension` | `getFileExtension()` / `setFileExtension()` | no caller | B | 0 |
| 6 | `Documents\Mapping\Driver\MappingDriverChain` | `defaultDriver` | `getDefaultDriver()` / `setDefaultDriver()` | no caller | B | 0 |
| 7 | `EventLoop\Status` | `status` | `isRunning()` / `setStatus()` | `EventLoop\Subscribers\EventLoopLifeCycle:32,37` | A (property name `running`) | 12 `isRunning()` (the modules' `ModuleEntities`/`StateEntities` and the DevicesModuleUiModule subscribers) |
| 8 | `Http\Entity` | `data` | `getData()` / `protected setData()` | no caller (no subclass calls it; `ScalarEntity` does not) | B (`public protected(set)`) | 0 identified |
| 9 | `Http\Exceptions\Http` | `title` | `getTitle()` / `setTitle()` | no caller | B | 0 |
| 10 | `Http\Exceptions\Http` | `description` | `getDescription()` / `setDescription()` | no caller (the 38 `setDescription` hits are Symfony `Command`s) | B | 0 |
| 11 | `Http\Routing\Route` | `name` | `getName()` / `setName()` (returns **void**, D13) | 147× `$route->setName()` in module route files | **X**: builder usage, unchanged | — |
| 12 | `Http\Routing\Router` | `basePath` | `getBasePath()` / `setBasePath()` | `RouteParserTest:160` | A (needs `IRouter` collapsed, T1) | 4 getters (the module `Router\Validator`s) + 1 test |
| 13 | `Persistence\Crud\CrudManager` | `flush` | `getFlush()` / `setFlush()` | no caller | B | 0 |
| 14 | `Persistence\Entities\HasEntityCreated` (trait) | `createdAt` | `getCreatedAt()` / `setCreatedAt()` | entities | **X**: entity trait | — |
| 15 | `Persistence\Entities\HasEntityUpdated` (trait) | `updatedAt` | `getUpdatedAt()` / `setUpdatedAt()` | entities | **X**: entity trait | — |
| 16 | `Persistence\Entities\TEntityRemoved` (trait) | `deletedAt` | `getDeletedAt()` / `setDeletedAt()` | — | **X**: deleted by T2 | — |
| 17 | `Phone\Entities\Phone` | `extension` | `getExtension()` / `setExtension()` | only `Phone::fromNumber()` (`$entity->set…`, Phone.php:245) | B | 0 (the 7 FbMqtt `getExtension()` hits are on another type) |
| 18 | `Phone` | `italianLeadingZero` | `getItalianLeadingZero()` / `setItalianLeadingZero()` | `Phone.php:240` | B | 0 |
| 19 | `Phone` | `numberOfLeadingZeros` | `getNumberOfLeadingZeros()` / `setNumberOfLeadingZeros()` | `Phone.php:249` | B | 0 |
| 20 | `Phone` | `timeZones` | `getTimeZones()` / `setTimeZones()` | `Phone.php:242` | B | 0 |
| 21 | `Security\Entities\HasOwner` (trait) | `owner` | `getOwnerId()` / `setOwnerId()` | entities | **X**: entity trait | — |
| 22–27 | `Security\Entities\Policies\Policy` | `v0`…`v5` | `getV0()`…`getV5()` / `setV0()`…`setV5()` | entities | **X**: Doctrine entity | — |
| 28 | `Security\Entities\Tokens\Token` | `state` | `getState()` / `setState()` | entities | **X**: Doctrine entity | — |
| 29 | `Security\Identity\UserStorage` | `identity` | `getIdentity()` / `setIdentity()` | `Identity\User:64,75` | A (needs `IUserStorage` collapsed) | 0 identified (the `getIdentity()` hits outside Core are on `User`/`AccessToken`) |
| 30 | `Security\Models\Casbin\Adapter` | `filtered` | `isFiltered()` / `setFiltered()` | `Adapter:123` | **X**: `isFiltered()` is required by `Casbin\Persist\FilteredAdapter` (K4-like) | — |
| 31 | `WebSockets\Controllers\Request` | `name` | `getControllerName()` / `setControllerName()` | `Wamp\RouteList:47,70`, `RequestTest` | H: declared on the `DispatchRequest` interface, so it becomes an interface property `string $controllerName { get; set; }` | 2 tests (Devices/Ui `TaggedServicesTest`) |
| 32 | `Controllers\Request` | `params` | `getParameters()` / `setParameters()` | `Application:116`, `RequestTest` | H: `DispatchRequest`, `array $parameters { get; set; }` | 0 identified |
| 33 | `WebSockets\Entities\Client` | `httpHeadersReceived` | `isHttpHeadersReceived()` / `setHttpHeadersReceived()` | `Server\Wrapper:79,107`, `ClientAuthenticationTest` | H: declared on `ConnectedClient`, so `bool $httpHeadersReceived { get; set; }` | 0 |
| 34 | `Entities\Client` | `httpBuffer` | `getHttpBuffer()` / `setHttpBuffer()` | `Wrapper:91,99` | H: `ConnectedClient`, `string $httpBuffer { get; set; }` | 0 |
| 35 | `Entities\Client` | `user` | `getUser()` / `setUser()` | **no caller** | **deleted (E5.3a)**: typed against `Nette\Security\User`, a class that does not exist (D19, X10); removed by #652 (#650 option D) | — |
| 36 | `WebSockets\Entities\WebSocket` | `established` | `isEstablished()` / `setEstablished()` | `Wrapper:291` | A (needs `IWebSocket` collapsed) | 0 |
| 37 | `Entities\WebSocket` | `closing` | `isClosing()` / `setClosing()` | `Encoding\RFC6455:274` | A | 0 |
| 38 | `WebSockets\Handshake\Request` | `protocolVersion` | `getProtocolVersion()` / `setProtocolVersion()` | `Handshake\RequestFactory:335` | A | 0 (the 9 hits outside Core are on other types) |
| 39 | `WebSockets\PushMessages\Pusher` | `connected` | `isConnected()` / `setConnected()` | — | **X**: deleted by T2 | — |
| 40 | `WebSockets\Server\Configuration` | `port` | `getPort()` / `setPort()` | **no caller** (every `setPort` hit is on `Nette\Http\Url`) | B (or `readonly`) | 0 |
| 41 | `Server\Configuration` | `address` | `getAddress()` / `setAddress()` | no caller | B (or `readonly`) | 0 |

**Totals.**

| Case | Count | Rows |
|---|---|---|
| A | 10 | 1, 2, 3, 4, 7, 12, 29, 36, 37, 38 |
| B | 12 | 5, 6, 8, 9, 10, 13, 17–20, 40, 41 |
| H | 4 | 31–34 |
| **In scope** | **26** | (#644 expected "about 26") |
| X | 15 | 11, 14, 15, 16, 21, 22–27 (6 pairs), 28, 30, 35, 39 |

---

## T7. Service locators

**Measured by.**

- **Locations:** a grep for `getByType` outside `DI/` in Core's `src`.
- **The graph:** `di.py services` and `python3 tools/census/e5/di.py
  var/tools/di-snapshot/e633-base reach 'Api\\Encoding\\SchemaContainer$' '<target>$'`. That is a
  DFS over constructor arguments, setup arguments and factories in every compiled container.

| Locator | Fetches (type) | When | Dependency path back to it, in production | Cycle? | **Decision (#639)** |
|---|---|---|---|---|---|
| `Api\Middleware\JsonApiMiddleware` (`fbCore.api.middleware`) | `Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface`, `JsonApiMiddleware.php:131` | lazily, in `getEncoder()` | `fbCore.api.schemas.container` → `fbVieraConnector.schemas.connector` → `fbCore.http.routing.router` → (router setup) `fbCore.api.middleware` | **yes**, in 32/46 containers (all those with a module schema, production included) | inject `Api\Encoding\SchemaContainer` (it extends neomerx's, and is the one autowired candidate) |
| `Api\Encoding\Builder` (`fbCore.api.builder`) | same interface, `Builder.php:195` | lazily | `schemas.container` → a schema → `http.routing.router` → a module `router.routes` → a controller → `fbCore.api.builder` | **yes**, 32/46 | inject `SchemaContainer` |
| `Api\Hydrators\Container` (`fbCore.api.hydrators.container`) | `Api\Encoding\SchemaContainer`, `Container.php:91`, memoised | lazily | `schemas.container` → a schema → `http.routing.router` → `router.routes` → a controller → `fbCore.api.hydrators.container` | **yes**, 32/46 | inject `SchemaContainer` |

**The lazy point.** Mark **`fbCore.api.schemas.container` as `lazy`**, a single definition. All
three cycles pass through it, so constructing any locator no longer constructs the schema
container. The alternative, three lazy edges, is not needed.

Two things must be checked in #639 before relying on this (Epic assumption 3, escalate if
either fails):

- nette/di 3.2.7 must generate a native lazy ghost for a service that has `setup` calls
  (`SchemaContainer` receives its schemas through setups).
- `final class SchemaContainer` must be supported. Native lazy ghosts do support final classes.

**`WebSockets\Controllers\Controller::$context`.** Confirmed never read (T2 row 25). Deleted in
#635.

**`ControllerFactory`.** It keeps `Nette\DI\Container`: `findByTag`, `createInstance`,
`callInjects`.

**Also seen, out of E5's scope.** The 4 module `Router\Validator`s are service locators too:
`$this->container->getByType(Routing\IRouter::class)` at Accounts:62, Devices:63, Triggers:63 and
Ui:63. #636 rewrites only the type. Converting them to injection belongs to E7 (#462).

**Related defect, not a locator (D21).** `Wamp\RouteList::$cachedRoutes` is an uninitialised
typed property read with `=== null`. So `LinkGenerator::link()`, the WebSockets link generator
the three `SocketsBridge`s inject, throws `Error` for every routed destination. It is tracked on
#625 and fixed outside E5, before #637's §14.2 check. #637 moves `LinkGenerator` and #639 changes
no WebSockets injection, so neither must work around it.

---

## T8. Clock consumers that mutate a `getNow()` result in place

**Measured by.** `tools/census/e5/php.sh tools/census/e5/t8.php /e633/files-php.txt`. It parses
every function, method and closure body; tracks every variable, property or array element
assigned from `->getNow()` (87 such assignments); and flags any
`modify/add/sub/setDate/setISODate/setTime/setTimestamp/setTimezone/setMicrosecond` call on it
whose result is discarded, plus any discarded `->getNow()->mutator()` chain.

A second pass (`calls.php` over the same 9 mutators, joined with the 87 assignments) also
catches mutator calls whose result **is** assigned.

**Result: 0 rows.** Nothing needs a "fix + test in E5.9".

- **Discarded-result mutations of a clock value: 0.**
- **Assigned-result `modify()` on a clock value: 11.** These are the
  `$executedTime = $this->clock->getNow(); assert($executedTime instanceof DateTimeImmutable);
  … ->modify('-5 second')` pattern in the `Discover`/`Install` commands of NsPanel, Shelly,
  Sonoff, Tuya and Viera (10), and `Accounts\Helpers\SecurityHash.php:57`. All 11 **assert
  `DateTimeImmutable` first and use the return value**, so they are immutability-safe today
  and afterwards.
- **Property-held clock values: 41** (e.g. `$this->lastConnectAttempt = $this->clock->getNow()`).
  None of them receives a mutator anywhere in its file: the mutator survey's only date-typed
  statement hits are below.
- **Two discarded `setTimezone()` calls exist, on values that do not come from the clock.**
  They are listed so E5.9 does not re-investigate them:
  - `Persistence\Subscribers\TimestampableSubscriber.php:308` acts on
    `DateTime::createFromFormat()`, which is mutable on purpose.
  - `Automator\DateTime\Entities\Conditions\TimeCondition::setTime()`, line 110, mutates its
    *argument*, which comes from hydration.

**For #641.** The `assert($x instanceof DateTimeImmutable)` lines after `getNow()` become
redundant once `now(): DateTimeImmutable` is typed. `FrozenClock` already stores a
`DateTimeImmutable`, converting a `DateTime` input with `createFromMutable()`, and returns a
clone of it, so it is immutable today (D22). `TokenTest` and `ClientAuthenticationTest`
construct it.

---

## T9. iPub strings

**Measured by.** `git grep -n -i ipub -- src/FastyBird/Core` (11 hits) and
`git grep -n 'ServerRuntime::VERSION'` (5 uses). No module, frontend or test reads any of these
values (`git grep` across `src`, `tests`, `*.ts`, `*.vue`).

| # | Site | Today | **Replacement (#637)** |
|---|---|---|---|
| 1 | `WebSockets\Server\ServerRuntime::VERSION` (`ServerRuntime.php:24`) | `'IPub/WebSockets/1.0.0'` | **`'FastyBird/WebSockets/1.0.0'`** (P5) |
| 1a | its uses: `X-Powered-By` in `Wrapper.php:281` and `:336`, `Subscribers\Client.php:244`, `Controllers\Application.php:143`; the WAMP welcome at `WampApplication.php:90` | the constant | unchanged references; the value changes |
| 2 | `Controllers\ControllerFactory::$mapping['IPubWebSockets']` (`ControllerFactory.php:39`) | `'IPubWebSockets' => ['IPubWebSocketsModule\\', '*\\', '*Controller']` | **the entry is deleted.** No class exists in namespace `IPubWebSocketsModule`, and no route names an `IPubWebSockets:` controller, so it maps nothing. The `'*'` entry and the module mappings set through `setMapping()` (Devices, Ui) are unaffected. |
| 3 | `Wamp\RouteList::match()`: the prefix that skips module-prefixing (`RouteList.php:46`, `strncmp($name, 'IPub:', 5)`) | `'IPub:'` | **`'Core:'`**, the same length, so the `5` stays |
| 4 | `Wamp\WampRoute`: a closure route's controller (`WampRoute.php:186`) | `'IPub:WebSocket'` | **`'Core:WebSocket'`** |
| — | `WebSocketsExtension.php:283` comment; `Core/Core/docs/Home.md` | prose | reworded in #637 (X8) |
| — | `IdentifierGuardTest.php:86-87` (`'ipub'`, `'iPublikuj'`) | the guard's denylist | **must stay** (X8) |

Rows 3 and 4 keep closure-route behaviour unchanged. Today it ends in `Exceptions\InvalidController`,
thrown by `ControllerFactory::getControllerClass()` before `processMessage()` reaches its
`BadRequest` check (X5).
`'Core:'` is not a module-mapping key and is not on the IdentifierGuard denylist.

---

## T10. Configuration

**Measured by.**

- **The schema:** `tools/census/e5/php.sh tools/census/e5/schema.php`, which walks
  `CoreExtension::getConfigSchema()`'s `Nette\Schema` objects by Reflection. It finds **8
  sections, 32 structures and 55 leaves**.
- **`Configuration`'s consumers:** `q.py '^FastyBird\\Core\\Configuration$'` and
  `calls.php` over its 10 getters.

### T10.1 The split of `FastyBird\Core\Configuration` (#640)

| New type | Owner, and who registers it | Fields (constructor order kept) | Consumers |
|---|---|---|---|
| **`FastyBird\Core\Security\Configuration`** | Security, registered by `SecurityExtension` from `security.*` | `Nette\Application\LinkGenerator $linkGenerator`, `string $tokenIssuer`, `string $tokenSignature`, `bool $enableMiddleware`, `bool $enableDoctrineMapping`, `bool $enableDoctrineModels`, `bool $enableNetteApplication`, `string\|null $applicationSignInUrl = null`, `string $applicationHomeUrl = '/'`; methods `getRedirectUrl()`, `getHomeUrl()`, plus the 6 getters | `Security\Presenters\HasAuthorization` (`getRedirectUrl`, `getHomeUrl`). **The other 6 getters have no caller** (D17). #640 may delete them; that is an option, not the default. |
| **`FastyBird\Core\Persistence\TimestampableConfiguration`** | Persistence, registered by `PersistenceExtension` from `persistence.timestampable.*` | `bool $lazyAssociation = false`, `bool $autoMapField = false`, `string $dbFieldType = 'datetime_immutable'`; methods `autoMapField()`, `useLazyAssociation()` | `Persistence\Mapping\Driver\Timestampable` (lines 231–254) |

The service name `fbCore.configuration` disappears with the root block. Each new type takes its
owner's prefix, `fbCore.security.configuration` and `fbCore.persistence.timestampable.configuration`,
and #640 declares both. Nothing looks the old name up: the only string lookup is in
`CoreExtensionTest`/`IdentifierGuardTest`, which #640 updates.

Neither name clashes: neither namespace declares a `Configuration`/`TimestampableConfiguration`
(`decls`). The existing `WebSockets\Server\Configuration` is in another namespace.

### T10.2 Typed configuration classes, one per configurable child (#640)

**Rule (default for X6).**

- The top-level class of each section is **`FastyBird\Core\<Capability>\DI\Config`**. The name
  matches `$this->getConfig()`, which returns it. It avoids clashing with the runtime
  `Configuration` types above, and it sits in `DI\` because it is compile-time only.
- Each nested structure gets a class in **`FastyBird\Core\<Capability>\DI\Config\`**, named by
  its key path below the section in PascalCase. These are the 24 names below. The orchestrator
  may adjust a nested name within this rule (§15).

Exchange, Values and Phone have no section and get no class.

| Section | Top-level class | Nested structures (key path → class) |
|---|---|---|
| `logging` | `Logging\DI\Config` | `rotatingFile` → `Config\RotatingFile`; `stdOut` → `Config\StdOut`; `console` → `Config\Console`; `sentry` → `Config\Sentry` |
| `documents` | `Documents\DI\Config` | — |
| `security` | `Security\DI\Config` | `token` → `Config\Token`; `enable` → `Config\Enable`; `enable.doctrine` → `Config\EnableDoctrine`; `enable.casbin` → `Config\EnableCasbin`; `enable.nette` → `Config\EnableNette`; `application` → `Config\Application`; `services` → `Config\Services`; `casbin` → `Config\Casbin` |
| `clock` | `Clock\DI\Config` | — |
| `persistence` | `Persistence\DI\Config` | `timestampable` → `Config\Timestampable` |
| `api` | `Api\DI\Config` | `meta` → `Config\Meta` |
| `webSockets` | `WebSockets\DI\Config` | `storage` → `Config\Storage`; `storage.clients` → `Config\StorageClients`; `storage.topics` → `Config\StorageTopics`; `server` → `Config\Server`; `server.secured` → `Config\ServerSecured`; `access` → `Config\Access` |
| `http` | `Http\DI\Config` | `static` → `Config\StaticFiles` (`Static` is a reserved word); `server` → `Config\Server`; `cors` → `Config\Cors`; `cors.allow` → `Config\CorsAllow` |

That is **8 + 24 = 32 classes**. `Application` as a class name is allowed: `make naming` rejects
it only as a namespace segment, and Core already declares `Security\Subscribers\Application`.
#640 must keep every key, default and type: proven by the schema dump and the snapshot
(Epic §3.9).

---

## T11. PHP 8.5 and `lcobucci/jwt`

**Measured by.**

- **Releases:** `gh api repos/lcobucci/jwt/releases`, and
  `gh api repos/lcobucci/jwt/contents/composer.json?ref=<tag>` for 4.3.0, 5.5.0 and 5.6.0.
- **Upgrade notes:** `docs/upgrading.md@5.6.0`, section "v4.x to v5.x".
- **Call sites:** the `refs.php` index for `Lcobucci\*`.
- **The PHP ceiling:** `composer why-not php 8.5.0` in the application image.

### T11.1 The 5.x line

**`lcobucci/jwt` 5.6.0** (released 2025-10-17, the newest release) requires:

- `php: ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0`;
- `ext-openssl`, `ext-sodium`;
- `psr/clock: ^1.0`, already locked at 1.0.0.

5.5.0 caps at `~8.4.0`, so **5.6 is the minimum**. In 5.x, `lcobucci/clock` is only a
`require-dev` dependency. In our lock nothing else requires `lcobucci/clock` 3.3.1, so it leaves
the lock. The other `psr/clock` dependents, `nesbot/carbon` and `sanmai/duoclock`, are
unaffected.

**#643's constraint:** `"lcobucci/jwt": "^5.6"` in Core's `composer.json`, which today has `^4.2`.

### T11.2 API differences that affect the 7 call sites

The 7 call sites: Core's `Security\Identity\{TokenBuilder, TokenValidator, TokenReader,
IdentityFactory, IdentityProvider}` and `Security\Subscribers\Application`, plus
`Module\Accounts\Security\IdentityFactory`. The tests `TokenTest` and `ClientAuthenticationTest`
also use the library.

| Call site | 4.3 usage | 5.6 change | Required edit |
|---|---|---|---|
| `TokenBuilder::build()` | `$jwtBuilder->issuedBy()`, `identifiedBy()`, `issuedAt()`, `expiresAt()`, `withClaim()` ×2, each **as a statement**, then `getToken()` | **The `Builder` is `@immutable`**: every call returns a new builder. The discarded results mean the token would be issued **with no `iss`, `jti`, `iat`, `exp`, `user` or `roles`**, silently. | Reassign or chain every call. `TokenTest::testBuiltTokenCarriesTheUserAndRoleClaims` and `testIssuedAtComesFromTheClockNotTheWallClock` already catch it. |
| `TokenValidator::validate()` | `$configuration->setValidationConstraints(...)` | **Deprecated since 5.5.** `phpstan-deprecation-rules` is installed, so it would be reported. | `$configuration = $configuration->withValidationConstraints(...)` |
| `TokenValidator::validate()` | `new LooseValidAt(new Lcobucci\Clock\FrozenClock($now))` | `LooseValidAt(Psr\Clock\ClockInterface $clock, ?DateInterval $leeway = null)`; `lcobucci/clock` is no longer installed | Pass Core's PSR-20 clock after #641, so `now()` is the same instant the code reads today |
| `TokenBuilder`, `TokenValidator` | `Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($sig))` | unchanged. The HMAC minimum key length (256 bits) is enforced identically in 4.3 and 5.6 (`Signer/Hmac.php`). | none |
| `TokenValidator`, `TokenReader`, `IdentityFactory`, `IdentityProvider`, `Subscribers\Application` | `parser()->parse()`, `UnencryptedToken`, `claims()->has()/get()`, `validator()->validate()` | unchanged | none |
| `Accounts\Security\IdentityFactory::create()` | `JWT\Token $token`, then **`$token->toString()` as the lookup key of the persisted `AccessToken`** | unchanged API, **but** the string must round-trip byte for byte, or every persisted session stops resolving | none in code. The compatibility test below pins it. |

### T11.3 Design of the fixture-token compatibility test (#634 writes it, #643 relies on it)

- **Fixtures**, committed under `src/FastyBird/Core/Core/tests/fixtures/tokens/`, minted once by
  today's 4.3 code through the real `TokenBuilder`, with a `FrozenClock` at a fixed instant:
  - `valid.jwt`: an `exp` far in the future, user UUID `5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34`,
    roles `['administrator']`, issuer and signature from `tests/common.neon`;
  - `expired.jwt`: the same with an `exp` in the past;
  - `foreign-signature.jwt`: signed with another key;
  - `fixture.json`: every claim, the signing key, the issuer and the frozen instant.
- **Assertions, identical under 4.3 (green at #634's merge) and under 5.6 (green at #643):**
  - `TokenValidator::validate(valid.jwt)` returns a token whose `claims()` equal `fixture.json`
    (`iss`, `jti`, `iat` with microseconds, `exp`, `user`, `roles`).
  - `$token->toString() === file_get_contents('valid.jwt')`, byte for byte. That is the
    Accounts lookup key.
  - `TokenReader::readHeader('Bearer ' . valid.jwt)` succeeds.
  - `IdentityFactory::create()` yields the same user and roles.
  - `expired.jwt` and `foreign-signature.jwt` both raise `UnauthorizedAccess`.
  - A token freshly built by the current `TokenBuilder` validates. That round trip catches the
    builder-immutability break.
  - Algorithm `HS256` in the header.
  - An Accounts test (`SessionV1Test`-style, DB-backed) persists `valid.jwt` as an `AccessToken`
    and resolves it through `Accounts\Security\IdentityFactory`.
- **The tokens are never re-minted.** The fixture is the frozen output of 4.3. A diff in it is a
  security escalation (§15).

### T11.4 Packages the `PHP 8.5 (Core)` job must ignore

`composer why-not php 8.5.0` on the base lock lists exactly 10 packages:

- `lcobucci/clock` 3.3.1, which #643 removes;
- **9 `orisai/*` packages**, all requiring `php: 7.4 - 8.4`: `coding-standard` 3.11.0 (dev),
  `exceptions` 1.1.5, `nette-di` 1.5.0, `nette-object-mapper` 0.3.0, `object-mapper` 0.3.0,
  `object-mapper-contracts` 1.0.2, `reflection-meta` 1.0.5, `source-map` 1.0.3, `utils` 1.0.3.

**After #643 the set is exactly those 9 `orisai/*` packages.**

Composer cannot scope `--ignore-platform-req=php+` to named packages: the flag is global. So the
job must:

1. run `composer install --ignore-platform-req=php+`;
2. run `composer why-not php <the job's PHP version>` and fail unless the packages it prints are
   exactly the 9 above. This is what makes the ignore "scoped" in effect;
3. run Core's suite.

`docs/deprecations.md` records the same 9 names.

---

## T12. Coverage: every code path E5 changes, and the test that reaches it today

**Measured by.**

- **Test inventory:** `grep -l` of each subject over the 229 tracked `*Test.php` files
  (`/tmp/e633/testfiles.txt`), then `grep -o 'public function test…'` per class. Both are grep
  locators, and each named test was then read to confirm it reaches the path.
- **Module controller suites:** they boot the full container and dispatch through
  `JsonApiMiddleware` (`DevicesV1Test::testRead` and the rest).

**"none — #634 adds it"** marks a gap #634 must close. The full list is in T12.2.

### T12.1 All rows

| # | Path E5 changes | PR | Test that reaches it today |
|---|---|---|---|
| 1 | Deletion of the 14 + 9 dead types (T2) | #635 | none needed. The API manifest diff proves the removal, and nothing references them. |
| 2 | `Controller::$context` removal; controllers built by `ControllerFactory` | #635 | `Core ControllerTest::testInjectPrimarySucceedsOnceAndRejectsASecondCall`, `Devices TaggedServicesTest::testControllerFactoryCreatesTheTaggedControllerService` |
| 3 | `UTCDateTime` and its NEON registrations removed | #635 | `NeonClassReferencesTest::testEveryClassNameInEveryNeonFileResolves` (NEON side); `tests/cases/application/EntityMappingTest::testMappingIsValidAtProductionScope` (production DBAL types) |
| 4 | `Console` loses its formatter branch (X9) | #635 | **none — #634 adds it**: `Helpers\Console` logs through its `echo` fallback without an `Error` |
| 5 | The 9 dead `$on*`/`$before*`/`$after*` arrays and their `Arrays::invoke` calls | #635 | `EntityCreator`/`Updater`/`Deleter`: indirectly, every module `*V1Test::testCreate/testUpdate/testDelete`. `User::login/logout`: `ClientAuthenticationTest::testResolvingTheRolesLeavesTheUserServiceSignedOut`. `QueryObject`: every repository-backed controller test. |
| 6 | Collapse of the 27 Api/Http/Persistence/Security interfaces | #636 | module HTTP suites (`*V1Test::testRead/Create/Update/Delete` for Accounts, Devices, Triggers, Ui and the 3 HomeKit bridges); `Core HydratorTest`, `HydratorFieldsTest`, `RouteParserTest`; `Devices RouterTest::testPrefixedRoutes` |
| 7 | `IUserStorage` → `UserStorage` (identity storage behind `Identity\User`) | #636 | `AnnotationCheckerTest`, `Accounts AccessTest::testPermissionAnnotation`, `ClientAuthenticationTest` (login/logout through `User`) |
| 8 | Collapse of the 12 WebSockets interfaces | #637 | `WrapperTest` (4), `ApplicationTest` (4), `ClientAuthenticationTest` (18), `WebSocketsStorageDriversTest` (7), `Devices`/`Ui TaggedServicesTest`, `Devices ExchangeV1Test` (8) |
| 9 | **The `IControllerFactory` test seam** (`WampApplicationTest`'s anonymous class) | #637 | `WampApplicationTest`. Its double becomes a `ControllerFactory` mock (call-syntax change only). |
| 10 | `LinkGenerator` moved to `WebSockets\Routing` (DI service name unchanged) | #637 | `ControllerTest`; the module `SocketsBridge`s: **none at unit level — #634 adds it**: `LinkGenerator::link()` for a module WAMP route, resolved from the compiled container by type. **Today that call throws `Error`** (D21, the uninitialised `RouteList::$cachedRoutes`), so the test pins the throw as a known defect until #625 lands. |
| 11 | `ServerRuntime::VERSION` value on the wire (4 headers + WAMP welcome) | #637 | **none — #634 adds it**: `X-Powered-By` on the handshake response (`Wrapper::attemptUpgrade`), on a 401 close (`Subscribers\Client::closeSession`), on an `Application::close`, and the welcome message's agent field. It asserts the reference `ServerRuntime::VERSION`, so #637 changes only the constant. |
| 12 | `ControllerFactory` mapping key, `RouteList` `'IPub:'` prefix, `WampRoute` closure default | #637 | `Devices`/`Ui TaggedServicesTest::testTaggedSocketRoutesReachTheWampRouter` (module route). Closure route: **none — #634 adds it**: a closure route resolves to the reserved controller name, is not module-prefixed, and ends in `Exceptions\InvalidController` from `ControllerFactory::getControllerClass()`, before `processMessage()`'s `BadRequest` check (X5). |
| 13 | **Hook order at server create**, including the 3 module `SocketsBridge` enablers and their relative order | #638 | **none — #634 adds it**: build the production container, call `ServerRuntime::create()` with stub sockets, and assert (a) the `CreateEvent`/`ServerCreated` listeners run before the enablers and (b) the `Exchange\Consumers\Container` consumption order is Devices → Ui → DevicesModuleUiModule. Repeat in `test/Bridge/DevicesModuleUiModule`, whose order X4 changes and #638 declares. |
| 14 | Hook order at server start (`onStart`: dispatch, then `OnServerStartHandler`) | #638 | `ServerTest::testOnStartFiresRegisteredHandlerWithLoopAndServer` (array mechanics only). DI-wired order: **none — #634 adds it** under X1-B; nothing to add under X1-A. |
| 15 | Each of the 13 WebSockets hooks dispatches the right event with the right payload | #638 | Array mechanics: `ServerTest` (3), `WrapperTest` (4, covering 5 hooks), `ApplicationTest` (4), `WampApplicationTest` (1). **Through the DI bridge to the PSR-14 dispatcher: none — #634 adds it**: one test per hook against the compiled container's dispatcher, asserting the event class and payload. It pins today's double dispatch of `onClientConnected`/`onIncomingMessage` and the dropped `$message` in `IncomingMessage`. |
| 16 | `Subscribers\Client` as the only listener (`ClientConnected`, `IncomingMessage`) | #638 | `ClientAuthenticationTest` calls `clientConnected()`/`incomingMessage()` **directly**. Through the dispatcher: **none — #634 adds it** (a revoked token closes the client when the event is dispatched by `Wrapper`). |
| 17 | `WsServerStartup`/`WsServerError` renamed | #638 | **none — #634 adds it**: `WsServer::execute()` dispatches the startup event before `create()` (a stub `ServerRuntime`, loop stopped immediately). |
| 18 | JSON:API schema-container resolution through `JsonApiMiddleware`, `Builder` and `Hydrators\Container` | #639 | Warm path: every module `*V1Test::testRead` (Devices `DevicesV1Test::testRead` …). Cold container: **none — #634 adds it**: on a freshly built production container, fetch each of the 3 services **first**, before anything touches `SchemaContainer`, and encode/hydrate one document. That is the path where `lazy` must break the cycle (T7). |
| 19 | `Configuration` split and typed configuration | #640 | `CoreExtensionTest::testServicesRegistration`, `ConfigurationFilesTest::testEveryFbCoreSectionMatchesTheSchema`, `IdentifierGuardTest`. Timestampable: `Accounts EntityTimestampsTest` (6), `Triggers NotificationTimestampsTest`, `Devices DeviceEntitiesTest`. **`HasAuthorization` redirect/home URLs: none — #634 adds it** (`getRedirectUrl()` with and without a sign-in URL, `getHomeUrl()`). |
| 20 | Schema dump identical (key paths, types, defaults) | #640 | `ConfigurationFilesTest` validates every NEON. The dump itself is a tool artefact (`schema.php` here, #557's method), not a test. |
| 21 | `SystemClock`/`FrozenClock` → PSR-20 `now()` | #641 | `TokenTest::testIssuedAtComesFromTheClockNotTheWallClock`, `ClientAuthenticationTest` (FrozenClock); `SecurityHashTest::testPassword` and the RabbitMq/RedisDb/CouchDb publisher and states tests (`SystemClock`). Direct behaviour: **none — #634 adds it**: `SystemClock` returns a `DateTimeImmutable` near wall time; `FrozenClock` returns the instant it was built with, and is not affected by mutating a returned value (a `DateTime` input). |
| 22 | `Constants` dissolved | #642 | `Core ConstantsTest` (4 regexp behaviours). **Values: none — #634 adds it**: one test pinning all 64 values against T5 (Reflection on `Constants` today; #642 repoints it at the destinations without editing a value). |
| 23 | `lcobucci/jwt` 4.3 → 5.6 | #643 | `TokenTest` (15), `ClientAuthenticationTest` (18), Accounts `SessionV1Test` (4). **4.3-minted fixture tokens: none — #634 adds it (T11.3).** |
| 24 | PHPStan range 8.4–8.5; `PHP 8.5 (Core)` job | #643 | none (a CI and static gate, not a test) |
| 25 | The 26 accessor pairs (T6 A/B/H) | #644 | A: `DocumentTest` (PreLoad), `RouteParserTest` (`setBasePath`), `ClientAuthenticationTest` (`setHttpHeadersReceived`), `RequestTest` (`setControllerName`/`setParameters`), `WrapperTest` (`WebSocket` established/closing). **`EventLoop\Status`, `Http\Exceptions\Http`, `Phone`, `Server\Configuration`, `Documents\Mapping\ClassMetadata`/drivers: none — #634 adds it**: one read-back test per class, assigning through today's setter or constructor and reading through today's getter. It is rewritten to property syntax mechanically. |

### T12.2 The "none — #634 adds it" rows, verbatim for #634

1. **T12-4.** `WebSockets\Helpers\Console` logs every level through its `echo` fallback without
   throwing (today the uninitialised `$formatter` makes it throw `Error`; pin the post-#635
   expectation as a known-defect test, or assert the throw today and flip it in #635).
2. **T12-10.** `LinkGenerator::link()` for a module WAMP route, resolved from the compiled
   container by type. Today it throws `Error` because of D21, so pin the throw as a known defect
   until #625.
3. **T12-11.** `X-Powered-By` equals `ServerRuntime::VERSION` on:
   - the handshake response (`Wrapper::attemptUpgrade`);
   - a 401 close (`Subscribers\Client::closeSession`);
   - `Application::close()`;
   - and the WAMP welcome message's agent field equals `ServerRuntime::VERSION`.
4. **T12-12.** A closure WAMP route: resolves to the reserved controller name, is not
   module-prefixed by `RouteList`, and ends in `Exceptions\InvalidController` thrown by
   `ControllerFactory::getControllerClass()` when `Application::processMessage()` calls it, so
   the `BadRequest` check is never reached (X5).
5. **T12-13.** Server create in the compiled production container:
   - `CreateEvent`/`ServerCreated` listeners run before the 3 `SocketsBridge` enablers;
   - the `Exchange\Consumers\Container` consumption order is Devices → Ui →
     DevicesModuleUiModule;
   - the same in `test/Bridge/DevicesModuleUiModule`, recording today's order there. In that
     container, and in `test/Module/Devices` and `test/Module/Ui` with their overlays,
     `create()` throws `InvalidArgument` on the first hook, because the enabled
     `SocketsBridge` is not registered (X4). Pin the throw.
6. **T12-14.** Server start in the compiled container: the `StartEvent` dispatch runs before
   `OnServerStartHandler` (only under X1-B).
7. **T12-15.** For each of the 13 WebSockets hooks, through the compiled container's PSR-14
   dispatcher:
   - the event class(es) dispatched and their payload, in order;
   - including the double dispatch on `onClientConnected`/`onIncomingMessage` and the dropped
     `$message` in `IncomingMessage`.
8. **T12-16.** `Subscribers\Client` reached through the dispatcher: a client whose token was
   revoked is closed when `Wrapper` dispatches the connect/message event.
9. **T12-17.** `Commands\WsServer::execute()` dispatches `WsServerStartup` before
   `ServerRuntime::create()`.
10. **T12-18.** Cold-container JSON:API: on a freshly built production container, fetch
    `JsonApiMiddleware`, `Encoding\Builder` and `Hydrators\Container` before anything touches
    `SchemaContainer`, and encode/hydrate one document through each.
11. **T12-19.** `Security\Presenters\HasAuthorization`: `getRedirectUrl()` with and without
    `applicationSignInUrl`, and `getHomeUrl()`.
12. **T12-21.** `SystemClock::getNow()` is a `DateTimeImmutable` within a second of wall time.
    `FrozenClock` returns the instant it was built with and is not shifted by mutating a returned
    value, including when it was built from a `DateTime`.
13. **T12-22.** All 64 `Constants` values pinned against T5.
14. **T12-23.** The 4.3-minted fixture-token test exactly as T11.3.
15. **T12-25.** Read-back tests for:
    - `EventLoop\Status` (`setStatus`/`isRunning`);
    - `Http\Exceptions\Http` (title, description);
    - `Phone\Entities\Phone` (extension, italianLeadingZero, numberOfLeadingZeros, timeZones via
      `fromNumber`);
    - `WebSockets\Server\Configuration` (port, address);
    - `Documents\Mapping\ClassMetadata` (owningEntity, isMappedSuperclass, inheritanceType);
    - `AttributeDriver::getFileExtension()`;
    - `MappingDriverChain::getDefaultDriver()`;
    - `Crud\CrudManager::getFlush()`.

---

## All proposed names, in one place

| Table | Today | Proposed |
|---|---|---|
| T1 | `WebSockets\Clients\Drivers\IDriver` | `WebSockets\Clients\Drivers\Driver` |
| T1 | `WebSockets\Topics\Drivers\IDriver` | `WebSockets\Topics\Drivers\Driver` |
| T1 (only if R1 is rejected) | `Http\Routing\IRouter` / `WebSockets\Encoding\IProtocol` | `Http\Routing\RequestRouter` / `WebSockets\Encoding\Protocol` |
| T4 | `CreateEvent`, `StartEvent`, `StopEvent` | `ServerCreated`, `ServerStarted`, `ServerStopped` |
| T4 | `ClientConnectEvent` + `ClientConnected` | `ClientConnected` |
| T4 | `ClientDisconnectEvent`, `ClientErrorEvent` | `ClientDisconnected`, `ClientFailed` |
| T4 | `IncommingMessageEvent` + `IncomingMessage`, `AfterIncommingMessageEvent` | `MessageReceived`, `MessageProcessed` |
| T4 | `OpenEvent`, `CloseEvent`, `MessageEvent`, `ErrorEvent` | `ConnectionOpened`, `ConnectionClosed`, `ApplicationMessageReceived`, `ApplicationFailed` |
| T4 | `PushEvent` | deleted (X1-A) / `TopicPushed` (X1-B) |
| T4 | `WsServerStartup`, `WsServerError` | `ServerLaunched`, `ServerFailed` |
| T5 | 33 C1 constants | literals in the `Values\Types\Sources\*` cases (rows 1–33) |
| T5 | 11 C2 constants | same constant names, on the extension's own `Constants` (rows 34–40, 42–44); `RedisDbExtension::EXCHANGE_CHANNEL_NAME` (row 41) |
| T5 | C3 | `TokenReader::HEADER_NAME`, `TokenReader::HEADER_PATTERN`, `TokenBuilder::CLAIM_USER`, `TokenBuilder::CLAIM_ROLES`, `Access\Checker::PERMISSIONS_DELIMITER`, `WebSockets\Subscribers\Client::HEADER_AUTHORIZATION/HEADER_WS_KEY/HEADER_ORIGIN` |
| T5 | C4 | `Http\Routing\Router::API_PREFIX`, `Values\Utilities\Value::NOT_SET`, `EquationTransformer::PATTERN`, `Exchange\Publisher\MessagePublisher::ROUTING_KEY_PREFIX`, `TokenReader::COOKIE_NAME`, `Identity\User::ROLE_ANONYMOUS/ROLE_VISITOR/ROLE_USER/ROLE_MANAGER/ROLE_ADMINISTRATOR`, `Identity\User::ANONYMOUS_ID` |
| T6 | `EventLoop\Status::$status` | public property `$running` |
| T9 | `'IPub/WebSockets/1.0.0'`, `'IPub:'`, `'IPub:WebSocket'`, mapping `'IPubWebSockets'` | `'FastyBird/WebSockets/1.0.0'`, `'Core:'`, `'Core:WebSocket'`, entry deleted |
| T10 | `FastyBird\Core\Configuration` | `FastyBird\Core\Security\Configuration` + `FastyBird\Core\Persistence\TimestampableConfiguration`; services `fbCore.security.configuration`, `fbCore.persistence.timestampable.configuration` |
| T10 | `stdClass` config | `FastyBird\Core\<Capability>\DI\Config` ×8, plus the 24 nested `…\DI\Config\<Path>` classes |

---

## How to reproduce

Every script is committed under `tools/census/e5/`. Scratch output goes to `/tmp/e633`, which is
mounted at `/e633` inside the image. Every PHP script runs through `tools/census/e5/php.sh`:

- the repository is mounted read-only;
- `PHP_INI_SCAN_DIR=:/app/tools/php.d`, as `make tests` sets it, so vendor's PHP 8.4
  deprecations do not print to stdout;
- `TZ`, `PHP_DATE_TIMEZONE` and `XDEBUG_MODE` are set.

No command here is piped through `tail` or `head` where its status matters.

```bash
# 0. vendor/ for the measured tree: copies, not symlinks (CLAUDE.md traps)
docker run --rm -v "$PWD":/app -w /app -e COMPOSER_MIRROR_PATH_REPOS=1 -e XDEBUG_MODE=off \
  -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC fb-e2-app:latest composer install --no-interaction
find vendor/fastybird -maxdepth 1 -type l                                  # prints nothing
diff -rq src/FastyBird/Core/Core/src vendor/fastybird/miniserver-core/src  # prints nothing
rm -rf var/temp/cache && mkdir -p /tmp/e633

# 1. reference index (php-parser + NameResolver), the base of T1, T2, T6, T11
git ls-files -- '*.php' > /tmp/e633/files-php.txt                          # 3540 files
tools/census/e5/php.sh tools/census/e5/refs.php /e633/files-php.txt > /tmp/e633/refs.json

# 2. compiled DI graph of every container (47 recorded, 46 compile)
docker run --rm -v "$PWD":/app -w /app -e XDEBUG_MODE=off -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC \
  fb-e2-app:latest php tools/di-snapshot.php var/tools/di-snapshot/e633-base --jobs 6

# T1
python3 tools/census/e5/t1.py /tmp/e633/refs.json > /tmp/e633/t1.txt
python3 tools/census/e5/t1rows.py /tmp/e633/t1.txt
python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base wiring \
  'Routing\\(IRouter|Router)$|IUserStorage|Drivers\\IDriver|IControllerFactory|IEntityMapper'
# T2
python3 tools/census/e5/t2.py /tmp/e633/refs.json
python3 tools/census/e5/q.py /tmp/e633/refs.json 'Security\\Latte|PushMessages|Formatter|Caching\\'
python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base services \
  'PushMessages|OnServerStartHandler|PushMessageSerializer|EntityCrudFactory|Helpers\\Console'
git ls-files '*.latte'                                                     # prints nothing
# T3 / T4
tools/census/e5/php.sh tools/census/e5/members.php callbacks               # 22 arrays
python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base hookgroups   # compiled setup order
python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base enablers     # enabled consumer registered? (X4)
tools/census/e5/php.sh tools/census/e5/calls.php /e633/files-php.txt run,stop,create,handlePush
git grep -n 'WebSockets\\Events'
# T5
tools/census/e5/php.sh tools/census/e5/members.php constants > /tmp/e633/constvals.txt
tools/census/e5/php.sh tools/census/e5/consts.php /e633/files-php.txt FastyBird\\Core\\Constants > /tmp/e633/consts.txt
python3 tools/census/e5/t5.py /tmp/e633/constvals.txt /tmp/e633/consts.txt
# T6
tools/census/e5/php.sh tools/census/e5/members.php accessors > /tmp/e633/accessors.txt
cut -f3,4 /tmp/e633/accessors.txt | tr '\t' '\n' | sed 's/^public //; s/^protected //; s/()//' | sort -u > /tmp/e633/accnames.txt
tools/census/e5/php.sh tools/census/e5/calls.php /e633/files-php.txt @/e633/accnames.txt > /tmp/e633/calls-acc.txt
# T7
python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base reach 'Api\\Encoding\\SchemaContainer$' 'Api\\Middleware\\JsonApiMiddleware$'
python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base reach 'Api\\Encoding\\SchemaContainer$' 'Api\\Encoding\\Builder$'
python3 tools/census/e5/di.py var/tools/di-snapshot/e633-base reach 'Api\\Encoding\\SchemaContainer$' 'Api\\Hydrators\\Container$'
# T8
tools/census/e5/php.sh tools/census/e5/t8.php /e633/files-php.txt > /tmp/e633/t8.txt
tools/census/e5/php.sh tools/census/e5/calls.php /e633/files-php.txt modify,add,sub,setDate,setISODate,setTime,setTimestamp,setTimezone,setMicrosecond
# T9
git grep -n -i ipub -- src/FastyBird/Core
git grep -n 'ServerRuntime::VERSION'
# T10
tools/census/e5/php.sh tools/census/e5/schema.php                          # 32 structures, 55 leaves
python3 tools/census/e5/q.py /tmp/e633/refs.json '^FastyBird\\Core\\Configuration$'
# T11
gh api 'repos/lcobucci/jwt/releases?per_page=30' --jq '.[] | "\(.tag_name) \(.published_at)"'
gh api 'repos/lcobucci/jwt/contents/composer.json?ref=5.6.0' --jq .content | base64 -d
gh api 'repos/lcobucci/jwt/contents/docs/upgrading.md?ref=5.6.0' --jq .content | base64 -d
docker run --rm -v "$PWD":/app:ro -w /app -e COMPOSER_HOME=/tmp/ch fb-e2-app:latest \
  composer why-not php 8.5.0                                               # exit 1: 10 packages
```

| Script | What it does | Feeds |
|---|---|---|
| `php.sh` | runs a PHP census script in the application image (read-only mount, test ini, fixed TZ) | all |
| `refs.php` | php-parser + `NameResolver` over every tracked PHP file: declarations (kind, final/abstract, extends/implements/traits, anonymous classes), every resolved class reference in code and in docblock type positions, and every string literal naming `FastyBird\Core\…` | T1, T2, T6, T10, T11 |
| `q.py` | ad-hoc query of that index: referencing files or lines per FQCN regex, and subtypes | T1, T2, T5, T7, T10 |
| `t1.py`, `t1rows.py` | the 50 prefixed types: transitive implementers, files by area, DI lines, NEON/Latte/XML/JSON/YAML lines | T1 |
| `t2.py` | every unreferenced Core type, plus evidence per candidate | T2 |
| `di.py` | reads a `tools/di-snapshot.php` recording: `hooks`/`hookgroups` (setup order on the hook services), `enablers` (whether each consumer the `onCreate` setups enable is registered), `wiring` (autowiring candidates), `services`, `reach` (reference paths between services) | T1, T2, T3, T7 |
| `members.php` | Reflection: `callbacks`, `constants`, `accessors` (bodies via `PhpToken::tokenize`) | T3, T5, T6 |
| `calls.php` | every method call by name, with receiver source, chain and discarded-result flag | T3, T6, T8, T10 |
| `consts.php` | every `ClassConstFetch` of a class, resolved to FQCN | T5 |
| `t5.py` | groups those fetches per constant and extension | T5 |
| `t8.php` | clock-held variables and properties per scope, and their in-place mutations | T8 |
| `schema.php` | walks the real `fbCore` `Nette\Schema` tree | T10 |

**Sanity controls run with the harness.**

- `t1.py`'s first version recorded `implements` names unresolved and reported 0 implementers for
  `IDocument`. That known-positive (`Document implements IDocument`) caught the bug, and it was
  fixed by resolving through `resolvedName`.
- The parser's `IRouter` file set was diffed against `git grep -lw IRouter`. The two agree
  except the root `tests/` file and non-PHP hits.
- The `Constants` consumer set was computed twice, by fetch and by class reference, and both
  give the same 76 files.
