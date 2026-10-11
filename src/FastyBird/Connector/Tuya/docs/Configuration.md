# Configuration

To use [Tuya](https://www.tuya.com) devices with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or through the console.

There are two types of connectors available for selection:

- **Local** - This connector uses the local network for communication.
- **Cloud** - This connector communicates with the [Tuya](https://www.tuya.com) cloud instance.

## Setting Up Tuya Devices

Before using the Tuya connector, you will need to pair your Tuya devices with the Tuya cloud platform and mobile app.
Refer to your device's manual for instructions on how to do this.

## Obtaining Tuya Access Credentials

In order to use the Tuya connector, you will need to obtain certain access credentials.
These can be obtained by following these steps:

### Creating a Tuya Developer Account

To get started, go to [iot.tuya.com](https://iot.tuya.com) and either create a new account or log in if you already have one. Note that this
account is different from the account you use for the Tuya mobile app.

![Login to Tuya cloud platform](_media/tuya_login.png)

> [!NOTE]
Tuya frequently updates their portal and the process for obtaining credentials may change.
If you have trouble following these instructions, please create an issue or pull request with screenshots so we can update them.

### Creating New Cloud Project

From side menu select cloud platform:

![Tuya platform menu](_media/tuya_select_cloud.png)

And now click on "Create Cloud Project" button:

![New project creation](_media/tuya_create_project_btn.png)

Fill in you new project details a do not forget to select all Data Centers you are using in your Tuya application:

![New project creation](_media/tuya_create_project.png)

Now you have to authorize basic Tuya services. This services enables OpenApi for you cloud project and this API
interface will be used by Tuya connector to get devices details.

![Project wizard](_media/tuya_project_wizard.png)

On the overview page you could find your **Access ID** and **Access Secret**. Note these credentials, you will use them later.

![Tuya project credentials](_media/tuya_credentials.png)

### Connecting your Tuya Application

Open **Devices** tab and then open **Link Tuya App Account** and click on **Add App Account**

![Account assign](_media/tuya_cloud_projects.png)

Scan given QR code with you Tuya application, and follow steps shown in you application. When application authorization
is finished, you will see your devices in the Devices list.

![Application code](_media/tuya_scan_code.png)

After your devices are linked with your Tuya cloud account you are able to get Tuya user identifier.

![User identifier](_media/tuya_cloud_user_id.png)

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:tuya-connector:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

This command is interactive and easy to operate.

The console will show you basic menu. To navigate in menu you could write value displayed in square brackets or you
could use arrows to select one of the options:

```
Tuya connector - installer
==========================

 ! [NOTE] This action will create|update|delete connector configuration

 What would you like to do? [Nothing]:
  [0] Create connector
  [1] Edit connector
  [2] Delete connector
  [3] Manage connector
  [4] List connectors
  [5] Nothing
```

### Create connector

If you choose to create a new connector, you will be asked to choose the mode in which the connector will communicate with the devices:

```
 In what mode should this connector communicate with Tuya devices? [Cloud server mode]:
  [0] Local network mode
  [1] Cloud server mode
 > 0
```

You will then be asked to provide a connector identifier and name:

```
 Provide connector identifier:
 > my-tuya
```

```
 Provide connector name:
 > My Tuya
```

In the following steps, you will need to input your **Access ID** and **Access Secret**, which you obtained previously.

```
 Provide cloud authentication Access ID:
 > q3ctsrwvcqdprx5kjtm7
```

```
 Provide cloud authentication Access Secret:
 > dc098fa182774e1ca292e76328f8beb6
```

```
 Provide which cloud data center you are using? [Central Europe]:
  [0] Central Europe
  [1] Western Europe
  [2] Western America
  [3] Eastern America
  [4] China
  [5] India
 > 0
```

> [!TIP]
It is necessary to chose correct data centre which you used during registration.

After providing the necessary information, your new [Tuya](https://www.tuya.com) connector will be ready for use.

```
 [OK] New connector "My Tuya" was successfully created
```

### Connectors and Devices management

With this console command you could manage all your connectors and their devices. Just use the main menu to navigate to proper action.

## Configuring the Connector with the FastyBird User Interface

You can also configure the [Tuya](https://www.tuya.com) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.
