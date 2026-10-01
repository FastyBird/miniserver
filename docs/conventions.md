# Coding conventions

These are enforced where enforcement is possible: `make cs` for docblocks and code shape,
`make naming` for identity, `make phpstan` for types. Where a rule is not machine-checkable it
says so.

## Identity

Core was assembled by merging fifteen packages: `Library/Metadata`, `DateTimeFactory`,
`DoctrineCrud`, `DoctrineOrmQuery`, `DoctrineTimestampable`, `JsonApi`, `Phone`, `SlimRouter`,
`WebSockets`, `Core/Application`, `Core/Exchange`, `Core/SimpleAuth`, `Core/Tools`,
`Plugin/WebServer` and `Plugin/WsServer`. Several of those had themselves absorbed third-party
libraries whose names also survive in the tree — `iPublikuj:SlimRouter`,
`iPublikuj:DoctrineCrud`, `iPublikuj:JsonAPIDocument` and others.

**The rule is not "never write these words."** It is that a name may not be used as a grouping
layer *because that is where the code came from*. The test is whether the name says what the
code IS, or only which package once shipped it:

- `Security\` is right and `SimpleAuth\` is wrong. The capability is authentication and
  authorization; SimpleAuth was only the brand of the library that implemented it.
- `Http\` is right and `SlimRouter\` is wrong, for the same reason.
- `WebSockets\` and `Exchange\` are right **even though packages by those names were merged in**,
  because Core genuinely has a WebSockets capability and an Exchange capability. There the word
  is doing descriptive work rather than carrying a lineage.

`tools/check-naming.php` is the machine-readable form of this distinction. Its two denylists
differ on purpose: `Application`, `Metadata` and `Tools` are rejected as namespace segments but
allowed inside a class name, where they are ordinary English words.

Enforced by `make naming`, which checks three places: namespace segments under
`FastyBird\Core`, declared type names under `FastyBird\Core`, and `use FastyBird\Core\... as X`
aliases anywhere in the repository.

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

`tools/naming-baseline.txt` recorded **3,076 aliases violating this rule** when the convention
was written, in 131 distinct forms using 112 distinct alias names. `FastyBird\Core\Exceptions`
alone was aliased 11 different ways, one per library the importing file happened to come from:

```
538  as ApplicationExceptions      73  as DoctrineCrudExceptions     25  as ExchangeExceptions
121  as JsonApiExceptions          56  as DoctrineOrmQueryExceptions 11  as SimpleAuthExceptions
 33  as ToolsExceptions             8  as WebSocketsExceptions        6  as SlimRouterExceptions
  2  as PhoneExceptions             1  as Exceptions (redundant -- import it bare)
```

The baseline may only shrink. A stale entry in it fails the gate. **As of E3.15 (#508), the
last capability PR of the Core identity refactor's Epic E3, the Core-alias entries in the
baseline were 0** -- every alias of a Core namespace anywhere in the repository was legal. The
table above is kept as history, not current state.

**Single-segment exception, generalized.** A name with only one segment (`use Casbin;`,
`use Monolog;`, `use Exception;`) has no two-segment form, so it always stays bare, even when it
collides with something. This was approved for `Casbin` specifically -- there is no
`Something\Casbin` to alias it from -- and #541 states it as the general rule: it applies to
every single-segment import, not just that one name.

```php
use Casbin;                                 // legal even inside a collision group
use FastyBird\Core\Security\Models\Casbin as ModelsCasbin; // the colliding sibling still aliases
```

**One named exception: `Doctrine\ORM\Mapping as ORM`.** Unlike the single-segment rule above,
this is not a general mechanism -- it is exactly one FQCN, allowed exactly one alias, listed as
a single constant map in `tools/check-naming.php` (`FB_ALIAS_EXCEPTIONS`). `ORM` is Doctrine's
own documented attribute convention (`#[ORM\Entity]`, `#[ORM\Column]`, ...), used by the 54
entity files that import it with no collision at all; the last-two-segments rule would want
`ORMMapping` instead, which exists nowhere in the codebase and would split entity files across
two styles for no reader benefit. Only this exact pair is exempt: `Doctrine\ORM\Mapping` left
bare, or aliased anything other than `ORM` (`Orm`, `ORMMapping`), while inside a collision group
is still a violation, and the alias `make naming` expects is `ORM`, not a climbed one. Every
other member of the same group is unaffected and still gets the ordinary `fbExpectedAlias` rule.

```php
use Doctrine\ORM\Mapping as ORM;                              // legal, even colliding
use FastyBird\Core\Persistence\Mapping as PersistenceMapping; // ordinary rule, unaffected
```

**#541: every collision group, not just Core's.** Before #541, `make naming` only checked
aliases of `FastyBird\Core\...` imports (check 3 above). The identical problem -- a bare import
left in place while a same-named sibling gets aliased -- happens just as often between two
ordinary, non-Core namespaces: a package's own `Documents` left bare next to
`Devices\Documents as DevicesDocuments`, or a module's own `Caching` left bare next to
`Nette\Caching as NetteCaching`. `make naming`'s check 4 now applies the same rule -- bare
unless it collides, aliased to the smallest colliding `k >= 2` when it does, single-segment
names always exempt -- to every top-level `use` import in every file the gate scans, per KIND
(`use`, `use function` and `use const` never collide with each other). A `use` inside a class
body (trait composition) is not an import and is never in scope; neither is a standalone alias
with no colliding sibling in the file (`use Doctrine\ORM\Mapping as ORM;` on its own).

Both checks now compute a colliding import's expected alias from the same function over the
same, whole-file, same-kind sibling list -- check 3's Core-only siblings widened to match check
4's, so the two can never disagree about what one particular import is expected to be aliased
as.

Seeding this check found 534 additional violations, 124 of them the `Doctrine\ORM\Mapping`
collisions the named exception above then removed, leaving **410 violations across the
baseline**, entirely pre-existing -- this PR adds no code rewrite, only the check and the
baseline entries it newly makes visible. They will be worked down package by package in the PRs
#541 plans next.

## Namespace layout

**E3 of the Core identity refactor got Core here.** Before it, `src/FastyBird/Core/Core/src`
was the type-first layout PRs #454/#455 produced: `Middleware`, `Subscribers`, `Entities`,
`Controllers`, `Providers`, `Presenters`, `Helpers`, `Services`, `Types`, `Utilities` and more
all sat at the top level, with paths like the pre-#507 `Subscribers\Application\
EventLoopLifeCycle` (under the `FastyBird\Core\` root) that this layout contradicted.

Core is now **capability-first**, following Symfony's component convention.
`src/FastyBird/Core/Core/src` holds, at its top level, only:

- the **11 capabilities**: `Api\`, `Clock\`, `Documents\`, `Exchange\`, `Http\`, `Logging\`,
  `Persistence\`, `Phone\`, `Security\`, `Values\`, `WebSockets\`;
- `Exceptions\`, the odd one out — a **shared root**, not a capability, holding only the
  handful of genuinely cross-cutting exceptions (`Exception`, `InvalidArgument`,
  `InvalidController`, `InvalidLink`, `InvalidState`, `Logic`, `Runtime`, `UnexpectedValue`).
  Layer names — `Middleware`, `Subscribers`, `Entities`, `Controllers` — appear only *inside*
  a capability, never at the top level. Exceptions and events live inside their owning
  capability too; only these 8 genuinely cross-cutting exceptions sit at the shared root;
- the dissolved runtime namespaces: `Boot\`, `Caching\`, `DI\`, `EventLoop\`, `Presenters\`,
  `UI\`;
- two root-level classes, `FastyBird\Core\Configuration` and `FastyBird\Core\Constants`,
  flattened out of their own one-class sub-namespace (a single-class namespace collapses into
  a root-level class rather than keeping a stuttering `Configuration\Configuration` /
  `Constants\Constants` shape).

## DI

Epic #459 split Core's DI extension by capability and renamed every identifier it registers.
These rules keep it that way. `IdentifierGuardTest` (Core, `tests/cases/unit/DI`) enforces the
naming rules below. It checks every Core service name in the compiled Core test container, every
key path of the `fbCore` schema, and every tag constant. Its expected-violations list,
`identifier-guard-violations.txt`, is empty and may only shrink. Any new violation fails it.

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
  Nette UI and route list, the presenter mapping, the PSR-6 cache, the event-dispatcher fallback
  and `Configuration`.
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
  `fbCore.cache.<role>`, `fbCore.eventDispatcher` and `fbCore.configuration`.
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

| Tag | Constant | Before #559 |
|---|---|---|
| `fastybird.core.documents.attributeDriver` | `DocumentsExtension::DRIVER_TAG` | `fastybird.application.attribute.driver` |
| `fastybird.core.exchange.consumerState` | `ExchangeExtension::CONSUMER_STATE` | `consumer_state` |
| `fastybird.core.exchange.consumerRoutingKey` | `ExchangeExtension::CONSUMER_ROUTING_KEY` | `consumer_routing_key` |
| `fastybird.core.webSockets.routes` | `WebSocketsExtension::ROUTES_TAG` | `ipub.websockets.routes` |
| `fastybird.core.webSockets.controller` | `WebSocketsExtension::CONTROLLER_TAG` | `ipub.websockets.controller` |

## Docblocks

**No file header.** The licence is in `LICENSE.md`, the author in `composer.json`, and the
namespace supersedes `@package`. `@package`, `@subpackage`, `@author`, `@copyright`,
`@license`, `@since`, `@created`, `@version` and `@date` are forbidden. **Enforced for Core:**
`tools/phpcs.xml` runs `SlevomatCodingStandard.Commenting.ForbiddenAnnotations` with no exclusion
under `src/FastyBird/Core/Core`; `make cs` rejects any of these annotations there. The other six
package types (`Addon`, `Automator`, `Bridge`, `Connector`, `Module`, `Plugin`) are still exempt
via a shrink-only `<exclude-pattern>` list in `tools/phpcs.xml`, owned by E7 (#462).

- `@var`, `@param`, `@return`: omit where they only restate a native type. Keep for array
  shapes, generics and `@template`.
- `@throws`: **required and load-bearing.** PHPStan verifies them; an unused one fails CI.
- Class docblocks: only where they say something the signature does not.

## Naming

- Interfaces: no `I` prefix. Prefer deleting the interface entirely when it has one
  implementation and is not a DI substitution point — that remains the first choice. Where an
  interface must exist, **name it for what it does**, not with a type suffix: `IRouteParser` /
  `RouteParser` becomes an interface named for the role it expresses (e.g. `UrlGenerator`)
  alongside the concrete `RouteParser`.
- Traits: no `T` prefix, and no `…Trait` suffix either — name the capability, not the
  language construct.
- **`…Interface` and `…Trait` suffixes are rejected by `make cs`**:
  `SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming` and
  `SlevomatCodingStandard.Classes.SuperfluousTraitNaming` are active. Neither sniff objects to
  the `I`/`T` prefix — only to the suffix — but this repository rejects the prefix too, by the
  maintainer's ruling: name by role, no suffix either direction.
- No stuttering: not `Middleware\JsonApi\JsonApi`, not `Services\Phone\Phone`.

Core's `Http\Routing\` already shows why a name is needed rather than a mechanical drop of the `I`:
`IRoute`, `IRouteCollector`, `IRouteGroup`, `IRouteParser`, `IRouter` sit beside concrete
`Route`, `RouteCollector`, `RouteGroup`, `RouteParser`, `Router`. Simply deleting the `I` would
collide with the concrete class of the same name, so each interface needs a role name instead
— what it does, not what implements it.

## Code

- `final` by default. Drop it only for a class that is actually extended.
- No `Nette\SmartObject`. Typed properties make it redundant.
- `#[\Override]` on every genuine override.
- `readonly class` where every property is readonly.
- Typed class constants, enforced by `SlevomatCodingStandard.TypeHints.ClassConstantTypeHint`.
  **Active for `src/FastyBird/Core/Core` only** — the other 28 packages are excluded in
  `tools/phpcs.xml` until E7 types their remaining 1,419 constants across 563 files.
- Constructor property promotion, enforced by `SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion`.
