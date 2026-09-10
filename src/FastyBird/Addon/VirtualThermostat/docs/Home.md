<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Thermostat Device Addon is an extension for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
that enables seamless integration of software defined thermostats devices. It allows developers to easily create devices
which will act as thermostats and cooperate with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem.

# About Addon

This addon has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Addon\VirtualThermostat
  \Commands - Services used for user console interface
  \Drivers - Main thermostat services responsible for handling actions
  \Entities - All entities used by addon
  \Helpers - Useful helpers for reading values, bulding entities etc.
  \Schemas - {JSON:API} schemas mapping for API requests
  \Translations - Addon translations
```

All services, helpers, etc. are written to be self-descriptive :wink:.

> [!TIP]
To better understand what some parts of the addon meant to be used for, please refer to the [Naming Convention](Naming-Convention) page.

## Using Addon

The addon is ready to be used as is. Has configured all services in application container and there is no need to develop
some other services or bridges.

> [!TIP]
Find fundamental details regarding the installation and configuration of this addon on the [Configuration](Configuration) page.

This addon is equipped with interactive console. With this console commands you could manage almost all addon features.

* **fb:virtual-thermostat-addon:install**: is used for addon installation and configuration. With interactive menu you could manage addon and device.

Addon console command could be triggered like this :nerd_face:

```shell
php bin/fb-console fb:virtual-addon:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.
