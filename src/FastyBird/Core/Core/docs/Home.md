# MiniServer Core: capability guide

This guide describes what each of Core's capabilities does and how an extension uses it. The
[README](../README.md) lists the capabilities in one line each; every `fbCore` configuration key,
with its type and default, is in
[docs/configuration.md](../../../../../docs/configuration.md#core-fbcore); the naming, DI, event
and code rules are in [docs/conventions.md](../../../../../docs/conventions.md).

## Booting the application

`FastyBird\Core\Boot\Bootstrap::boot()` returns a configured `Boot\Configurator`, and
`createContainer()` compiles and returns the container. `public/index.php` and
`bin/fb-console.php` both start this way.

`boot()` first defines the directory constants from the environment (`$_ENV`, then `getenv()`):

| Constant | Default |
|---|---|
| `FB_APP_DIR` | the nearest directory above Core's `src/Boot` that holds a `vendor/` directory |
| `FB_PUBLIC_DIR` | `<FB_APP_DIR>/public` |
| `FB_RESOURCES_DIR` | `<FB_APP_DIR>/resources` |
| `FB_TEMP_DIR` | `<FB_APP_DIR>/var/temp`, created if missing |
| `FB_LOGS_DIR` | `<FB_APP_DIR>/var/logs`, created if missing |
| `FB_CONFIG_DIR` | `<FB_APP_DIR>/config` |

They become the static parameters `%appDir%`, `%wwwDir%`, `%tempDir%` and `%logsDir%`.
`%debugMode%` is `true` when `APP_ENV` is `dev`. Every environment variable named
`FB_APP_PARAMETER__<SECTION>_<KEY>` becomes a parameter as well: `FB_APP_PARAMETER__DATABASE_HOST`
is `%database.host%`. `boot()` then loads Core's own `config/common.neon` and `defaults.neon`, the
application's, and the operator's `local.neon`, in the order
[docs/architecture.md](../../../../../docs/architecture.md#configuration-load-order) describes.

Tracy is enabled, with deprecations kept out of its strict mode, unless the process runs under
PHPUnit.

## The `fbCore` extension

`FastyBird\Core\DI\CoreExtension`, registered as `fbCore`, is the only Core extension a container
registers. It composes one child extension per capability and gives each its own configuration
section:

```neon
fbCore:
    logging:       # Logging\DI\LoggingExtension
    documents:     # Documents\DI\DocumentsExtension
    security:      # Security\DI\SecurityExtension
    clock:         # Clock\DI\ClockExtension
    persistence:   # Persistence\DI\PersistenceExtension
    api:           # Api\DI\ApiExtension
    webSockets:    # WebSockets\DI\WebSocketsExtension
    http:          # Http\DI\HttpExtension
```

Exchange, Values and Phone have no configuration. A key the schema does not know fails the
compilation. Each child names its services `fbCore.<capability>.<role>`; the root services are
`fbCore.eventLoop.*`, `fbCore.ui.*`, `fbCore.cache.psr6` and, when nothing else provides a PSR-14
dispatcher, `fbCore.eventDispatcher`.

## Api

JSON:API on top of `neomerx/json-api`. `Api\Encoding\Encoder` and `Builder` produce documents,
`Api\Schemas\JsonApiSchema` is the base of every resource schema, and `Api\Hydrators\Hydrator` turns
a request document into entity data. An extension registers its schemas and hydrators as services;
the API extension collects them by type into `fbCore.api.schemas.container` and the hydrators
container.

`Api\Middleware\JsonApiMiddleware` (`fbCore.api.middleware`) turns an exception a controller
throws into a JSON:API error response: a `JsonApiError` or `JsonApiMultipleError` with its own
status and errors, an `Http\Exceptions\Http` with its status, anything else as a logged 500. The
application adds it to the HTTP router in `config/common.neon`. `fbCore.api.meta` sets the
`meta.author` and `meta.copyright` of every document.

## Clock

PSR-20. A service takes `Psr\Clock\ClockInterface` and calls `now()`, which returns a
`DateTimeImmutable`. `Clock\SystemClock` reads the system time in `fbCore.clock.timeZone`;
`Clock\FrozenClock` returns a fixed moment and is autowired instead when `fbCore.clock.frozen` is
set, which is how the test configurations pin time.

## Documents

A document is a typed object that carries an entity's data between processes. Its class is mapped
by attributes from `FastyBird\Core\Documents\Mapping` (`Document`, `MappedSuperclass`,
`InheritanceType`, `DiscriminatorColumn`, `DiscriminatorMap`, `RoutingMap`), and
`Documents\DocumentFactory::create($documentClass, $data)` builds one from an array, a JSON string
or an object, validated through the object mapper. `Documents\Document` is the common interface;
`CreatedAt`, `UpdatedAt` and `Owner` mark documents that carry those fields.

`fbCore.documents.mapping` maps a namespace to a directory of document classes. Modules add their
own through `DocumentsExtension::DRIVER_TAG` instead.

## Exchange

The publish/subscribe bus that connects the application's processes.

- **Publishing.** Autowire `Exchange\Publisher\MessagePublisher` (the publisher container,
  `fbCore.exchange.publisher`) and call `publish($source, $routingKey, $document)`. The source is a
  `Values\Types\Sources\Source` case naming the publishing extension; routing keys start with
  `MessagePublisher::ROUTING_KEY_PREFIX` (`fb.exchange`). The container forwards the message to
  every publisher a transport plugin registers. `Exchange\Publisher\Async\MessagePublisher` is the
  promise-returning variant for code running on the event loop.
- **Consuming.** Implement `Exchange\Consumers\Consumer::consume($source, $routingKey, $document)`
  and register the class as a service. The Exchange extension collects every consumer by type into
  `Exchange\Consumers\Container`. The tag `ExchangeExtension::CONSUMER_ROUTING_KEY` limits a
  consumer to one routing key, and `ExchangeExtension::CONSUMER_STATE` set to `false` registers it
  disabled; `Consumers\Container::enable()` and `disable()` switch it at runtime.
- **Transports.** A plugin that carries messages between processes (RedisDb, RabbitMq) implements
  `Exchange\Factory`; a long-running process, such as the WebSocket server, calls `create()` on
  every factory to start consuming.
- **Events.** `Exchange\Events\BeforeMessagePublished`, `AfterMessagePublished`,
  `BeforeMessageConsumed`, `AfterMessageConsumed` and `ExchangeError`.

## Http

The API stack, PSR-7 and PSR-15, with a FastRoute-based router.

- **Routes.** `Http\Routing\Router` (the service `fbCore.http.routing.router`, typed
  `ServerRouter`) collects routes in groups. A module registers its routes from its DI extension's
  `beforeCompile()`, as the Devices module's `DevicesExtension` does:

  ```php
  $routerService = $builder->getDefinitionByType(HttpRouting\Router::class);

  if ($routerService instanceof NetteDI\Definitions\ServiceDefinition) {
      $routerService->addSetup('?->registerRoutes(?)', [
          $builder->getDefinitionByType(Router\ApiRoutes::class),
          $routerService,
      ]);
  }
  ```

  Its `Router\ApiRoutes::registerRoutes()` builds them under `Router::API_PREFIX`, then the
  module prefix while the module's `apiPrefix` option is on, then `/v1`, and puts Security's
  middlewares on the group. Shortened to one route from `Module/Devices/src/Router/ApiRoutes.php`:

  ```php
  public function registerRoutes(Routing\Router $router): void
  {
      $routes = $router->group('/' . Routing\Router::API_PREFIX, function (
          Routing\RouteCollector $group,
      ): void {
          if ($this->usePrefix) {
              $group->group('/' . Devices\Constants::MODULE_DEVICES_PREFIX, function (
                  Routing\RouteCollector $group,
              ): void {
                  $this->buildRoutes($group);
              });

          } else {
              $this->buildRoutes($group);
          }
      });

      $routes->addMiddleware($this->accessControlMiddleware); // Security\Middleware\Authorization
      $routes->addMiddleware($this->userMiddleware); // Security\Middleware\User
      $routes->addMiddleware($this->devicesAccessControlMiddleware);
  }

  private function buildRoutes(Routing\Router|Routing\RouteCollector $group): Routing\RouteGroup
  {
      return $group->group('/v1', function (Routing\RouteCollector $group): void {
          $group->group('/devices', function (Routing\RouteCollector $group): void {
              $group->get('', [$this->devicesV1Controller, 'index']);
          });
      });
  }
  ```

  A controller method takes the PSR-7 request and response and returns the response.
- **Serving.** Under php-fpm, `public/index.php` hands every `/api…` request to
  `Http\Server\Application`. `php bin/fb-console.php fb:web-server:start` runs the same router in a
  ReactPHP server on `fbCore.http.server.address`/`port` (TLS with `server.certificate`), behind
  the CORS and static-file middlewares, the services `fbCore.http.middlewares.cors` and
  `fbCore.http.middlewares.staticFiles`, configured by the `fbCore.http.cors` and
  `fbCore.http.static` sections.
- **Events.** `Http\Events\HttpServerStartup`, `HttpServerRequest`, `HttpServerResponse` and
  `HttpServerError`.

## Logging

The Monolog handlers `fbCore.logging` configures: a daily rotating file in `FB_LOGS_DIR`, stdout,
and Symfony's console handler, whose level a subscriber sets when a console command starts.
`Logging\Logger::buildException()` builds the log context of an exception. With a DSN in
`fbCore.logging.sentry.dsn`, or in the environment variable `FB_APP_PARAMETER__SENTRY_DSN`,
warnings and worse also go to Sentry.

## Persistence

Helpers on top of Doctrine ORM 3:

- **Entity CRUD.** `Persistence\Crud\CrudFactory::create($entityClass)` returns an `EntityCrud`,
  whose creator, updater and deleter create, update and delete an entity from a Nette
  `ArrayHash`. An entity implements `Persistence\Entities\CrudEntity`.
- **Queries.** `Persistence\Query\QueryObject` is the base of a reusable query, and `ResultSet` its
  paginated result.
- **Timestamps.** A property with the `Persistence\Mapping\Annotation\Timestampable` attribute is
  stamped on create or update; the traits `HasEntityCreated` and `HasEntityUpdated` declare the
  usual pair. `fbCore.persistence.timestampable` decides how an unmapped one is handled.
- **UUIDs.** `Persistence\Rules\UuidRule` lets the object mapper read UUID values.
- **Schema.** `Persistence\Subscribers\SchemaSubscriber` adds Doctrine Migrations' own table to
  the schema the ORM generates, so schema tools do not propose dropping it. The schema itself is
  migrated from the application's `migrations/` (see
  [docs/architecture.md](../../../../../docs/architecture.md#database-schema)).
- **Events.** `Persistence\Events\DbTransactionStarted` and `DbTransactionFinished`.

## Phone

`Phone\Entities\Phone` is a phone number value object built with `libphonenumber`,
`Phone\Types\PhoneType` the Doctrine type that stores it, and `Phone\Services\PhoneNumberHelper`
validates and formats numbers.

## Security

Authentication and authorization for the API and the presenters. Nothing in it is registered
unless `fbCore.security.token.signature` is set.

- **Tokens.** `Security\Identity\TokenBuilder` issues and `TokenValidator` validates signed JWT
  access tokens (`lcobucci/jwt`). `TokenReader` reads one from the `Authorization: Bearer …`
  header, or from the `token` cookie on a presenter request.
- **Identity.** `Security\Identity\User` is the signed-in user, with the roles `guest`, `visitor`,
  `user`, `manager` and `administrator`. The Accounts module provides the authenticator and the
  identity factory.
- **Authorization.** Controllers and presenters declare access in `Secured` docblock annotations:
  `User`, `Role`, `Resource`, `Privilege`, `Permission` and `Domain`.
  `Security\Access\AnnotationChecker` reads them, and Casbin decides permissions, from policies
  in the database (`fbCore.security.enable.casbin.database`) or in a policy file.
- **Middlewares.** A module puts `Security\Middleware\Authorization` and `Security\Middleware\User`
  on its route group. A request without a token is refused with 403, one with an invalid token
  with 401.
- **API keys.** A credential for machine clients, off by default
  (`fbCore.security.enable.apiKeys`). `Security\Entities\ApiKeys\ApiKey` (table
  `fb_security_api_keys`) belongs to an account and stores only the SHA-256 hash of its 256-bit
  secret (`Identity\ApiKeySecret`); `Models\ApiKeys\Repository` and `Manager` and
  `Queries\FindApiKeys` work with it. `Middleware\User`, through `Services\Auth`, reads the
  bearer token first and, only when the request carries none, the `X-Api-Key` header
  (`Identity\ApiKeyReader`). A valid key signs in as its account, the Casbin subject, through the
  identity `Identity\AccountIdentityProvider` resolves it to; the module that owns accounts
  implements that interface (Accounts does), and refuses an account that may not sign in. Core
  ships no implementation, so without one no key signs anyone in. An unknown, suspended
  (`Types\ApiKeyState`), deleted or expired key, or one whose account may not sign in, is refused
  with 401. The commands `fb:api-key:create`, `list`, `suspend` and `delete`
  (`Security\Commands`) manage the keys. Not accepted for WebSocket connections. See
  [configuration.md](../../../../../docs/configuration.md#api-keys).
- **Ownership.** `Security\Entities\Owner` marks an entity that records the user who created it,
  through the owner mapping driver (`fbCore.security.enable.doctrine.mapping`).

## Values

The shared vocabulary of the exchange and the API:

- `Values\Types\DataType` and `DataTypeShort`, the data types of a property;
- `Values\Types\Payloads`, the `Button`, `Cover` and `Switcher` payloads;
- `Values\Types\Sources`, the enums `Addon`, `Automator`, `Bridge`, `Connector`, `Core`, `Module` and
  `Plugin`, whose cases are the exchange source identifiers. `Core::CORE` (`com.fastybird.core`) is
  Core's own: its WebSocket and HTTP server logs carry it as `source` and name the server in a
  separate `capability` context key (`ws-server`, `web-server`);
- `Values\Formats`, the value formats (`NumberRange`, `StringEnum`, `CombinedEnum`);
- `Values\Transformers`, the colour and equation transformers;
- `Values\Schemas\Validator`, the JSON schema validator (`opis/json-schema`);
- `Values\Utilities\Value` and `DataType`, conversions between them.

The JS package mirrors the types in `assets/values/types`.

## WebSockets

A WAMP v1 server on the React event loop, run by `php bin/fb-console.php fb:ws-server:start` on
`fbCore.webSockets.server.address`/`port`.

- **Routes.** A module tags a service with `WebSocketsExtension::ROUTES_TAG`; its `createRouter()`
  returns a `WebSockets\Wamp\RouteList` of `WampRoute`s, for example the Devices module's
  `/devices-module/v1/exchange`. `fbCore.webSockets.routes` adds routes from configuration.
- **Controllers.** Every `WebSockets\Controllers\RequestController` is tagged
  `WebSocketsExtension::CONTROLLER_TAG` automatically. `WebSockets\Controllers\ControllerFactory`
  creates them; a module maps its controller namespace with `setMapping()` on it, or through
  `fbCore.webSockets.mapping`.
- **Access.** `fbCore.webSockets.access.keys` makes a client send one of the keys in the `x-ws-key`
  handshake header, and `access.origins` limits the origins a client may connect from.
- **Storage.** Connected clients and WAMP topics are kept by a storage driver, in memory by
  default (`fbCore.webSockets.storage`).
- **Events.** `WebSockets\Events\ServerCreated`, `ServerLaunched`, `ServerStarted`,
  `ServerStopped`, `ServerFailed`, the client and connection events (`ClientConnected`,
  `ClientDisconnected`, `ClientFailed`, `ConnectionOpened`, `ConnectionClosed`) and the message
  events (`MessageReceived`, `MessageProcessed`, `ApplicationMessageReceived`, `ApplicationFailed`).
  A listener of `ServerCreated` in another package is an invokable service tagged
  `WebSocketsExtension::SERVER_CREATED_LISTENER_TAG`, with its priority as the tag value.

## The runtime namespaces

- **`EventLoop`** -- `EventLoop\Wrapper` around the React event loop, `Status`, and the
  `EventLoopStarted`, `EventLoopStopping` and `EventLoopStopped` events.
- **`Presenters`** -- `BasePresenter`, `DefaultPresenter` and `AppRouter`, which maps `/` to
  `App:Default:default` on the Nette route list `fbCore.ui.routes`.
- **`UI`** -- `UI\TemplateFactory`, which applies Core's layout template, `templates/@layout.latte`.
  The layout loads the Vue application shell from `assets/` through contributte/vite.

## The JS package

`@fastybird/miniserver-core` exports `assets/entry.ts`: the shell components and composables, and
the capabilities `api` (the backend client), `eventBus`, `extensions`, `security`, `stores`,
`values` and `websockets` (the WAMP v1 client). `assets/main.ts` is the Vite entry of the
application shell; [docs/architecture.md](../../../../../docs/architecture.md#frontend) describes
how it loads the registered extensions.
