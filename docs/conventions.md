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
last capability PR of the Core identity refactor's Epic E3, the baseline holds 0 entries** --
every alias of a Core namespace anywhere in the repository is legal. The table above is kept
as history, not current state.

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
