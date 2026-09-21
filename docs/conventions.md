# Coding conventions

These are enforced where enforcement is possible: `make cs` for docblocks and code shape,
`make naming` for identity, `make phpstan` for types. Where a rule is not machine-checkable it
says so.

## Identity

No file may name a library that `fastybird/miniserver-core` was assembled from. The 15 merged
packages were SimpleAuth, SlimRouter, DoctrineCrud, DoctrineOrmQuery, DoctrineTimestampable,
DoctrinePhone, JsonApi, JsonAPIDocument, WebSockets, WebSocketsWAMP, WsServerPlugin,
WebServerPlugin, MetadataLibrary, Tools, Exchange, DateTimeFactory and Application.

Functional and technical terms are fine, because they describe what a thing *is* rather than
which package it came from: `Http`, `WebSockets`, `Wamp`, `Server`, `Phone`, `Clock`,
`Exchange`.

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

The baseline may only shrink. A stale entry in it fails the gate.

## Namespace layout

Core is **capability-first**, following Symfony's component convention:
`Security\`, `WebSockets\`, `Api\`, `Http\`, `Persistence\`, `Values\`, `Documents\`,
`Exchange\`, `Phone\`, `Clock\`, `Logging\`.

Layer names — `Middleware`, `Subscribers`, `Entities`, `Controllers` — appear only *inside* a
capability, never at the top level. Exceptions and events live inside their capability too;
only genuinely cross-cutting exceptions sit at the root.

## Docblocks

**No file header.** The licence is in `LICENSE.md`, the author in `composer.json`, and the
namespace supersedes `@package`. `@package`, `@subpackage`, `@author`, `@copyright`,
`@license`, `@since` and `@date` are rejected by `make cs`.

- `@var`, `@param`, `@return`: omit where they only restate a native type. Keep for array
  shapes, generics and `@template`.
- `@throws`: **required and load-bearing.** PHPStan verifies them; an unused one fails CI.
- Class docblocks: only where they say something the signature does not.

## Naming

- Interfaces: no `I` prefix. Prefer no interface at all until there is a second implementation
  or a DI substitution point; otherwise the `…Interface` suffix.
- Traits: no `T` prefix. The `…Trait` suffix.
- No stuttering: not `Middleware\JsonApi\JsonApi`, not `Services\Phone\Phone`.

## Code

- `final` by default. Drop it only for a class that is actually extended.
- No `Nette\SmartObject`. Typed properties make it redundant.
- `#[\Override]` on every genuine override.
- `readonly class` where every property is readonly.
- Typed class constants, enforced by `SlevomatCodingStandard.TypeHints.ClassConstantTypeHint`.
- Constructor property promotion, enforced by `SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion`.
