# Coding conventions

These are enforced where enforcement is possible: `make cs` for docblocks and code shape,
`make naming` for identity, `make phpstan` for types. Where a rule is not machine-checkable it
says so.

## Identity

Core was assembled from fifteen earlier packages, some of which had themselves absorbed
third-party libraries. Those names make up the denylists of `tools/check-naming.php`.

**The rule is not "never write these words."** It is that a name may not be used as a grouping
layer *because that is where the code came from*. The test is whether the name says what the
code IS, or only which package once shipped it:

- `Values\` is right and `Metadata\` is wrong. The capability is data types, payloads and
  sources; Metadata was only the name of the library that held them.
- `Logging\` is right and `Tools\` is wrong, for the same reason.
- `WebSockets\` and `Exchange\` are right **even though packages by those names were merged in**,
  because Core genuinely has a WebSockets capability and an Exchange capability. There the word
  is doing descriptive work rather than carrying a lineage.

`tools/check-naming.php` is the machine-readable form of this distinction. Its two denylists
differ on purpose: `Application`, `Metadata` and `Tools` are rejected as namespace segments but
allowed inside a class name, where they are ordinary English words.

Enforced by `make naming`, which has no baseline: any violation fails it, and the tree has
none. Its first three checks cover namespace segments of every package
(`FastyBird\Core\...` and `FastyBird\<Type>\<Name>\...`), declared type names of every package,
and `use FastyBird\Core\... as X` aliases anywhere in the repository; the others (import
collisions, JS directories, interface and trait prefixes) are described where they apply. The
package's own root is exempt from the segment check, because it is the package's name; every
segment below it is checked. The same holds for a package's tests: its helper classes are
`FastyBird\<Type>\<Name>\Tests\Support` in `tests/support`, never `Tests\Tools`.

The same namespace denylist covers the JS of every package. No directory under any
`src/FastyBird/<Type>/<Name>/assets` may be named `application`, `metadata`, `tools` or any other
entry of it, compared case-insensitively. A JS capability directory is named for what its code is
(`api`, `eventBus`, `security`, `values`, `websockets`), exactly as in PHP: the Accounts, Devices
and Ui modules keep their JSON:API mappers in `assets/api/`, not `assets/jsonapi/`, and the
mapper class names stay as they are, because the denylist applies to directory segments only. A
package with a `package.json` must have an `assets/` root that holds at least one file, otherwise
the tool fails rather than passes.

## Import aliases

Import without an alias where the bare name does not collide. On collision, the alias is the
last two segments of the imported namespace, joined:

```php
use FastyBird\Core\Api\Schemas as ApiSchemas;        // legal
use FastyBird\Core\Documents as CoreDocuments;       // legal
use FastyBird\Core\Documents as ExchangeDocuments;   // rejected by make naming
```

Two different imports can still collide at two segments — `FastyBird\Core\Persistence\Mapping\Driver`
and `FastyBird\Core\Security\Mapping\Driver` both reduce to `MappingDriver`. When that happens, the
alias climbs to the smallest `k >= 2` that is unique among that file's imports, one segment at a time,
and it applies symmetrically: both colliding imports climb together, never just one of them. A longer
alias is legal only when a sibling import in the same file collides at `k - 1`; a stray `k > 2` alias
with no such sibling is still rejected.

```php
use FastyBird\Core\Persistence\Mapping\Driver as PersistenceMappingDriver; // legal (collides
use FastyBird\Core\Security\Mapping\Driver as SecurityMappingDriver;      // at 2 segments)
```

This is a positive rule, not a denylist. A denylist can only ban the names someone already
thought of; this bans every name not derived from where the symbol actually lives.

The rule exists because an alias is the cheapest way to move a class without rewriting the file
body, so aliases named after the importing file's origin re-form unless a gate refuses them.

**Single-segment exception.** A name with only one segment (`use Casbin;`,
`use Monolog;`, `use Exception;`) has no two-segment form, so it always stays bare, even when it
collides with something. There is no `Something\Casbin` to alias `Casbin` from, and the same
holds for every single-segment import.

```php
use Casbin;                                 // legal even inside a collision group
use FastyBird\Core\Security\Models\Casbin as ModelsCasbin; // the colliding sibling still aliases
```

**One named exception: `Doctrine\ORM\Mapping as ORM`.** Unlike the single-segment rule above,
this is not a general mechanism -- it is exactly one FQCN, allowed exactly one alias, listed as
a single constant map in `tools/check-naming.php` (`FB_ALIAS_EXCEPTIONS`). `ORM` is Doctrine's
own documented attribute convention (`#[ORM\Entity]`, `#[ORM\Column]`, ...), used by the entity
files that import it with no collision at all; the last-two-segments rule would want
`ORMMapping` instead, which exists nowhere in the codebase and would split entity files across
two styles for no reader benefit. Only this exact pair is exempt: `Doctrine\ORM\Mapping` left
bare, or aliased anything other than `ORM` (`Orm`, `ORMMapping`), while inside a collision group
is still a violation, and the alias `make naming` expects is `ORM`, not a climbed one. Every
other member of the same group is unaffected and still gets the ordinary `fbExpectedAlias` rule.

```php
use Doctrine\ORM\Mapping as ORM;                              // legal, even colliding
use FastyBird\Core\Persistence\Mapping as PersistenceMapping; // ordinary rule, unaffected
```

**Every collision group, not just Core's.** The same problem -- a bare import left in place
while a same-named sibling gets aliased -- happens between two ordinary, non-Core namespaces: a
package's own `Documents` left bare next to `Devices\Documents as DevicesDocuments`, or a
module's own `Caching` left bare next to `Nette\Caching as NetteCaching`. `make naming`'s check 4
applies the same rule -- bare unless it collides, aliased to the smallest colliding `k >= 2` when
it does, single-segment names always exempt -- to every top-level `use` import in every file the
gate scans, per KIND (`use`, `use function` and `use const` never collide with each other). A `use` inside a class
body (trait composition) is not an import and is never in scope. Check 4 compares a file's
imports only with each other, so a standalone import -- no colliding sibling `use` in the file --
is outside what it can see; the next paragraph covers those.

**Standalone imports** follow the same rule: bare unless they collide. For a standalone import
a collision is wider than check 4's sibling list -- a class, interface, trait
or enum declared in the file's own namespace counts, and so does the class the file itself
declares. A bare import silently shadows a same-namespace type of the same short name, and one
naming the file's own class does not compile. So `Http\Routing\RouteHandler`, which sits beside
Core's own `Routing\RouteCollector`, imports `FastRoute\RouteCollector as FastRouteRouteCollector`,
and `Logging\Subscribers\Console` imports `Symfony\Component\Console as ComponentConsole`. A file
never imports a namespace it is already inside: `FastyBird\Module\Triggers\Constants` writes
`Entities\Triggers\Trigger` relative to its own namespace. `ORM` stays the one named exception.
This is kept by review, not by `make naming`.

```php
use Nette\Security;                                        // bare: nothing collides
use const DIRECTORY_SEPARATOR;                             // bare: nothing collides
use Symfony\Component\Console as ComponentConsole;         // legal: the file declares `Console`
```

**The clock is imported as `use Psr\Clock\ClockInterface;`.** Time comes from PSR-20: a consumer
types against `Psr\Clock\ClockInterface` and calls `now()`, which returns a `DateTimeImmutable`.
Core's `Clock\SystemClock` and `Clock\FrozenClock` are its two implementations. Import the
interface itself, bare, in every file, never the `Psr\Clock` namespace: `Psr\Clock` and `FastyBird\Core\Clock` share their
last segment, so a namespace import would collide in every file that also constructs one of Core's
clocks and force both into `PsrClock`/`CoreClock` aliases, while `ClockInterface` collides with
nothing. The `…Interface` suffix rule below applies to the types this repository declares, not to
a third-party name it imports.

```php
use Psr\Clock\ClockInterface;           // the one canonical form
use FastyBird\Core\Clock;               // only where Clock\SystemClock or Clock\FrozenClock is named
```

**SPL exceptions may be imported as `PHP<Name>Exception`.** The per-package `Exceptions\`
directories extend the SPL exceptions under that alias (`class InvalidArgument extends
PHPInvalidArgumentException implements Exception`), and it is an accepted convention, not a
violation. For `Exception` itself, in an `Exceptions` namespace that
declares its own `Exception` interface, the alias is required: a bare `use Exception;` would make
the short name resolve to the global class instead of the package's interface, and
`src/FastyBird/Core/Core/src/Exceptions/InvalidController.php`, which `extends PHPException
implements Exception`, would stop compiling. `PhpException`
(`Connector/Modbus`) is an existing variant spelling of the same convention.

Checks 3 and 4 compute a colliding import's expected alias from the same function over the
same, whole-file, same-kind sibling list, so the two can never disagree about what one
particular import is expected to be aliased as.

## Namespace layout

Core is **capability-first**, following Symfony's component convention: package by what the
code does, with layer names only inside a capability. `src/FastyBird/Core/Core/src` holds, at
its top level, only:

- the **11 capabilities**: `Api\`, `Clock\`, `Documents\`, `Exchange\`, `Http\`, `Logging\`,
  `Persistence\`, `Phone\`, `Security\`, `Values\`, `WebSockets\`;
- `Exceptions\`, the odd one out — a **shared root**, not a capability, holding only the
  handful of genuinely cross-cutting exceptions (`Exception`, `InvalidArgument`,
  `InvalidController`, `InvalidLink`, `InvalidState`, `Logic`, `Runtime`, `UnexpectedValue`);
- five runtime namespaces, kept at the root because they are unrelated to each other and no
  umbrella name would say what they are: `Boot\`, `DI\`, `EventLoop\`, `Presenters\`, `UI\`.

Two structural rules follow:

- **Exceptions and events live inside their owning capability**
  (`Security\Exceptions\UnauthorizedAccess`, `Exchange\Events\BeforeMessagePublished`); only the
  cross-cutting exceptions above sit at the shared root.
- **Layer names appear only inside a capability.** `Middleware`, `Subscribers`, `Entities`,
  `Controllers`, `Helpers` and the like are never top-level namespaces.

There is no root-level `Configuration` class. Runtime settings belong to the capability that
reads them: `Security\Configuration` holds the token and application settings
`Security\Presenters\HasAuthorization` reads, and `Persistence\TimestampableConfiguration` holds
the settings of the timestampable driver.

There is no root-level `Constants` class. A constant lives, typed, on the type that owns its meaning
(`Http\Routing\Router::API_PREFIX`, `Security\Identity\User::ROLE_ADMINISTRATOR`,
`Security\Identity\TokenReader::HEADER_NAME`); a value used by one extension only lives in that
extension (`FastyBird\Module\Devices\Constants::MODULE_DEVICES_PREFIX`); and an extension's
identity is the literal of its `Values\Types\Sources\*` enum case. Do not add a shared constants
class back.

## DI

Core's DI extension is split by capability, and every identifier it registers is named after its
capability. `IdentifierGuardTest` (Core, `tests/cases/unit/DI`) enforces the naming rules below.
It checks every Core service name in the compiled Core test container, every
key path of the `fbCore` schema, and every tag constant. It has no list of tolerated
violations: any violation fails it.

### Extension layout

- Core registers exactly one compiler extension, the composite `FastyBird\Core\DI\CoreExtension`,
  under the name `fbCore` (`CoreExtension::NAME`).
- Each of the 11 capabilities has a child extension,
  `FastyBird\Core\<Capability>\DI\<Capability>Extension`, for example
  `FastyBird\Core\Security\DI\SecurityExtension`.
- The children are never registered with the compiler, because nette/di cannot add an extension
  while it is compiling. Instead, the composite does the following:
  - constructs each child;
  - calls `setCompiler()` on each child as `fbCore.<capability>`;
  - hands each child exactly its own configuration subtree;
  - forwards the `loadConfiguration()`, `beforeCompile()` and `afterCompile()` calls to the
    children that implement them, and appends each child's `getInitialization()` body to its own.
- Definition order is observable. nettrine's `EventPass`, the Symfony event dispatcher, the
  console command list and the generated container's `findByType()`/`findByTag()` lists all
  follow it. So the composite calls each child at the position its definitions hold in that
  order, not in a convenient order.
- A child that must contribute at two positions exposes one extra, explicitly named hook, and the
  composite calls it at the second position. Today there are two:
  `PersistenceExtension::loadTimestampable()` and `WebSocketsExtension::loadServerProcess()`.
- `CoreExtension` itself keeps only the composition and the root runtime: the event loop, the
  Nette UI and route list, the presenter mapping, the PSR-6 cache and the event-dispatcher
  fallback.
- A new capability needs:
  - a child extension;
  - an entry in the composite's constructor, `children()`, `getConfigSchema()` (if it has
    configuration) and hook calls;
  - an entry in `IdentifierGuardTest::CAPABILITIES`.

### Service names

- Name each service `fbCore.<capability>.<role>`. `<capability>` is one of `api`, `clock`,
  `documents`, `exchange`, `http`, `logging`, `persistence`, `phone`, `security`, `values` and
  `webSockets`. `<role>` is one or more dot-separated camelCase segments, for example
  `fbCore.security.token.builder` or `fbCore.webSockets.server.wrapper`. A child gets this
  prefix from `$this->prefix('<role>')`.
- The root services use the root forms: `fbCore.eventLoop.<role>`, `fbCore.ui.<role>`,
  `fbCore.cache.<role>` and `fbCore.eventDispatcher`. A runtime settings object belongs to the
  capability that reads it: `fbCore.security.configuration` and
  `fbCore.persistence.timestampable.configuration`.
- No segment may be the name of a library Core was assembled from, compared case-insensitively.
  The guard's `DENYLIST` holds those names: `jsonApi`, `simpleAuth`, `wsServer`, `ipub` and the
  rest. `application` may not be the segment directly under `fbCore`. Deeper down it may be,
  because there it names Nette's or the HTTP server's `Application`.
- Other extensions look Core services up by type (`getByType()`, `findByType()`), not by name.
  A reference by name in NEON, such as `@fbCore.api.middleware`, is part of the public surface,
  and renaming the service breaks it.

### Configuration keys

- `fbCore` has one section per configurable capability: `logging` (with `sentry`), `documents`,
  `security`, `clock`, `persistence` (with `timestampable`), `api`, `webSockets` (with `access`)
  and `http`. Exchange, Values and Phone have no configuration.
- Each child declares the schema of its own section in `getConfigSchema()`. The composite builds
  its schema from them and hands each child exactly its subtree. [configuration.md](./configuration.md#core-fbcore)
  documents every key.
- Each section is read through a typed class, not a `stdClass`. The schema casts
  every `Expect::structure()` with `->castTo()`: the section to
  `FastyBird\Core\<Capability>\DI\Config`, and each nested structure to
  `FastyBird\Core\<Capability>\DI\Config\<Path>`, named by its key path below the section in
  PascalCase (`webSockets > server > secured` is `WebSockets\DI\Config\ServerSecured`;
  `http > static` is `Http\DI\Config\StaticFiles`, because `static` is reserved). Each class is
  `final readonly`, with one public promoted property per key, in schema order, typed as the
  values the schema can hand over. The schema stays the single source of keys, types and
  defaults; the class declares no defaults. A child reads `$this->getConfig()` after
  `assert($configuration instanceof Config)`.
- The denylist applies to every key path, sections and leaves alike. `application` may not be a
  top-level key.

### Tags

- Name each tag `fastybird.core.<capability>.<role>`, with the capability list above.
- Define the tag string once, as a `public const string` on the extension of the capability that
  owns the tag, never on `CoreExtension`.
- The producer and the consumer both use that constant, never a string literal. A tag that one
  side misspells or stops reading is not an error: the tagged service is silently unused.
- The guard reads every public string constant other than `NAME` on `CoreExtension` and on each
  capability extension as a tag. So a public string constant on a Core extension is a tag.
- An extension outside Core imports the owning extension's `DI` namespace. There it collides with
  `Nette\DI`, so the import alias rule above applies: `use FastyBird\Core\Documents\DI as DocumentsDI;`,
  then `DocumentsDI\DocumentsExtension::DRIVER_TAG`.
- Tags that belong to a third party, such as `nette.inject`, keep their own names.

| Tag | Constant |
|---|---|
| `fastybird.core.documents.attributeDriver` | `DocumentsExtension::DRIVER_TAG` |
| `fastybird.core.exchange.consumerState` | `ExchangeExtension::CONSUMER_STATE` |
| `fastybird.core.exchange.consumerRoutingKey` | `ExchangeExtension::CONSUMER_ROUTING_KEY` |
| `fastybird.core.webSockets.routes` | `WebSocketsExtension::ROUTES_TAG` |
| `fastybird.core.webSockets.controller` | `WebSocketsExtension::CONTROLLER_TAG` |
| `fastybird.core.webSockets.serverCreatedListener` | `WebSocketsExtension::SERVER_CREATED_LISTENER_TAG` (the tag value is the listener's priority) |

### The container at runtime

Core's services get their dependencies through their constructors. These rules are kept by
review, not by a gate.

- **No service locators.** No class in any package takes `Nette\DI\Container` to look a service
  up at runtime (`getByType()`, `getService()`). A lookup hides the dependency from the DI graph
  and from `tools/di-snapshot.php`. There are three exceptions, and no others: Core's `Boot\`,
  which creates the container; `WebSockets\Controllers\ControllerFactory`; and the contributte
  `LazyListener` statements `WebSocketsExtension::beforeCompile()` compiles. The last two are described
  below.
- **A cycle is broken with `lazy`, not with a lookup.** When injecting a service would close a
  dependency cycle, its definition is made lazy (`$definition->lazy = true`). nette/di then
  returns a PHP 8.4 native lazy ghost, and builds the real service, its setups included, on first
  use. Today there is one: `fbCore.api.schemas.container`. The JSON:API builder, middleware and
  hydrators container take it, and every schema it collects reaches the router, which reaches
  them.
- **No PSR-11 adapter.** `Nette\DI\Container` does not implement
  `Psr\Container\ContainerInterface`, and Core does not wrap it in one.
- **`WebSockets\Controllers\ControllerFactory`.** It keeps
  `Nette\DI\Container`, because it creates WebSocket controllers with `findByTag()`,
  `createService()`, `createInstance()` and `callInjects()`, and `createInstance()` and
  `callInjects()` have no PSR-11 equivalent. A PSR-11 type would only hide that dependency.
- **The `LazyListener` statements.** `WebSocketsExtension::beforeCompile()` hands the container's
  own definition to each contributte `LazyListener` it generates for a
  `SERVER_CREATED_LISTENER_TAG` service. The listener is therefore resolved from the container at
  runtime, inside contributte's dispatcher, when its event is first dispatched. That is a lookup,
  accepted because it is how contributte attaches every lazy listener, and because the consumers
  a module's listener needs depend on the dispatcher themselves, so a constructor dependency
  would close a cycle ([#658](https://github.com/FastyBird/miniserver/issues/658), option C).

## Events

Core's capabilities announce what happens through PSR-14 events, and only through them. These
rules are kept by review, not by a gate.

- **Dispatch through PSR-14 only.** A class that announces something takes
  `Psr\EventDispatcher\EventDispatcherInterface` in its constructor and calls `dispatch()`. No
  callback arrays: no `public array $onX`, no `Nette\Utils\Arrays::invoke()`, and no DI
  `addSetup()` that appends a closure to a hook. `Utils\Arrays::invoke` has no call site in Core's
  `src`.
- **One event class per moment.** Two classes for the same hook, or one hook dispatching twice,
  is a duplicate to merge, not a feature.
- **Names are past tense, with no `Event` suffix**: `ServerCreated`, `ClientConnected`,
  `MessageReceived`, `Exchange\Events\BeforeMessagePublished`. The class lives in its
  capability's `Events\` namespace.
- **Shape.** `final class X extends Symfony\Contracts\EventDispatcher\Event`, the payload as
  `private readonly` promoted constructor properties with a getter each, in the order the
  dispatcher passes them. A class extending `Event` cannot be a `readonly class`.
- **Order is behaviour.** Where the order of listeners matters, give them explicit priorities;
  registration order differs between containers.
- **Listeners in other packages.** A subscriber inside a package that loads
  `contributte/event-dispatcher` (production does) is collected by type, as
  `EventSubscriberInterface`. A listener of a WebSocket server lifecycle event from another package
  -- today `WebSockets\Events\ServerCreated` -- is **not** a subscriber: it is a `final` invokable
  service tagged `WebSocketsExtension::SERVER_CREATED_LISTENER_TAG` with its priority as the tag
  value, and `WebSocketsExtension` attaches it, lazily, to whichever Symfony dispatcher the
  container autowires. That works in the package test containers too, which have only Core's
  fallback dispatcher and no subscriber collection. It must not also implement
  `EventSubscriberInterface`, or production would register it twice.

## Docblocks

**No file header.** The licence is in `LICENSE.md`, the author in `composer.json`, and the
namespace supersedes `@package`. `@package`, `@subpackage`, `@author`, `@copyright`,
`@license`, `@since`, `@created`, `@version` and `@date` are forbidden. **Enforced everywhere:**
the coding standard (`tools/phpcs-ruleset.xml`) runs
`SlevomatCodingStandard.Commenting.ForbiddenAnnotations`, and `tools/phpcs.xml` excludes no
package from it; `make cs` rejects any of these annotations in all 29 packages and their tests,
and equally in the repository-root `tests/` and `bin/`, which `make cs` scans as well.
`make naming` also runs `tools/strip-file-headers.php --check`, which refuses the legacy file
header in PHP and `.neon` files repository-wide.

- `@var`, `@param`, `@return`: omit where they only restate a native type. Keep for array
  shapes, generics and `@template`.
- `@throws`: **required and load-bearing.** PHPStan verifies them; an unused one fails CI.
- Class docblocks: only where they say something the signature does not.

## Naming

- Interfaces: no `I` prefix. Prefer deleting the interface entirely when it has one
  implementation and is not a DI substitution point — that remains the first choice. Where an
  interface must exist, **name it for what it does**, not with a type suffix: an interface
  beside a concrete `RouteParser` is named for the role it expresses (for example
  `UrlGenerator`), never `IRouteParser`.
- Traits: no `T` prefix, and no `…Trait` suffix either — name the capability, not the
  language construct.
- **The prefixes are rejected by `make naming`** (check 6), in every package and in
  its tests: an `interface I[A-Z]…` or a `trait T[A-Z]…` fails the gate. The capital is part of
  the test, so a name that merely begins with the letter, such as the trait `Tlv`, is fine.
- **`…Interface` and `…Trait` suffixes are rejected by `make cs`**:
  `SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming` and
  `SlevomatCodingStandard.Classes.SuperfluousTraitNaming` are active. Neither sniff objects to
  the `I`/`T` prefix — only to the suffix — but this repository rejects the prefix too, by the
  maintainer's ruling: name by role, no suffix either direction.
- No stuttering: not `Middleware\Cors\Cors`, not `Services\Phone\Phone`.

A name is needed rather than a mechanical drop of the `I`: an `IRouter` beside a concrete
`Router` cannot simply become `Router`, because it would collide with the class, so the interface
needs a role name instead -- what it does, not what implements it.

### Interfaces: K1–K4

Every package keeps an interface only for one of four reasons: K1, two or more production
implementations; K2, an implementation outside the package; K3, a substitution point
(configuration, a `findByType()` or tag collection, or a Nette `setImplement()` factory); K4, a
third-party contract. Test doubles do not count, and (rule R1) an implementation that extends the
sole other implementation does not count towards K1 or K2. The rule is the same in all 29
packages.

- **K1 counts implementations of the same contract across a package family**, not only inside
  one package. Rule R1 alone would count the five `Writers\Writer` interfaces of the
  NsPanel, Sonoff, Tuya, Viera and Virtual connectors, and Triggers' `Entities\EntityParams`, as
  one implementation each, because every implementer descends from one abstract root
  (`Writers\Periodic`, `Entities\Triggers\Trigger`). They are kept: the same contract is
  implemented by several classes, in several packages for the writers.
- **A marker interface is kept while it has an implementer.** A package's `Exceptions\Exception`
  marker is kept as long as at least one exception implements it: catching by package is the
  contract it expresses. A marker with no implementer and no `catch` site is dead code and is
  deleted.
- **An interface with one local implementer and no K3 use is collapsed**: a `findByType()`, a
  tag, a `setImplement()` factory or a NEON reference is a K3 use; its absence is not.

Every interface declared in `src/FastyBird/Core/Core/src` is listed below, by capability.
Implementer counts are transitive and exclude `tests/`. None of them is kept for K4. A package's
own interfaces are kept by the same rule, without a table here.

| Interface (FQCN under `FastyBird\Core\`) | Kept for | Evidence |
|---|---|---|
| `Api\Exceptions\JsonApi` | K1 | `JsonApiError`, `JsonApiMultipleError`. `JsonApiMiddleware.php:57` catches by it. |
| `Documents\Document` | K1, K2 | 158 implementers in 21 packages, none in Core (18 direct, in Accounts, Devices, Triggers and Ui). Devices and Ui extend it with their own `Document` interface. |
| `Documents\CreatedAt` | K1, K2 | 129 implementers, all outside Core (14 direct, in Devices and Ui). |
| `Documents\UpdatedAt` | K1, K2 | 129 implementers, all outside Core (14 direct, in Devices and Ui). |
| `Documents\Owner` | K1, K2 | 140 implementers, all outside Core (18 direct, in Devices, Triggers and Ui). |
| `Documents\Mapping\MappingAttribute` | K1 | 7 attribute classes (`Document`, `MappedSuperclass`, `DiscriminatorMap`, …). `AttributeReader.php:77` selects attributes by it. |
| `Documents\Mapping\Driver\MappingDriver` | K1 | `AttributeDriver`, `MappingDriverChain`. |
| `Exceptions\Exception` | K1 | 32 exception classes across Core's capabilities, for example `Exceptions\InvalidState` and `WebSockets\Exceptions\Storage`. |
| `Exchange\Consumers\Consumer` | K1, K2, K3 | 16 implementers, 15 outside Core: the 10 connectors' `Writers\Exchange`, 3 in Devices, 1 in Ui, 1 in DevicesModuleUiModule. `findByType()` at `ExchangeExtension.php:71` registers them on `Consumers\Container`. |
| `Exchange\Publisher\MessagePublisher` | K1, K2, K3 | `Publisher\Container`, RabbitMq's and RedisDb's `Publishers\Publisher`. `findByType()` at `ExchangeExtension.php:98`. |
| `Exchange\Publisher\Async\MessagePublisher` | K1, K2, K3 | `Async\Container`, RedisDb's `Publishers\Async\Publisher`. `findByType()` at `ExchangeExtension.php:117`. |
| `Exchange\Factory` | K1, K2, K3 | RabbitMq's `Channels\Factory`, RedisDb's `Exchange\Factory`; none in Core. `findByType()` at `WebSocketsExtension.php:312` and `DevicesExtension.php:965,974`. |
| `Http\Routing\Handlers\Handler` | K1 | `RequestHandler`, `RequestResponseHandler`. |
| `Persistence\Crud\CrudFactory` | K3 | `setImplement()` at `PersistenceExtension.php:112`; Nette generates the only implementation. |
| `Persistence\Crud\Create\EntityCreatorFactory` | K3 | `setImplement()` at `PersistenceExtension.php:94`. |
| `Persistence\Crud\Update\EntityUpdaterFactory` | K3 | `setImplement()` at `PersistenceExtension.php:100`. |
| `Persistence\Crud\Delete\EntityDeleterFactory` | K3 | `setImplement()` at `PersistenceExtension.php:106`. |
| `Persistence\Entities\CrudEntity` | K1, K2 | 155 implementers, 153 outside Core. In Core: `Security\Entities\Policies\Policy`, `Security\Entities\Tokens\Token`. Accounts, Devices, Triggers and Ui extend it with their own `Entity` interface. |
| `Persistence\Entities\EntityCreated` | K1, K2 | 153 implementers, all outside Core (27 direct, in Accounts, Devices, Triggers, Ui, HomeKit and ApiKey). |
| `Persistence\Entities\EntityUpdated` | K1, K2 | 153 implementers, all outside Core (27 direct, as `EntityCreated`). |
| `Security\Access\Checker` | K1 | `AnnotationChecker`, `LatteChecker`, `LinkChecker`. |
| `Security\Entities\Owner` | K1, K2 | 45 implementers, all outside Core (6 direct, in Devices, Triggers and Ui). |
| `Security\Identity\Authenticator` | K2 | The one implementer is Accounts' `Security\Authenticator`. Core's `Identity\User` takes it. |
| `Security\Identity\IdentityProvider` | K1, K2, K3 | Core's `Identity\IdentityFactory`, Accounts' `Security\IdentityFactory`. `fbCore > security > services > identity` (default `false`, `SecurityExtension.php:122`) registers Core's; otherwise Accounts registers its own. |
| `Security\Identity\UserIdentity` | K1, K2 | Core's `PlainIdentity`, Accounts' `Entities\Identities\Identity`. |
| `Values\Transformers\Transformer` | K1 | `HsbTransformer`, `HsiTransformer`, `MiredTransformer`, `RgbTransformer`. |
| `Values\Types\Payloads\Payload` | K1 | The enums `Button`, `Cover`, `Switcher`. An enum cannot extend a class, so this is their only common type. |
| `Values\Types\Sources\Source` | K1 | The enums `Addon`, `Automator`, `Bridge`, `Connector`, `Core`, `Module`, `Plugin`. |
| `WebSockets\Clients\Drivers\Driver` | K3 | Selected by `fbCore > webSockets > storage > clients > driver` (`WebSocketsExtension.php:132`). Production implementer: `InMemory`. |
| `WebSockets\Topics\Drivers\Driver` | K3 | Selected by `fbCore > webSockets > storage > topics > driver` (`WebSocketsExtension.php:209`). Production implementer: `InMemory`. |
| `WebSockets\Controllers\RequestController` | K3 | `findByType()` at `WebSocketsExtension.php:298` tags every implementer with `CONTROLLER_TAG`, which `ControllerFactory` reads. The implementers are the abstract `Controller` and its subclasses, Devices' and Ui's `ExchangeV1` (R1: not K1 or K2). |
| `WebSockets\Controllers\Responses\ControllerResponse` | K1 | `ErrorResponse`, `MessageResponse`, `NullResponse`. |
| `WebSockets\Encoding\FrameData` | K1 | `RFC6455\Frame`, `RFC6455\Message`. |
| `WebSockets\Server\ServerWrapper` | K1 | `Wrapper`, `FlashWrapper`. |
| `WebSockets\Wamp\WampRouter` | K1 | `RouteList`, `WampRoute`. The service `fbCore.webSockets.routing.router` has this type (`WebSocketsExtension.php:147`). |

A new interface in Core needs a row in this table, with its reason, in the PR that adds it.

## Code

- `final` by default. Drop it only for a class that is actually extended.
- No `Nette\SmartObject`. Typed properties make it redundant. Enforced by `make cs`
  (`SlevomatCodingStandard.PHP.ForbiddenClasses`, `forbiddenTraits`), repository-wide.
- `#[\Override]` on every genuine override, interface methods included. Enforced by
  `make phpstan` (`checkMissingOverrideMethodAttribute`, in both configurations), repository-wide.
- `readonly class` where every property is readonly.
- Typed class constants, enforced by `SlevomatCodingStandard.TypeHints.ClassConstantTypeHint`.
  Active in all 29 packages; `tools/phpcs.xml` excludes none.
- Constructor property promotion, enforced by `SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion`.
- **A get/set pair is a property, not two methods.** Where a getter only returns
  a property and a setter only assigns it, declare the property and drop both methods. Kept by
  review, not by a gate:
  - **A typed public property** when other classes write it (`EventLoop\Status::$running`,
    `Http\Routing\Router::$basePath`).
  - **Asymmetric visibility** when only the class writes it, or nothing does:
    `public private(set)` (`Phone\Entities\Phone::$extension`, written by `fromNumber()`), or
    `public protected(set)` when a subclass writes it or redeclares its default
    (`Persistence\Crud\CrudManager::$flush`; `Http\Exceptions\Http::$title`, whose default
    `HttpNotFound` and `HttpMethodNotAllowed` redeclare as `public protected(set)`). A
    `private(set)` property is implicitly final, so no subclass can redeclare it.
  - **An interface property with hooks** when an interface declares the accessor:
    `public string $name { get; set; }`, laid out as below. The implementing class satisfies it
    with a plain public property. A PHPUnit double of the interface stubs it with
    `->method(PropertyHook::get('name'))`; a double of a class with a plain public property
    assigns it instead. No Core interface declares one today.
  - **PHP_CodeSniffer 4 parses interface properties but not property hooks.** No suppression
    comment is used. A single hook fits on one line
    (`public int $revision { get; }`); two hooks go on a line each, because `{ get; set; }` on one
    line is reported as two statements (`Generic.Formatting.DisallowMultipleStatements.SameLine`):

    ```php
    public string $name {
    	get;
    	set;
    }
    ```

    Property hooks **with bodies** (`get => …`, `set(…) { … }`) are still not used. PHP_CodeSniffer
    4.0.4 reads the `$this` and `$value` in a hook body as further property declarations, and
    `PSR2.Classes.PropertyDeclaration` reports them (`Multiple`, `ScopeMissing`,
    `SpacingAfterType`). The restriction lifts when PHP_CodeSniffer tokenizes hooks, or if the
    standard gives those three codes up;
    [#675](https://github.com/FastyBird/miniserver/issues/675) records the trade-off.
  - **A method** when the accessor does more than read or write the property, and always for
    Doctrine entities and their traits (hydration bypasses hooks), orisai `MappedObject`s, fluent
    setters and builders (`Http\Routing\Route::setName()`), and getters without a setter.
