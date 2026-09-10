<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

# About Plugin

The purpose of this plugin is to create php based WS server for serving and handling sockets real time connections.

This library has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Plugin\RedisDb
  \Commands - Console commands to run WS server
  \Events - Events which are triggered by plugin and other services
  \Subscribers - Plugin subscribers which are subscribed to main sockets library
```

All services, helpers, etc. are written to be self-descriptive :wink:.

## Using Plugin

The plugin is ready to be used as is. Has configured all services in application container and there is no need to develop
some other services or bridges.

This plugin is dependent on other extensions, and they have to be registered too

```neon
extensions:
    ...
    contributteConsole: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
    ipubWebsockets: IPub\WebSockets\DI\WebSocketsExtension
    ipubWebsocketsWamp: IPub\WebSocketsWAMP\DI\WebSocketsWAMPExtension
```

## Plugin Configuration

This plugin has some configuration options:

```neon
fbWsServerPlugin:
    access:
        keys: f9657db3-b9e0-4a6d-a482-76a8099edbce
        origins: yourdomain.tld,service.yourdomain.tld
```

Where:

- `access -> keys` are comma separated access keys which will server validate on clients connections
- `access -> origins` are comma separated allowed domain names which will server validate on clients connections

## Running server

This plugin has implemented command interface for running server. All you have to do is just run one command:

```sh
<app_root>/vendor/bin/fb-console fb:ws-server:start
```
