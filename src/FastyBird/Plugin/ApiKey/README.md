<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# FastyBird Api key plugin

***

## What is api key plugin?

Api key plugin is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is implementing api access key management and middleware.

### Features

- Build-in [PSR-7](http://www.php-fig.org/psr/psr-7/) middleware for validating api keys
- Build-in console command for managing keys
- Support for extending keys

Api key plugin is an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) on top of the [Nette framework](https://nette.org) and [Symfony framework](https://symfony.com).

## Installation

This plugin is part of [MiniServer](https://github.com/FastyBird/miniserver) but is not registered by default. To enable it, register its DI extension in `config/local.neon`, as described in [Configuration](../../../../docs/configuration.md#api-key-authentication-apikey-plugin):

```neon
extensions:
    fbApiKeyPlugin: FastyBird\Plugin\ApiKey\DI\ApiKeyExtension
```

The plugin takes no configuration; registering the DI extension is enough.

## Documentation

Learn how to create, manage and use api keys
in [documentation](docs/index.md).

# FastyBird

<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/fastybird_row.svg?raw=true" alt="FastyBird"/>
</p>

FastyBird is an Open Source IOT solution built from decoupled components with powerful API and the highest quality code. Read more on [fastybird.com.com](https://www.fastybird.com).

## Documentation

Documentation is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

## Contributing

The sources of this package are contained in the [FastyBird MiniServer monorepo](https://github.com/FastyBird/miniserver). We welcome contributions for this package on [FastyBird/miniserver](https://github.com/FastyBird/miniserver).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/miniserver/issues) for bugs
or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the
project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/miniserver/releases).

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img alt="akadlec" width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4" />
				</a>
				<br>
				<a href="https://github.com/akadlec">Adam Kadlec</a>
			</td>
		</tr>
	</tbody>
</table>

***
Homepage [https://www.fastybird.com](https://www.fastybird.com).
