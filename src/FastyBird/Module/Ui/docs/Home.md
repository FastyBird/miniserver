<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# Getting started

This module adds support for managing [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface components.

***

## Installation

This module is part of [MiniServer](https://github.com/FastyBird/miniserver) and is registered by default (`fbUiModule` in [`config/common.neon`](../../../../../config/common.neon)), so there is nothing to install.

**@fastybird/ui-module** is part of the [FastyBird MiniServer](https://github.com/FastyBird/miniserver) monorepo and is not published as a standalone npm package.

## Configuration

This module is dependent on other Nette extensions. MiniServer enables and configures all of them in `config/common.neon`
(see [Configuration](../../../../../docs/configuration.md)). An example of a complete NEON configuration could be found [here](../config/example.neon).

## Initialization

This module is using database, and need some initial data to be inserted into it.

```sh
php bin/fb-console.php fb:ui-module:install
```

This console command is interactive and will ask for all required information.
