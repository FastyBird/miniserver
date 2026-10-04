<?php declare(strict_types = 1);

/**
 * Boots the application exactly as public/index.php does -- Bootstrap::boot() with FB_APP_DIR
 * pointing at the repository root, so config/common.neon and every extension it registers
 * load -- runs ONE probe of Epic E5's characterization tests (#634) against it, and reports
 * what it saw as JSON on stdout. The probe is the first argument:
 *
 *   server-lifecycle        ServerRuntime create(), run() and stop(): every WebSockets event
 *                           and which module SocketsBridge consumers are enabled at each step
 *   jsonapi-cold            the JSON:API middleware, response builder and hydrators container,
 *                           each fetched first from a fresh container, resolving the schema
 *                           container they need
 *   wamp-module-routes      the modules' WAMP routes resolved through the WAMP router and the
 *                           controller factory
 *   wamp-links              the WAMP link generator, fetched by type, linking to the modules'
 *                           exchange controllers the way their SocketsBridge consumers do
 *
 * Run as a child process by the tests beside it; see EntityMappingTest for why the production
 * scope cannot be booted in-process.
 */

// The deprecation notices vendor emits on PHP 8.4 would otherwise be interleaved with the
// JSON this script writes to stdout. See tools/php.d/tests.ini.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

use FastyBird\Bridge\DevicesModuleUiModule\Consumers as DevicesModuleUiModuleConsumers;
use FastyBird\Core\Api\Encoding as ApiEncoding;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Api\Middleware;
use FastyBird\Core\Boot;
use FastyBird\Core\Constants;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\DI as WebSocketsDI;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Wamp;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Module\Ui\Consumers as UiConsumers;
use Neomerx\JsonApi\Contracts as JsonApiContracts;
use Nette\DI as NetteDI;
use Nette\Http;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use React\EventLoop;
use React\Http\Message\ServerRequest;
use React\Socket;
use Symfony\Component\EventDispatcher;

$report = static function (array $payload): never {
	echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), PHP_EOL;

	exit(0);
};

$boot = static function (): NetteDI\Container {
	$configurator = Boot\Bootstrap::boot();

	// Same override, same fixture and same reason as bootstrap-production-scope.php
	$configurator->addConfig([
		'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
	]);

	return $configurator->createContainer();
};

$short = static function (string $class): string {
	$position = strrpos($class, '\\');

	return $position === false ? $class : substr($class, $position + 1);
};

/**
 * The server lifecycle, as a timeline of strings: each step, with the module SocketsBridge
 * consumers that are enabled at that moment, in the exchange consumer container's order (which
 * is the order they were enabled in: enable() re-inserts a consumer at the end).
 */
$serverLifecycle = static function () use ($boot, $short): array {
	$container = $boot();

	$bridges = [
		DevicesConsumers\SocketsBridge::class => 'Devices',
		UiConsumers\SocketsBridge::class => 'Ui',
		DevicesModuleUiModuleConsumers\SocketsBridge::class => 'DevicesModuleUiModule',
	];

	$consumers = $container->getByType(ExchangeConsumers\Container::class);
	$storage = new ReflectionProperty(ExchangeConsumers\Container::class, 'consumers');

	$enabled = static function () use ($consumers, $storage, $bridges): string {
		$registered = $storage->getValue($consumers);
		assert($registered instanceof SplObjectStorage);

		$names = [];

		foreach ($registered as $consumer) {
			$info = $registered[$consumer];
			assert($info instanceof ExchangeConsumers\Info);

			if (isset($bridges[$consumer::class]) && $info->isEnabled()) {
				$names[] = $bridges[$consumer::class];
			}
		}

		return '[' . implode(', ', $names) . ']';
	};

	$timeline = [];

	$dispatcher = $container->getByType(EventDispatcher\EventDispatcherInterface::class);

	foreach ([
		Events\AfterIncommingMessageEvent::class,
		Events\ClientConnectEvent::class,
		Events\ClientConnected::class,
		Events\ClientDisconnectEvent::class,
		Events\ClientErrorEvent::class,
		Events\CloseEvent::class,
		Events\CreateEvent::class,
		Events\ErrorEvent::class,
		Events\IncomingMessage::class,
		Events\IncommingMessageEvent::class,
		Events\MessageEvent::class,
		Events\OpenEvent::class,
		Events\StartEvent::class,
		Events\StopEvent::class,
		Events\WsServerError::class,
		Events\WsServerStartup::class,
	] as $event) {
		$dispatcher->addListener(
			$event,
			static function (object $dispatched) use (&$timeline, $enabled, $short): void {
				$timeline[] = 'dispatched ' . $short($dispatched::class) . ' ' . $enabled();
			},
			PHP_INT_MAX,
		);
	}

	$server = $container->getByType(Server\ServerRuntime::class);

	// the loop the server runs, whatever the container calls it
	$loop = (new ReflectionProperty(Server\ServerRuntime::class, 'loop'))->getValue($server);
	assert($loop instanceof EventLoop\LoopInterface);

	$socket = new Socket\SocketServer('127.0.0.1:0', [], $loop);
	$flashSocket = new Socket\SocketServer('127.0.0.1:0', [], $loop);

	$timeline[] = 'before create ' . $enabled();

	$server->create($socket, $flashSocket);

	$timeline[] = 'after create ' . $enabled();

	$loop->futureTick(static function () use (&$timeline, $loop): void {
		$timeline[] = 'loop running';

		$loop->stop();
	});

	// never wait for ever, whatever else the start hooks schedule
	$guard = $loop->addTimer(10, static fn () => $loop->stop());

	$server->run();

	$loop->cancelTimer($guard);

	$timeline[] = 'after run';

	$server->stop();

	$timeline[] = 'after stop';

	$socket->close();
	$flashSocket->close();

	return $timeline;
};

/**
 * Each JSON:API service locator fetched FIRST from its own fresh container -- nothing resolved
 * before it -- and made to resolve the schema container it needs.
 */
$jsonApiCold = static function () use ($boot): array {
	$result = [];

	// what the hydrators container is asked about, read from a separate container so the cold
	// one below resolves nothing in advance
	$hydratorServices = [
		'fbAccountsModule.hydrators.accounts',
		'fbDevicesModule.hydrators.device.generic',
		'fbUiModule.hydrators.widgets.analogSensor',
	];

	$warm = $boot();
	$warmSchemas = $warm->getByType(ApiEncoding\SchemaContainer::class);
	$types = [];

	foreach ($hydratorServices as $name) {
		$hydrator = $warm->getService($name);
		assert($hydrator instanceof Hydrators\Hydrator);

		$types[$name] = $warmSchemas->getSchemaByClassName($hydrator->getEntityName())->getType();
	}

	// 1. the middleware turns a JSON:API error into the error document
	$container = $boot();
	$middleware = $container->getByType(Middleware\JsonApiMiddleware::class);
	$schemaContainers = $container->findByType(JsonApiContracts\Schema\SchemaContainerInterface::class);

	$response = $middleware->process(
		new ServerRequest('GET', 'http://localhost/api/v1/e5-probe'),
		new class implements RequestHandlerInterface {

			/**
			 * @throws ApiExceptions\JsonApiError
			 */
			public function handle(ServerRequestInterface $request): ResponseInterface
			{
				throw new ApiExceptions\JsonApiError(422, 'E5 probe title', 'E5 probe detail');
			}

		},
	);

	$result['middleware'] = [
		'schemaContainers' => $schemaContainers,
		'status' => $response->getStatusCode(),
		'contentType' => $response->getHeaderLine('Content-Type'),
		'body' => json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR),
		'schemaContainerCreated' => $container->isCreated($schemaContainers[0]),
	];

	// 2. the response builder encodes a document
	$container = $boot();
	$builder = $container->getByType(ApiEncoding\Builder::class);

	$response = $builder->build(
		new ServerRequest('GET', 'http://localhost/api/v1/e5-probe'),
		$container->getByType(ResponseFactoryInterface::class)->createResponse(),
		null,
	);

	$result['builder'] = [
		'status' => $response->getStatusCode(),
		'contentType' => $response->getHeaderLine('Content-Type'),
		'body' => json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR),
		'schemaContainerCreated' => $container->isCreated($schemaContainers[0]),
	];

	// 3. the hydrators container finds the hydrator for a document's resource type
	$container = $boot();
	$hydrators = $container->getByType(Hydrators\Container::class);
	$found = [];

	foreach ($types as $name => $type) {
		$hydrator = $hydrators->findHydrator(ApiEncoding\Document::create(json_encode([
			'data' => ['type' => $type, 'id' => '6f1e2d3c-4b5a-4987-8a6b-5c4d3e2f1a0b', 'attributes' => new stdClass()],
		], JSON_THROW_ON_ERROR)));

		$found[$name] = [
			'type' => $type,
			'hydrator' => $hydrator !== null ? $hydrator::class : null,
			'isTheService' => $hydrator === $container->getService($name),
		];
	}

	$result['hydrators'] = [
		'found' => $found,
		'schemaContainerCreated' => $container->isCreated($schemaContainers[0]),
	];

	return $result;
};

/**
 * Each module's WAMP exchange route, from the URL a client subscribes to, through the WAMP
 * router to the controller the factory creates.
 */
$wampModuleRoutes = static function () use ($boot): array {
	$container = $boot();
	$router = $container->getByType(Wamp\WampRouter::class);
	assert($router instanceof Wamp\RouteList);
	$factory = $container->getByType(Controllers\IControllerFactory::class);
	$tagged = $container->findByTag(WebSocketsDI\WebSocketsExtension::CONTROLLER_TAG);

	$masks = [];

	foreach ($router as $moduleRouter) {
		$list = [];

		foreach ($moduleRouter instanceof Wamp\RouteList ? $moduleRouter : [] as $route) {
			$list[] = $route instanceof Wamp\WampRoute ? $route->getMask() : get_debug_type($route);
		}

		$masks[] = $list;
	}

	$routes = [];

	foreach ([
		'/' . Constants::MODULE_DEVICES_PREFIX . '/v1/exchange',
		'/' . Constants::MODULE_UI_PREFIX . '/v1/exchange',
	] as $path) {
		$request = $router->match(new Handshake\Request(new Http\UrlScript('ws://localhost' . $path)));

		if ($request === null) {
			$routes[$path] = null;

			continue;
		}

		$name = $request->getControllerName();
		$class = $factory->getControllerClass($name);
		$controller = $factory->createController($name);

		$routes[$path] = [
			'controllerName' => $name,
			'parameters' => array_keys($request->getParameters()),
			'class' => $class,
			'created' => $controller::class,
			'tagged' => in_array($class, $tagged, true),
		];
	}

	return ['routers' => $masks, 'routes' => $routes];
};

/**
 * The link each module's SocketsBridge publishes exchange messages under, and what the generator
 * does with a destination no route takes.
 */
$wampLinks = static function () use ($boot): array {
	$generator = $boot()->getByType(Routing\LinkGenerator::class);

	$links = [];

	foreach (['DevicesModule:Exchange:', 'UiModule:Exchange:', 'E5Probe:Missing:'] as $destination) {
		try {
			$links[$destination] = $generator->link($destination);
		} catch (Throwable $ex) {
			$links[$destination] = $ex::class . ': ' . $ex->getMessage();
		}
	}

	return $links;
};

try {
	$probe = $argv[1] ?? '';

	$report(['error' => null, 'result' => match ($probe) {
		'server-lifecycle' => $serverLifecycle(),
		'jsonapi-cold' => $jsonApiCold(),
		'wamp-module-routes' => $wampModuleRoutes(),
		'wamp-links' => $wampLinks(),
		default => throw new InvalidArgumentException(sprintf('Unknown probe "%s"', $probe)),
	}]);
} catch (Throwable $ex) {
	$report([
		'error' => $ex::class . ': ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine(),
		'result' => null,
	]);
}
