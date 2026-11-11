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

## Data source history

`GET /api/ui-module/v1/widgets/{widget}/data-sources/{id}/history` serves the history of what a data source shows, for a chart: `Controllers\DataSourceHistoryV1`, with the schema `Schemas\Widgets\DataSources\History` (type `com.fastybird.ui-module/data-source/history`). The module keeps no history and cannot import the module that does, so the controller reads it through `Models\History\Reader`, which the Devices module bridge implements (`Models\History\PropertyReader`), and returns a `Models\History\Series` (`time`, `min`, `max`, `avg` and `last` columns). A data source that is not bound to a property with a history has none and is a `404`; with no reader registered, none has. The query (`filter[from]`, `filter[to]`, `filter[period]` and `filter[points]`) and the limits are in [Configuration](../../../../../docs/configuration.md#property-value-history).

The chart graph display has a `period` setting, the number of seconds a chart shows when it opens (a day by default, 60 to 31,622,400). It is stored in the display's `params`, so it needs no migration, and it is the default period of the history endpoint.
