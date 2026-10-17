<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# Getting started

This module adds support for managing [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) connectors and devices.

***

## Installation

This module is part of [MiniServer](https://github.com/FastyBird/miniserver) and is registered by default (`fbDevicesModule` in [`config/common.neon`](../../../../../config/common.neon)), so there is nothing to install.

**@fastybird/devices-module** is part of the [FastyBird MiniServer](https://github.com/FastyBird/miniserver) monorepo and is not published as a standalone npm package.

## Configuration

This module is dependent on other Nette extensions. MiniServer enables and configures all of them in `config/common.neon`
(see [Configuration](../../../../../docs/configuration.md)). An example of a complete NEON configuration could be found [here](../config/example.neon).

## Initialization

This module is using database, and need some initial data to be inserted into it.

```sh
php bin/fb-console.php fb:devices-module:install
```

This console command is interactive and will ask for all required information.

## Property value history

The module can record every dynamic property's value over time, so that a chart can draw it (#624). It is off by default (`fbDevicesModule.history.enabled`), and [Configuration](../../../../../docs/configuration.md#property-value-history) lists its keys and what it records.

- **Where it comes from.** `Subscribers\StateHistory` listens to the six `...PropertyStateEntityCreated` and `...PropertyStateEntityUpdated` events, which the property state managers dispatch once for each stored change, in whichever process wrote it. It feeds `Models\History\Recorder`. Without a registered state store (today only the RedisDb bridge) no state event fires, so nothing is recorded.
- **What it stores.** `Entities\History\Sample` (`fb_devices_module_history_samples`) holds one row per stored change, as a property id, UTC epoch seconds and a double: `NULL` is a gap, a state that was not valid. `Entities\History\Rollup` (`fb_devices_module_history_rollups`) holds the 5-minute and 1-hour buckets of `Types\HistoryResolution`, with their minimum, maximum, average, last value and sample count. Both are mapped read-only, own their schema and have no foreign key. Reads and writes go through DBAL.
- **How often.** The recorder skips a mapped property (its parent is recorded), a type that is not a number, a boolean or a switch, a value equal to the last stored one and a change smaller than the property's `step`. A change within `minInterval` of the last stored sample waits, and a later one replaces it. While the event loop runs, a timer writes the buffer every `flushInterval` seconds. A process without a running loop writes each change at once.
- **A history failure never fails a state write.** The recorder logs a failed write and keeps up to 10,000 rows for the next one.
