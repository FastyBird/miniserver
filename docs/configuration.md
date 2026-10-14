# Configuration

Shipped wiring lives in `config/common.neon` and `config/defaults.neon`. Operator overrides go in `config/local.neon`, which is git-ignored, or in environment variables named `FB_APP_PARAMETER__<SECTION>_<KEY>` (see [architecture.md](./architecture.md#configuration-load-order)).

## Environment variable typing

An environment variable arrives as a string. `Boot\Bootstrap::loadEnvParameters()` types it before it becomes a container parameter, by these rules:

| Value | Becomes | Examples |
|---|---|---|
| `true` or `false`, in any case | a boolean | `FB_APP_PARAMETER__API_PREFIXED_MODULES=false` is `%api.prefixed.modules%` = `false` |
| a canonical integer: `0`, or an optional `-`, then digits with no leading zero | an int | `3306`, `0`, `-5` |
| anything else, the empty string included | the string, unchanged | `0123`, `+5`, `1.5`, `1e3`, `10.11`, `127.0.0.1` |

An integer too large for PHP (above `9223372036854775807`) stays a string too.

A variable whose name ends in `PASSWORD`, `USERNAME`, `SIGNATURE`, `DSN` or `KEY`, in any case, is never typed: its value stays the string it was, so a password of `12345678`, `true` or `0123` reaches the connection unchanged. Only the end of the name counts: `..._PASSWORD_AGE` is typed like any other variable.

Any other name that must be a string and looks like a number, such as an all-digit `FB_APP_PARAMETER__DATABASE_DBNAME`, is typed as an int, and the schema that reads it will reject it. Set that one in `config/local.neon` instead.

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

Since Epic #459 (#558), every Core service is named `fbCore.<capability>.<role>`, after the capability that registers it. The root services are `fbCore.eventLoop.*`, `fbCore.ui.*`, `fbCore.cache.psr6` and `fbCore.eventDispatcher`. The four unprefixed `document.*` services are now `fbCore.documents.*`. Some examples:

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
| `sentry.dsn` | string or null | `null` | Sentry DSN. The environment variable `FB_APP_PARAMETER__SENTRY_DSN` takes precedence. With neither set, no Sentry service is registered. An event carries no request body, which can hold a password or a token. |
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
| `enable.apiKeys` | bool | `false` | Accept API keys in the `X-Api-Key` header and register the `fb:api-key:*` commands, see [API keys](#api-keys). When `false`, the header is ignored and no API key service or command is registered. |
| `application.signInUrl` | string | `null` | Where a presenter redirects a visitor who is denied access and not signed in. When `null`, a denied request fails with 403 instead of redirecting. |
| `application.homeUrl` | string | `/` | Where a presenter redirects a signed-in user who is denied access. Used only when `application.signInUrl` is set. |
| `services.identity` | bool | `false` | Register Core's identity factory, which reads the user and the roles from the token's claims and looks nothing up: a correctly signed token is accepted until it expires, and nothing revokes it. Only for an application without the Accounts module. Accounts registers its own identity factory, which also requires the token, its identity and its account to be active, and with both registered the container does not compile (`Multiple services of type ...IdentityProvider found`). |
| `casbin.model` | string | Core's `resources/model.conf` | Casbin model file. It must exist. |
| `casbin.policy` | string | `null` | Casbin policy file. Required, and it must exist, when `enable.casbin.database` is `false`. |

#### API keys

An API key is a credential for a machine client, such as a script or another server. It belongs to an account and authenticates as that account: the account is the Casbin subject, so a key has exactly the roles and permissions a bearer token for that account has. There are no scopes. A key is not meant for the browser frontend, which signs in with a bearer token; a key built into a public bundle would protect nothing.

API keys are off by default. To turn them on:

```neon
fbCore:
    security:
        enable:
            apiKeys: true
```

They also need `enable.middleware`, which the application's `config/common.neon` turns on. The `fb_security_api_keys` table exists on every install, created by the migration `Version20261012224409`, whether keys are on or off.

**Commands.** All four are registered only while `enable.apiKeys` is on, and none of them asks a question:

| Command | What it does |
|---|---|
| `fb:api-key:create --name=<name> --account=<account UUID> [--expires-at=<date and time>]` | Creates an active key for an account that can sign in, and prints its secret. The secret is shown this once and cannot be read back. With `--quiet`, the secret is the only output. `--expires-at` takes a date and time in the future, ISO 8601 recommended (`2027-01-01T00:00:00+00:00`); without it, the key never expires. |
| `fb:api-key:list` | Lists every key with its identifier, name, account, state (`active`, `suspended`, `deleted`, or `expired` for an active key past its expiry), expiry and creation time. It never prints a secret or a hash. |
| `fb:api-key:suspend <key UUID>` | Suspends the key. |
| `fb:api-key:delete <key UUID>` | Deletes the key. The row is kept in the `deleted` state, so `list` still shows it. |

```sh
php bin/fb-console.php fb:api-key:create --name="Home Assistant" --account=5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34
```

**Using a key.** Send the secret in the `X-Api-Key` header:

```sh
curl -H 'X-Api-Key: <secret>' http://localhost/api/devices-module/v1/devices
```

- `Security\Middleware\User` checks the bearer token first. **A bearer token wins:** when a request carries an `Authorization: Bearer …` header, `X-Api-Key` is not read at all, so an invalid token is refused with 401 even if a valid key comes with it.
- A request with no bearer token and a valid key is signed in as the key's account.
- A key that is unknown, suspended, deleted or expired is refused with 401, as an invalid bearer token is. A request with neither is anonymous and is refused with 403 wherever the route requires a signed-in user.
- Keys are accepted by every API route group that carries Security's `User` middleware. They are not accepted for WebSocket connections.

**What is stored.** The secret is 256 random bits, hex encoded (64 characters). Only its SHA-256 hash is stored, and Core logs neither the secret nor the hash.

**A key follows its account.** The module that owns accounts decides whether a key's account may sign in, through `Security\Identity\AccountIdentityProvider`; the Accounts module implements it (`Security\AccountIdentityFactory`) with the rule of a password sign-in: only an `active` account. A key whose account is unknown, blocked, deleted, not activated or awaiting approval is refused with 401 for as long as the account stays that way, and `fb:api-key:create` refuses such an account. Without a module that implements the interface, no key can be created and none signs anyone in. A key signs in as the account itself (`Security\AccountIdentity`).

**A key reads and writes data; it does not administer accounts.** It is accepted on the data routes of every module (Devices, Triggers, Ui, the bridges), with its account's roles. It is refused with 403 on every route of the Accounts module (`Middleware\InteractiveOnly`): the session and its tokens, `/me` with the account's profile, password, emails and identities, other accounts, roles and registration. Those are account administration, and some of them issue credentials, so a leaked key cannot change its account's password or mint tokens. A bearer token keeps them all.

**Revocation takes effect on the next request.** The key and its account are read from the database on every request, also in a long-running process (`fb:web-server:start`) that would otherwise hold them in its entity manager, so a key suspended or deleted, or an account blocked, by another process is refused without a restart.

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

## Automators

`config/common.neon` registers both automators, `fbDateTimeAutomator` (`FastyBird\Automator\DateTime\DI\DateTimeExtension`) and `fbDevicesModuleAutomator` (`FastyBird\Automator\DevicesModule\DI\DevicesModuleExtension`). Neither takes configuration. They add the triggers module's conditions and actions:

- **DateTime**: the date and time conditions.
- **DevicesModule**: the device property and channel property conditions and actions. It also adds two Doctrine `onFlush` subscribers. They reject a trigger with two channel property actions on the same property, or with two device property or two channel property conditions on the same property.

For each type, an automator adds its entity, document, JSON:API schema and hydrator. Neither adds a console command, a route, a timer or an exchange consumer. The migration `Version20261012172407` creates their 14 nullable columns: 5 on `fb_triggers_module_actions` and 9 on `fb_triggers_module_conditions`.

## Extensions not registered by default

None of the extensions below are registered by default. Each snippet is a complete `config/local.neon` that registers the extension's DI extension class alongside whatever `config/common.neon` already registers -- Nette merges the `extensions:` sections, so you do not need to repeat the ones already wired.

API keys are not among them: they are part of Core's Security capability and are switched on with `fbCore.security.enable.apiKeys`, see [API keys](#api-keys). The ApiKey plugin this list used to include was removed in #730.

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

`client.username` and `client.password` default to `null`; add them if your Redis instance requires auth. `exchange.channel` defaults to `fb_exchange` (`RedisDbExtension::EXCHANGE_CHANNEL_NAME`) if omitted.

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

`connection.port` defaults to `5984`, CouchDB's port. Until #726 the default was `5672`, RabbitMQ's port, left over from adapting the RabbitMq plugin.

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

**`queue.name` must be unique per process.** The exchange is a topic exchange and every process binds its own queue to it; that is how a message reaches every process. Left unset, each process declares its own private, auto-delete queue (`rabbit.plugin_` plus a random string), which is the right setup. If you set `queue.name`, give each process a different name. Processes that share a queue name share one queue, and RabbitMQ delivers each message to only one of its consumers, so they compete for the messages instead of each receiving them: a message published by a connector would reach one other process, not all of them. The plugin does not reject a shared name at startup.

## Messages a process publishes itself, and WebSocket clients

**A process skips its own messages.** RedisDb and RabbitMq stamp each message with the publishing process's sender identifier (`Utilities\IdentifierGenerator`, one service per process, a UUID generated when the service is created) and skip a message that carries their own. The process already did what the message reports before it published it, so a second pass would repeat the work: a connector's exchange writer would write the connector's own state update back to the device.

- RedisDb's `Exchange\Handler` drops the message.
- RabbitMq's `Handlers\Message` answers `MESSAGE_ACK`, without running any consumer, and `Channels\Factory` calls `$channel->ack($message)`, so the broker drops the message. It is never nacked: a nack **re-queues** the message, the per-process queue has no other consumer, and the process would receive its own message again, and nack it again, for ever (#685). A shared `queue.name` does not change this, but it makes the processes compete for messages, which is why each process needs its own queue name (see the RabbitMq section above).

**A WebSocket SET is a command, not a broadcast.** A WAMP SET (`Devices\Controllers\ExchangeV1`) is addressed to its target, the connector or device process that owns the property, and only the target consumes it. It is not propagated to the other WebSocket clients. A document published with the `devices` source is a command (a WebSocket SET or an API write, as an action or as the state write it produces), and the Devices and DevicesModuleUiModule SocketsBridges, which forward exchange documents to WebSocket clients, skip it. So does the DevicesModuleUiModule state subscriber, which publishes a widget data source's document when the state of the property it reads changes: it still cleans the data source's caches, but publishes nothing for a `devices`-sourced change. The clients see the change when the target reports the resulting state under its own source, which is the path every change a connector reports already takes.

What a connector reports about itself is not a command, so it carries the connector's source, not `devices`: its device and connector connection state (`Utilities\DeviceConnection::setState()` and `ConnectorConnection::setState()` take the source of whoever reports) and the validity reset of its properties when it starts or stops. WebSocket clients receive those, which includes Devices clients that did not before.

## Compose services

`docker/dev/docker-compose.yml` puts `redis`, `couchdb`, `rabbitmq` and `mqtt` behind Compose profiles, and `docker/prod/docker-compose.yml` puts `redis` behind one, because none of the extensions under [Extensions not registered by default](#extensions-not-registered-by-default) are registered by default. Enable the matching profile when you register the extension, for example:

```sh
docker compose -f docker/dev/docker-compose.yml --profile redis up -d
```
