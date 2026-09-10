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

use IPub\SlimRouter\Routing;

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
    IPub\SlimRouter\Routing\Router:
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
    IPub\SlimRouter\Routing\Router:
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

use IPub\SlimRouter\Routing;

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
