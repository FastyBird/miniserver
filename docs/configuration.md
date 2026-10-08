# Configuration

Shipped wiring lives in `config/common.neon` and `config/defaults.neon`. Operator overrides go in `config/local.neon`, which is git-ignored, or in environment variables named `FB_APP_PARAMETER__<SECTION>_<KEY>` (see [architecture.md](./architecture.md#configuration-load-order)).

## Core (`fbCore`)

`fbCore` is `FastyBird\Core\DI\CoreExtension`, and every container registers it. Its configuration has one section per configurable capability: `logging`, `documents`, `security`, `clock`, `persistence`, `api`, `webSockets` and `http`. Exchange, Values and Phone have no configuration. Each section is a strict schema. A key the schema does not know fails container compilation, for example `Unexpected item 'fbCore › jsonApi'.`

Core's own `src/FastyBird/Core/Core/config/common.neon` sets `fbCore.logging` from the `%logger.*%` parameters in its `config/defaults.neon`. The application's `config/defaults.neon` overrides `logger.stdOut.enabled` and `logger.console.enabled` with `%debugMode%`. The application's `config/common.neon` sets `security`, `api`, `webSockets.server`, `http` and `documents`. A `config/local.neon` overrides any of them, for example:

```neon
fbCore:
    http:
        cors:
            enabled: true
            allow:
                origin: https://example.com
```

### Renamed keys (breaking change)

Before Epic #459 (#557), these sections carried the names of the libraries Core was assembled from. Only the section path changed. Every key below a section, and every type and default, is the same.

| Old path | New path |
|---|---|
| `fbCore.application.logging` | `fbCore.logging` |
| `fbCore.tools.sentry` | `fbCore.logging.sentry` |
| `fbCore.application.documents` | `fbCore.documents` |
| `fbCore.simpleAuth` | `fbCore.security` |
| `fbCore.dateTimeFactory` | `fbCore.clock` |
| `fbCore.doctrineTimestampable` | `fbCore.persistence.timestampable` |
| `fbCore.jsonApi` | `fbCore.api` |
| `fbCore.webSockets` | `fbCore.webSockets` (unchanged) |
| `fbCore.wsServer.access` | `fbCore.webSockets.access` |
| `fbCore.httpServer` | `fbCore.http` |

The old sections `application`, `tools` and `wsServer` are gone. There is no live installation, so there is no compatibility layer and no migration path. A `config/local.neon` that still uses an old key fails at the first container compilation with `Unexpected item 'fbCore › <old key>'`. To fix it, move the section to its new path. For example, `fbCore: simpleAuth: token: signature: …` becomes `fbCore: security: token: signature: …`.

### Renamed service names (breaking change)

Since Epic #459 (#558), every Core service is named `fbCore.<capability>.<role>`, after the capability that registers it. The root services are `fbCore.eventLoop.*`, `fbCore.ui.*`, `fbCore.cache.psr6` and `fbCore.eventDispatcher`. The four unprefixed `document.*` services are now `fbCore.documents.*`. The full old-to-new table is `tools/di-maps/06-services.php`. Some examples:

| Old name | New name |
|---|---|
| `fbCore.jsonApi.middlewares.jsonapi` | `fbCore.api.middleware` |
| `fbCore.simpleAuth.token.builder` | `fbCore.security.token.builder` |
| `fbCore.wsServer.server.server` | `fbCore.webSockets.server.runtime` |
| `fbCore.httpServer.routing.router` | `fbCore.http.routing.router` |
| `fbCore.application.eventLoop.wrapper` | `fbCore.eventLoop.wrapper` |
| `document.factory` | `fbCore.documents.factory` |

The service types are unchanged, so autowiring is not affected. Only a reference by name breaks. A `config/local.neon` that still names an old service, for example `@fbCore.jsonApi.middlewares.jsonapi` in a `decorator:` setup, fails container compilation with a missing-service error. To fix it, use the new name.

Since Epic #460 (#640), the root `fbCore.configuration` service (`FastyBird\Core\Configuration`) is two services, each registered by the capability that owns its settings: `fbCore.security.configuration` (`FastyBird\Core\Security\Configuration`, from `fbCore > security`) and `fbCore.persistence.timestampable.configuration` (`FastyBird\Core\Persistence\TimestampableConfiguration`, from `fbCore > persistence > timestampable`). No configuration key changed. Code that autowires the old type must take the new one; a reference by the old name breaks as above.

### `fbCore.logging`

| Key | Type | Default | Meaning |
|---|---|---|---|
| `rotatingFile.enabled` | bool | `true` | Log to a daily rotating file (Monolog `RotatingFileHandler`, 10 files kept). |
| `rotatingFile.level` | int | `Monolog\Level::Info` (200) | Lowest level written to that file. |
| `rotatingFile.filename` | string | `app.log` | File name inside the logs directory (`FB_LOGS_DIR`). |
| `stdOut.enabled` | bool | `false` | Also log to `php://stdout`. |
| `stdOut.level` | int | `Monolog\Level::Info` (200) | Lowest level written to stdout. |
| `console.enabled` | bool | `false` | Register Symfony's Monolog `ConsoleHandler` and the subscriber that sets its level when a console command starts. |
| `console.level` | int | `Monolog\Level::Info` (200) | The level that subscriber sets. |
| `sentry.dsn` | string or null | `null` | Sentry DSN. The environment variable `FB_APP_PARAMETER__SENTRY_DSN` takes precedence. With neither set, no Sentry service is registered. |
| `sentry.level` | int | `Monolog\Level::Warning` (300) | Lowest level sent to Sentry. |

### `fbCore.documents`

| Key | Type | Default | Meaning |
|---|---|---|---|
| `mapping` | map of namespace to directory | `[]` | Document classes read through their attributes. Every directory must exist, or compilation fails. Modules add their own document namespaces through a DI tag, not through this key. |
| `excludePaths` | map of strings | `[]` | Paths the document attribute driver skips. |

### `fbCore.security`

Nothing in this section takes effect unless `token.signature` is set. With an empty signature, no authentication or authorization service is registered.

| Key | Type | Default | Meaning |
|---|---|---|---|
| `token.issuer` | string | `null` | Issuer written to and expected in access tokens. |
| `token.signature` | string | `''` | Secret that signs and validates access tokens. The application sets it from `%security.signature%`, that is `FB_APP_PARAMETER__SECURITY_SIGNATURE` or `config/local.neon`. |
| `enable.middleware` | bool | `false` | Register the authorization and user middlewares of the HTTP server. |
| `enable.doctrine.mapping` | bool | `false` | Register the owner mapping driver and the Doctrine subscriber that records the acting user on entities. |
| `enable.doctrine.models` | bool | `false` | Register the token repository and manager, and map Core's security entities on the default entity manager. |
| `enable.casbin.database` | bool | `false` | Keep Casbin policies in the database (adapter, policy subscriber, policy repository and manager, entity mapping). When `false`, policies are read from the `casbin.policy` file. |
| `enable.nette.application` | bool | `false` | Dispatch Nette `Application` request and response events, and register the subscriber that signs the user in from the access-token cookie on every presenter request. |
| `application.signInUrl` | string | `null` | Where a presenter redirects a visitor who is denied access and not signed in. When `null`, a denied request fails with 403 instead of redirecting. |
| `application.homeUrl` | string | `/` | Where a presenter redirects a signed-in user who is denied access. Used only when `application.signInUrl` is set. |
| `services.identity` | bool | `false` | Register Core's identity factory. |
| `casbin.model` | string | Core's `resources/model.conf` | Casbin model file. It must exist. |
| `casbin.policy` | string | `null` | Casbin policy file. Required, and it must exist, when `enable.casbin.database` is `false`. |

### `fbCore.clock`

| Key | Type | Default | Meaning |
|---|---|---|---|
| `timeZone` | string | `UTC` | Time zone of the clock. It must be a valid PHP time zone identifier. |
| `system` | bool | `true` | Register the system clock. It is autowired only while `frozen` is `null`. |
| `frozen` | float, or a statement such as `DateTimeImmutable('2020-04-01T12:00:00+00:00')` | `null` | When set, register a clock frozen at this moment and autowire it instead of the system clock. The test configurations use this. |

### `fbCore.persistence`

| Key | Type | Default | Meaning |
|---|---|---|---|
| `timestampable.lazyAssociation` | bool | `false` | Leave a `#[Timestampable]` property that has no valid mapping alone, instead of mapping it automatically or failing. |
| `timestampable.autoMapField` | bool | `true` | Map a `#[Timestampable]` property that has no Doctrine mapping as a nullable column automatically, instead of failing. |
| `timestampable.dbFieldType` | string | `datetime_immutable` | Doctrine type used for an automatically mapped field. |

### `fbCore.api`

| Key | Type | Default | Meaning |
|---|---|---|---|
| `meta.author` | string or list of strings | `FastyBird team` | `meta.author` of every JSON:API document. |
| `meta.copyright` | string or null | `null` | `meta.copyright` of every JSON:API document. |

### `fbCore.webSockets`

| Key | Type | Default | Meaning |
|---|---|---|---|
| `server.address` | string | `0.0.0.0` | Address the WebSocket server (`fb:ws-server:start`) listens on. The application sets it from `%sockets.address%`. |
| `server.port` | int | `8080` | Port the WebSocket server listens on. The application sets it from `%sockets.port%` (8888). |
| `server.httpHost` | string | `localhost` | Host the Flash socket policy allows, on port 80 and on `server.port`. |
| `server.secured.enable` | bool | `false` | Serve the WebSocket server over TLS. |
| `server.secured.sslSettings` | array | `[]` | Stream context SSL options for TLS. |
| `storage.clients.driver` | service reference | `@fbCore.webSockets.clients.driver.memory` | Service the connected-clients storage keeps its clients in. It must implement `FastyBird\Core\WebSockets\Clients\Drivers\Driver`. See [storage drivers](#websockets-storage-drivers). |
| `storage.clients.ttl` | int | `0` | Time to live the clients storage passes to its driver. The in-memory driver ignores it. |
| `storage.topics.driver` | service reference | `@fbCore.webSockets.wamp.topics.driver.memory` | Service the WAMP topics storage keeps its topics in. It must implement `FastyBird\Core\WebSockets\Topics\Drivers\Driver`. See [storage drivers](#websockets-storage-drivers). |
| `storage.topics.ttl` | int | `0` | Time to live the topics storage passes to its driver. The in-memory driver ignores it. |
| `routes` | map of mask to action | `[]` | Extra WAMP routes. Modules contribute theirs through a DI tag. |
| `mapping` | map | `[]` | Controller name mapping for the WebSocket controller factory, as in Nette's presenter mapping. |
| `loop` | string, statement or null | `null` | The React event loop to run on. With `null`, the container's own React event loop service is used, and one is created only if the container has none. |
| `access.keys` | string or null | `null` | Comma-separated keys, one of which a client must send in the `x-ws-key` handshake header. With `null`, no key is required. |
| `access.origins` | string or null | `null` | Comma-separated origins a client may connect from. With `null`, any origin is accepted. |

#### WebSockets storage drivers

`storage.clients.driver` and `storage.topics.driver` each name a service, in any of these forms:

- `@name`, unquoted, as the defaults are written. NEON reads it as a service reference.
- `"@name"`, quoted. NEON reads a quoted string as literal text, but the option can only name a service, so it is read the same way.
- `name`, the bare service name.

The service can come from any extension or from the `services:` section of any configuration file, for example `config/local.neon`:

```neon
fbCore:
    webSockets:
        storage:
            clients:
                driver: @myClientsDriver

services:
    myClientsDriver: App\WebSockets\MyClientsDriver
```

The clients driver must implement `FastyBird\Core\WebSockets\Clients\Drivers\Driver`, and the topics driver `FastyBird\Core\WebSockets\Topics\Drivers\Driver`. Core always registers the in-memory clients driver, `fbCore.webSockets.clients.driver.memory`. It registers the in-memory topics driver, `fbCore.webSockets.wamp.topics.driver.memory`, only when `storage.topics.driver` names it. A name that matches no service fails container compilation with `Reference to missing service`. The type is not checked at compile time: a service that does not implement the interface fails with a `TypeError` from `setStorageDriver()` when the storage is first created.

### `fbCore.http`

| Key | Type | Default | Meaning |
|---|---|---|---|
| `server.address` | string | `127.0.0.1` | Address the HTTP server (`fb:web-server:start`) listens on. The application sets it from `%server.address%` (`0.0.0.0`). |
| `server.port` | int | `8000` | Port the HTTP server listens on. The application sets it from `%server.port%`. |
| `server.certificate` | string or null | `null` | Certificate file. When set, the HTTP server serves TLS, and a missing file stops it with an error. |
| `static.enabled` | bool | `false` | Serve static files from `static.publicRoot`. The application enables it. |
| `static.publicRoot` | string or null | `null` | Directory static files are served from. The application sets `%appDir%/public/dist/`. |
| `cors.enabled` | bool | `false` | Add CORS headers to responses. |
| `cors.allow.origin` | string | `*` | `Access-Control-Allow-Origin`. |
| `cors.allow.methods` | list of strings | `GET`, `POST`, `PATCH`, `DELETE`, `OPTIONS` | `Access-Control-Allow-Methods`. |
| `cors.allow.credentials` | bool | `true` | `Access-Control-Allow-Credentials`. |
| `cors.allow.headers` | list of strings | `Content-Type`, `Authorization`, `X-Requested-With` | `Access-Control-Allow-Headers`. The application adds `X-Api-Key`. |

## Extensions not registered by default

None of the extensions below are registered by default. Each snippet is a complete `config/local.neon` that registers the extension's DI extension class alongside whatever `config/common.neon` already registers -- Nette merges the `extensions:` sections, so you do not need to repeat the ones already wired.

## Redis-backed state storage (RedisDb plugin)

Registers `FastyBird\Plugin\RedisDb\DI\RedisDbExtension` (service name `fbRedisDbPlugin`):

```neon
extensions:
    fbRedisDbPlugin: FastyBird\Plugin\RedisDb\DI\RedisDbExtension

fbRedisDbPlugin:
    client:
        host: redis
        port: 6379
    exchange:
        channel: fb_exchange
```

`client.username` and `client.password` default to `null`; add them if your Redis instance requires auth. `exchange.channel` defaults to the metadata library's `EXCHANGE_CHANNEL_NAME` constant if omitted.

### Devices module state bridge

Registers `FastyBird\Bridge\RedisDbPluginDevicesModule\DI\RedisDbPluginDevicesModuleExtension` (service name `fbRedisDbPluginDevicesModuleBridge`), so device/channel/connector property state reads and writes go through Redis instead of the default in-memory/Doctrine store:

```neon
extensions:
    fbRedisDbPluginDevicesModuleBridge: FastyBird\Bridge\RedisDbPluginDevicesModule\DI\RedisDbPluginDevicesModuleExtension

fbRedisDbPluginDevicesModuleBridge:
    database: 0
```

`database` selects the Redis logical database (0-15) and defaults to `0`.

### Triggers module state bridge

Registers `FastyBird\Bridge\RedisDbPluginTriggersModule\DI\RedisDbPluginTriggersModuleExtension` (service name `fbRedisDbPluginTriggersModuleBridge`):

```neon
extensions:
    fbRedisDbPluginTriggersModuleBridge: FastyBird\Bridge\RedisDbPluginTriggersModule\DI\RedisDbPluginTriggersModuleExtension

fbRedisDbPluginTriggersModuleBridge:
    database: 1
```

`database` defaults to `1` -- one Redis logical database apart from the devices-module bridge's default of `0`, so both bridges can run against the same Redis instance without colliding.

## Redis-backed Nette cache storage (RedisDbCache plugin)

Registers `FastyBird\Plugin\RedisDbCache\DI\RedisDbCacheExtension` (service name `fbRedisDbCachePlugin`). Unlike `RedisDb` above, this replaces the framework's own cache storage, not application state:

```neon
extensions:
    fbRedisDbCachePlugin: FastyBird\Plugin\RedisDbCache\DI\RedisDbCacheExtension

fbRedisDbCachePlugin:
    client:
        host: redis
        port: 6379
        database: 0
```

## CouchDB state storage (CouchDb plugin)

Registers `FastyBird\Plugin\CouchDb\DI\CouchDbExtension` (service name `fbCouchDbPlugin`):

```neon
extensions:
    fbCouchDbPlugin: FastyBird\Plugin\CouchDb\DI\CouchDbExtension

fbCouchDbPlugin:
    connection:
        database: state_storage
        host: couchdb
        port: 5984
        username: admin
        password: admin
```

`connection.port` must be set explicitly to `5984` (CouchDB's real port) -- the extension's own schema default is `5672`, left over from being adapted from the RabbitMq plugin.

## RabbitMQ message exchange (RabbitMq plugin)

Registers `FastyBird\Plugin\RabbitMq\DI\RabbitMqExtension` (service name `fbRabbitMqPlugin`):

```neon
extensions:
    fbRabbitMqPlugin: FastyBird\Plugin\RabbitMq\DI\RabbitMqExtension

fbRabbitMqPlugin:
    client:
        host: rabbitmq
        port: 5672
        vhost: /
        username: guest
        password: guest
```

`exchange.name` and `queue.name` are optional and default to the plugin's own exchange name and an anonymous queue respectively.

## Automators

Both automators take no configuration -- registering the DI extension is enough to make their conditions/actions available to the triggers module:

```neon
extensions:
    fbDateTimeAutomator: FastyBird\Automator\DateTime\DI\DateTimeExtension
    fbDevicesModuleAutomator: FastyBird\Automator\DevicesModule\DI\DevicesModuleExtension
```

## API key authentication (ApiKey plugin)

Registers `FastyBird\Plugin\ApiKey\DI\ApiKeyExtension` (service name `fbApiKeyPlugin`); it also takes no configuration:

```neon
extensions:
    fbApiKeyPlugin: FastyBird\Plugin\ApiKey\DI\ApiKeyExtension
```

Once registered, create a key with:

```sh
php bin/fb-console.php fb:api-key:create
```

## Compose services

`docker/dev/docker-compose.yml` puts `redis`, `couchdb`, `rabbitmq` and `mqtt` behind Compose profiles, and `docker/prod/docker-compose.yml` puts `redis` behind one, because none of the extensions above are registered by default. Enable the matching profile when you register the extension, for example:

```sh
docker compose -f docker/dev/docker-compose.yml --profile redis up -d
```
