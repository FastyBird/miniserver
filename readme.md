# FastyBird MiniServer app

[![Build Status](https://badgen.net/github/checks/FastyBird/application-events/master?cache=300&style=flast-square)](https://github.com/FastyBird/miniserver-app/actions)
[![Code coverage](https://badgen.net/coveralls/c/github/FastyBird/miniserver-app?cache=300&style=flast-square)](https://coveralls.io/r/FastyBird/miniserver-app)
![PHP](https://badgen.net/packagist/php/FastyBird/miniserver-app?cache=300&style=flast-square)
[![Licence](https://badgen.net/packagist/license/FastyBird/miniserver-app?cache=300&style=flast-square)](https://packagist.org/packages/FastyBird/miniserver-app)
[![Downloads total](https://badgen.net/packagist/dt/FastyBird/miniserver-app?cache=300&style=flast-square)](https://packagist.org/packages/FastyBird/miniserver-app)
[![Latest stable](https://badgen.net/packagist/v/FastyBird/miniserver-app/latest?cache=300&style=flast-square)](https://packagist.org/packages/FastyBird/miniserver-app)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

## What is FastyBird MiniServer app?

MiniServer app is main management application for configuring devices, triggers, etc.

FastyBird MiniServer app is an Apache2 licensed distributed application, developed in PHP with [Nette framework](https://nette.org).

## Requirements

FastyBird MiniServer app is tested against PHP 7.4 and [ReactPHP http](https://github.com/reactphp/http) 0.8 event-driven, streaming plaintext HTTP server

## Getting started

> **NOTE:** If you don't want to install it manually, try [docker image](#install-with-docker)

The best way to install **fastybird/miniserver-app** is using [Composer](https://getcomposer.org/). If you don't have Composer yet, [download it](https://getcomposer.org/download/) following the instructions.
Then use command:

```sh
$ composer create-project --no-dev fastybird/miniserver-app path/to/install
$ cd path/to/install
```

Everything required will be then installed in the provided folder `path/to/install`

This microservice has several console command.

##### HTTP server

```sh
$ vendor/bin/fb-console fb:web-server:start
```

This command is to start build in web server which is listening for incoming http api request messages from clients and is listening for new data from exchange bus from other microservices. 

## Install with docker

![Docker Image Version (latest by date)](https://img.shields.io/docker/v/fastybird/miniserver-app?style=flat-square)
![Docker Image Size (latest by date)](https://img.shields.io/docker/image-size/fastybird/miniserver-app?style=flat-square)
![Docker Cloud Build Status](https://img.shields.io/docker/cloud/build/fastybird/miniserver-app?style=flat-square)

Docker image: [fastybird/miniserver-app](https://hub.docker.com/r/fastybird/miniserver-app/)

### Use docker hub image

```bash
$ docker run -d -it --name devices fastybird/miniserver-app:latest
```

### Generate local image

```bash
$ docker build --tag=miniserver-app .
$ docker run -d -it --name miniserver-app miniserver-app
```

## Configuration

This application is preconfigured for default connections, but your infrastructure could be different.

Configuration could be made via environment variables:

| Environment Variable | Description |
| ---------------------- | ---------------------------- |
| `FB_APP_PARAMETER__DATABASE_VERSION=5.7` | MySQL server version |
| `FB_APP_PARAMETER__DATABASE_HOST=127.0.0.1` | MySQL host address |
| `FB_APP_PARAMETER__DATABASE_PORT=3306` | MySQL access port |
| `FB_APP_PARAMETER__DATABASE_DBNAME=miniserver` | MySQL database name |
| `FB_APP_PARAMETER__DATABASE_USERNAME=root` | Username |
| `FB_APP_PARAMETER__DATABASE_PASSWORD=` | Password |
| | |
| `FB_APP_PARAMETER__REDIS_HOST=127.0.0.1` | Redis server host address |
| `FB_APP_PARAMETER__REDIS_PORT=6379` | Redis server access port |
| `FB_APP_PARAMETER__REDIS_USERNAME=` | Redis server username |
| `FB_APP_PARAMETER__REDIS_PASSWORD=` | Redis server password |
| | |
| `FB_APP_PARAMETER__SERVER_ADDRESS=0.0.0.0` | HTTP server host address |
| `FB_APP_PARAMETER__SERVER_PORT=8000` | HTTP server access port |
| | |
| `FB_APP_PARAMETER__SECURITY_SIGNATURE=` | Security token signature string |

> **NOTE:** In case you are not using docker image or you are not able to configure environment variables, you could edit configuration file `./config/default.neon`

## Initialization

This application is using database, and need some initial data to be inserted into it. This could be done via shell command:

```sh
$ vendor/bin/fb-console fb:initialize
```

This console command is interactive and will ask for all required information.

After this steps, application could be started with [server command](#http-server)

## Feedback

Use the [issue tracker](https://github.com/FastyBird/miniserver-app/issues) for bugs or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/miniserver-app/releases)

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4">
				</a>
				<br>
				<a href="https://github.com/akadlec">Adam Kadlec</a>
			</td>
		</tr>
	</tbody>
</table>

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and repository [https://github.com/fastybird/miniserver-app](https://github.com/fastybird/miniserver-app).
