# Core Consolidation Design

- **Date:** 2026-09-20
- **Status:** agreed with Adam Kadlec in principle; a few marked items need confirmation on review (see §8 Open Questions)
- **Implementation plan:** not yet written — this spec feeds the writing-plans skill next

## 1. Goal

Replace the 15 packages that today provide shared, always-needed capability — 4 `Core/*`
packages (`Application`, `Exchange`, `SimpleAuth`, `Tools`), 9 `Library/*` packages
(`DateTimeFactory`, `DoctrineCrud`, `DoctrineOrmQuery`, `DoctrineTimestampable`, `JsonApi`,
`Metadata`, `Phone`, `SlimRouter`, `WebSockets`), and 2 always-on `Plugin/*` packages
(`WebServer`, `WsServer`) — with one PHP package (`fastybird/miniserver-core`) and one JS
package (`@fastybird/miniserver-core`). Every one of the other 32 extensions (Addon, Automator,
Bridge, Connector, Module, and the genuinely optional Plugins) depends on this single Core
package instead of picking individual Core/Library packages piecemeal. Core becomes the
orchestrator: the minimum an extension needs to exist at all.

This is a later, separate effort from both the original [[miniserver-merge-plan]] and this
session's `fastybird/*`/`ipub/*` vendor-absorption effort (see
[[vendor-absorption-progress]]) — same repository, same general shape of work (fold N packages
into one), but a distinct task with its own decisions.

## 2. Background, facts as of 2026-09-20

### 2.1 Why these 15 and not the others

`Plugin/ApiKey`, `Plugin/CouchDb`, `Plugin/RabbitMq`, `Plugin/RedisDb`, `Plugin/RedisDbCache`
are **not** registered by default in `config/common.neon` — `docs/configuration.md` documents
them as present in the tree but requiring manual wiring, genuine pick-one-or-none alternatives
(e.g. Redis vs CouchDb as a data store). `Plugin/WebServer` and `Plugin/WsServer` **are**
registered by default (`config/common.neon` lines 41-42) — every deployment needs an HTTP and a
WS entry point, there is no alternative implementation to swap in. That default-registration
line is the actual test for "belongs in Core" versus "stays a genuine plugin," not the `Plugin/`
directory name. The analogy: nobody treats `nette/http` as a swappable plugin either.

### 2.2 Current consumption

Counting `require` (not `require-dev`) across every extension's `composer.json`:
`fastybird/application` is required by 30 of 34 extensions, `fastybird/metadata-library` by 27,
`fastybird/tools` by 20, `fastybird/exchange` by 11, `fastybird/doctrine-orm-query-library` by
9, `fastybird/doctrine-crud-library` by 8, `fastybird/slim-router-library` by 8,
`fastybird/simple-auth` by 5, `fastybird/json-api-library` by 5, the rest (websockets, phone,
datetime-factory, doctrine-timestampable) each by 1-2. Most consumers already declare 3-6
separate `fastybird/*` requires to get "the basics" — this is the friction the consolidation
removes.

### 2.3 Reference precedent: SmartPanel

`FastyBird/smart-panel` (the structural reference project, see
[[user-adam-fastybird-maintainer]]) does not split shared capability into separate packages at
all. `apps/backend/src/modules/*` (`auth`, `config`, `tools`, `devices`, `users`, `websocket`,
`system`, ...) are plain folders inside one NestJS app, not separately versioned packages. Only
`apps/backend/src/plugins/*` — genuinely swappable device integrations, weather providers,
notification channels — are structured as separate, pluggable units. This is the same shape
proposed here: one Core orchestrator with internal structure, real plugins stay real plugins.

### 2.4 Current top-level folder census (basis for the type taxonomy in §4)

Tally across all 13 Core/Library packages' `src/` (folder name: package count):
`Exceptions` 12, `DI` 10, `Events` 7, `Entities` 6, `Types` 4, `Middleware` 3, `Mapping` 3,
`Translations` 2, `Subscribers` 2, `Schemas` 2, `Router` 2, `Http` 2, `Helpers` 2, `Documents`
2, `Application` 2, plus ~30 folders appearing in exactly one package (`Wamp`, `Utilities`,
`UI`, `Transformers`, `StringFunctions`, `Server`, `Security`, `Routing`, `Queries`,
`Publisher`, `Providers`, `Protocols`, `Presenters`, `Objects`, `ObjectMapper`, `Models`,
`Logger`, `Latte`, `JsonApi`, `Hydrators`, `Formats`, `Exchange`, `EventLoop`, `Crud`,
`Consumers`, `Clients`, `Caching`, `Boot`, `Access`, `Builder`, `Controllers`). Per-package
breakdown is in Appendix A. `Plugin/WebServer` adds `Middleware`, `Server`, `Utils`, `Http`,
`Commands`, `Subscribers`, `Application`, `Events`, `Router`, `DI`, `Exceptions`.
`Plugin/WsServer` adds only `DI`, `Exceptions`, `Commands`, `Subscribers`, `Events` — all
already-generic types.

### 2.5 Exception inventory (basis for §5)

Full per-class inventory with declared parent is in Appendix B. Headline finding: `InvalidArgument`
(13 occurrences), `InvalidState` (9), `Runtime` (5), `Logic`/`Logical` (4), `UnexpectedValue` (2),
`MalformedInput` (2) all extend the *same* PHP SPL parent in every package they appear in — they
are not domain-flavored, they are the same generic concept reimplemented once per formerly-standalone
package. `InvalidMapping` in `SimpleAuth` and `DoctrineTimestampable` are byte-for-byte identical
(confirmed by reading both files). One real inconsistency found: `Library/JsonApi/Exceptions/Logic`
extends `RuntimeException`, not `LogicException` like every other `Logic`/`Logical` — a pre-existing
bug in the vendored code that consolidation will fix by construction. One real incompatibility found:
`NotImplemented` exists in `DoctrineOrmQuery` (extends `RuntimeException`) and `WebSockets` (extends
`Nette\NotImplementedException`) — different lineages, not mergeable without a behavior change.

### 2.6 WebSockets / WAMP / WsServer / WebServer split

`Library/WebSockets` already contains the absorbed `ipub/websockets-wamp` addition under a
`Wamp\*` sub-namespace (PR #450, this session). Investigation for this design found the actual
consumer shape:

- `Library/WebSockets/src/Server/*` (`Server`, `Wrapper`, `Configuration`, `FlashWrapper`,
  `Handlers`) — the ReactPHP/Ratchet socket-accept runtime — is instantiated in exactly one
  place: `Plugin/WsServer/src/Commands/WsServer.php`, behind `fb:web-server:start`. No other
  extension touches it directly.
- `Library/WebSockets/src/Router/*` (`Route`, `RouteList`, `LinkGenerator`) and
  `Application/Controller/*` are a routing/controller-definition framework that **modules use
  directly** to define their own WAMP endpoints: `Module/Devices/src/Router/SocketRoutes.php`
  builds a `WebSockets\Router\RouteList`, `Module/Devices/src/Controllers/ExchangeV1.php`
  extends `WebSockets\Application\Controller\Controller`, `Module/Ui` does the same. These
  cannot move into `Plugin/WsServer` without making Modules depend on a Plugin to define their
  own endpoints — backwards for this layering (`tools/layering.php` already treats this
  direction as forbidden). `Module/Devices/src/DI/DevicesExtension.php` already treats the
  Server service itself as optional (`$builder->findByType(...) !== []` guards, not a hard
  `getByType()`), so the split below formalizes an existing soft dependency rather than
  introducing a new one.

Decision: `Server\*` moves to a `WsServer` domain tag (paired with the `Plugin/WsServer` plugin
that actually runs it — the plugin becomes a thin runtime shell around it). `Router\*`,
`Application\Controller\*`, and the rest of the WAMP protocol/message layer stay in Core under
one `WebSockets` domain tag — WAMP's own `Wamp\*` sub-namespace is dropped, its classes fold
directly into the `WebSockets` domain tag (no third nesting level).

Symmetrically, `Plugin/WebServer` splits the same way: its `Server\*`/`Utils` (the actual HTTP
listener runtime) becomes the `HttpServer` domain tag (renamed from `WebServer` for naming
symmetry with `WsServer` — both plugins already share the identical generic-type vocabulary:
`DI`, `Exceptions`, `Commands`, `Subscribers`, `Events`); its `Router`, `Http`, `Middleware`,
`Application` (the request-routing framework other extensions build endpoints against) stay in
Core.

The JS WAMP client (`useWampV1Client`, `Client`, absorbed from `vue-wamp-v1` into
`Library/WebSockets/assets` earlier this session, PR #452) is consumed by `Module/Ui`,
`Module/Devices`, `Connector/HomeKit`, and `Core/Application`'s own `main.ts` — none of which is
`WsServer`-specific, and `Plugin/WsServer` has no `assets/` directory at all. The JS client
stays in Core's JS package unconditionally; it has nothing to do with which backend plugin
happens to be running the server.

## 3. Decisions

| ID | Decision |
|---|---|
| D1 | One PHP package `fastybird/miniserver-core` (namespace root `FastyBird\Core\`) and one JS package `@fastybird/miniserver-core`, replacing all 15 packages named in §1. Package naming mirrors the root `fastybird/miniserver` / `@fastybird/miniserver` family. |
| D2 | Internal organization is **type-first, then former-domain, then class name**: `FastyBird\Core\<Type>\<FormerDomain>\<ClassName>`. Not a flat single namespace (collisions are real and cheap to prove — see §2.5/Appendix B), not per-domain sub-namespaces either (the "orchestrator" framing calls for organizing by architectural role, per Adam: "inside core it should be split by type - service, helper, etc."). The rule is applied uniformly, not case by case, per Adam: "the naming have to be consistent for all core files." |
| D3 | Exception markers: the 13 near-identical `Exceptions\Exception` marker interfaces collapse into one shared `FastyBird\Core\Exceptions\Exception`. |
| D4 | Exception classes that share both a name *and* an underlying SPL parent across packages merge into one shared class each: `InvalidArgument`, `InvalidState`, `Runtime`, `UnexpectedValue`, `MalformedInput`, `Logic` (fixing `JsonApi`'s wrong `RuntimeException` parent to `LogicException` as part of the merge), `InvalidMapping` (subclass of the shared `InvalidArgument`). Exceptions with genuinely distinct meaning or incompatible parents stay separate — see §5 for the full list and the `NotImplemented` incompatibility. |
| D5 | `DI` and `Boot` are structural exceptions to D2's per-domain pattern: there is exactly **one** Nette DI extension for the whole Core package (replacing the 10 existing extension classes' worth of registration logic) and exactly **one** Bootstrap, not one per former domain. |
| D6 | `Plugin/WebServer` and `Plugin/WsServer` fold into Core (not just the 13 Core/Library packages) because they are unconditionally registered in `config/common.neon`, unlike the genuinely optional plugins (`ApiKey`, `CouchDb`, `RabbitMq`, `RedisDb`, `RedisDbCache`) which stay separate, opt-in plugins. See §2.1. |
| D7 | Each plugin's actual server *runtime* (`Library/WebSockets/src/Server/*`, `Plugin/WebServer/src/Server/*` + `Utils`) becomes its own domain tag (`WsServer`, `HttpServer` respectively) inside Core, since only that one command instantiates it. Everything else the runtime depends on that other extensions also build against directly (routing/controller framework, WAMP protocol/message layer) stays under a shared `WebSockets` domain tag reachable by any extension, matching how `Module/Devices` and `Module/Ui` already define their own WAMP routes and controllers today. See §2.6. |
| D8 | WAMP's existing `Wamp\*` sub-namespace (from the `websockets-wamp` absorption, PR #450) is dropped during this consolidation — its classes fold directly into the `WebSockets` domain tag at the same level as base WebSockets classes, not nested a third level deep. |
| D9 | This lands as one large migration/consolidation PR, not phased — per Adam: "maybe this time it could be as one large migration/consolidation PR." |
| D10 | Both the PHP and JS sides consolidate together in this effort (not JS deferred to a later pass) — `Core/Application`, `Core/Tools`, `Library/Metadata`, `Library/WebSockets` all currently ship an `assets/` directory; all four fold into `@fastybird/miniserver-core`. |

## 4. Namespace & type taxonomy

Type buckets, derived from the folder census in §2.4 rather than invented generically — see
Appendix A for the complete current-folder → new-location mapping. Generic buckets used by
several former domains: `Exceptions`, `Events`, `Entities`, `Types`, `Middleware`, `Mapping`,
`Subscribers`, `Translations`, `Helpers`, `Http`, `Documents`, `Commands`, `Controllers`,
`Caching`, `Presenters`, `Providers`. Technical-layer buckets that group several one-off,
domain-flavored folders under one recognizable role rather than a generic catch-all (Approach B
from the design discussion, chosen over dumping everything into one `Services` bucket):
`Persistence` (`DoctrineCrud/Crud`, `Application/ObjectMapper`, `SimpleAuth/Models`,
`SimpleAuth/Queries`, `JsonApi/Hydrators`), `Routing` (`Application/Router`,
`WebSockets/Router`, `SlimRouter/Routing`, `WebServer/Router`), `Security`
(`SimpleAuth/Security`, `SimpleAuth/Access`). Structural exceptions to the domain-tagged
pattern: `DI` (one, whole-package), `Boot` (one, whole-package) — see D5.

## 5. Exception consolidation detail

**Merges into one shared `Core\Exceptions\<Name>` (D3/D4):** `Exception` (marker, 13→1),
`InvalidArgument` (13→1), `InvalidState` (9→1), `Runtime` (5→1), `Logic` (4→1, fixes
`JsonApi`'s parent), `UnexpectedValue` (2→1), `MalformedInput` (2→1), `InvalidMapping` (2→1,
confirmed byte-identical).

**Stays domain-tagged, genuinely distinct meaning:** `SimpleAuth`'s `Authentication`,
`ForbiddenAccess`, `UnauthorizedAccess`; `DoctrineCrud`'s `EntityCreation`,
`MissingRequiredField`; `DoctrineOrmQuery`'s `Query`, `NotImplemented`; `Phone`'s
`NoValidCountry`/`NoValidPhone`/`NoValidType` (already correctly subclass their own
`InvalidArgument` — this hierarchy carries over unchanged, just repointed at the shared
parent); `JsonApi`'s `JsonApiError`/`JsonApiMultipleError`/`JsonApi` interface; `SlimRouter`'s
whole `Http*` family (`Http`, `HttpMethodNotAllowed`, `HttpNotFound`, `HttpSpecialized`,
`StreamResourceCall`); `WebSockets`' whole WAMP-protocol family (`Abort`, `BadRequest`,
`BadResponse`, `BadSignal`, `ClientNotFound`, `ForbiddenRequest`, `InvalidController`,
`InvalidLink`, `Storage`, `Terminate`, `WebSockets`' own `NotImplemented`).

**Resolved:** `Core\Application\Exceptions\Mapping` (empty, `extends LogicException`) is
eliminated. It was behaviorally identical to the shared `Logic` exception; its few call sites
repoint at `Core\Exceptions\Logic` directly.

## 6. DI consolidation

Ten existing `DI/*Extension.php` classes (one per former package, `CompilerExtension`
subclasses) consolidate into one `FastyBird\Core\DI\CoreExtension`. This is real integration
work, not a namespace move: each existing extension's `loadConfiguration()`/`beforeCompile()`
registers its own services, decorators, and (for `WebSockets`) event-wiring guards (the
`Application\Application::class`-presence guard added in PR #450 is a concrete example of logic
that must be preserved, not just relocated). The consolidated extension needs the union of all
ten extensions' configuration schema (each currently exposes its own `getConfigSchema()`), which
risks parameter-name collisions the same way the class-name collisions in §5/Appendix B did —
this needs its own inventory pass during plan-writing, not assumed clean.

## 7. Migration scope (single PR, per D9)

Every one of the 32 remaining extensions' `composer.json` swaps its subset of the 15 old
`fastybird/*` requires for one `fastybird/miniserver-core: @dev`. Every `config/common.neon` /
`tests/common.neon` DI extension registration (`fbApplication`, `fbExchange`, `fbSimpleAuth`,
`fbTools`, `fbMetadata`, `fbJsonApi`, `fbSlimRouter`, `fbWebSockets`, `fbDateTimeFactory`,
`fbPhone`, `fbDoctrineCrud`, `fbDoctrineOrmQuery`, `fbDoctrineTimestampable`, plus the raw NEON
`factory:` registrations for classes with no DI extension of their own — SlimRouter's pattern
from PR #451, see [[vendor-absorption-progress]]) collapses to one `fbCore` line. Every `use
FastyBird\Core\Application\...` / `FastyBird\Core\Exchange\...` / `FastyBird\Core\SimpleAuth\...`
/ `FastyBird\Core\Tools\...` / `FastyBird\Library\DateTimeFactory\...` / ... / `FastyBird\Library\WebSockets\...`
import across the whole `src/FastyBird` tree gets rewritten to the new `FastyBird\Core\<Type>\<Domain>\...`
paths from the Appendix A mapping. Every `package.json` dependency on
`@fastybird/metadata-library` / `@fastybird/websockets-library` becomes
`@fastybird/miniserver-core`, and every corresponding `import ... from '@fastybird/...'`
specifier updates to match. `tools/layering.php`'s existing peer-exception entries that
reference now-merged packages (`Library/JsonApi` → `Library/SlimRouter`, from PR #451) need
re-examining — once both sides are inside Core, the exception may simply become unnecessary.

Verification bar: same discipline as the vendor-absorption effort this session (see
[[absorption-mirror-staleness-and-lsp-narrowing]] and [[vendor-absorption-progress]]) — full
`rm -rf vendor/fastybird && composer install` rebuild, a Reflection-based LSP sweep across the
*entire* `src/FastyBird` tree (not just the touched package, given every extension is a
consumer this time), two clean `make tests` runs, `pnpm types`/`pnpm build` for the JS side,
before this PR is considered ready — at a scale 30x larger than any single package absorbed so
far, so budget for this taking meaningfully longer than prior absorptions.

## 8. Open questions

1. ~~Self-named folders~~ — **resolved.** All three read and placed in Appendix A:
   `Exchange/Exchange/Factory.php` → new minimal `Factories` bucket; `JsonApi/JsonApi/`
   (actually `Encoder.php`/`SchemaContainer.php`, correcting an earlier mis-statement in this
   spec) → `Encoding`; `WebServer/Application/Application.php` → confirmed Server-runtime code,
   moves to the `HttpServer` domain tag alongside `Server`/`Utils`, no real collision once its
   contents were read.
2. ~~`Core\Application\Exceptions\Mapping`~~ — **resolved: eliminate it.** Call sites repoint
   at the shared `Core\Exceptions\Logic`.
3. **`NotImplemented`** stays as two separate exceptions (`DoctrineOrmQuery`'s and
   `WebSockets`') since they have incompatible SPL parents — confirmed acceptable, not picking
   one parent and changing the other's behavior.
4. ~~Unreviewed one-off folders~~ — **resolved.** All ~15 read and placed in Appendix A with
   their actual contents noted.
5. ~~Exchange's `Consumers`/`Publisher` bucket~~ — **resolved: new `Messaging` bucket.**
   `Core\Messaging\Exchange\` for `Consumers`/`Publisher`/`Factory`. `Persistence` keeps its
   narrower ORM/data-access meaning (`DoctrineCrud`, `SimpleAuth` `Models`/`Queries`,
   `Application/ObjectMapper`, `JsonApi/Hydrators`).

All open questions are now resolved.

## Appendix A: current folder → new location (proposed)

| Current | New |
|---|---|
| `*/Exceptions/` (12 packages) | `Core\Exceptions\<Domain>\` (generic ones merge per §5) |
| `*/DI/` (10 packages) | `Core\DI\CoreExtension` (one, not domain-tagged — D5) |
| `*/Events/` (7 packages) | `Core\Events\<Domain>\` |
| `*/Entities/` (6 packages) | `Core\Entities\<Domain>\` |
| `*/Types/` (4 packages) | `Core\Types\<Domain>\` |
| `*/Middleware/` (3 packages) | `Core\Middleware\<Domain>\` |
| `*/Mapping/` (3 packages) | `Core\Mapping\<Domain>\` |
| `*/Subscribers/` (2 packages) | `Core\Subscribers\<Domain>\` |
| `*/Translations/` (2 packages) | `Core\Translations\<Domain>\` |
| `Tools/Helpers`, `JsonApi/Helpers` | `Core\Helpers\<Domain>\` |
| `SlimRouter/Http`, `WebSockets/Http` | `Core\Http\<Domain>\` |
| `Application/Documents`, `Exchange/Documents` | `Core\Documents\<Domain>\` |
| `Tools/Schemas` (JSON validation), `JsonApi/Schemas` (JSON:API resource schemas) | `Core\Schemas\<Domain>\` (same folder name, different concepts — domain tag disambiguates) |
| `WebSockets/Commands`, `WebServer/Commands`, `WsServer/Commands` | `Core\Commands\<Domain>\` |
| `SlimRouter/Controllers` | `Core\Controllers\<Domain>\` |
| `DoctrineCrud/Crud`, `Application/ObjectMapper`, `SimpleAuth/Models`, `SimpleAuth/Queries`, `JsonApi/Hydrators` | `Core\Persistence\<Domain>\` |
| `Application/Router`, `WebSockets/Router`, `SlimRouter/Routing`, `WebServer/Router` | `Core\Routing\<Domain>\` |
| `SimpleAuth/Security`, `SimpleAuth/Access` | `Core\Security\<Domain>\` |
| `Application/Boot` | `Core\Boot` (one, not domain-tagged — D5) |
| `Application/Caching` | `Core\Caching\Application\` |
| `Application/EventLoop` | `Core\EventLoop\Application\` |
| `Application/Presenters` | `Core\Presenters\Application\` |
| `Application/UI` | `Core\UI\Application\` (content not reviewed — open question 4) |
| `SimpleAuth/Latte` (`AccessExtension.php` + `Nodes/`, a Latte macro extension for template-level access checks) | `Core\Latte\SimpleAuth\` |
| `DoctrineTimestampable/Providers` (`DateProvider.php`, the injectable "current time" source for timestampable entities) | `Core\Providers\DoctrineTimestampable\` |
| `Application/Caching` (`MemoryAdapterStorage.php`, `MemoryStorage.php`) | `Core\Caching\Application\` |
| `Application/EventLoop` (`Status.php`, `Wrapper.php` — wraps the single shared ReactPHP event loop that both `HttpServer` and `WsServer` runtimes drive) | `Core\EventLoop\Application\` |
| `Application/Presenters` (`BasePresenter.php`, `DefaultPresenter.php`) | `Core\Presenters\Application\` |
| `Application/UI` (`TemplateFactory.php`) | `Core\UI\Application\` |
| `JsonApi/Builder` (`Builder.php`, constructs a `Document` ready for encoding — output direction, pairs with `Encoder`/`SchemaContainer`/`Objects` below, not with `Hydrators`, which is input direction) | `Core\Encoding\JsonApi\` |
| `JsonApi/Objects` (30 files: the JSON:API value-object hierarchy — `StandardObject`, `ResourceObject`, `ErrorObject`, `LinkObject`, `MetaObject`, `RelationshipObject`, `SourceObject` families, from the `ipub/json-api-document` absorption, PR #449) | `Core\Encoding\JsonApi\Objects\` (internal `Objects\` subfolder preserved as-is under the domain slice — only the top-level type/domain placement changes, not JsonApi's own internal structure) |
| `JsonApi/Document.php`, `IDocument.php` (top-level, not inside a subfolder) | `Core\Encoding\JsonApi\` |
| `JsonApi/JsonApi/` (**resolved** — actually contains `Encoder.php` + `SchemaContainer.php`, not `Document.php` as an earlier pass of this spec mis-stated; corrected here) | `Core\Encoding\JsonApi\` |
| `DoctrineCrud/StringFunctions` | `Core\Helpers\DoctrineCrud\` |
| `WebSockets/Clients` (**resolved, moved from an earlier draft's `Core\Clients\WebSockets\`** — `ClientFactory`, `IClientFactory`, `Storage`, `IStorage`, `Drivers/`; this is live-connection state for *this* running server process, not shared WAMP protocol, so it belongs with the runtime) | `Core\Clients\WsServer\` |
| `WebSockets/Logger` (**resolved, moved from an earlier draft's `Core\Helpers\WebSockets\`** — `Console.php`, `Formatter/`; console output formatting for the `fb:web-server:start`/`fb:ws-server:start` command, runtime-specific) | `Core\Helpers\WsServer\` |
| `WebSockets/Server`, `WebSockets/Wamp/*` | `Core\<Type>\WsServer\` for `Server\*` (D7); everything else in `Wamp\*` distributes into the matching generic type bucket tagged `WebSockets` (D8), e.g. `Wamp/Events/*` → `Core\Events\WebSockets\` |
| `WebSockets/Encoding`, `WebSockets/Protocols` | `Core\Encoding\WebSockets\` |
| `WebServer/Server`, `WebServer/Utils` | `Core\<Type>\HttpServer\` (D7) |
| `WebServer/Application` (**resolved** — `Application.php` is the "Base application service": the actual PSR-7 HTTP request-dispatch runtime, built on `SlimRouter\Routing`. This is Server-runtime code exactly like `WebSockets\Server\Server`, not a routing framework other extensions build against — no genuine collision with the `Core/Application` former-package name once you see what's inside, it simply lands under a completely different domain tag) | `Core\<Type>\HttpServer\` (D7), alongside `Server`/`Utils` |
| `Exchange/Exchange/Factory.php`, `Exchange/Consumers`, `Exchange/Publisher` (**resolved**) | `Core\Messaging\Exchange\` — a dedicated bucket, kept separate from `Persistence` which stays scoped to ORM/data-access (`DoctrineCrud/Crud`, `SimpleAuth/Models`/`Queries`, `Application/ObjectMapper`, `JsonApi/Hydrators`) |

## Appendix B: full exception class inventory

See §2.5 for the merge/keep summary. Complete per-package listing with declared parent class was
produced during design (13 packages × their `Exceptions/*.php`, each file's `class X extends Y
implements Exception` declaration line) — reproduce with:

```bash
for d in Core/Application Core/Exchange Core/SimpleAuth Core/Tools Library/DateTimeFactory \
         Library/DoctrineCrud Library/DoctrineOrmQuery Library/DoctrineTimestampable \
         Library/JsonApi Library/Metadata Library/Phone Library/SlimRouter Library/WebSockets; do
  for f in "src/FastyBird/$d/src/Exceptions"/*.php; do
    grep -m1 -E '^(class|interface)\s' "$f"
  done
done
```
