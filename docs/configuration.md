# Configuration

Shipped wiring lives in `config/common.neon` and `config/defaults.neon`. Operator overrides go in `config/local.neon`, which is git-ignored, or in environment variables named `FB_APP_PARAMETER__<SECTION>_<KEY>` (see [architecture.md](./architecture.md#configuration-load-order)).

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
