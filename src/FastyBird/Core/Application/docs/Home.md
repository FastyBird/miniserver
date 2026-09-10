<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

This extension plays a vital role in the [FastyBird](https://www.fastybird.com) application by orchestrating the establishment of essential core services.
In addition to its primary function, it incorporates default extensions that contribute to the overall enhancement of the application's functionality,
ensuring a robust and versatile framework for various tasks and operations.

# About Library

This library has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Core\Application
  \Boot - Application bootstrap related services
  \Caching - Cache related services
  \Helpers - Useful helpers for working with database, logger etc.
  \ObjectMapper - Object mapper custom rules
```

All services, helpers, etc. are written to be self-descriptive :wink:.

## Using Library

This extension is configured via **env** variables or via Neon **parameters** or their combination.

Environment variables are used to define all necessary folders for application to run:

```
FB_APP_DIR - is application root dir. Default value is folder where is composer.json located

FB_RESOURCES_DIR - is dir for additional resources. Default value is FB_APP_DIR . '/resources'

FB_TEMP_DIR - is dir for storing temporary data like filecache, generated DI etc. Default value is FB_APP_DIR . '/var/temp'

FB_LOGS_DIR - is dir for application logs or exceptions. Default value is FB_APP_DIR . '/var/logs'

FB_CONFIG_DIR - is dir where you sould place your custom configuration. Default value is FB_APP_DIR . '/config' 
```

> [!TIP]
You don't need to configure this environment variables. It is totally ok to use defined values. Folders will then be defined inside project root

PHP constants are generated from these environment variables, allowing their convenient use throughout the application. Moreover, the values associated
with FB_APP_DIR, FB_TEMP_DIR, and FB_LOGS_DIR are seamlessly integrated into the nenon configuration parser, facilitating their utilization in the
configuration of various services.

```neon
services: 
    -
        type: Your\Cool\Service
        arguments: [
            temp: %tempDir%/cache
            logs: %logsDir%/service.result.log
            root: %appDir%
        ]
```

### Overriding parameters

FastyBird application could be configured via `parameters` section in configuration neon file, but in case you don't want to
store you sensitive data in file you could use configuration via env variables.

Application will search for all env variables prefixed with `FB_APP_PARAMETER_` and create parameters array from them.
Also structuring parameters is supported, just use delimiter `_`:

```nenon
parameters:
    database:
        password: secretPass
```

is equivalent to:

```php
$_ENV[FB_APP_PARAMETER_DATABASE_PASSWORD] = 'secretPass';
```

### Custom application configuration

Application configuration is done via neon files and this files have to be places into **FB_CONFIG_DIR**.

Application will automatically load configuration files, all what you have to do is follow naming convention for neon
files:

```
common.neon - file for configuring extension, services, etc.

defaults.neon - file for placing all you parameters

local.neon - file for additional configuration or user specific
```

**common.neon** and **defaults.neon** should be versioned in you application repository, **local.neon** is meant to be
custom local file not be stored in repository.

## Create Application Container

Now when you have application configured you could move to next step, creating application entrypoint which will
loads DI and fire `Nette\Application\Bootstrap::run`

You can copy & paste it to your project, for example to `<app_root>/www/index.php`.

```php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(\FastyBird\Core\Application\Boot\Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run());
```

When a call `FastyBird\Core\Application\Boot\Bootstrap::boot()` is made, application will try to configure application and
prepare everything for building container.

## Default Extensions

This extension has preconfigured some useful extensions.

### Console Support

Is implemented via [contribute/console](https://github.com/contributte/console) package. Console entrypoint could be
found in composer `bin` folder and to run command just run:

```sh
vendor/bin/fb_console your:command
```

### Application Logger

Is implemented via [contribute/monolog](https://github.com/contributte/monolog) package. And is configured to log all
actions with severity errors and higher.

You could configure optional output of this logger to standard output or into rotating file:

```neon
parameters:
    logger:
        rotatingFile: your.filename.log
        stdOut: true
```

Logger severity level could be also configured via neon extension configuration:

```neon
parameters:
    logger:
        level: 400 # Levels: DEBUG = 100, INFO = 200, NOTICE = 250, WARNING = 300, ERROR = 400, CRITICAL = 500, ALERT = 550, EMERGENCY = 600
```

