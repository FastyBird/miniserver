# Getting started

This module adds support for managing [FastyBird](https://www.fastybird.com) IoT triggers.

***

## Installation

This module is part of [MiniServer](https://github.com/FastyBird/miniserver) and is registered by default (`fbTriggersModule` in [`config/common.neon`](../../../../../config/common.neon)), so there is nothing to install.

**@fastybird/triggers-module** is part of the [FastyBird MiniServer](https://github.com/FastyBird/miniserver) monorepo and is not published as a standalone npm package.

## Configuration

This module is dependent on other Nette extensions. MiniServer enables and configures all of them in `config/common.neon`
(see [Configuration](../../../../../docs/configuration.md)). An example of a complete NEON configuration could be found [here](../config/example.neon).

## Initialization

This module is using database, and need some initial data to be inserted into it.

```sh
php bin/fb-console.php fb:triggers-module:install
```

This console command is interactive and will ask for all required information.

## Running module interface

This module is dependent on the HTTP server of [MiniServer Core](../../../Core/Core/README.md) which is server-less web
server for serving API content. This module is registering its routes to this webserver automatically. All what you have
to do is start this server with the console entrypoint:

```sh
php bin/fb-console.php fb:web-server:start
```

After successful start, server is listening for incoming http api request messages from clients.
