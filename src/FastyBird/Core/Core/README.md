# FastyBird MiniServer Core

Core is the package every other MiniServer extension builds on: composer
`fastybird/miniserver-core`, npm `@fastybird/miniserver-core`, PHP namespace `FastyBird\Core`. It
boots the application, compiles the DI container and provides the services the modules,
connectors, plugins, bridges, automators and the addon share.

## Capabilities

Each capability is a top-level namespace under `FastyBird\Core\` with its own DI extension.

- **`Api`** -- JSON:API documents: the encoder, schemas, hydrators, and the middleware that turns
  exceptions into JSON:API error responses.
- **`Clock`** -- PSR-20 clocks: `SystemClock`, and `FrozenClock` for tests.
- **`Documents`** -- typed document objects mapped by attributes and created from arrays or JSON;
  the payload of exchange messages.
- **`Exchange`** -- the publish/subscribe bus between processes: publishers, consumers, and the
  transport factory a plugin implements.
- **`Http`** -- the PSR-7/PSR-15 API stack: the router, its middlewares, the request handler for
  php-fpm and the ReactPHP server behind `fb:web-server:start`.
- **`Logging`** -- the Monolog handlers (rotating file, stdout, console) and Sentry.
- **`Persistence`** -- Doctrine helpers: entity create/update/delete, query objects,
  timestamps, UUID mapping rules and the schema subscriber Doctrine Migrations needs.
- **`Phone`** -- a phone number value object, its Doctrine type and its validation.
- **`Security`** -- authentication and authorization: access tokens, identities, Casbin policies,
  the `Secured` annotations and the API middlewares that enforce them.
- **`Values`** -- data types, payloads, exchange sources, value formats, transformers and the JSON
  schema validator.
- **`WebSockets`** -- the WAMP v1 WebSocket server behind `fb:ws-server:start`: protocol, routing,
  controllers, clients and topics.

Beside them sit the shared `Exceptions` root and five runtime namespaces: `Boot`, `DI`,
`EventLoop`, `Presenters` and `UI`. The JS side, under `assets/`, has the capabilities `api`,
`eventBus`, `extensions`, `security`, `stores`, `values` and `websockets`, and the Vue application
shell at its root.

## The PHP surface

- **One DI extension, `fbCore`.** `FastyBird\Core\DI\CoreExtension` composes the eleven
  capability extensions. Its configuration has one section per configurable capability,
  `logging`, `documents`, `security`, `clock`, `persistence`, `api`, `webSockets` and `http`,
  each read through a typed `final readonly` class.
- **No `Constants` or `Configuration` class.** A constant lives on the type that owns its meaning,
  for example `Http\Routing\Router::API_PREFIX`. The runtime settings are
  `Security\Configuration` and `Persistence\TimestampableConfiguration`, each registered by its
  capability.
- **PSR-14 events.** Every capability announces what happens through
  `Psr\EventDispatcher\EventDispatcherInterface`, with one past-tense event class per moment in
  its `Events\` namespace, for example `WebSockets\Events\ClientConnected`. There are no callback
  arrays.
- **PSR-20 time.** A consumer takes `Psr\Clock\ClockInterface` and calls `now()`.
- **Property hooks and asymmetric visibility.** A value that is only read and written is a typed
  property, `public private(set)` where only its class writes it, not a getter/setter pair.

## How the other extensions use Core

Every extension except the RedisDbCache plugin, a Nette cache storage that needs nothing from
Core, requires `fastybird/miniserver-core`, and every UI except the Triggers module's imports
`@fastybird/miniserver-core`. They build on Core's contracts rather than on concrete services:

- Core services are autowired by type; a NEON reference by name, such as
  `@fbCore.api.middleware`, is part of the public surface.
- An extension's DI extension adds to Core in `beforeCompile()`: its API routes on
  `Http\Routing\Router`, its presenter routes on the Nette route list, its WAMP routes and
  controllers through the `WebSocketsExtension` tags, and its document classes through
  `DocumentsExtension::DRIVER_TAG`.
- An exchange consumer implements `Exchange\Consumers\Consumer` and is collected by type; a
  transport plugin implements `Exchange\Publisher\MessagePublisher` and `Exchange\Factory`.

Only `Core` may be depended on by every package; `make layers` enforces the direction.

## Documentation

- [docs/Home.md](./docs/Home.md) -- the guide to each capability.
- [docs/configuration.md](../../../../docs/configuration.md#core-fbcore) -- every `fbCore` key.
- [docs/architecture.md](../../../../docs/architecture.md) -- where Core sits in the application:
  the capability layout, request routing, the configuration load order and the database schema.
- [docs/conventions.md](../../../../docs/conventions.md) -- the naming, DI, event and code rules
  Core follows.
