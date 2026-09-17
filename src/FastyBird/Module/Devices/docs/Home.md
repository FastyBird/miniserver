<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# Getting started

This module adds support for managing [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) connectors and devices.

***

## Installation

The best way to install **fastybird/devices-module** is using [Composer](https://getcomposer.org/).

> If you don't have Composer yet, [download it](https://getcomposer.org/download/) following the instructions.

### Create new project

If you don't have a project created yet you could start with Nette base project.

You could create new project with simple composer command.

```sh
composer create-project nette/web-project path/to/install
```

Everything required will be then installed in the provided folder.

```sh
cd path/to/install
```

### Install module

Module could be added to your project with composer command:

```sh
composer require fastybird/devices-module
```

### Module user interface

**@fastybird/devices-module** is part of the [FastyBird MiniServer](https://github.com/FastyBird/miniserver) monorepo and is not published as a standalone npm package.

## Configuration

This module is dependent on other Nette extensions. All this extensions have to be enabled and configured in NEON
configuration file.

Example configuration could be found [here](https://github.com/FastyBird/devices-module/blob/main/config/example.neon)

## Initialization

This module is using database, and need some initial data to be inserted into it.

```sh
your-console-entrypoint fb:devices-module:install
```

This console command is interactive and will ask for all required information.
