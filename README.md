![FastyBird MiniServer](docs/assets/fastybird_miniserver_readme.png)

<h1 align="center">FastyBird MiniServer</h1>

<p align="center">A self-hosted IoT server: devices, connectors, automations and a JSON:API + Vue admin interface, in one PHP application.</p>

## What is FastyBird MiniServer?

MiniServer is a standalone application built on the [FastyBird](https://www.fastybird.com) IoT extension set, developed on top of the [Nette](https://nette.org) and [Symfony](https://symfony.com) frameworks. It integrates third-party device ecosystems (Shelly, Tuya, Sonoff, Viera, NsPanel, Zigbee2Mqtt, Modbus, generic MQTT, virtual devices) and Apple HomeKit, behind a JSON:API backend and a Vue 3 admin UI served from the same entry point.

## Requirements

PHP 8.2, Node 24, yarn 1, MariaDB. Redis, CouchDB and RabbitMQ are optional -- see [docs/configuration.md](docs/configuration.md).

## Getting started

### With Docker (recommended)

```sh
docker compose -f docker/prod/docker-compose.yml up -d
```

This builds `docker/prod/Dockerfile`, starts the application and a MariaDB database, and runs pending migrations on first start. See [docs/deployment.md](docs/deployment.md) for the full process layout (nginx, php-fpm, the WebSocket server and the devices-module exchange worker under supervisord), for the required `SECURITY_SIGNATURE`, and for the pre-built image at `ghcr.io/fastybird/miniserver`.

### Traditional installation

```sh
composer install --no-dev --prefer-dist --classmap-authoritative
yarn install --frozen-lockfile && yarn build
```

Then create the schema and any module-specific data:

```sh
php bin/fb-console.php migrations:migrate --no-interaction --allow-no-migration
php bin/fb-console.php fb:devices-module:install
php bin/fb-console.php fb:accounts-module:install
```

Point a web server at `public/`, or run the built-in ReactPHP server for local use:

```sh
php bin/fb-console.php fb:web-server:start
```

## Documentation

- [docs/architecture.md](docs/architecture.md) -- extension inventory, request routing, config load order
- [docs/configuration.md](docs/configuration.md) -- enabling the extensions that ship in the tree but are not wired by default
- [docs/deployment.md](docs/deployment.md) -- Docker images, supervisor processes, production defaults

## Feedback

Use the [issue tracker](https://github.com/FastyBird/miniserver/issues) for bugs, or [mail](mailto:code@fastybird.com) us for ideas that can improve the project.

## Changelog

For release info check the [release page](https://github.com/FastyBird/miniserver/releases).

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
Homepage [https://www.fastybird.com](https://www.fastybird.com) and repository [https://github.com/FastyBird/miniserver](https://github.com/FastyBird/miniserver).
