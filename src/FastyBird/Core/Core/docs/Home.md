# FastyBird MiniServer Core

## Application bootstrap

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


## Exchange (pub/sub bus)

<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

# Quick start

When a service within your extension requires the publication of messages to the data exchange bus for other extensions,
a recommended approach is to implement the `FastyBird\Core\Exchange\Publisher\Publisher` interface. This allows seamless
integration with the data exchange bus.

Following this implementation, you can register your custom publisher as a service. This structured approach ensures that
your extension's services can efficiently communicate and share relevant information with other components in
the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem.

***

## Creating custom publisher

If some service of your extension have to publish messages to data exchange bus for other extensions, you could just
implement `FastyBird\Core\Exchange\Publisher\Publisher` interface and register your publisher as service

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Core\Exchange\Publisher\Publisher;
use FastyBird\Core\Application\Documents;
use FastyBird\Core\Values\Types;

class ModuleDataPublisher implements Publisher
{

    public function publish(
        Types\Sources\Module|Types\Sources\Plugin|Types\Sources\Connector $source,
        string $routingKey,
        Documents\Document|null $entity,
    ) : void {
        // Service logic here, e.g. publish message to RabbitMQ or Redis etc. 
    }

}
```

You could create as many publishers as you need. Publisher proxy then will collect all of them.

### Asynchronous publisher

As the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem utilizes
an asynchronous event-loop system within its services, it is recommended to implement asynchronous publishers. This ensures
that code processing remains unblocked. These publishers have to follow a Promise-based approach when publishing messages.

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Core\Exchange\Publisher\Async\Publisher;
use FastyBird\Core\Application\Documents;
use FastyBird\Core\Values\Types;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class ModuleDataPublisher implements Publisher
{

    /**
    * @return PromiseInterface<bool>
     */
    public function publish(
        Types\Sources\Module|Types\Sources\Plugin|Types\Sources\Connector $source,
        string $routingKey,
        Documents\Document|null $entity,
    ) : PromiseInterface {
        $deferred  = new Deferred();

        // Service logic here, e.g. publish message to RabbitMQ or Redis etc.
        
        return $deferred->promise(); 
    }

}
```

## Publishing message

In your code you could just import one publisher - proxy publisher.

```php
namespace Your\CoolApp\Actions;

use FastyBird\Core\Exchange\Publisher\Container;

class SomeHandler
{

    /** @var Container */
    private Container $publisher;

    public function __construct(
        Container $publisher
    ) {
        $this->publisher = $publisher;
    }

    public function updateSomething()
    {
        // Your interesting logic here...

        $this->publisher->publish(
            $origin,
            $routingKey,
            $entity,
        );
    }
}
```

And that is it, global publisher will call all your publishers and publish message to all your systems.

## Creating custom consumer

One part is done, message is published. Now have to be consumed.

If some service of your extension have is waiting for messages from data exchange bus from other extensions, you could just
implement `FastyBird\Core\Exchange\Consumer\Consumer` interface and register your consumer as service

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Core\Exchange\Consumers\Consumer;
use FastyBird\Core\Application\Documents;
use FastyBird\Core\Values\Types;

class DataConsumer implements Consumer
{

    public function consume(
        Types\Sources\Module|Types\Sources\Plugin|Types\Sources\Connector $source,
        string $routingKey,
        Documents\Document|null $entity,
    ) : void {
        // Do your data processing logic here 
    }

}
```

You could create as many consumers as you need. Consumer proxy then will collect all of them.

## Tools

<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

## HTTP server runtime

<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

# About Plugin

The purpose of this plugin is to create php based web server for serving and handling API request and responses.

This library has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Plugin\RedisDb
  \Commands - Console commands to run WS server
  \Events - Events which are triggered by plugin and other services
  \Middleware - Server basic middlewares
  \Subscribers - Plugin subscribers which are subscribed to main sockets library
```

All services, helpers, etc. are written to be self-descriptive :wink:.

## Using Plugin

The plugin is ready to be used as is. Has configured all services in application container and there is no need to develop
some other services or bridges.

This plugin is dependent on other extensions, and they have to be registered too

```neon
extensions:
    ...
    contributteConsole: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
    contributteEvents: Contributte\EventDispatcher\DI\EventDispatcherExtension
```

## Plugin Configuration

This plugin has some configuration options:

```neon
fbWebServerPlugin:
    static:
        publicRoot: /path/to/public/folder
        enabled: false
    server:
        address: 127.0.0.1
        port: 8000
        certificate: /path/to/your/certificate.pa
```

Where:

- `static -> publicRoot` is path to public static files and this files could be served by this webserver
- `static -> enabled` enable or disable serving static files support


- `server -> address` is address where is server listening for incoming requests
- `server -> port` is address port where is server listening for incoming requests
- `server -> certificate` is path to your private certificate to enable SSL communication

## Application routes

This plugin has router service. This service could be used to be injected in other services for registering routes.
Or in case you want to implement automatic routes registration, you could use service **decorator**

```php
namespace Your\CoolApp\Routing;

use FastyBird\Library\SlimRouter\Routing;

use Your\CoolApp\Controllers;

class Routes
{

    /** @var Controllers\ArticlesController */
    private Controllers\ArticlesController $articlesV1Controller;

    public function __construct(
        Controllers\ArticlesController $articlesV1Controller
    ) {
        $this->articlesV1Controller = $articlesV1Controller;
    }

    public function registerRoutes(Routing\IRouter $router): void
    {
        return $router->group('/v1', function (Routing\RouteCollector $group): void {
            $group->group('/articles', function (Routing\RouteCollector $group): void {
                $group->get('', [$this->articlesV1Controller, 'index']);
    
                $group->get('/{id}', [$this->articlesV1Controller, 'read']);
    
                $group->post('', [$this->articlesV1Controller, 'create']);
    
                $group->patch('/{id}', [$this->articlesV1Controller, 'update']);
    
                $group->delete('/{id}', [$this->articlesV1Controller, 'delete']);
    
                $group->get('/{id}/relationships/{relation}', [
                    $this->articlesV1Controller,
                    'readRelationship',
                ]);
            });
        });
    }

}
```

And in your configuration neon:

```neon
services:
    appRoutes:
        factory: Your\CoolApp\Routing\Routes

decorator:
    FastyBird\Library\SlimRouter\Routing\Router:
        setup:
            @appRoutes::registerRoutes
```

For more info how to write routes and controllers please
visit: [ipub/slim-router](https://github.com/iPublikuj/slim-router/blob/main/docs/index.md) package documentation

## Custom middleware

With middleware, you could modify incoming requests and also outgoing responses.

```php
namespace Your\CoolApp\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AccessControlMiddleware implements MiddlewareInterface
{

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Do you logic here e.g. modify request

        // With $handler call another middleware
        $response = $handler->handle($request);

        // Do another logic e.g. modify response

        return $response;
    }

}
```

Each middleware have to be registered as a service

```neon
services:
    accessControlMiddleware:
        factory: Your\CoolApp\AccessControlMiddleware
```

Registration of middlewares to router is done via decorator:

```neon
decorator: 
    FastyBird\Library\SlimRouter\Routing\Router:
        setup:
            - addMiddleware(@accessControlMiddleware)
```

And if you have more middlewares, you could define their execution order. First registered is executed as last.

This type of middleware will be used for each route. But there could be cases, where you want use your middleware for specific routes only.

```neon
services:
    - {factory: Your\CoolApp\AccessControlMiddleware}
```

Middleware will be registered as usual service and could be injected into router, where you could add it to specific route.

```php
namespace Your\CoolApp\Routing;

use FastyBird\Library\SlimRouter\Routing;

class Routes
{
    // ...

    /** @var AccessControlMiddleware */
    private AccessControlMiddleware $accessControlMiddleware;

    public function registerRoutes(Routing\IRouter $router): void
    {
        // ...

            $deleteRoute = $group->delete('/{id}', [$this->articlesV1Controller, 'delete']);
            $deleteRoute->addMiddleware($this->accessControlMiddleware);

        // ...
    }
}
```

For more info how to write middleware please
visit: [ipub/slim-router](https://github.com/iPublikuj/slim-router/blob/main/docs/index.md) package documentation

## Running server

This plugin has implemented command interface for running server. All you have to do is just run one command:

```sh
<app_root>/vendor/bin/fb-console fb:web-server:start
```

## What about Apache or Nginx?

If you have any reason to use classic web server like [Apache](https://www.apache.org) or [Nginx](https://www.nginx.com)
, this extension has solution for you.

Steps to achieve this way is almost same as in console version. You have to create an entrypoint which will loads DI and
fire `FastyBird\Plugin\WebServer\Application\Application::run`

You can copy & paste it to your project, for example to `<app_root>/www/index.php`.

```php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(Your\CoolApp\Application::boot()
    ->createContainer()
    ->getByType(FastyBird\Plugin\WebServer\Application\Application::class)
    ->run());
```

And as a last step, you have to configure you Apache or Nginx server to load your page from: `<app_root>/www/index.php`

# Tips

If you want to use [{JSON:API}](https://jsonapi.org/) for you api calls, you could
use [fastybird/json-api](https://github.com/FastyBird/json-api) package. This package brings you schemas factory for
your responses and document to entity hydrator

And last but not least, [fastybird/simple-auth](https://github.com/FastyBird/simple-auth). With this package you could
create basic token based authentication and authorization.

## WS server runtime

<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

# About Plugin

The purpose of this plugin is to create php based WS server for serving and handling sockets real time connections.

This library has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Plugin\RedisDb
  \Commands - Console commands to run WS server
  \Events - Events which are triggered by plugin and other services
  \Subscribers - Plugin subscribers which are subscribed to main sockets library
```

All services, helpers, etc. are written to be self-descriptive :wink:.

## Using Plugin

The plugin is ready to be used as is. Has configured all services in application container and there is no need to develop
some other services or bridges.

This plugin is dependent on other extensions, and they have to be registered too

```neon
extensions:
    ...
    contributteConsole: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
    ipubWebsockets: IPub\WebSockets\DI\WebSocketsExtension
    ipubWebsocketsWamp: IPub\WebSocketsWAMP\DI\WebSocketsWAMPExtension
```

## Plugin Configuration

This plugin has some configuration options:

```neon
fbWsServerPlugin:
    access:
        keys: f9657db3-b9e0-4a6d-a482-76a8099edbce
        origins: yourdomain.tld,service.yourdomain.tld
```

Where:

- `access -> keys` are comma separated access keys which will server validate on clients connections
- `access -> origins` are comma separated allowed domain names which will server validate on clients connections

## Running server

This plugin has implemented command interface for running server. All you have to do is just run one command:

```sh
<app_root>/vendor/bin/fb-console fb:ws-server:start
```

