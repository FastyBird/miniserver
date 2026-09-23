# E3.1 — Census, target layout and name table for Core's capability restructure

Subtask of Epic E3 (#458). Satisfies the checklist in #494. **Merging this PR is the
maintainer's approval of Core's target layout and every type name in it.** Every later E3 PR
(#496–#508) executes this document verbatim; any deviation after merge is an escalation to
#458, not a local decision (#510).

Measured against `origin/main` at `9188c4d72` (`fix(core): stop schema-tool from proposing to
drop doctrine_migrations`, #511) — one commit past the Technical Implementation Plan's baseline
of `464880ce6`. That commit added
`src/FastyBird/Core/Core/src/Subscribers/DoctrineMigrations/SchemaSubscriber.php`, so **Core
has 410 files, not 409.** It is allocated to `Persistence\Subscribers\SchemaSubscriber`,
beside `Persistence\Subscribers\TimestampableSubscriber` (its `Subscribers\DoctrineTimestampable`
predecessor) — the natural default named in this PR's own instructions, and the only file in
the tree that needed a placement decision not already covered by the Epic's draft allocation.
Every other row matches the Technical Implementation Plan's §3.5 table exactly (re-verified
below); nothing else moved between `464880ce6` and `9188c4d72`.

## Decisions for the maintainer

These are the open items #494 and #458 §1.6/§3.5/§16 name as needing the maintainer's call.
Every row in this document already reflects the recommended choice; flip a decision and only
this document (and the one downstream census row it touches) needs to change.

| # | Decision | Recommendation | One-line reason |
|---|---|---|---|
| 1 | **Interface policy (§3.6)** | **B** (default) | E3 renames only the 27 types with an external implementer, a DI-generated factory, or 2+ implementers inside Core; the 47 single-implementer candidates and 3 dead types move but keep their prefix, handed to #460 in one sweep. Renaming all 77 now (policy A) means E5 deletes several of the just-renamed ones again — the double-sweep the Epic's own reasoning argues against. The non-binding appendix below gives placeholder names for all 50 in case the maintainer prefers A. |
| 2 | `Compat\User` | **`WebSockets\Compat\User`**, not `Security\Compat\User` (Epic's default) | Its own `core.tsv` row shows it declares `namespace Nette\Security;`, not `FastyBird\Core\…` — it is a conditional polyfill (`if (!class_exists('\Nette\Security\User')) { final class User {} }`), not a Core symbol at all, so `make naming` never sees it wherever it lives. Only `WebSockets` code references `Nette\Security` in a way this shim would matter to (Epic's own measurement); Security's own files use the real `Nette\Security\User`. Placing it under `Security\` would associate a global polyfill with a capability that has no code-level dependency on it. |
| 3 | `PresenterRequest` / `PresenterResponse` | **`Presenters\Events\{PresenterRequest,PresenterResponse}`**, beside `Presenters\`, not `Security` | These describe a presenter's request/response lifecycle; structural rule 1 (Epic §"Two structural rules") says events live with the concept they describe, not with their sole listener. `Presenters\` is already one of the Definition-of-Done's allowed root namespaces, so it can carry its own `Events\` the same way a capability does. |
| 4 | `Commands\HttpServer` / `Commands\WsServer` | **`Http\Commands\HttpServer`, `WebSockets\Commands\WsServer`** (Epic's default) | No departure — `Commands` is a layer name and dissolves into the capability it starts, per structural rule 2. |
| 5 | `Constants` / `Configuration` | **Root-level types**: `FastyBird\Core\Constants`, `FastyBird\Core\Configuration` (files directly under `src/`), not a sub-namespace | A one-class sub-namespace (`Constants\Constants`, `Configuration\Configuration`) is exactly the stutter the checklist flags. Flattening removes the stutter without inventing a role name for either class. `Constants`' internal split across capabilities stays out of scope (E5, #460, per Epic §3.10). |
| 6 | `InvalidController` / `InvalidLink` / `UnexpectedValue` | **Shared root** `Exceptions\{InvalidController,InvalidLink,UnexpectedValue}` — no change | None has a consumer outside Core (verified below). `InvalidController`/`InvalidLink` are used mostly by `WebSockets` plus one `Http\Routing\LinkGenerator` reference each; `UnexpectedValue` splits between `WebSockets` and `Persistence`. Giving either capability sole ownership would introduce a new cross-capability import that does not exist today; the shared root avoids that for zero cost. |
| 7 | `Subscribers/Application/Console.php` | **`Logging\Subscribers\Console`**, not dissolved-root `Subscribers\` (which the Definition of Done does not allow at top level anyway) | Its body wires a `Monolog\Logger` and a `Symfony\Bridge\Monolog\Handler\ConsoleHandler` in response to `ConsoleEvents::COMMAND` — it is a Logging concern by content, not a generic "Application" one. A content-driven correction to the Epic's draft bucket, as #494 invites ("the census verifies it against the source and may move individual files"). |
| 8 | `Subscribers/Application/EventLoopLifeCycle.php` | **`EventLoop\Subscribers\EventLoopLifeCycle`** | Wires `EventLoop\Status` to `Events\EventLoopStarted/Stopped/Stopping`; belongs with `EventLoop\`, one of the Definition of Done's allowed root namespaces, not a bare top-level `Subscribers\`. |

## Interface and trait policy: B, with a non-binding appendix for A

Per §3.6 of the Technical Implementation Plan, policy B is the default this census is written
against:

- **27 renamed in E3** (20 surviving interfaces + 7 live traits) — see the name table below.
- **50 moved but not renamed** — the 47 single-implementer candidates and 3 dead types, handed
  to #460 (see "Hand-off list for #460" below). They keep their `I`/`T` prefix through E3; that
  prefix is *expected* in the naming baseline for these 50 FQCNs and is not a naming-gate
  violation (the validation script below treats them as an explicit allowlist).
- A **non-binding appendix** proposes a placeholder name for all 50, generated mechanically
  (drop the prefix; if that collides with a sibling type in the same target namespace, append
  `Contract`) so a maintainer decision to flip to policy A costs no second census round. These
  are explicitly *not* role names — #460 is expected to replace most of them with something
  better once it does the real per-interface judgement the Epic calls for.

## Summary counts

| Capability | Files (E3.1 census) | Epic §3.5 draft | Delta | I/T types renamed | I/T types handed off (unrenamed) |
|---|---|---|---|---|---|
| Clock | 3 | 3 | — | 0 | 0 |
| Logging | 3 | 2 | +1 (`Console.php`, decision 7) | 0 | 0 |
| Phone | 8 | 8 | — | 0 | 1 (`TPhone`, dead) |
| Values | 29 | 29 | — | 0 | 0 |
| Documents | 27 | 27 | — | 3 | 0 |
| Exchange | 13 | 13 | — | 0 | 0 |
| Persistence | 45 | 44 | +1 (`SchemaSubscriber.php`, new on `main`) | 9 | 4 |
| Api | 55 | 55 | — | 0 | 16 |
| Http | 50 | 50 | — | 1 | 8 |
| WebSockets | 103 | 102 | +1 (`Compat\User`, decision 2) | 9 | 20 |
| Security | 47 | 48 | −1 (`Compat\User` moved out, decision 2) | 5 | 1 |
| Root (dissolved) | 19 | 20 | −1 (`Console.php` moved out, decision 7) | 0 | 0 |
| Exceptions (shared root) | 8 | 8 stay | — | 0 | 0 |
| **Total** | **410** | **409** | **+1** | **27** | **50** |

(`EventLoop` and `Presenters` files above are counted under "Root (dissolved)" since neither is
one of the 11 capabilities; the per-file table below groups them under their real target
namespace.)

## The `Routing\` three-way split (before anything else, per #458)

`Routing\` is not one thing — E1 found it holds a Slim-derived HTTP router, the WAMP/WebSockets
router, and a Nette presenter registrar that share a namespace only by merge history. All 24
files, resolved individually:

| Current path | Current FQCN | Target FQCN | System |
|---|---|---|---|
| `Routing/AppRouter.php` | `FastyBird\Core\Routing\AppRouter` | `FastyBird\Core\Presenters\AppRouter` | Nette presenter registrar — dissolved at the root, beside `Presenters\` |
| `Routing/IWampRouter.php` | `FastyBird\Core\Routing\IWampRouter` | `FastyBird\Core\WebSockets\Wamp\WampRouter` | WAMP router (renamed, policy B — 2 implementers in Core) |
| `Routing/RouteList.php` | `FastyBird\Core\Routing\RouteList` | `FastyBird\Core\WebSockets\Wamp\RouteList` | WAMP router — the trap: `extends Nette\Utils\ArrayList implements IWampRouter`, tagged `@package iPublikuj:WebSockets!` |
| `Routing/WampRoute.php` | `FastyBird\Core\Routing\WampRoute` | `FastyBird\Core\WebSockets\Wamp\WampRoute` | WAMP router |
| `Routing/FastRouteDispatcher.php`, `IRoute.php`, `IRouteCollector.php`, `IRouteGroup.php`, `IRouteParser.php`, `IRouter.php`, `LinkGenerator.php`, `Route.php`, `RouteCollector.php`, `RouteGroup.php`, `RouteHandler.php`, `RouteParser.php`, `Router.php`, `RoutingResults.php`, `ServerRouter.php` (15) | `FastyBird\Core\Routing\*` | `FastyBird\Core\Http\Routing\*` (names unchanged; the 5 `I*` interfaces are single-implementer, policy-B hand-off) | Slim-derived HTTP router |
| `Routing/Handlers/IHandler.php`, `IRequestHandler.php`, `RequestHandler.php`, `RequestResponseArgsHandler.php` (dead), `RequestResponseHandler.php` (5) | `FastyBird\Core\Routing\Handlers\*` | `FastyBird\Core\Http\Routing\Handlers\*` (`IHandler` renamed → `Handler`, 3 implementers in Core; `IRequestHandler` single-implementer, hand-off) | Slim-derived HTTP router |

`RouteList`/`IWampRouter`/`WampRoute` land under `WebSockets\Wamp\`, never `Http\` — the
specific trap #458 and #510 both name as an escalation if it happens.

## Events/ and Exceptions/ assignment

**All 36 `Events/` files and all 41 `Exceptions/` files are assigned**, one owning capability
each except the 3 exceptions below. Full detail is in the grouped table; the allocation rule,
reproducible from `~/.cache/e3/target.php`'s `fbTargetNamespace()`:

- `Events/`: `LoadClassMetadata`/`PostLoad`/`PreLoad` → `Documents`; the 5 `*Message*`/`ExchangeError` → `Exchange`; `DbTransactionStarted`/`DbTransactionFinished` → `Persistence`; the 4 `HttpServer*` → `Http`; `EventLoopStarted`/`Stopped`/`Stopping` → `EventLoop`; `PresenterRequest`/`PresenterResponse` → `Presenters` (decision 3); the remaining 17 → `WebSockets`.
- `Exceptions/`: `NoValidCountry`/`NoValidPhone`/`NoValidType` → `Phone`; `InvalidValue`/`InvalidData` → `Values`; `MalformedInput` → `Documents`; `EntityCreation`/`InvalidMapping`/`MissingRequiredField`/`Query`/`QueryNotImplemented` → `Persistence`; `JsonApi`/`JsonApiError`/`JsonApiMultipleError` → `Api`; `Http`/`HttpMethodNotAllowed`/`HttpNotFound`/`HttpSpecialized`/`FileNotFound`/`StreamResourceCall` → `Http`; `Authentication`/`ForbiddenAccess`/`UnauthorizedAccess` → `Security`; `Abort`/`BadRequest`/`BadResponse`/`BadSignal`/`ClientNotFound`/`ForbiddenRequest`/`Storage`/`Terminate`/`TopicNotFound`/`WampNotImplemented` → `WebSockets`; the shared root keeps `Exception`, `InvalidArgument`, `InvalidState`, `Logic`, `Runtime`, plus `InvalidController`/`InvalidLink`/`UnexpectedValue` (decision 6, verified below).

**The 3 undecided exceptions, verified against `refs.tsv` (whole-repository, resolved
references, current tree)** — none has a consumer outside `src/FastyBird/Core/Core`:

| Exception | Consumers (all inside Core) |
|---|---|
| `InvalidController` | `Routing/LinkGenerator.php` (→ `Http`), `Controllers/WebSockets/Application.php`, `Controllers/WebSockets/Controller/ControllerFactory.php`, `Controllers/WebSockets/Controller/IControllerFactory.php` (→ `WebSockets`, 3 of 4) |
| `InvalidLink` | `Routing/LinkGenerator.php` (→ `Http`), `Controllers/WebSockets/Controller/Controller.php`, `Messaging/WebSockets/PushMessages/Pusher.php` (→ `WebSockets`, 2 of 3) |
| `UnexpectedValue` | `Server/WsServer/FlashWrapper.php` (→ `WebSockets`), `Subscribers/DoctrineTimestampable/TimestampableSubscriber.php` (→ `Persistence`) — 1 of each |

Recommendation: shared root for all three (decision 6 above).


## Name table — the 27 policy-B renames

Every surviving interface and live trait, its §1.6 classification, and its target role name
(no `I`/`T` prefix, no `…Interface`/`…Trait` suffix, per `docs/conventions.md`). Regenerable:
`php ~/.cache/e3/render_renames.php` against this census's `target.php`.

Total renamed: 27

| Current FQCN | Kind | Classification (§1.6) | Target FQCN (role name) |
|---|---|---|---|
| `FastyBird\Core\Clients\WsServer\IClientFactory` | interface | 2+ implementers inside Core | `FastyBird\Core\WebSockets\Clients\ClientProvider` |
| `FastyBird\Core\Controllers\WebSockets\Controller\IController` | interface | 2+ implementers inside Core | `FastyBird\Core\WebSockets\Controllers\Controller\RequestController` |
| `FastyBird\Core\Controllers\WebSockets\IApplication` | interface | 2+ implementers inside Core | `FastyBird\Core\WebSockets\Controllers\Dispatcher` |
| `FastyBird\Core\Controllers\WebSockets\IRequest` | interface | 2+ implementers inside Core (one of 2 IRequest interfaces -- see collision table) | `FastyBird\Core\WebSockets\Controllers\DispatchRequest` |
| `FastyBird\Core\Controllers\WebSockets\Responses\IResponse` | interface | 2+ implementers inside Core (one of 2 IResponse interfaces -- see collision table) | `FastyBird\Core\WebSockets\Controllers\Responses\ControllerResponse` |
| `FastyBird\Core\Documents\TCreatedAt` | trait | trait in use (14 files outside Core) | `FastyBird\Core\Documents\HasCreatedAt` |
| `FastyBird\Core\Documents\TOwner` | trait | trait in use (18 files outside Core) -- one of 2 TOwner traits, see collision table | `FastyBird\Core\Documents\HasOwner` |
| `FastyBird\Core\Documents\TUpdatedAt` | trait | trait in use (14 files outside Core) | `FastyBird\Core\Documents\HasUpdatedAt` |
| `FastyBird\Core\Encoding\WebSockets\IData` | interface | 2+ implementers inside Core | `FastyBird\Core\WebSockets\Encoding\FrameData` |
| `FastyBird\Core\Entities\DoctrineCrud\IEntity` | interface | external extension point (implemented outside Core) | `FastyBird\Core\Persistence\Entities\CrudEntity` |
| `FastyBird\Core\Entities\DoctrineTimestampable\IEntityCreated` | interface | external extension point (implemented outside Core) | `FastyBird\Core\Persistence\Entities\EntityCreated` |
| `FastyBird\Core\Entities\DoctrineTimestampable\IEntityUpdated` | interface | external extension point (implemented outside Core) | `FastyBird\Core\Persistence\Entities\EntityUpdated` |
| `FastyBird\Core\Entities\DoctrineTimestampable\TEntityCreated` | trait | trait in use (27 files outside Core) | `FastyBird\Core\Persistence\Entities\HasEntityCreated` |
| `FastyBird\Core\Entities\DoctrineTimestampable\TEntityUpdated` | trait | trait in use (27 files outside Core) | `FastyBird\Core\Persistence\Entities\HasEntityUpdated` |
| `FastyBird\Core\Entities\SimpleAuth\TOwner` | trait | trait in use (6 files outside Core) -- one of 2 TOwner traits, see collision table | `FastyBird\Core\Security\Entities\HasOwner` |
| `FastyBird\Core\Entities\WsServer\IClient` | interface | 2+ implementers inside Core | `FastyBird\Core\WebSockets\Entities\ConnectedClient` |
| `FastyBird\Core\Persistence\DoctrineCrud\Crud\Create\IEntityCreator` | interface | DI-generated factory (Nette setImplement(), 0 hand-written implementers) | `FastyBird\Core\Persistence\Crud\Create\EntityCreatorFactory` |
| `FastyBird\Core\Persistence\DoctrineCrud\Crud\Delete\IEntityDeleter` | interface | DI-generated factory (Nette setImplement(), 0 hand-written implementers) | `FastyBird\Core\Persistence\Crud\Delete\EntityDeleterFactory` |
| `FastyBird\Core\Persistence\DoctrineCrud\Crud\IEntityCrudFactory` | interface | DI-generated factory (Nette setImplement(), 0 hand-written implementers) | `FastyBird\Core\Persistence\Crud\CrudFactory` |
| `FastyBird\Core\Persistence\DoctrineCrud\Crud\Update\IEntityUpdater` | interface | DI-generated factory (Nette setImplement(), 0 hand-written implementers) | `FastyBird\Core\Persistence\Crud\Update\EntityUpdaterFactory` |
| `FastyBird\Core\Presenters\SimpleAuth\TSimpleAuth` | trait | trait in use (1 file outside Core) | `FastyBird\Core\Security\Presenters\HasAuthorization` |
| `FastyBird\Core\Routing\Handlers\IHandler` | interface | 2+ implementers inside Core | `FastyBird\Core\Http\Routing\Handlers\Handler` |
| `FastyBird\Core\Routing\IWampRouter` | interface | 2+ implementers inside Core | `FastyBird\Core\WebSockets\Wamp\WampRouter` |
| `FastyBird\Core\Security\SimpleAuth\IAuthenticator` | interface | external extension point (implemented outside Core) | `FastyBird\Core\Security\Identity\Authenticator` |
| `FastyBird\Core\Security\SimpleAuth\IIdentity` | interface | external extension point (implemented outside Core) | `FastyBird\Core\Security\Identity\UserIdentity` |
| `FastyBird\Core\Security\SimpleAuth\IIdentityFactory` | interface | external extension point (implemented outside Core) | `FastyBird\Core\Security\Identity\IdentityProvider` |
| `FastyBird\Core\Server\WsServer\IWrapper` | interface | 2+ implementers inside Core (also below the rename threshold, §1.9) | `FastyBird\Core\WebSockets\Server\ServerWrapper` |


## Appendix (NON-BINDING) — placeholder names for the 50 policy-B hand-off types

**Used only if the maintainer chooses policy A** (§3.6) instead of the default B. These are
mechanical placeholders, not considered role names: drop the `I`/`T` prefix; where that would
collide with a sibling type already declared in the same target namespace, append `Contract`
instead of inventing a bespoke name. `#460` is expected to replace most of these with something
better when it does the real per-interface judgement the Epic calls for — this appendix exists
only so a policy flip costs no second census round. Regenerable:
`php ~/.cache/e3/render_appendix.php`.

| Target FQCN (moved, prefix kept under policy B) | Placeholder name if policy A |
|---|---|
| `FastyBird\Core\Api\Encoding\IDocument` | `FastyBird\Core\Api\Encoding\DocumentContract` |
| `FastyBird\Core\Api\Encoding\Objects\IErrorObject` | `FastyBird\Core\Api\Encoding\Objects\ErrorObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\IErrorObjectCollection` | `FastyBird\Core\Api\Encoding\Objects\ErrorObjectCollectionContract` |
| `FastyBird\Core\Api\Encoding\Objects\ILinkObject` | `FastyBird\Core\Api\Encoding\Objects\LinkObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\ILinkObjectCollection` | `FastyBird\Core\Api\Encoding\Objects\LinkObjectCollectionContract` |
| `FastyBird\Core\Api\Encoding\Objects\IMetaObject` | `FastyBird\Core\Api\Encoding\Objects\MetaObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\IMetaObjectCollection` | `FastyBird\Core\Api\Encoding\Objects\MetaObjectCollectionContract` |
| `FastyBird\Core\Api\Encoding\Objects\IRelationshipObject` | `FastyBird\Core\Api\Encoding\Objects\RelationshipObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\IRelationshipObjectCollection` | `FastyBird\Core\Api\Encoding\Objects\RelationshipObjectCollectionContract` |
| `FastyBird\Core\Api\Encoding\Objects\IResourceIdentifierCollection` | `FastyBird\Core\Api\Encoding\Objects\ResourceIdentifierCollectionContract` |
| `FastyBird\Core\Api\Encoding\Objects\IResourceIdentifierObject` | `FastyBird\Core\Api\Encoding\Objects\ResourceIdentifierObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\IResourceObject` | `FastyBird\Core\Api\Encoding\Objects\ResourceObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\IResourceObjectCollection` | `FastyBird\Core\Api\Encoding\Objects\ResourceObjectCollectionContract` |
| `FastyBird\Core\Api\Encoding\Objects\ISourceObject` | `FastyBird\Core\Api\Encoding\Objects\SourceObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\IStandardObject` | `FastyBird\Core\Api\Encoding\Objects\StandardObjectContract` |
| `FastyBird\Core\Api\Encoding\Objects\IStandardObjectCollection` | `FastyBird\Core\Api\Encoding\Objects\StandardObjectCollectionContract` |
| `FastyBird\Core\Http\Controllers\IControllerResolver` | `FastyBird\Core\Http\Controllers\ControllerResolverContract` |
| `FastyBird\Core\Http\Middleware\IMiddlewareDispatcher` | `FastyBird\Core\Http\Middleware\MiddlewareDispatcherContract` |
| `FastyBird\Core\Http\Routing\Handlers\IRequestHandler` | `FastyBird\Core\Http\Routing\Handlers\RequestHandlerContract` |
| `FastyBird\Core\Http\Routing\IRoute` | `FastyBird\Core\Http\Routing\RouteContract` |
| `FastyBird\Core\Http\Routing\IRouteCollector` | `FastyBird\Core\Http\Routing\RouteCollectorContract` |
| `FastyBird\Core\Http\Routing\IRouteGroup` | `FastyBird\Core\Http\Routing\RouteGroupContract` |
| `FastyBird\Core\Http\Routing\IRouteParser` | `FastyBird\Core\Http\Routing\RouteParserContract` |
| `FastyBird\Core\Http\Routing\IRouter` | `FastyBird\Core\Http\Routing\RouterContract` |
| `FastyBird\Core\Persistence\Crud\IEntityCrud` | `FastyBird\Core\Persistence\Crud\EntityCrudContract` |
| `FastyBird\Core\Persistence\Entities\IEntityRemoved` | `FastyBird\Core\Persistence\Entities\EntityRemoved` |
| `FastyBird\Core\Persistence\Entities\TEntityRemoved` | `FastyBird\Core\Persistence\Entities\HasEntityRemoved` |
| `FastyBird\Core\Persistence\Mapping\IEntityMapper` | `FastyBird\Core\Persistence\Mapping\EntityMapperContract` |
| `FastyBird\Core\Phone\Entities\TPhone` | `FastyBird\Core\Phone\Entities\PhoneContract` |
| `FastyBird\Core\Security\Identity\IUserStorage` | `FastyBird\Core\Security\Identity\UserStorageContract` |
| `FastyBird\Core\WebSockets\Clients\Drivers\IDriver` | `FastyBird\Core\WebSockets\Clients\Drivers\Driver` |
| `FastyBird\Core\WebSockets\Clients\IStorage` | `FastyBird\Core\WebSockets\Clients\StorageContract` |
| `FastyBird\Core\WebSockets\Controllers\Controller\IControllerFactory` | `FastyBird\Core\WebSockets\Controllers\Controller\ControllerFactoryContract` |
| `FastyBird\Core\WebSockets\Controllers\IWampApplication` | `FastyBird\Core\WebSockets\Controllers\WampApplicationContract` |
| `FastyBird\Core\WebSockets\Encoding\IFrame` | `FastyBird\Core\WebSockets\Encoding\Frame` |
| `FastyBird\Core\WebSockets\Encoding\IMessage` | `FastyBird\Core\WebSockets\Encoding\Message` |
| `FastyBird\Core\WebSockets\Encoding\IProtocol` | `FastyBird\Core\WebSockets\Encoding\Protocol` |
| `FastyBird\Core\WebSockets\Encoding\IValidator` | `FastyBird\Core\WebSockets\Encoding\ValidatorContract` |
| `FastyBird\Core\WebSockets\Entities\IWampClient` | `FastyBird\Core\WebSockets\Entities\WampClientContract` |
| `FastyBird\Core\WebSockets\Entities\IWebSocket` | `FastyBird\Core\WebSockets\Entities\WebSocketContract` |
| `FastyBird\Core\WebSockets\Entities\PushMessages\IMessage` | `FastyBird\Core\WebSockets\Entities\PushMessages\MessageContract` |
| `FastyBird\Core\WebSockets\Entities\Topics\ITopic` | `FastyBird\Core\WebSockets\Entities\Topics\TopicContract` |
| `FastyBird\Core\WebSockets\Handshake\IRequest` | `FastyBird\Core\WebSockets\Handshake\RequestContract` |
| `FastyBird\Core\WebSockets\Handshake\IResponse` | `FastyBird\Core\WebSockets\Handshake\Response` |
| `FastyBird\Core\WebSockets\Helpers\Formatter\IFormatter` | `FastyBird\Core\WebSockets\Helpers\Formatter\Formatter` |
| `FastyBird\Core\WebSockets\PushMessages\IConsumer` | `FastyBird\Core\WebSockets\PushMessages\ConsumerContract` |
| `FastyBird\Core\WebSockets\PushMessages\IConsumersRegistry` | `FastyBird\Core\WebSockets\PushMessages\ConsumersRegistryContract` |
| `FastyBird\Core\WebSockets\PushMessages\IPusher` | `FastyBird\Core\WebSockets\PushMessages\PusherContract` |
| `FastyBird\Core\WebSockets\Topics\Drivers\IDriver` | `FastyBird\Core\WebSockets\Topics\Drivers\Driver` |
| `FastyBird\Core\WebSockets\Topics\IStorage` | `FastyBird\Core\WebSockets\Topics\StorageContract` |

## Collision table

Every pair from Epic §1.6–§1.7, plus the ones this census additionally found while validating
target FQCNs, with its resolution. "Resolved by sub-namespace" means both sides already land in
different target namespaces without any rename; "resolved by rename" means at least one side's
type name changed.

| Pair | Resolution |
|---|---|
| `IDriver` × 2 (`Clients/WsServer/Drivers`, `Topics/WsServer/Drivers`) | Sub-namespace: `WebSockets\Clients\Drivers\IDriver` vs `WebSockets\Topics\Drivers\IDriver`. Both single-implementer — policy-B hand-off, prefix kept. |
| `IStorage` × 2 (`Clients/WsServer`, `Topics/WsServer`) | Sub-namespace: `WebSockets\Clients\IStorage` vs `WebSockets\Topics\IStorage`. Both hand-off. |
| `IMessage` × 2 (`Encoding/WebSockets`, `Entities/WebSockets/PushMessages`) | Sub-namespace: `WebSockets\Encoding\IMessage` vs `WebSockets\Entities\PushMessages\IMessage`. Both single-implementer — hand-off (neither is in the Epic's "10 two-or-more-implementer" renamed group). |
| `IRequest` × 2 (`Controllers/WebSockets`, `Http`) | Rename + sub-namespace: the 2-implementer one renames to `WebSockets\Controllers\DispatchRequest`; the WS-handshake one (single-implementer) stays `WebSockets\Handshake\IRequest`. |
| `IResponse` × 2 (`Controllers/WebSockets/Responses`, `Http`) | Rename + sub-namespace: `WebSockets\Controllers\Responses\ControllerResponse` vs `WebSockets\Handshake\IResponse`. |
| `IRouter`/`Router`, `IRoute`/`Route`, `IRouteCollector`/`RouteCollector`, `IRouteGroup`/`RouteGroup`, `IRouteParser`/`RouteParser` (`Http\Routing`, 5 pairs) | Not renamed under policy B (all single-implementer) — no collision exists today since the prefix survives. If policy A is chosen, the non-binding appendix's `…Contract` suffix resolves each. |
| `TCreatedAt`/`CreatedAt`, `TOwner`/`Owner`, `TUpdatedAt`/`UpdatedAt` (`Documents`) | Rename: traits become `HasCreatedAt`/`HasOwner`/`HasUpdatedAt`; the interfaces (`CreatedAt`/`Owner`/`UpdatedAt`) are unprefixed and untouched. |
| Two `DataType`s in `Values` (`Types\Metadata\DataType` enum, `Utilities\Tools\DataType` class) | Sub-namespace: `Values\Types\DataType` (enum) vs `Values\Utilities\DataType` (class). |
| Two `Request`s in `WebSockets` (`Http\Request` handshake, `Controllers\WebSockets\Request` dispatch) | Sub-namespace: `WebSockets\Handshake\Request` vs `WebSockets\Controllers\Request`. |
| Two `Router`s in `Http` (`Routing\Router`, `Middleware\WebServer\Router`) | Sub-namespace: `Http\Routing\Router` vs `Http\Middleware\Router`. |
| Three `Phone`s (`Entities\Phone\Phone`, `Services\Phone\Phone`, `Types\Phone\Phone`) | See stutter table: `Phone\Entities\Phone` unchanged, `Phone\Services\PhoneNumberHelper`, `Phone\Types\PhoneType`. |
| Four `User`s in `Security` (`Security\SimpleAuth\User`, `Middleware\SimpleAuth\User`, `Subscribers\SimpleAuth\User`, `Compat\User`) | Sub-namespace for the first three: `Security\Identity\User`, `Security\Middleware\User`, `Security\Subscribers\User` (all unchanged names). `Compat\User` moves out of `Security` entirely (decision 2) to `WebSockets\Compat\User`, and it isn't really a `FastyBird\Core` symbol (declares `namespace Nette\Security;`), so it was never a same-namespace collision to begin with. |
| **Found by this census** — `IApplication` (2 implementers: `Application`, `WampApplication`) would collide with the concrete `Application` class on a bare I-drop | Rename: `WebSockets\Controllers\Dispatcher`. |
| **Found by this census** — `IClientFactory` (`Clients/WsServer`) would collide with the concrete `ClientFactory` on a bare I-drop | Rename: `WebSockets\Clients\ClientProvider`. |
| **Found by this census** — `IController` (`Controllers/WebSockets/Controller`) would collide with the concrete `Controller` on a bare I-drop | Rename: `WebSockets\Controllers\Controller\RequestController`. |
| **Found by this census** — `IEntityCrudFactory`/`IEntityCreator`/`IEntityUpdater`/`IEntityDeleter` (Persistence DI factories) would each collide with their concrete `Entity*` sibling on a bare I-drop | Rename: `CrudFactory`, `EntityCreatorFactory`, `EntityUpdaterFactory`, `EntityDeleterFactory`. |
| **Found by this census** — `IIdentityFactory` would collide with the concrete `IdentityFactory` on a bare I-drop | Rename: `Security\Identity\IdentityProvider`. |
| **Found by this census** — `IIdentity` → `Identity` would stutter against the `Security\Identity\` namespace | Rename: `Security\Identity\UserIdentity` (see stutter table). |
| **Found by this census** — `IWrapper` (`Server/WsServer`) would collide with the concrete `Wrapper` on a bare I-drop | Rename: `WebSockets\Server\ServerWrapper`. |

## Stutter and banned-segment renames

Every rename below is either explicitly named in #494's checklist, or a stutter the mechanical
move would newly create (found by `validate.php`, see "How this was produced"). "Stutter" means
the target namespace's last segment equals the declared type's short name.

| Current FQCN | Why | Target FQCN |
|---|---|---|
| `Services\Phone\Phone` | checklist; stutters (`Phone\Phone`) | `Phone\Services\PhoneNumberHelper` |
| `Types\Phone\Phone` | checklist ("the other two Phones"); stutters | `Phone\Types\PhoneType` (it's the Doctrine DBAL type, `getName() === 'phone'`) |
| `Entities\Phone\Phone` | checklist ("the other two Phones") | **not renamed** — `Phone\Entities\Phone` doesn't stutter (last segment `Entities` ≠ `Phone`); moving it under the `Phone` capability is enough |
| `Schemas\JsonApi\JsonApi` | checklist; stutters | `Api\Schemas\JsonApiSchema` |
| `Middleware\JsonApi\JsonApi` | checklist; stutters; also carries `// phpcs:ignoreFile` (#489) | `Api\Middleware\JsonApiMiddleware` |
| `Helpers\DoctrineCrud\Helpers` | checklist; stutters (`Helpers\Helpers`) | `Persistence\Helpers\ConstructorAutowiring` (its actual role: reflection-based constructor-argument autowiring for entity creation) |
| `Constants\Constants` | checklist; stutters | `FastyBird\Core\Constants` (flattened, decision 5) |
| `Configuration\Configuration` | checklist; stutters | `FastyBird\Core\Configuration` (flattened, decision 5) |
| `Presenters\SimpleAuth\TSimpleAuth` | checklist (T-prefix) | `Security\Presenters\HasAuthorization` |
| `Messaging\Exchange\Publisher\Publisher` | found; stutters (`Publisher\Publisher`) | `Exchange\Publisher\MessagePublisher` |
| `Messaging\Exchange\Publisher\Async\Publisher` | found; stutters | `Exchange\Publisher\Async\MessagePublisher` |
| `Controllers\WebSockets\Controller\Controller` | found; stutters (`Controller\Controller`); abstract base class | `WebSockets\Controllers\Controller\AbstractController` |
| `Server\WsServer\Server` | found; stutters (`Server\Server`) | `WebSockets\Server\ServerRuntime` |
| `Security\SimpleAuth\IIdentity` | found — renaming it to plain `Identity` (the mechanical I-drop) would stutter against the `Security\Identity\` namespace this bucket collapses into | `Security\Identity\UserIdentity` |
| `Services\DateTimeFactory\Clock` | **deliberately not fixed** — Epic §4 E3.3: "`Clock\Clock` is a temporary stutter until #460 replaces the interface with PSR-20" (§3.10) | `Clock\Clock` (unchanged; accepted) |

`Messaging\Exchange\Consumers\Consumer` is a near-miss (`Consumers` ≠ `Consumer`, not an exact
match) and is left alone — a judgement call, noted here rather than silently skipped.

## Hand-off list for #460

**50 prefixed types, moved but not renamed (policy B).** Full list with target FQCNs: see
`~/.cache/e3/handoff.txt` (reproduced in the non-binding appendix below, one row each with a
placeholder name). Regenerable: `php ~/.cache/e3/handoff_gen.php` against this census's
`target.php`.

**15 unreferenced types (Epic §1.8).** 3 of these are also on the 50-type hand-off list above
(`IEntityRemoved`, `TEntityRemoved`, `TPhone`); the other 12 are ordinary classes/enums that are
dead but not I/T-prefixed, so #460 (not policy B) decides whether to delete or keep each:

| Current FQCN | Target FQCN | Why it looks dead |
|---|---|---|
| `FastyBird\Core\Caching\Application\MemoryAdapterStorage` | `FastyBird\Core\Caching\MemoryAdapterStorage` | unreferenced; a commented-out NEON line in `Core/config/common.neon:30` is the only trace |
| `FastyBird\Core\Entities\DoctrineTimestampable\IEntityRemoved` (also hand-off) | `FastyBird\Core\Persistence\Entities\IEntityRemoved` | unreferenced anywhere |
| `FastyBird\Core\Entities\DoctrineTimestampable\TEntityRemoved` (also hand-off) | `FastyBird\Core\Persistence\Entities\TEntityRemoved` | unreferenced anywhere |
| `FastyBird\Core\Entities\Phone\TPhone` (also hand-off) | `FastyBird\Core\Phone\Entities\TPhone` | unreferenced anywhere |
| `FastyBird\Core\Exceptions\WampNotImplemented` | `FastyBird\Core\WebSockets\Exceptions\WampNotImplemented` | unreferenced anywhere |
| `FastyBird\Core\Helpers\WsServer\Formatter\Symfony` | `FastyBird\Core\WebSockets\Helpers\Formatter\Symfony` | unreferenced anywhere |
| `FastyBird\Core\Http\ScalarEntity` | `FastyBird\Core\Http\ScalarEntity` | unreferenced anywhere |
| `FastyBird\Core\Latte\SimpleAuth\AccessExtension` | `FastyBird\Core\Security\Latte\AccessExtension` | unreferenced by FQCN — likely live through Latte's own extension registration, not PHP references; #460 to confirm before deleting |
| `FastyBird\Core\Messaging\WebSockets\PushMessages\Consumer` | `FastyBird\Core\WebSockets\PushMessages\Consumer` | unreferenced anywhere |
| `FastyBird\Core\Messaging\WebSockets\PushMessages\Pusher` | `FastyBird\Core\WebSockets\PushMessages\Pusher` | unreferenced anywhere |
| `FastyBird\Core\Persistence\DoctrineCrud\Crud\EntityCrudFactory` | `FastyBird\Core\Persistence\Crud\EntityCrudFactory` | unreferenced by FQCN — likely live through DI (`CoreExtension.php`), not a direct PHP reference; #460 to confirm |
| `FastyBird\Core\Presenters\Application\DefaultPresenter` | `FastyBird\Core\Presenters\DefaultPresenter` | unreferenced by FQCN — live through the Nette presenter mask string (`CoreExtension.php:1156`), not a class reference; #460 to confirm |
| `FastyBird\Core\Routing\Handlers\RequestResponseArgsHandler` | `FastyBird\Core\Http\Routing\Handlers\RequestResponseArgsHandler` | unreferenced anywhere |
| `FastyBird\Core\Transformers\Tools\DataTypeTransformer` | `FastyBird\Core\Values\Transformers\DataTypeTransformer` | unreferenced anywhere |
| `FastyBird\Core\Types\DoctrineTimestampable\UTCDateTime` | `FastyBird\Core\Persistence\Types\UTCDateTime` | unreferenced by FQCN — live through the `utcdatetime` DBAL type name in NEON, not a class reference; #460 to confirm |

## Files below git's rename threshold (§1.9)

Measured on the current tree (`similarity.php`, upper bound: namespace line + every
`use FastyBird\Core…` line + every line using a bound alias, all counted as changed). Buckets:
`<25%` 351 files, `25–40%` 52, `40–50%` 2, `≥50%` **5** — matching the Epic's upper bound of 5
exactly, same 5 files, same order:

| File | % of non-empty lines changed | Target FQCN |
|---|---|---|
| `Types/Metadata/Sources/Connector.php` | 68% of 22 lines | `FastyBird\Core\Values\Types\Sources\Connector` |
| `Types/Metadata/Sources/Plugin.php` | 59% of 17 lines | `FastyBird\Core\Values\Types\Sources\Plugin` |
| `Types/Metadata/Sources/Bridge.php` | 56% of 16 lines | `FastyBird\Core\Values\Types\Sources\Bridge` |
| `Server/WsServer/IWrapper.php` | 55% of 11 lines | `FastyBird\Core\WebSockets\Server\ServerWrapper` (renamed, policy B) |
| `Types/Metadata/Sources/Module.php` | 50% of 14 lines | `FastyBird\Core\Values\Types\Sources\Module` |

This is an upper bound across the *whole* epic; each individual capability PR changes only a
subset of these lines, so the real per-PR count is at most 5 and likely lower. `Types\Metadata\Sources\Automator.php`
(42% of 12 lines) is the next-closest file and stays above the threshold.

## How this was produced / how to reproduce

Every number above comes from token-level parsing (`PhpToken::tokenize`) of the tree at
`origin/main` `9188c4d72`, run in the `fb-e2-app:latest` application container (matches
`docker/dev/php`, not the bare-PHP tools image — see `CLAUDE.md`'s gate-under-128M trap), never
by regex over a text window. Scripts live in `~/.cache/e3/` (copied and adapted from the
planning session's `~/.cache/e3plan/`, re-run against the current tree per this issue's
instructions):

```bash
# From the repository root, all output re-verified against a clean docker run each time:
docker run --rm -v "$PWD":/app -v "$HOME/.cache":/cache -w /app \
  -e XDEBUG_MODE=off -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC fb-e2-app:latest \
  php /cache/e3/<script>.php [args]
```

| Script | What it produces | Used for |
|---|---|---|
| `census.php <dir>` | one row per `.php` file: path, namespace, kind, name, modifiers, extends, implements | `core.tsv` (410 rows, `src/FastyBird/Core/Core/src`), `all.tsv` (3,437 rows, whole repo — matches `check-naming.php`'s own self-check floor) |
| `refs.php <repo>` | every resolved reference (code / docblock / string) from every tracked `.php` file to a Core symbol | `refs.tsv`, 7,379 rows (Epic's §1.2 measured 7,376 at `464880ce6`; +3 is `SchemaSubscriber`'s own references) |
| `alloc.php` | `fbAlloc()` — path → draft capability, from the Epic's §3.5 table, +1 rule added for `Subscribers/DoctrineMigrations/` → `Persistence` | shared by every script below |
| `target.php` | `fbTargetNamespace()` (dirname/basename → target namespace) and `$RENAMES` (current FQCN → target simple name) — **this census's actual layout decision**, one entry per directory group and per renamed type | `gen.php`, `handoff_gen.php`, `validate.php` |
| `gen.php` | applies `target.php` to every row of `core.tsv` | `final.tsv`, 410 rows: current path, current FQCN, capability, kind, target FQCN |
| `handoff_gen.php` | every I/T-prefixed interface/trait not in `$RENAMES` | `handoff.txt`, 50 rows — matches the Epic's count exactly |
| `validate.php` | checks every target FQCN in `final.tsv` against `tools/check-naming.php`'s `FB_NAMESPACE_DENYLIST`/`FB_TYPE_DENYLIST`, literal stutter (last namespace segment == short name), duplicate FQCNs, and short-name collisions within the same target namespace, against the `handoff.txt` allowlist for expected `I`/`T` prefixes | reported **zero violations** (one explicitly accepted exception: `Clock\Clock`, §4 E3.3) |
| `ifaces.php` | classifies all 77 `I`/`T`-prefixed interfaces/traits by implementer count inside/outside Core (§1.6) | `ifaces.txt` — byte-identical to the planning session's version; the new file is a class, not an I/T type, so this list is unaffected by the +1 file |
| `dead.php` | every Core symbol with zero references outside its own file | `dead.tsv`, 15 rows — byte-identical to the planning session's version |
| `similarity.php` | upper-bound rename-similarity estimate per file | rename-threshold table above — same 5 files, same order, as the planning session |
| `metrics.php` | per-capability rollup (files, referencing files, packages, PHPStan/naming-baseline hits) | cross-checked against Epic §3.5's table; identical except Persistence +1 (the new file) |
| `groupby.php` | groups `core.tsv` by exact `dirname()`, 138 distinct groups | the input this census's `target.php` was hand-designed against, one decision per group rather than per file |

Where this census's measurement disagrees with #458's §3.5 draft: only the file count
(409 → 410, the one new file) and the two capability counts it shifts (`Persistence` 44 → 45)
plus the two departures in the "Decisions for the maintainer" table (`Compat\User` to
`WebSockets` instead of `Security`; `Console.php`/`EventLoopLifeCycle.php` out of the generic
"root" bucket into `Logging`/`EventLoop`). Nothing else in §3.5's table changed.


## Full file-by-file table (410 rows, grouped by capability)

One row per file under `src/FastyBird/Core/Core/src`, grouped by target capability, sorted by
current path within each group. Regenerable: `php ~/.cache/e3/render.php` against `final.tsv`.


### Clock (3 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Services/DateTimeFactory/Clock.php` | `FastyBird\Core\Services\DateTimeFactory\Clock` | interface | `FastyBird\Core\Clock\Clock` |
| `Services/DateTimeFactory/FrozenClock.php` | `FastyBird\Core\Services\DateTimeFactory\FrozenClock` | class | `FastyBird\Core\Clock\FrozenClock` |
| `Services/DateTimeFactory/SystemClock.php` | `FastyBird\Core\Services\DateTimeFactory\SystemClock` | class | `FastyBird\Core\Clock\SystemClock` |

### Logging (3 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Helpers/Tools/Logger.php` | `FastyBird\Core\Helpers\Tools\Logger` | class | `FastyBird\Core\Logging\Logger` |
| `Helpers/Tools/Sentry.php` | `FastyBird\Core\Helpers\Tools\Sentry` | class | `FastyBird\Core\Logging\Sentry` |
| `Subscribers/Application/Console.php` | `FastyBird\Core\Subscribers\Application\Console` | class | `FastyBird\Core\Logging\Subscribers\Console` |

### Phone (8 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Entities/Phone/Phone.php` | `FastyBird\Core\Entities\Phone\Phone` | class | `FastyBird\Core\Phone\Entities\Phone` |
| `Entities/Phone/TPhone.php` | `FastyBird\Core\Entities\Phone\TPhone` | trait | `FastyBird\Core\Phone\Entities\TPhone` |
| `Exceptions/NoValidCountry.php` | `FastyBird\Core\Exceptions\NoValidCountry` | class | `FastyBird\Core\Phone\Exceptions\NoValidCountry` |
| `Exceptions/NoValidPhone.php` | `FastyBird\Core\Exceptions\NoValidPhone` | class | `FastyBird\Core\Phone\Exceptions\NoValidPhone` |
| `Exceptions/NoValidType.php` | `FastyBird\Core\Exceptions\NoValidType` | class | `FastyBird\Core\Phone\Exceptions\NoValidType` |
| `Services/Phone/Phone.php` | `FastyBird\Core\Services\Phone\Phone` | class | `FastyBird\Core\Phone\Services\PhoneNumberHelper` |
| `Subscribers/Phone/PhoneObjectSubscriber.php` | `FastyBird\Core\Subscribers\Phone\PhoneObjectSubscriber` | class | `FastyBird\Core\Phone\Subscribers\PhoneObjectSubscriber` |
| `Types/Phone/Phone.php` | `FastyBird\Core\Types\Phone\Phone` | class | `FastyBird\Core\Phone\Types\PhoneType` |

### Values (29 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Exceptions/InvalidData.php` | `FastyBird\Core\Exceptions\InvalidData` | class | `FastyBird\Core\Values\Exceptions\InvalidData` |
| `Exceptions/InvalidValue.php` | `FastyBird\Core\Exceptions\InvalidValue` | class | `FastyBird\Core\Values\Exceptions\InvalidValue` |
| `Formats/Tools/CombinedEnum.php` | `FastyBird\Core\Formats\Tools\CombinedEnum` | class | `FastyBird\Core\Values\Formats\CombinedEnum` |
| `Formats/Tools/CombinedEnumItem.php` | `FastyBird\Core\Formats\Tools\CombinedEnumItem` | class | `FastyBird\Core\Values\Formats\CombinedEnumItem` |
| `Formats/Tools/NumberRange.php` | `FastyBird\Core\Formats\Tools\NumberRange` | class | `FastyBird\Core\Values\Formats\NumberRange` |
| `Formats/Tools/StringEnum.php` | `FastyBird\Core\Formats\Tools\StringEnum` | class | `FastyBird\Core\Values\Formats\StringEnum` |
| `Schemas/Tools/Validator.php` | `FastyBird\Core\Schemas\Tools\Validator` | class | `FastyBird\Core\Values\Schemas\Validator` |
| `Transformers/Tools/DataTypeTransformer.php` | `FastyBird\Core\Transformers\Tools\DataTypeTransformer` | class | `FastyBird\Core\Values\Transformers\DataTypeTransformer` |
| `Transformers/Tools/EquationTransformer.php` | `FastyBird\Core\Transformers\Tools\EquationTransformer` | class | `FastyBird\Core\Values\Transformers\EquationTransformer` |
| `Transformers/Tools/HsbTransformer.php` | `FastyBird\Core\Transformers\Tools\HsbTransformer` | class | `FastyBird\Core\Values\Transformers\HsbTransformer` |
| `Transformers/Tools/HsiTransformer.php` | `FastyBird\Core\Transformers\Tools\HsiTransformer` | class | `FastyBird\Core\Values\Transformers\HsiTransformer` |
| `Transformers/Tools/MiredTransformer.php` | `FastyBird\Core\Transformers\Tools\MiredTransformer` | class | `FastyBird\Core\Values\Transformers\MiredTransformer` |
| `Transformers/Tools/RgbTransformer.php` | `FastyBird\Core\Transformers\Tools\RgbTransformer` | class | `FastyBird\Core\Values\Transformers\RgbTransformer` |
| `Transformers/Tools/Transformer.php` | `FastyBird\Core\Transformers\Tools\Transformer` | interface | `FastyBird\Core\Values\Transformers\Transformer` |
| `Types/Metadata/DataType.php` | `FastyBird\Core\Types\Metadata\DataType` | enum | `FastyBird\Core\Values\Types\DataType` |
| `Types/Metadata/DataTypeShort.php` | `FastyBird\Core\Types\Metadata\DataTypeShort` | enum | `FastyBird\Core\Values\Types\DataTypeShort` |
| `Types/Metadata/Payloads/Button.php` | `FastyBird\Core\Types\Metadata\Payloads\Button` | enum | `FastyBird\Core\Values\Types\Payloads\Button` |
| `Types/Metadata/Payloads/Cover.php` | `FastyBird\Core\Types\Metadata\Payloads\Cover` | enum | `FastyBird\Core\Values\Types\Payloads\Cover` |
| `Types/Metadata/Payloads/Payload.php` | `FastyBird\Core\Types\Metadata\Payloads\Payload` | interface | `FastyBird\Core\Values\Types\Payloads\Payload` |
| `Types/Metadata/Payloads/Switcher.php` | `FastyBird\Core\Types\Metadata\Payloads\Switcher` | enum | `FastyBird\Core\Values\Types\Payloads\Switcher` |
| `Types/Metadata/Sources/Addon.php` | `FastyBird\Core\Types\Metadata\Sources\Addon` | enum | `FastyBird\Core\Values\Types\Sources\Addon` |
| `Types/Metadata/Sources/Automator.php` | `FastyBird\Core\Types\Metadata\Sources\Automator` | enum | `FastyBird\Core\Values\Types\Sources\Automator` |
| `Types/Metadata/Sources/Bridge.php` | `FastyBird\Core\Types\Metadata\Sources\Bridge` | enum | `FastyBird\Core\Values\Types\Sources\Bridge` |
| `Types/Metadata/Sources/Connector.php` | `FastyBird\Core\Types\Metadata\Sources\Connector` | enum | `FastyBird\Core\Values\Types\Sources\Connector` |
| `Types/Metadata/Sources/Module.php` | `FastyBird\Core\Types\Metadata\Sources\Module` | enum | `FastyBird\Core\Values\Types\Sources\Module` |
| `Types/Metadata/Sources/Plugin.php` | `FastyBird\Core\Types\Metadata\Sources\Plugin` | enum | `FastyBird\Core\Values\Types\Sources\Plugin` |
| `Types/Metadata/Sources/Source.php` | `FastyBird\Core\Types\Metadata\Sources\Source` | interface | `FastyBird\Core\Values\Types\Sources\Source` |
| `Utilities/Tools/DataType.php` | `FastyBird\Core\Utilities\Tools\DataType` | class | `FastyBird\Core\Values\Utilities\DataType` |
| `Utilities/Tools/Value.php` | `FastyBird\Core\Utilities\Tools\Value` | class | `FastyBird\Core\Values\Utilities\Value` |

### Documents (27 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Documents/CreatedAt.php` | `FastyBird\Core\Documents\CreatedAt` | interface | `FastyBird\Core\Documents\CreatedAt` |
| `Documents/Document.php` | `FastyBird\Core\Documents\Document` | interface | `FastyBird\Core\Documents\Document` |
| `Documents/DocumentFactory.php` | `FastyBird\Core\Documents\DocumentFactory` | class | `FastyBird\Core\Documents\DocumentFactory` |
| `Documents/Mapping/ClassMetadata.php` | `FastyBird\Core\Documents\Mapping\ClassMetadata` | class | `FastyBird\Core\Documents\Mapping\ClassMetadata` |
| `Documents/Mapping/ClassMetadataFactory.php` | `FastyBird\Core\Documents\Mapping\ClassMetadataFactory` | class | `FastyBird\Core\Documents\Mapping\ClassMetadataFactory` |
| `Documents/Mapping/DiscriminatorColumn.php` | `FastyBird\Core\Documents\Mapping\DiscriminatorColumn` | class | `FastyBird\Core\Documents\Mapping\DiscriminatorColumn` |
| `Documents/Mapping/DiscriminatorEntry.php` | `FastyBird\Core\Documents\Mapping\DiscriminatorEntry` | class | `FastyBird\Core\Documents\Mapping\DiscriminatorEntry` |
| `Documents/Mapping/DiscriminatorMap.php` | `FastyBird\Core\Documents\Mapping\DiscriminatorMap` | class | `FastyBird\Core\Documents\Mapping\DiscriminatorMap` |
| `Documents/Mapping/Document.php` | `FastyBird\Core\Documents\Mapping\Document` | class | `FastyBird\Core\Documents\Mapping\Document` |
| `Documents/Mapping/Driver/AttributeDriver.php` | `FastyBird\Core\Documents\Mapping\Driver\AttributeDriver` | class | `FastyBird\Core\Documents\Mapping\Driver\AttributeDriver` |
| `Documents/Mapping/Driver/AttributeReader.php` | `FastyBird\Core\Documents\Mapping\Driver\AttributeReader` | class | `FastyBird\Core\Documents\Mapping\Driver\AttributeReader` |
| `Documents/Mapping/Driver/MappingDriver.php` | `FastyBird\Core\Documents\Mapping\Driver\MappingDriver` | interface | `FastyBird\Core\Documents\Mapping\Driver\MappingDriver` |
| `Documents/Mapping/Driver/MappingDriverChain.php` | `FastyBird\Core\Documents\Mapping\Driver\MappingDriverChain` | class | `FastyBird\Core\Documents\Mapping\Driver\MappingDriverChain` |
| `Documents/Mapping/InheritanceType.php` | `FastyBird\Core\Documents\Mapping\InheritanceType` | class | `FastyBird\Core\Documents\Mapping\InheritanceType` |
| `Documents/Mapping/MappedSuperclass.php` | `FastyBird\Core\Documents\Mapping\MappedSuperclass` | class | `FastyBird\Core\Documents\Mapping\MappedSuperclass` |
| `Documents/Mapping/MappingAttribute.php` | `FastyBird\Core\Documents\Mapping\MappingAttribute` | interface | `FastyBird\Core\Documents\Mapping\MappingAttribute` |
| `Documents/Mapping/RoutingMap.php` | `FastyBird\Core\Documents\Mapping\RoutingMap` | class | `FastyBird\Core\Documents\Mapping\RoutingMap` |
| `Documents/Owner.php` | `FastyBird\Core\Documents\Owner` | interface | `FastyBird\Core\Documents\Owner` |
| `Documents/RoutingDocumentFactory.php` | `FastyBird\Core\Documents\RoutingDocumentFactory` | class | `FastyBird\Core\Documents\RoutingDocumentFactory` |
| `Documents/TCreatedAt.php` | `FastyBird\Core\Documents\TCreatedAt` | trait | `FastyBird\Core\Documents\HasCreatedAt` |
| `Documents/TOwner.php` | `FastyBird\Core\Documents\TOwner` | trait | `FastyBird\Core\Documents\HasOwner` |
| `Documents/TUpdatedAt.php` | `FastyBird\Core\Documents\TUpdatedAt` | trait | `FastyBird\Core\Documents\HasUpdatedAt` |
| `Documents/UpdatedAt.php` | `FastyBird\Core\Documents\UpdatedAt` | interface | `FastyBird\Core\Documents\UpdatedAt` |
| `Events/LoadClassMetadata.php` | `FastyBird\Core\Events\LoadClassMetadata` | class | `FastyBird\Core\Documents\Events\LoadClassMetadata` |
| `Events/PostLoad.php` | `FastyBird\Core\Events\PostLoad` | class | `FastyBird\Core\Documents\Events\PostLoad` |
| `Events/PreLoad.php` | `FastyBird\Core\Events\PreLoad` | class | `FastyBird\Core\Documents\Events\PreLoad` |
| `Exceptions/MalformedInput.php` | `FastyBird\Core\Exceptions\MalformedInput` | class | `FastyBird\Core\Documents\Exceptions\MalformedInput` |

### Exchange (13 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Events/AfterMessageConsumed.php` | `FastyBird\Core\Events\AfterMessageConsumed` | class | `FastyBird\Core\Exchange\Events\AfterMessageConsumed` |
| `Events/AfterMessagePublished.php` | `FastyBird\Core\Events\AfterMessagePublished` | class | `FastyBird\Core\Exchange\Events\AfterMessagePublished` |
| `Events/BeforeMessageConsumed.php` | `FastyBird\Core\Events\BeforeMessageConsumed` | class | `FastyBird\Core\Exchange\Events\BeforeMessageConsumed` |
| `Events/BeforeMessagePublished.php` | `FastyBird\Core\Events\BeforeMessagePublished` | class | `FastyBird\Core\Exchange\Events\BeforeMessagePublished` |
| `Events/ExchangeError.php` | `FastyBird\Core\Events\ExchangeError` | class | `FastyBird\Core\Exchange\Events\ExchangeError` |
| `Messaging/Exchange/Consumers/Consumer.php` | `FastyBird\Core\Messaging\Exchange\Consumers\Consumer` | interface | `FastyBird\Core\Exchange\Consumers\Consumer` |
| `Messaging/Exchange/Consumers/Container.php` | `FastyBird\Core\Messaging\Exchange\Consumers\Container` | class | `FastyBird\Core\Exchange\Consumers\Container` |
| `Messaging/Exchange/Consumers/Info.php` | `FastyBird\Core\Messaging\Exchange\Consumers\Info` | class | `FastyBird\Core\Exchange\Consumers\Info` |
| `Messaging/Exchange/Factory.php` | `FastyBird\Core\Messaging\Exchange\Factory` | interface | `FastyBird\Core\Exchange\Factory` |
| `Messaging/Exchange/Publisher/Async/Container.php` | `FastyBird\Core\Messaging\Exchange\Publisher\Async\Container` | class | `FastyBird\Core\Exchange\Publisher\Async\Container` |
| `Messaging/Exchange/Publisher/Async/Publisher.php` | `FastyBird\Core\Messaging\Exchange\Publisher\Async\Publisher` | interface | `FastyBird\Core\Exchange\Publisher\Async\MessagePublisher` |
| `Messaging/Exchange/Publisher/Container.php` | `FastyBird\Core\Messaging\Exchange\Publisher\Container` | class | `FastyBird\Core\Exchange\Publisher\Container` |
| `Messaging/Exchange/Publisher/Publisher.php` | `FastyBird\Core\Messaging\Exchange\Publisher\Publisher` | interface | `FastyBird\Core\Exchange\Publisher\MessagePublisher` |

### Persistence (45 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Entities/Application/Mapping/DiscriminatorEntry.php` | `FastyBird\Core\Entities\Application\Mapping\DiscriminatorEntry` | class | `FastyBird\Core\Persistence\Mapping\DiscriminatorEntry` |
| `Entities/DoctrineCrud/IEntity.php` | `FastyBird\Core\Entities\DoctrineCrud\IEntity` | interface | `FastyBird\Core\Persistence\Entities\CrudEntity` |
| `Entities/DoctrineTimestampable/IEntityCreated.php` | `FastyBird\Core\Entities\DoctrineTimestampable\IEntityCreated` | interface | `FastyBird\Core\Persistence\Entities\EntityCreated` |
| `Entities/DoctrineTimestampable/IEntityRemoved.php` | `FastyBird\Core\Entities\DoctrineTimestampable\IEntityRemoved` | interface | `FastyBird\Core\Persistence\Entities\IEntityRemoved` |
| `Entities/DoctrineTimestampable/IEntityUpdated.php` | `FastyBird\Core\Entities\DoctrineTimestampable\IEntityUpdated` | interface | `FastyBird\Core\Persistence\Entities\EntityUpdated` |
| `Entities/DoctrineTimestampable/TEntityCreated.php` | `FastyBird\Core\Entities\DoctrineTimestampable\TEntityCreated` | trait | `FastyBird\Core\Persistence\Entities\HasEntityCreated` |
| `Entities/DoctrineTimestampable/TEntityRemoved.php` | `FastyBird\Core\Entities\DoctrineTimestampable\TEntityRemoved` | trait | `FastyBird\Core\Persistence\Entities\TEntityRemoved` |
| `Entities/DoctrineTimestampable/TEntityUpdated.php` | `FastyBird\Core\Entities\DoctrineTimestampable\TEntityUpdated` | trait | `FastyBird\Core\Persistence\Entities\HasEntityUpdated` |
| `Events/DbTransactionFinished.php` | `FastyBird\Core\Events\DbTransactionFinished` | class | `FastyBird\Core\Persistence\Events\DbTransactionFinished` |
| `Events/DbTransactionStarted.php` | `FastyBird\Core\Events\DbTransactionStarted` | class | `FastyBird\Core\Persistence\Events\DbTransactionStarted` |
| `Exceptions/EntityCreation.php` | `FastyBird\Core\Exceptions\EntityCreation` | class | `FastyBird\Core\Persistence\Exceptions\EntityCreation` |
| `Exceptions/InvalidMapping.php` | `FastyBird\Core\Exceptions\InvalidMapping` | class | `FastyBird\Core\Persistence\Exceptions\InvalidMapping` |
| `Exceptions/MissingRequiredField.php` | `FastyBird\Core\Exceptions\MissingRequiredField` | class | `FastyBird\Core\Persistence\Exceptions\MissingRequiredField` |
| `Exceptions/Query.php` | `FastyBird\Core\Exceptions\Query` | class | `FastyBird\Core\Persistence\Exceptions\Query` |
| `Exceptions/QueryNotImplemented.php` | `FastyBird\Core\Exceptions\QueryNotImplemented` | class | `FastyBird\Core\Persistence\Exceptions\QueryNotImplemented` |
| `Helpers/DoctrineCrud/Helpers.php` | `FastyBird\Core\Helpers\DoctrineCrud\Helpers` | class | `FastyBird\Core\Persistence\Helpers\ConstructorAutowiring` |
| `Helpers/DoctrineCrud/StringFunctions/DateFormat.php` | `FastyBird\Core\Helpers\DoctrineCrud\StringFunctions\DateFormat` | class | `FastyBird\Core\Persistence\Helpers\StringFunctions\DateFormat` |
| `Helpers/Tools/Database.php` | `FastyBird\Core\Helpers\Tools\Database` | class | `FastyBird\Core\Persistence\Helpers\Database` |
| `Mapping/DoctrineCrud/Attribute/Crud.php` | `FastyBird\Core\Mapping\DoctrineCrud\Attribute\Crud` | class | `FastyBird\Core\Persistence\Mapping\Attribute\Crud` |
| `Mapping/DoctrineCrud/EntityMapper.php` | `FastyBird\Core\Mapping\DoctrineCrud\EntityMapper` | class | `FastyBird\Core\Persistence\Mapping\EntityMapper` |
| `Mapping/DoctrineCrud/IEntityMapper.php` | `FastyBird\Core\Mapping\DoctrineCrud\IEntityMapper` | interface | `FastyBird\Core\Persistence\Mapping\IEntityMapper` |
| `Mapping/DoctrineTimestampable/Annotation/Timestampable.php` | `FastyBird\Core\Mapping\DoctrineTimestampable\Annotation\Timestampable` | class | `FastyBird\Core\Persistence\Mapping\Annotation\Timestampable` |
| `Mapping/DoctrineTimestampable/Driver/Timestampable.php` | `FastyBird\Core\Mapping\DoctrineTimestampable\Driver\Timestampable` | class | `FastyBird\Core\Persistence\Mapping\Driver\Timestampable` |
| `Persistence/Application/Rules/UuidArgs.php` | `FastyBird\Core\Persistence\Application\Rules\UuidArgs` | class | `FastyBird\Core\Persistence\Rules\UuidArgs` |
| `Persistence/Application/Rules/UuidRule.php` | `FastyBird\Core\Persistence\Application\Rules\UuidRule` | class | `FastyBird\Core\Persistence\Rules\UuidRule` |
| `Persistence/Application/Rules/UuidValue.php` | `FastyBird\Core\Persistence\Application\Rules\UuidValue` | class | `FastyBird\Core\Persistence\Rules\UuidValue` |
| `Persistence/DoctrineCrud/Crud/Create/EntityCreator.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\Create\EntityCreator` | class | `FastyBird\Core\Persistence\Crud\Create\EntityCreator` |
| `Persistence/DoctrineCrud/Crud/Create/IEntityCreator.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\Create\IEntityCreator` | interface | `FastyBird\Core\Persistence\Crud\Create\EntityCreatorFactory` |
| `Persistence/DoctrineCrud/Crud/CrudManager.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\CrudManager` | class | `FastyBird\Core\Persistence\Crud\CrudManager` |
| `Persistence/DoctrineCrud/Crud/Delete/EntityDeleter.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\Delete\EntityDeleter` | class | `FastyBird\Core\Persistence\Crud\Delete\EntityDeleter` |
| `Persistence/DoctrineCrud/Crud/Delete/IEntityDeleter.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\Delete\IEntityDeleter` | interface | `FastyBird\Core\Persistence\Crud\Delete\EntityDeleterFactory` |
| `Persistence/DoctrineCrud/Crud/EntityCrud.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\EntityCrud` | class | `FastyBird\Core\Persistence\Crud\EntityCrud` |
| `Persistence/DoctrineCrud/Crud/EntityCrudFactory.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\EntityCrudFactory` | class | `FastyBird\Core\Persistence\Crud\EntityCrudFactory` |
| `Persistence/DoctrineCrud/Crud/IEntityCrud.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\IEntityCrud` | interface | `FastyBird\Core\Persistence\Crud\IEntityCrud` |
| `Persistence/DoctrineCrud/Crud/IEntityCrudFactory.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\IEntityCrudFactory` | interface | `FastyBird\Core\Persistence\Crud\CrudFactory` |
| `Persistence/DoctrineCrud/Crud/Update/EntityUpdater.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\Update\EntityUpdater` | class | `FastyBird\Core\Persistence\Crud\Update\EntityUpdater` |
| `Persistence/DoctrineCrud/Crud/Update/IEntityUpdater.php` | `FastyBird\Core\Persistence\DoctrineCrud\Crud\Update\IEntityUpdater` | interface | `FastyBird\Core\Persistence\Crud\Update\EntityUpdaterFactory` |
| `Persistence/DoctrineOrmQuery/QueryObject.php` | `FastyBird\Core\Persistence\DoctrineOrmQuery\QueryObject` | class | `FastyBird\Core\Persistence\Query\QueryObject` |
| `Persistence/DoctrineOrmQuery/ResultSet.php` | `FastyBird\Core\Persistence\DoctrineOrmQuery\ResultSet` | class | `FastyBird\Core\Persistence\Query\ResultSet` |
| `Providers/DoctrineTimestampable/DateProvider.php` | `FastyBird\Core\Providers\DoctrineTimestampable\DateProvider` | interface | `FastyBird\Core\Persistence\Providers\DateProvider` |
| `Subscribers/Application/EntityDiscriminator.php` | `FastyBird\Core\Subscribers\Application\EntityDiscriminator` | class | `FastyBird\Core\Persistence\Subscribers\EntityDiscriminator` |
| `Subscribers/DoctrineMigrations/SchemaSubscriber.php` | `FastyBird\Core\Subscribers\DoctrineMigrations\SchemaSubscriber` | class | `FastyBird\Core\Persistence\Subscribers\SchemaSubscriber` |
| `Subscribers/DoctrineTimestampable/TimestampableSubscriber.php` | `FastyBird\Core\Subscribers\DoctrineTimestampable\TimestampableSubscriber` | class | `FastyBird\Core\Persistence\Subscribers\TimestampableSubscriber` |
| `Types/DoctrineTimestampable/UTCDateTime.php` | `FastyBird\Core\Types\DoctrineTimestampable\UTCDateTime` | class | `FastyBird\Core\Persistence\Types\UTCDateTime` |
| `Utilities/Tools/DateTimeProvider.php` | `FastyBird\Core\Utilities\Tools\DateTimeProvider` | class | `FastyBird\Core\Persistence\Utilities\DateTimeProvider` |

### Api (55 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Encoding/JsonApi/Builder.php` | `FastyBird\Core\Encoding\JsonApi\Builder` | class | `FastyBird\Core\Api\Encoding\Builder` |
| `Encoding/JsonApi/Document.php` | `FastyBird\Core\Encoding\JsonApi\Document` | class | `FastyBird\Core\Api\Encoding\Document` |
| `Encoding/JsonApi/Encoder.php` | `FastyBird\Core\Encoding\JsonApi\Encoder` | class | `FastyBird\Core\Api\Encoding\Encoder` |
| `Encoding/JsonApi/IDocument.php` | `FastyBird\Core\Encoding\JsonApi\IDocument` | interface | `FastyBird\Core\Api\Encoding\IDocument` |
| `Encoding/JsonApi/Objects/ErrorObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ErrorObject` | class | `FastyBird\Core\Api\Encoding\Objects\ErrorObject` |
| `Encoding/JsonApi/Objects/ErrorObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ErrorObjectCollection` | class | `FastyBird\Core\Api\Encoding\Objects\ErrorObjectCollection` |
| `Encoding/JsonApi/Objects/IErrorObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IErrorObject` | interface | `FastyBird\Core\Api\Encoding\Objects\IErrorObject` |
| `Encoding/JsonApi/Objects/IErrorObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IErrorObjectCollection` | interface | `FastyBird\Core\Api\Encoding\Objects\IErrorObjectCollection` |
| `Encoding/JsonApi/Objects/ILinkObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ILinkObject` | interface | `FastyBird\Core\Api\Encoding\Objects\ILinkObject` |
| `Encoding/JsonApi/Objects/ILinkObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ILinkObjectCollection` | interface | `FastyBird\Core\Api\Encoding\Objects\ILinkObjectCollection` |
| `Encoding/JsonApi/Objects/IMetaObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IMetaObject` | interface | `FastyBird\Core\Api\Encoding\Objects\IMetaObject` |
| `Encoding/JsonApi/Objects/IMetaObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IMetaObjectCollection` | interface | `FastyBird\Core\Api\Encoding\Objects\IMetaObjectCollection` |
| `Encoding/JsonApi/Objects/IRelationshipObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IRelationshipObject` | interface | `FastyBird\Core\Api\Encoding\Objects\IRelationshipObject` |
| `Encoding/JsonApi/Objects/IRelationshipObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IRelationshipObjectCollection` | interface | `FastyBird\Core\Api\Encoding\Objects\IRelationshipObjectCollection` |
| `Encoding/JsonApi/Objects/IResourceIdentifierCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IResourceIdentifierCollection` | interface | `FastyBird\Core\Api\Encoding\Objects\IResourceIdentifierCollection` |
| `Encoding/JsonApi/Objects/IResourceIdentifierObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IResourceIdentifierObject` | interface | `FastyBird\Core\Api\Encoding\Objects\IResourceIdentifierObject` |
| `Encoding/JsonApi/Objects/IResourceObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IResourceObject` | interface | `FastyBird\Core\Api\Encoding\Objects\IResourceObject` |
| `Encoding/JsonApi/Objects/IResourceObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IResourceObjectCollection` | interface | `FastyBird\Core\Api\Encoding\Objects\IResourceObjectCollection` |
| `Encoding/JsonApi/Objects/ISourceObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ISourceObject` | interface | `FastyBird\Core\Api\Encoding\Objects\ISourceObject` |
| `Encoding/JsonApi/Objects/IStandardObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IStandardObject` | interface | `FastyBird\Core\Api\Encoding\Objects\IStandardObject` |
| `Encoding/JsonApi/Objects/IStandardObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\IStandardObjectCollection` | interface | `FastyBird\Core\Api\Encoding\Objects\IStandardObjectCollection` |
| `Encoding/JsonApi/Objects/LinkObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\LinkObject` | class | `FastyBird\Core\Api\Encoding\Objects\LinkObject` |
| `Encoding/JsonApi/Objects/LinkObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\LinkObjectCollection` | class | `FastyBird\Core\Api\Encoding\Objects\LinkObjectCollection` |
| `Encoding/JsonApi/Objects/MetaObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\MetaObject` | class | `FastyBird\Core\Api\Encoding\Objects\MetaObject` |
| `Encoding/JsonApi/Objects/MetaObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\MetaObjectCollection` | class | `FastyBird\Core\Api\Encoding\Objects\MetaObjectCollection` |
| `Encoding/JsonApi/Objects/Obj.php` | `FastyBird\Core\Encoding\JsonApi\Objects\Obj` | class | `FastyBird\Core\Api\Encoding\Objects\Obj` |
| `Encoding/JsonApi/Objects/RelationshipObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\RelationshipObject` | class | `FastyBird\Core\Api\Encoding\Objects\RelationshipObject` |
| `Encoding/JsonApi/Objects/RelationshipObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\RelationshipObjectCollection` | class | `FastyBird\Core\Api\Encoding\Objects\RelationshipObjectCollection` |
| `Encoding/JsonApi/Objects/ResourceIdentifierCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ResourceIdentifierCollection` | class | `FastyBird\Core\Api\Encoding\Objects\ResourceIdentifierCollection` |
| `Encoding/JsonApi/Objects/ResourceIdentifierObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ResourceIdentifierObject` | class | `FastyBird\Core\Api\Encoding\Objects\ResourceIdentifierObject` |
| `Encoding/JsonApi/Objects/ResourceObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ResourceObject` | class | `FastyBird\Core\Api\Encoding\Objects\ResourceObject` |
| `Encoding/JsonApi/Objects/ResourceObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\ResourceObjectCollection` | class | `FastyBird\Core\Api\Encoding\Objects\ResourceObjectCollection` |
| `Encoding/JsonApi/Objects/SourceObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\SourceObject` | class | `FastyBird\Core\Api\Encoding\Objects\SourceObject` |
| `Encoding/JsonApi/Objects/StandardObject.php` | `FastyBird\Core\Encoding\JsonApi\Objects\StandardObject` | class | `FastyBird\Core\Api\Encoding\Objects\StandardObject` |
| `Encoding/JsonApi/Objects/StandardObjectCollection.php` | `FastyBird\Core\Encoding\JsonApi\Objects\StandardObjectCollection` | class | `FastyBird\Core\Api\Encoding\Objects\StandardObjectCollection` |
| `Encoding/JsonApi/SchemaContainer.php` | `FastyBird\Core\Encoding\JsonApi\SchemaContainer` | class | `FastyBird\Core\Api\Encoding\SchemaContainer` |
| `Exceptions/JsonApi.php` | `FastyBird\Core\Exceptions\JsonApi` | interface | `FastyBird\Core\Api\Exceptions\JsonApi` |
| `Exceptions/JsonApiError.php` | `FastyBird\Core\Exceptions\JsonApiError` | class | `FastyBird\Core\Api\Exceptions\JsonApiError` |
| `Exceptions/JsonApiMultipleError.php` | `FastyBird\Core\Exceptions\JsonApiMultipleError` | class | `FastyBird\Core\Api\Exceptions\JsonApiMultipleError` |
| `Helpers/JsonApi/CrudReader.php` | `FastyBird\Core\Helpers\JsonApi\CrudReader` | class | `FastyBird\Core\Api\Helpers\CrudReader` |
| `Middleware/JsonApi/JsonApi.php` | `FastyBird\Core\Middleware\JsonApi\JsonApi` | class | `FastyBird\Core\Api\Middleware\JsonApiMiddleware` |
| `Persistence/JsonApi/Hydrators/Container.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Container` | class | `FastyBird\Core\Api\Hydrators\Container` |
| `Persistence/JsonApi/Hydrators/Fields/ArrayField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\ArrayField` | class | `FastyBird\Core\Api\Hydrators\Fields\ArrayField` |
| `Persistence/JsonApi/Hydrators/Fields/BackedEnumField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\BackedEnumField` | class | `FastyBird\Core\Api\Hydrators\Fields\BackedEnumField` |
| `Persistence/JsonApi/Hydrators/Fields/BooleanField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\BooleanField` | class | `FastyBird\Core\Api\Hydrators\Fields\BooleanField` |
| `Persistence/JsonApi/Hydrators/Fields/CollectionField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\CollectionField` | class | `FastyBird\Core\Api\Hydrators\Fields\CollectionField` |
| `Persistence/JsonApi/Hydrators/Fields/DateTimeField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\DateTimeField` | class | `FastyBird\Core\Api\Hydrators\Fields\DateTimeField` |
| `Persistence/JsonApi/Hydrators/Fields/EntityField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\EntityField` | class | `FastyBird\Core\Api\Hydrators\Fields\EntityField` |
| `Persistence/JsonApi/Hydrators/Fields/Field.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\Field` | class | `FastyBird\Core\Api\Hydrators\Fields\Field` |
| `Persistence/JsonApi/Hydrators/Fields/MixedField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\MixedField` | class | `FastyBird\Core\Api\Hydrators\Fields\MixedField` |
| `Persistence/JsonApi/Hydrators/Fields/NumberField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\NumberField` | class | `FastyBird\Core\Api\Hydrators\Fields\NumberField` |
| `Persistence/JsonApi/Hydrators/Fields/SingleEntityField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\SingleEntityField` | class | `FastyBird\Core\Api\Hydrators\Fields\SingleEntityField` |
| `Persistence/JsonApi/Hydrators/Fields/TextField.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\TextField` | class | `FastyBird\Core\Api\Hydrators\Fields\TextField` |
| `Persistence/JsonApi/Hydrators/Hydrator.php` | `FastyBird\Core\Persistence\JsonApi\Hydrators\Hydrator` | class | `FastyBird\Core\Api\Hydrators\Hydrator` |
| `Schemas/JsonApi/JsonApi.php` | `FastyBird\Core\Schemas\JsonApi\JsonApi` | class | `FastyBird\Core\Api\Schemas\JsonApiSchema` |

### Http (50 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Commands/HttpServer.php` | `FastyBird\Core\Commands\HttpServer` | class | `FastyBird\Core\Http\Commands\HttpServer` |
| `Controllers/SlimRouter/ControllerResolver.php` | `FastyBird\Core\Controllers\SlimRouter\ControllerResolver` | class | `FastyBird\Core\Http\Controllers\ControllerResolver` |
| `Controllers/SlimRouter/IControllerResolver.php` | `FastyBird\Core\Controllers\SlimRouter\IControllerResolver` | interface | `FastyBird\Core\Http\Controllers\IControllerResolver` |
| `Events/HttpServerError.php` | `FastyBird\Core\Events\HttpServerError` | class | `FastyBird\Core\Http\Events\HttpServerError` |
| `Events/HttpServerRequest.php` | `FastyBird\Core\Events\HttpServerRequest` | class | `FastyBird\Core\Http\Events\HttpServerRequest` |
| `Events/HttpServerResponse.php` | `FastyBird\Core\Events\HttpServerResponse` | class | `FastyBird\Core\Http\Events\HttpServerResponse` |
| `Events/HttpServerStartup.php` | `FastyBird\Core\Events\HttpServerStartup` | class | `FastyBird\Core\Http\Events\HttpServerStartup` |
| `Exceptions/FileNotFound.php` | `FastyBird\Core\Exceptions\FileNotFound` | class | `FastyBird\Core\Http\Exceptions\FileNotFound` |
| `Exceptions/Http.php` | `FastyBird\Core\Exceptions\Http` | class | `FastyBird\Core\Http\Exceptions\Http` |
| `Exceptions/HttpMethodNotAllowed.php` | `FastyBird\Core\Exceptions\HttpMethodNotAllowed` | class | `FastyBird\Core\Http\Exceptions\HttpMethodNotAllowed` |
| `Exceptions/HttpNotFound.php` | `FastyBird\Core\Exceptions\HttpNotFound` | class | `FastyBird\Core\Http\Exceptions\HttpNotFound` |
| `Exceptions/HttpSpecialized.php` | `FastyBird\Core\Exceptions\HttpSpecialized` | class | `FastyBird\Core\Http\Exceptions\HttpSpecialized` |
| `Exceptions/StreamResourceCall.php` | `FastyBird\Core\Exceptions\StreamResourceCall` | class | `FastyBird\Core\Http\Exceptions\StreamResourceCall` |
| `Http/Entity.php` | `FastyBird\Core\Http\Entity` | class | `FastyBird\Core\Http\Entity` |
| `Http/Response.php` | `FastyBird\Core\Http\Response` | class | `FastyBird\Core\Http\Response` |
| `Http/ResponseAttributes.php` | `FastyBird\Core\Http\ResponseAttributes` | interface | `FastyBird\Core\Http\ResponseAttributes` |
| `Http/ResponseFactory.php` | `FastyBird\Core\Http\ResponseFactory` | class | `FastyBird\Core\Http\ResponseFactory` |
| `Http/ScalarEntity.php` | `FastyBird\Core\Http\ScalarEntity` | class | `FastyBird\Core\Http\ScalarEntity` |
| `Http/ServerResponse.php` | `FastyBird\Core\Http\ServerResponse` | class | `FastyBird\Core\Http\ServerResponse` |
| `Http/ServerResponseFactory.php` | `FastyBird\Core\Http\ServerResponseFactory` | class | `FastyBird\Core\Http\ServerResponseFactory` |
| `Http/Stream.php` | `FastyBird\Core\Http\Stream` | class | `FastyBird\Core\Http\Stream` |
| `Middleware/SlimRouter/IMiddlewareDispatcher.php` | `FastyBird\Core\Middleware\SlimRouter\IMiddlewareDispatcher` | interface | `FastyBird\Core\Http\Middleware\IMiddlewareDispatcher` |
| `Middleware/SlimRouter/MiddlewareDispatcher.php` | `FastyBird\Core\Middleware\SlimRouter\MiddlewareDispatcher` | class | `FastyBird\Core\Http\Middleware\MiddlewareDispatcher` |
| `Middleware/WebServer/Cors.php` | `FastyBird\Core\Middleware\WebServer\Cors` | class | `FastyBird\Core\Http\Middleware\Cors` |
| `Middleware/WebServer/Router.php` | `FastyBird\Core\Middleware\WebServer\Router` | class | `FastyBird\Core\Http\Middleware\Router` |
| `Middleware/WebServer/StaticFiles.php` | `FastyBird\Core\Middleware\WebServer\StaticFiles` | class | `FastyBird\Core\Http\Middleware\StaticFiles` |
| `Routing/FastRouteDispatcher.php` | `FastyBird\Core\Routing\FastRouteDispatcher` | class | `FastyBird\Core\Http\Routing\FastRouteDispatcher` |
| `Routing/Handlers/IHandler.php` | `FastyBird\Core\Routing\Handlers\IHandler` | interface | `FastyBird\Core\Http\Routing\Handlers\Handler` |
| `Routing/Handlers/IRequestHandler.php` | `FastyBird\Core\Routing\Handlers\IRequestHandler` | interface | `FastyBird\Core\Http\Routing\Handlers\IRequestHandler` |
| `Routing/Handlers/RequestHandler.php` | `FastyBird\Core\Routing\Handlers\RequestHandler` | class | `FastyBird\Core\Http\Routing\Handlers\RequestHandler` |
| `Routing/Handlers/RequestResponseArgsHandler.php` | `FastyBird\Core\Routing\Handlers\RequestResponseArgsHandler` | class | `FastyBird\Core\Http\Routing\Handlers\RequestResponseArgsHandler` |
| `Routing/Handlers/RequestResponseHandler.php` | `FastyBird\Core\Routing\Handlers\RequestResponseHandler` | class | `FastyBird\Core\Http\Routing\Handlers\RequestResponseHandler` |
| `Routing/IRoute.php` | `FastyBird\Core\Routing\IRoute` | interface | `FastyBird\Core\Http\Routing\IRoute` |
| `Routing/IRouteCollector.php` | `FastyBird\Core\Routing\IRouteCollector` | interface | `FastyBird\Core\Http\Routing\IRouteCollector` |
| `Routing/IRouteGroup.php` | `FastyBird\Core\Routing\IRouteGroup` | interface | `FastyBird\Core\Http\Routing\IRouteGroup` |
| `Routing/IRouteParser.php` | `FastyBird\Core\Routing\IRouteParser` | interface | `FastyBird\Core\Http\Routing\IRouteParser` |
| `Routing/IRouter.php` | `FastyBird\Core\Routing\IRouter` | interface | `FastyBird\Core\Http\Routing\IRouter` |
| `Routing/LinkGenerator.php` | `FastyBird\Core\Routing\LinkGenerator` | class | `FastyBird\Core\Http\Routing\LinkGenerator` |
| `Routing/Route.php` | `FastyBird\Core\Routing\Route` | class | `FastyBird\Core\Http\Routing\Route` |
| `Routing/RouteCollector.php` | `FastyBird\Core\Routing\RouteCollector` | class | `FastyBird\Core\Http\Routing\RouteCollector` |
| `Routing/RouteGroup.php` | `FastyBird\Core\Routing\RouteGroup` | class | `FastyBird\Core\Http\Routing\RouteGroup` |
| `Routing/RouteHandler.php` | `FastyBird\Core\Routing\RouteHandler` | class | `FastyBird\Core\Http\Routing\RouteHandler` |
| `Routing/RouteParser.php` | `FastyBird\Core\Routing\RouteParser` | class | `FastyBird\Core\Http\Routing\RouteParser` |
| `Routing/Router.php` | `FastyBird\Core\Routing\Router` | class | `FastyBird\Core\Http\Routing\Router` |
| `Routing/RoutingResults.php` | `FastyBird\Core\Routing\RoutingResults` | class | `FastyBird\Core\Http\Routing\RoutingResults` |
| `Routing/ServerRouter.php` | `FastyBird\Core\Routing\ServerRouter` | class | `FastyBird\Core\Http\Routing\ServerRouter` |
| `Server/HttpServer/Application.php` | `FastyBird\Core\Server\HttpServer\Application` | class | `FastyBird\Core\Http\Server\Application` |
| `Server/HttpServer/Factory.php` | `FastyBird\Core\Server\HttpServer\Factory` | class | `FastyBird\Core\Http\Server\Factory` |
| `Server/HttpServer/MimeTypesList.php` | `FastyBird\Core\Server\HttpServer\MimeTypesList` | class | `FastyBird\Core\Http\Server\MimeTypesList` |
| `Subscribers/HttpServer/Server.php` | `FastyBird\Core\Subscribers\HttpServer\Server` | class | `FastyBird\Core\Http\Subscribers\Server` |

### WebSockets (103 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Clients/WsServer/ClientFactory.php` | `FastyBird\Core\Clients\WsServer\ClientFactory` | class | `FastyBird\Core\WebSockets\Clients\ClientFactory` |
| `Clients/WsServer/Drivers/IDriver.php` | `FastyBird\Core\Clients\WsServer\Drivers\IDriver` | interface | `FastyBird\Core\WebSockets\Clients\Drivers\IDriver` |
| `Clients/WsServer/Drivers/InMemory.php` | `FastyBird\Core\Clients\WsServer\Drivers\InMemory` | class | `FastyBird\Core\WebSockets\Clients\Drivers\InMemory` |
| `Clients/WsServer/IClientFactory.php` | `FastyBird\Core\Clients\WsServer\IClientFactory` | interface | `FastyBird\Core\WebSockets\Clients\ClientProvider` |
| `Clients/WsServer/IStorage.php` | `FastyBird\Core\Clients\WsServer\IStorage` | interface | `FastyBird\Core\WebSockets\Clients\IStorage` |
| `Clients/WsServer/Storage.php` | `FastyBird\Core\Clients\WsServer\Storage` | class | `FastyBird\Core\WebSockets\Clients\Storage` |
| `Clients/WsServer/WampClientFactory.php` | `FastyBird\Core\Clients\WsServer\WampClientFactory` | class | `FastyBird\Core\WebSockets\Clients\WampClientFactory` |
| `Commands/WsServer.php` | `FastyBird\Core\Commands\WsServer` | class | `FastyBird\Core\WebSockets\Commands\WsServer` |
| `Compat/User.php` | `Nette\Security\User` | class | `FastyBird\Core\WebSockets\Compat\User` |
| `Controllers/WebSockets/Application.php` | `FastyBird\Core\Controllers\WebSockets\Application` | class | `FastyBird\Core\WebSockets\Controllers\Application` |
| `Controllers/WebSockets/Controller/Controller.php` | `FastyBird\Core\Controllers\WebSockets\Controller\Controller` | class | `FastyBird\Core\WebSockets\Controllers\Controller\AbstractController` |
| `Controllers/WebSockets/Controller/ControllerFactory.php` | `FastyBird\Core\Controllers\WebSockets\Controller\ControllerFactory` | class | `FastyBird\Core\WebSockets\Controllers\Controller\ControllerFactory` |
| `Controllers/WebSockets/Controller/IController.php` | `FastyBird\Core\Controllers\WebSockets\Controller\IController` | interface | `FastyBird\Core\WebSockets\Controllers\Controller\RequestController` |
| `Controllers/WebSockets/Controller/IControllerFactory.php` | `FastyBird\Core\Controllers\WebSockets\Controller\IControllerFactory` | interface | `FastyBird\Core\WebSockets\Controllers\Controller\IControllerFactory` |
| `Controllers/WebSockets/IApplication.php` | `FastyBird\Core\Controllers\WebSockets\IApplication` | interface | `FastyBird\Core\WebSockets\Controllers\Dispatcher` |
| `Controllers/WebSockets/IRequest.php` | `FastyBird\Core\Controllers\WebSockets\IRequest` | interface | `FastyBird\Core\WebSockets\Controllers\DispatchRequest` |
| `Controllers/WebSockets/IWampApplication.php` | `FastyBird\Core\Controllers\WebSockets\IWampApplication` | interface | `FastyBird\Core\WebSockets\Controllers\IWampApplication` |
| `Controllers/WebSockets/Reflection.php` | `FastyBird\Core\Controllers\WebSockets\Reflection` | class | `FastyBird\Core\WebSockets\Controllers\Reflection` |
| `Controllers/WebSockets/Request.php` | `FastyBird\Core\Controllers\WebSockets\Request` | class | `FastyBird\Core\WebSockets\Controllers\Request` |
| `Controllers/WebSockets/Responses/ErrorResponse.php` | `FastyBird\Core\Controllers\WebSockets\Responses\ErrorResponse` | class | `FastyBird\Core\WebSockets\Controllers\Responses\ErrorResponse` |
| `Controllers/WebSockets/Responses/IResponse.php` | `FastyBird\Core\Controllers\WebSockets\Responses\IResponse` | interface | `FastyBird\Core\WebSockets\Controllers\Responses\ControllerResponse` |
| `Controllers/WebSockets/Responses/MessageResponse.php` | `FastyBird\Core\Controllers\WebSockets\Responses\MessageResponse` | class | `FastyBird\Core\WebSockets\Controllers\Responses\MessageResponse` |
| `Controllers/WebSockets/Responses/NullResponse.php` | `FastyBird\Core\Controllers\WebSockets\Responses\NullResponse` | class | `FastyBird\Core\WebSockets\Controllers\Responses\NullResponse` |
| `Controllers/WebSockets/WampApplication.php` | `FastyBird\Core\Controllers\WebSockets\WampApplication` | class | `FastyBird\Core\WebSockets\Controllers\WampApplication` |
| `Encoding/WebSockets/HyBi10.php` | `FastyBird\Core\Encoding\WebSockets\HyBi10` | class | `FastyBird\Core\WebSockets\Encoding\HyBi10` |
| `Encoding/WebSockets/IData.php` | `FastyBird\Core\Encoding\WebSockets\IData` | interface | `FastyBird\Core\WebSockets\Encoding\FrameData` |
| `Encoding/WebSockets/IFrame.php` | `FastyBird\Core\Encoding\WebSockets\IFrame` | interface | `FastyBird\Core\WebSockets\Encoding\IFrame` |
| `Encoding/WebSockets/IMessage.php` | `FastyBird\Core\Encoding\WebSockets\IMessage` | interface | `FastyBird\Core\WebSockets\Encoding\IMessage` |
| `Encoding/WebSockets/IProtocol.php` | `FastyBird\Core\Encoding\WebSockets\IProtocol` | interface | `FastyBird\Core\WebSockets\Encoding\IProtocol` |
| `Encoding/WebSockets/IValidator.php` | `FastyBird\Core\Encoding\WebSockets\IValidator` | interface | `FastyBird\Core\WebSockets\Encoding\IValidator` |
| `Encoding/WebSockets/ProtocolProxy.php` | `FastyBird\Core\Encoding\WebSockets\ProtocolProxy` | class | `FastyBird\Core\WebSockets\Encoding\ProtocolProxy` |
| `Encoding/WebSockets/PushMessageSerializer.php` | `FastyBird\Core\Encoding\WebSockets\PushMessageSerializer` | class | `FastyBird\Core\WebSockets\Encoding\PushMessageSerializer` |
| `Encoding/WebSockets/RFC6455.php` | `FastyBird\Core\Encoding\WebSockets\RFC6455` | class | `FastyBird\Core\WebSockets\Encoding\RFC6455` |
| `Encoding/WebSockets/RFC6455/Frame.php` | `FastyBird\Core\Encoding\WebSockets\RFC6455\Frame` | class | `FastyBird\Core\WebSockets\Encoding\RFC6455\Frame` |
| `Encoding/WebSockets/RFC6455/HandshakeVerifier.php` | `FastyBird\Core\Encoding\WebSockets\RFC6455\HandshakeVerifier` | class | `FastyBird\Core\WebSockets\Encoding\RFC6455\HandshakeVerifier` |
| `Encoding/WebSockets/RFC6455/Message.php` | `FastyBird\Core\Encoding\WebSockets\RFC6455\Message` | class | `FastyBird\Core\WebSockets\Encoding\RFC6455\Message` |
| `Encoding/WebSockets/Validator.php` | `FastyBird\Core\Encoding\WebSockets\Validator` | class | `FastyBird\Core\WebSockets\Encoding\Validator` |
| `Entities/WebSockets/IWebSocket.php` | `FastyBird\Core\Entities\WebSockets\IWebSocket` | interface | `FastyBird\Core\WebSockets\Entities\IWebSocket` |
| `Entities/WebSockets/PushMessages/IMessage.php` | `FastyBird\Core\Entities\WebSockets\PushMessages\IMessage` | interface | `FastyBird\Core\WebSockets\Entities\PushMessages\IMessage` |
| `Entities/WebSockets/PushMessages/Message.php` | `FastyBird\Core\Entities\WebSockets\PushMessages\Message` | class | `FastyBird\Core\WebSockets\Entities\PushMessages\Message` |
| `Entities/WebSockets/WebSocket.php` | `FastyBird\Core\Entities\WebSockets\WebSocket` | class | `FastyBird\Core\WebSockets\Entities\WebSocket` |
| `Entities/WsServer/Client.php` | `FastyBird\Core\Entities\WsServer\Client` | class | `FastyBird\Core\WebSockets\Entities\Client` |
| `Entities/WsServer/IClient.php` | `FastyBird\Core\Entities\WsServer\IClient` | interface | `FastyBird\Core\WebSockets\Entities\ConnectedClient` |
| `Entities/WsServer/IWampClient.php` | `FastyBird\Core\Entities\WsServer\IWampClient` | interface | `FastyBird\Core\WebSockets\Entities\IWampClient` |
| `Entities/WsServer/Topics/ITopic.php` | `FastyBird\Core\Entities\WsServer\Topics\ITopic` | interface | `FastyBird\Core\WebSockets\Entities\Topics\ITopic` |
| `Entities/WsServer/Topics/Topic.php` | `FastyBird\Core\Entities\WsServer\Topics\Topic` | class | `FastyBird\Core\WebSockets\Entities\Topics\Topic` |
| `Entities/WsServer/WampClient.php` | `FastyBird\Core\Entities\WsServer\WampClient` | class | `FastyBird\Core\WebSockets\Entities\WampClient` |
| `Events/AfterIncommingMessageEvent.php` | `FastyBird\Core\Events\AfterIncommingMessageEvent` | class | `FastyBird\Core\WebSockets\Events\AfterIncommingMessageEvent` |
| `Events/ClientConnectEvent.php` | `FastyBird\Core\Events\ClientConnectEvent` | class | `FastyBird\Core\WebSockets\Events\ClientConnectEvent` |
| `Events/ClientConnected.php` | `FastyBird\Core\Events\ClientConnected` | class | `FastyBird\Core\WebSockets\Events\ClientConnected` |
| `Events/ClientDisconnectEvent.php` | `FastyBird\Core\Events\ClientDisconnectEvent` | class | `FastyBird\Core\WebSockets\Events\ClientDisconnectEvent` |
| `Events/ClientErrorEvent.php` | `FastyBird\Core\Events\ClientErrorEvent` | class | `FastyBird\Core\WebSockets\Events\ClientErrorEvent` |
| `Events/CloseEvent.php` | `FastyBird\Core\Events\CloseEvent` | class | `FastyBird\Core\WebSockets\Events\CloseEvent` |
| `Events/CreateEvent.php` | `FastyBird\Core\Events\CreateEvent` | class | `FastyBird\Core\WebSockets\Events\CreateEvent` |
| `Events/ErrorEvent.php` | `FastyBird\Core\Events\ErrorEvent` | class | `FastyBird\Core\WebSockets\Events\ErrorEvent` |
| `Events/IncomingMessage.php` | `FastyBird\Core\Events\IncomingMessage` | class | `FastyBird\Core\WebSockets\Events\IncomingMessage` |
| `Events/IncommingMessageEvent.php` | `FastyBird\Core\Events\IncommingMessageEvent` | class | `FastyBird\Core\WebSockets\Events\IncommingMessageEvent` |
| `Events/MessageEvent.php` | `FastyBird\Core\Events\MessageEvent` | class | `FastyBird\Core\WebSockets\Events\MessageEvent` |
| `Events/OpenEvent.php` | `FastyBird\Core\Events\OpenEvent` | class | `FastyBird\Core\WebSockets\Events\OpenEvent` |
| `Events/PushEvent.php` | `FastyBird\Core\Events\PushEvent` | class | `FastyBird\Core\WebSockets\Events\PushEvent` |
| `Events/StartEvent.php` | `FastyBird\Core\Events\StartEvent` | class | `FastyBird\Core\WebSockets\Events\StartEvent` |
| `Events/StopEvent.php` | `FastyBird\Core\Events\StopEvent` | class | `FastyBird\Core\WebSockets\Events\StopEvent` |
| `Events/WsServerError.php` | `FastyBird\Core\Events\WsServerError` | class | `FastyBird\Core\WebSockets\Events\WsServerError` |
| `Events/WsServerStartup.php` | `FastyBird\Core\Events\WsServerStartup` | class | `FastyBird\Core\WebSockets\Events\WsServerStartup` |
| `Exceptions/Abort.php` | `FastyBird\Core\Exceptions\Abort` | class | `FastyBird\Core\WebSockets\Exceptions\Abort` |
| `Exceptions/BadRequest.php` | `FastyBird\Core\Exceptions\BadRequest` | class | `FastyBird\Core\WebSockets\Exceptions\BadRequest` |
| `Exceptions/BadResponse.php` | `FastyBird\Core\Exceptions\BadResponse` | class | `FastyBird\Core\WebSockets\Exceptions\BadResponse` |
| `Exceptions/BadSignal.php` | `FastyBird\Core\Exceptions\BadSignal` | class | `FastyBird\Core\WebSockets\Exceptions\BadSignal` |
| `Exceptions/ClientNotFound.php` | `FastyBird\Core\Exceptions\ClientNotFound` | class | `FastyBird\Core\WebSockets\Exceptions\ClientNotFound` |
| `Exceptions/ForbiddenRequest.php` | `FastyBird\Core\Exceptions\ForbiddenRequest` | class | `FastyBird\Core\WebSockets\Exceptions\ForbiddenRequest` |
| `Exceptions/Storage.php` | `FastyBird\Core\Exceptions\Storage` | class | `FastyBird\Core\WebSockets\Exceptions\Storage` |
| `Exceptions/Terminate.php` | `FastyBird\Core\Exceptions\Terminate` | class | `FastyBird\Core\WebSockets\Exceptions\Terminate` |
| `Exceptions/TopicNotFound.php` | `FastyBird\Core\Exceptions\TopicNotFound` | class | `FastyBird\Core\WebSockets\Exceptions\TopicNotFound` |
| `Exceptions/WampNotImplemented.php` | `FastyBird\Core\Exceptions\WampNotImplemented` | class | `FastyBird\Core\WebSockets\Exceptions\WampNotImplemented` |
| `Helpers/WsServer/Console.php` | `FastyBird\Core\Helpers\WsServer\Console` | class | `FastyBird\Core\WebSockets\Helpers\Console` |
| `Helpers/WsServer/Formatter/IFormatter.php` | `FastyBird\Core\Helpers\WsServer\Formatter\IFormatter` | interface | `FastyBird\Core\WebSockets\Helpers\Formatter\IFormatter` |
| `Helpers/WsServer/Formatter/Symfony.php` | `FastyBird\Core\Helpers\WsServer\Formatter\Symfony` | class | `FastyBird\Core\WebSockets\Helpers\Formatter\Symfony` |
| `Http/IRequest.php` | `FastyBird\Core\Http\IRequest` | interface | `FastyBird\Core\WebSockets\Handshake\IRequest` |
| `Http/IResponse.php` | `FastyBird\Core\Http\IResponse` | interface | `FastyBird\Core\WebSockets\Handshake\IResponse` |
| `Http/Request.php` | `FastyBird\Core\Http\Request` | class | `FastyBird\Core\WebSockets\Handshake\Request` |
| `Http/RequestFactory.php` | `FastyBird\Core\Http\RequestFactory` | class | `FastyBird\Core\WebSockets\Handshake\RequestFactory` |
| `Http/WampResponse.php` | `FastyBird\Core\Http\WampResponse` | class | `FastyBird\Core\WebSockets\Handshake\WampResponse` |
| `Messaging/WebSockets/PushMessages/Consumer.php` | `FastyBird\Core\Messaging\WebSockets\PushMessages\Consumer` | class | `FastyBird\Core\WebSockets\PushMessages\Consumer` |
| `Messaging/WebSockets/PushMessages/ConsumersRegistry.php` | `FastyBird\Core\Messaging\WebSockets\PushMessages\ConsumersRegistry` | class | `FastyBird\Core\WebSockets\PushMessages\ConsumersRegistry` |
| `Messaging/WebSockets/PushMessages/IConsumer.php` | `FastyBird\Core\Messaging\WebSockets\PushMessages\IConsumer` | interface | `FastyBird\Core\WebSockets\PushMessages\IConsumer` |
| `Messaging/WebSockets/PushMessages/IConsumersRegistry.php` | `FastyBird\Core\Messaging\WebSockets\PushMessages\IConsumersRegistry` | interface | `FastyBird\Core\WebSockets\PushMessages\IConsumersRegistry` |
| `Messaging/WebSockets/PushMessages/IPusher.php` | `FastyBird\Core\Messaging\WebSockets\PushMessages\IPusher` | interface | `FastyBird\Core\WebSockets\PushMessages\IPusher` |
| `Messaging/WebSockets/PushMessages/Pusher.php` | `FastyBird\Core\Messaging\WebSockets\PushMessages\Pusher` | class | `FastyBird\Core\WebSockets\PushMessages\Pusher` |
| `Routing/IWampRouter.php` | `FastyBird\Core\Routing\IWampRouter` | interface | `FastyBird\Core\WebSockets\Wamp\WampRouter` |
| `Routing/RouteList.php` | `FastyBird\Core\Routing\RouteList` | class | `FastyBird\Core\WebSockets\Wamp\RouteList` |
| `Routing/WampRoute.php` | `FastyBird\Core\Routing\WampRoute` | class | `FastyBird\Core\WebSockets\Wamp\WampRoute` |
| `Server/WsServer/Configuration.php` | `FastyBird\Core\Server\WsServer\Configuration` | class | `FastyBird\Core\WebSockets\Server\Configuration` |
| `Server/WsServer/FlashWrapper.php` | `FastyBird\Core\Server\WsServer\FlashWrapper` | class | `FastyBird\Core\WebSockets\Server\FlashWrapper` |
| `Server/WsServer/Handlers.php` | `FastyBird\Core\Server\WsServer\Handlers` | class | `FastyBird\Core\WebSockets\Server\Handlers` |
| `Server/WsServer/IWrapper.php` | `FastyBird\Core\Server\WsServer\IWrapper` | interface | `FastyBird\Core\WebSockets\Server\ServerWrapper` |
| `Server/WsServer/Server.php` | `FastyBird\Core\Server\WsServer\Server` | class | `FastyBird\Core\WebSockets\Server\ServerRuntime` |
| `Server/WsServer/Wrapper.php` | `FastyBird\Core\Server\WsServer\Wrapper` | class | `FastyBird\Core\WebSockets\Server\Wrapper` |
| `Subscribers/WsServer/Client.php` | `FastyBird\Core\Subscribers\WsServer\Client` | class | `FastyBird\Core\WebSockets\Subscribers\Client` |
| `Subscribers/WsServer/OnServerStartHandler.php` | `FastyBird\Core\Subscribers\WsServer\OnServerStartHandler` | class | `FastyBird\Core\WebSockets\Subscribers\OnServerStartHandler` |
| `Topics/WsServer/Drivers/IDriver.php` | `FastyBird\Core\Topics\WsServer\Drivers\IDriver` | interface | `FastyBird\Core\WebSockets\Topics\Drivers\IDriver` |
| `Topics/WsServer/Drivers/InMemory.php` | `FastyBird\Core\Topics\WsServer\Drivers\InMemory` | class | `FastyBird\Core\WebSockets\Topics\Drivers\InMemory` |
| `Topics/WsServer/IStorage.php` | `FastyBird\Core\Topics\WsServer\IStorage` | interface | `FastyBird\Core\WebSockets\Topics\IStorage` |
| `Topics/WsServer/Storage.php` | `FastyBird\Core\Topics\WsServer\Storage` | class | `FastyBird\Core\WebSockets\Topics\Storage` |

### Security (47 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Entities/SimpleAuth/Owner.php` | `FastyBird\Core\Entities\SimpleAuth\Owner` | interface | `FastyBird\Core\Security\Entities\Owner` |
| `Entities/SimpleAuth/Policies/Policy.php` | `FastyBird\Core\Entities\SimpleAuth\Policies\Policy` | class | `FastyBird\Core\Security\Entities\Policies\Policy` |
| `Entities/SimpleAuth/TOwner.php` | `FastyBird\Core\Entities\SimpleAuth\TOwner` | trait | `FastyBird\Core\Security\Entities\HasOwner` |
| `Entities/SimpleAuth/Tokens/Token.php` | `FastyBird\Core\Entities\SimpleAuth\Tokens\Token` | class | `FastyBird\Core\Security\Entities\Tokens\Token` |
| `Exceptions/Authentication.php` | `FastyBird\Core\Exceptions\Authentication` | class | `FastyBird\Core\Security\Exceptions\Authentication` |
| `Exceptions/ForbiddenAccess.php` | `FastyBird\Core\Exceptions\ForbiddenAccess` | class | `FastyBird\Core\Security\Exceptions\ForbiddenAccess` |
| `Exceptions/UnauthorizedAccess.php` | `FastyBird\Core\Exceptions\UnauthorizedAccess` | class | `FastyBird\Core\Security\Exceptions\UnauthorizedAccess` |
| `Latte/SimpleAuth/AccessExtension.php` | `FastyBird\Core\Latte\SimpleAuth\AccessExtension` | class | `FastyBird\Core\Security\Latte\AccessExtension` |
| `Latte/SimpleAuth/Nodes/AllowedHrefNode.php` | `FastyBird\Core\Latte\SimpleAuth\Nodes\AllowedHrefNode` | class | `FastyBird\Core\Security\Latte\Nodes\AllowedHrefNode` |
| `Latte/SimpleAuth/Nodes/IfAllowedNode.php` | `FastyBird\Core\Latte\SimpleAuth\Nodes\IfAllowedNode` | class | `FastyBird\Core\Security\Latte\Nodes\IfAllowedNode` |
| `Latte/SimpleAuth/Nodes/NElseAllowedNode.php` | `FastyBird\Core\Latte\SimpleAuth\Nodes\NElseAllowedNode` | class | `FastyBird\Core\Security\Latte\Nodes\NElseAllowedNode` |
| `Mapping/SimpleAuth/Attribute/Owner.php` | `FastyBird\Core\Mapping\SimpleAuth\Attribute\Owner` | class | `FastyBird\Core\Security\Mapping\Attribute\Owner` |
| `Mapping/SimpleAuth/Driver/Owner.php` | `FastyBird\Core\Mapping\SimpleAuth\Driver\Owner` | class | `FastyBird\Core\Security\Mapping\Driver\Owner` |
| `Middleware/SimpleAuth/Authorization.php` | `FastyBird\Core\Middleware\SimpleAuth\Authorization` | class | `FastyBird\Core\Security\Middleware\Authorization` |
| `Middleware/SimpleAuth/User.php` | `FastyBird\Core\Middleware\SimpleAuth\User` | class | `FastyBird\Core\Security\Middleware\User` |
| `Persistence/SimpleAuth/Models/Casbin/Adapter.php` | `FastyBird\Core\Persistence\SimpleAuth\Models\Casbin\Adapter` | class | `FastyBird\Core\Security\Models\Casbin\Adapter` |
| `Persistence/SimpleAuth/Models/Casbin/Filter.php` | `FastyBird\Core\Persistence\SimpleAuth\Models\Casbin\Filter` | class | `FastyBird\Core\Security\Models\Casbin\Filter` |
| `Persistence/SimpleAuth/Models/Policies/Manager.php` | `FastyBird\Core\Persistence\SimpleAuth\Models\Policies\Manager` | class | `FastyBird\Core\Security\Models\Policies\Manager` |
| `Persistence/SimpleAuth/Models/Policies/Repository.php` | `FastyBird\Core\Persistence\SimpleAuth\Models\Policies\Repository` | class | `FastyBird\Core\Security\Models\Policies\Repository` |
| `Persistence/SimpleAuth/Models/Tokens/Manager.php` | `FastyBird\Core\Persistence\SimpleAuth\Models\Tokens\Manager` | class | `FastyBird\Core\Security\Models\Tokens\Manager` |
| `Persistence/SimpleAuth/Models/Tokens/Repository.php` | `FastyBird\Core\Persistence\SimpleAuth\Models\Tokens\Repository` | class | `FastyBird\Core\Security\Models\Tokens\Repository` |
| `Persistence/SimpleAuth/Queries/FindPolicies.php` | `FastyBird\Core\Persistence\SimpleAuth\Queries\FindPolicies` | class | `FastyBird\Core\Security\Queries\FindPolicies` |
| `Persistence/SimpleAuth/Queries/FindTokens.php` | `FastyBird\Core\Persistence\SimpleAuth\Queries\FindTokens` | class | `FastyBird\Core\Security\Queries\FindTokens` |
| `Presenters/SimpleAuth/TSimpleAuth.php` | `FastyBird\Core\Presenters\SimpleAuth\TSimpleAuth` | trait | `FastyBird\Core\Security\Presenters\HasAuthorization` |
| `Security/SimpleAuth/Access/AnnotationChecker.php` | `FastyBird\Core\Security\SimpleAuth\Access\AnnotationChecker` | class | `FastyBird\Core\Security\Access\AnnotationChecker` |
| `Security/SimpleAuth/Access/CheckRequirements.php` | `FastyBird\Core\Security\SimpleAuth\Access\CheckRequirements` | interface | `FastyBird\Core\Security\Access\CheckRequirements` |
| `Security/SimpleAuth/Access/Checker.php` | `FastyBird\Core\Security\SimpleAuth\Access\Checker` | interface | `FastyBird\Core\Security\Access\Checker` |
| `Security/SimpleAuth/Access/LatteChecker.php` | `FastyBird\Core\Security\SimpleAuth\Access\LatteChecker` | class | `FastyBird\Core\Security\Access\LatteChecker` |
| `Security/SimpleAuth/Access/LinkChecker.php` | `FastyBird\Core\Security\SimpleAuth\Access\LinkChecker` | class | `FastyBird\Core\Security\Access\LinkChecker` |
| `Security/SimpleAuth/EnforcerFactory.php` | `FastyBird\Core\Security\SimpleAuth\EnforcerFactory` | class | `FastyBird\Core\Security\Identity\EnforcerFactory` |
| `Security/SimpleAuth/IAuthenticator.php` | `FastyBird\Core\Security\SimpleAuth\IAuthenticator` | interface | `FastyBird\Core\Security\Identity\Authenticator` |
| `Security/SimpleAuth/IIdentity.php` | `FastyBird\Core\Security\SimpleAuth\IIdentity` | interface | `FastyBird\Core\Security\Identity\UserIdentity` |
| `Security/SimpleAuth/IIdentityFactory.php` | `FastyBird\Core\Security\SimpleAuth\IIdentityFactory` | interface | `FastyBird\Core\Security\Identity\IdentityProvider` |
| `Security/SimpleAuth/IUserStorage.php` | `FastyBird\Core\Security\SimpleAuth\IUserStorage` | interface | `FastyBird\Core\Security\Identity\IUserStorage` |
| `Security/SimpleAuth/IdentityFactory.php` | `FastyBird\Core\Security\SimpleAuth\IdentityFactory` | class | `FastyBird\Core\Security\Identity\IdentityFactory` |
| `Security/SimpleAuth/PlainIdentity.php` | `FastyBird\Core\Security\SimpleAuth\PlainIdentity` | class | `FastyBird\Core\Security\Identity\PlainIdentity` |
| `Security/SimpleAuth/TokenBuilder.php` | `FastyBird\Core\Security\SimpleAuth\TokenBuilder` | class | `FastyBird\Core\Security\Identity\TokenBuilder` |
| `Security/SimpleAuth/TokenReader.php` | `FastyBird\Core\Security\SimpleAuth\TokenReader` | class | `FastyBird\Core\Security\Identity\TokenReader` |
| `Security/SimpleAuth/TokenValidator.php` | `FastyBird\Core\Security\SimpleAuth\TokenValidator` | class | `FastyBird\Core\Security\Identity\TokenValidator` |
| `Security/SimpleAuth/User.php` | `FastyBird\Core\Security\SimpleAuth\User` | class | `FastyBird\Core\Security\Identity\User` |
| `Security/SimpleAuth/UserStorage.php` | `FastyBird\Core\Security\SimpleAuth\UserStorage` | class | `FastyBird\Core\Security\Identity\UserStorage` |
| `Services/SimpleAuth/Auth.php` | `FastyBird\Core\Services\SimpleAuth\Auth` | class | `FastyBird\Core\Security\Services\Auth` |
| `Subscribers/SimpleAuth/Application.php` | `FastyBird\Core\Subscribers\SimpleAuth\Application` | class | `FastyBird\Core\Security\Subscribers\Application` |
| `Subscribers/SimpleAuth/Policy.php` | `FastyBird\Core\Subscribers\SimpleAuth\Policy` | class | `FastyBird\Core\Security\Subscribers\Policy` |
| `Subscribers/SimpleAuth/User.php` | `FastyBird\Core\Subscribers\SimpleAuth\User` | class | `FastyBird\Core\Security\Subscribers\User` |
| `Types/SimpleAuth/PolicyType.php` | `FastyBird\Core\Types\SimpleAuth\PolicyType` | enum | `FastyBird\Core\Security\Types\PolicyType` |
| `Types/SimpleAuth/TokenState.php` | `FastyBird\Core\Types\SimpleAuth\TokenState` | enum | `FastyBird\Core\Security\Types\TokenState` |

### Root (dissolved) (19 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Boot/Bootstrap.php` | `FastyBird\Core\Boot\Bootstrap` | class | `FastyBird\Core\Boot\Bootstrap` |
| `Boot/Configurator.php` | `FastyBird\Core\Boot\Configurator` | class | `FastyBird\Core\Boot\Configurator` |
| `Caching/Application/MemoryAdapterStorage.php` | `FastyBird\Core\Caching\Application\MemoryAdapterStorage` | class | `FastyBird\Core\Caching\MemoryAdapterStorage` |
| `Caching/Application/MemoryStorage.php` | `FastyBird\Core\Caching\Application\MemoryStorage` | class | `FastyBird\Core\Caching\MemoryStorage` |
| `Configuration/Configuration.php` | `FastyBird\Core\Configuration\Configuration` | class | `FastyBird\Core\Configuration` |
| `Constants/Constants.php` | `FastyBird\Core\Constants\Constants` | class | `FastyBird\Core\Constants` |
| `DI/CoreExtension.php` | `FastyBird\Core\DI\CoreExtension` | class | `FastyBird\Core\DI\CoreExtension` |
| `EventLoop/Application/Status.php` | `FastyBird\Core\EventLoop\Application\Status` | class | `FastyBird\Core\EventLoop\Status` |
| `EventLoop/Application/Wrapper.php` | `FastyBird\Core\EventLoop\Application\Wrapper` | class | `FastyBird\Core\EventLoop\Wrapper` |
| `Events/EventLoopStarted.php` | `FastyBird\Core\Events\EventLoopStarted` | class | `FastyBird\Core\EventLoop\Events\EventLoopStarted` |
| `Events/EventLoopStopped.php` | `FastyBird\Core\Events\EventLoopStopped` | class | `FastyBird\Core\EventLoop\Events\EventLoopStopped` |
| `Events/EventLoopStopping.php` | `FastyBird\Core\Events\EventLoopStopping` | class | `FastyBird\Core\EventLoop\Events\EventLoopStopping` |
| `Events/PresenterRequest.php` | `FastyBird\Core\Events\PresenterRequest` | class | `FastyBird\Core\Presenters\Events\PresenterRequest` |
| `Events/PresenterResponse.php` | `FastyBird\Core\Events\PresenterResponse` | class | `FastyBird\Core\Presenters\Events\PresenterResponse` |
| `Presenters/Application/BasePresenter.php` | `FastyBird\Core\Presenters\Application\BasePresenter` | class | `FastyBird\Core\Presenters\BasePresenter` |
| `Presenters/Application/DefaultPresenter.php` | `FastyBird\Core\Presenters\Application\DefaultPresenter` | class | `FastyBird\Core\Presenters\DefaultPresenter` |
| `Routing/AppRouter.php` | `FastyBird\Core\Routing\AppRouter` | class | `FastyBird\Core\Presenters\AppRouter` |
| `Subscribers/Application/EventLoopLifeCycle.php` | `FastyBird\Core\Subscribers\Application\EventLoopLifeCycle` | class | `FastyBird\Core\EventLoop\Subscribers\EventLoopLifeCycle` |
| `UI/Application/TemplateFactory.php` | `FastyBird\Core\UI\Application\TemplateFactory` | class | `FastyBird\Core\UI\TemplateFactory` |

### Exceptions (8 files)

| Current path | Current FQCN | Kind | Target FQCN |
|---|---|---|---|
| `Exceptions/Exception.php` | `FastyBird\Core\Exceptions\Exception` | interface | `FastyBird\Core\Exceptions\Exception` |
| `Exceptions/InvalidArgument.php` | `FastyBird\Core\Exceptions\InvalidArgument` | class | `FastyBird\Core\Exceptions\InvalidArgument` |
| `Exceptions/InvalidController.php` | `FastyBird\Core\Exceptions\InvalidController` | class | `FastyBird\Core\Exceptions\InvalidController` |
| `Exceptions/InvalidLink.php` | `FastyBird\Core\Exceptions\InvalidLink` | class | `FastyBird\Core\Exceptions\InvalidLink` |
| `Exceptions/InvalidState.php` | `FastyBird\Core\Exceptions\InvalidState` | class | `FastyBird\Core\Exceptions\InvalidState` |
| `Exceptions/Logic.php` | `FastyBird\Core\Exceptions\Logic` | class | `FastyBird\Core\Exceptions\Logic` |
| `Exceptions/Runtime.php` | `FastyBird\Core\Exceptions\Runtime` | class | `FastyBird\Core\Exceptions\Runtime` |
| `Exceptions/UnexpectedValue.php` | `FastyBird\Core\Exceptions\UnexpectedValue` | class | `FastyBird\Core\Exceptions\UnexpectedValue` |
