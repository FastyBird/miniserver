# Configuration

To connect to devices that use the [Zigbee2MQTT](https://www.zigbee2mqtt.io) service with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you must
set up at least one connector. You can configure the connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or
by using the console.

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:zigbee2mqtt-connector:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

This command is interactive and easy to operate.

The console will show you basic menu. To navigate in menu you could write value displayed in square brackets or you
could use arrows to select one of the options:

```
Zigbee2MQTT connector - installer
=================================

 ! [NOTE] This action will create|update|delete connector configuration

 What would you like to do? [Nothing]:
  [0] Create connector
  [1] Edit connector
  [2] Delete connector
  [3] Manage connector
  [4] List connectors
  [5] Nothing
 > 0
```

### Create connector

When opting to create a new connector, you'll be prompted to provide a connector identifier and name:

```
 Provide connector identifier:
 > zigbee2mqtt
```

```
 Provide connector name:
 > Zigbee2MQTT Integration
```

Next questions are related to connection to MQTT broker. If you are using local MQTT broker with default values, you cloud
use prefilled values.

```
 Provide MQTT server address [127.0.0.1]:
 > 
```

```
 Provide MQTT server port [1883]:
 > 
```

```
 Provide MQTT server secured port [8883]:
 > 
```

```
 Provide MQTT server username (leave blank if no username is required):
 > 
```

After providing the necessary information, your new [Zigbee2MQTT](https://www.zigbee2mqtt.io) connector will be ready for use.

```
 [OK] New connector "Zigbee2MQTT Integration" was successfully created
```

### Create bridge

According to naming convention, bridge device have to be created and configured with [Zigbee2MQTT](https://www.zigbee2mqtt.io) service.

After new connector is created you will be asked if you want to create new device:

```
 Would you like to configure connector bridge(s)? (yes/no) [yes]:
 > 
```

Or you could choose to manage connector bridges from the main menu.

Now you will be asked to provide some device details:

```
 Provide device name:
 > Zigbee2MQTT Bridge
```

And that's it! One bridge is ready to by executed, it will handle all hardware devices automatically.

```
 [OK] Bridge "Zigbee2MQTT Bridge" was successfully created.
```

## Configuring the Connector with the FastyBird User Interface

You can also configure the [Zigbee2MQTT](https://www.zigbee2mqtt.io) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information
on how to do this, please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [documentation](https://miniserver.fastybird.com/docs).

# Configuring Zigbee2MQTT Service

In order of proper cooperation between Zigbee2MQTT connector and [Zigbee2MQTT](https://www.zigbee2mqtt.io) service you have
to do basic configuration to this service.

This configuration is highly recommended.

> [!TIP]
Zigbee2MQTT service configuration file could be edited with your favorite editor, path to the file vary on where you have
installed your Zigbee2MQTT service instance: `/path/to/zigbee2mqtt/data/configuration.yaml`

```yaml
availability: true
advanced:
  legacy_availability_payload: false
device_options:
  retain: true
```

> [!TIP]
In case you have in your Zigbee2MQTT configuration file sections with same names, just add specific parts.

`availability: true` is used to enable reporting device and bridge status

`advanced -> legacy_availability_payload` is used to define that availability messages are in JSON format

`device_options -> retain` this option will set device state message to be retained. This is useful when connector is starting
communication, connector will then receive last known status of all devices.