<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Sonoff NS Panel Connector is
an extension of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
ecosystem that enables effortless integration  with [Sonoff NS Panels](https://sonoff.tech/product/central-control-panel/nspanel-pro/).

It provides users with a simple and user-friendly interface to connect FastyBird devices with **Sonoff NS Panel**
and also to connect NS Panel devices with [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things),
allowing easy control of the devices from the NS Panels screens and from FastyBird interface. This makes managing and monitoring your devices hassle-free.

# About Connector

This connector has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Connector\NsPanel
  \API - Services and helpers related to API - for managing data exchange validation and data parsing
  \Clients - Services which handle communication with NsPanel devices
  \Commands - Services used for user console interface
  \Controllers - HTTP api services for handling incomming requests
  \Entities - All entities used by connector
  \Helpers - Useful helpers for reading values, bulding entities etc.
  \Mapping - Services for validating NS panel data structure
  \Protocol - Services responsible for creating devices configuration for NS Panels
  \Queue - Services related to connector internal communication
  \Schemas - {JSON:API} schemas mapping for API requests
  \Services - Communication services factories
  \Servers - HTTP server related services
  \Translations - Connector translations
  \Writers - Services for handling request from other services
```

All services, helpers, etc. are written to be self-descriptive :wink:.

> [!TIP]
To better understand what some parts of the connector meant to be used for, please refer to the [Naming Convention](Naming-Convention) page.

## Using Connector

The connector is ready to be used as is. Has configured all services in application container and there is no need to develop
some other services or bridges.

> [!TIP]
Find fundamental details regarding the installation and configuration of this connector on the [Configuration](Configuration) page.

> [!TIP]
The connector features a built-in physical device discovery capability, and you can find detailed information about device
discovery on the dedicated [Discovery](Discovery) page.

This connector is equipped with interactive console. With this console commands you could manage almost all connector features.

* **fb:ns-panel-connector:install**: is used for connector installation and configuration. With interactive menu you could manage connector and devices.
* **fb:ns-panel-connector:discover**: is used for direct devices discover. This command will trigger actions which are responsible for devices discovery.
* **fb:ns-panel-connector:execute**: is used for connector execution. It is simple command that will trigger all services which are related to communication with NsPanel devices and services with other [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem services like state storage, or user interface communication.

Each console command could be triggered like this :nerd_face:

```shell
php bin/fb-console fb:ns-panel-connector:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

# Known Issues and Limitations

## Third-Party Devices

The **Sonoff NS Panel** support variety of devices categories but due to some bugs in their system only some of them could be configured.
So only available are listed in interfaces.

Some Capabilities have not necessary Protocols, but they have to be configured to be able to connect devices with the **Sonoff NS Panel**.
In that case you could configure them as not mapped and set fixed value.