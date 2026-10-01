<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Clients\Drivers as ClientsDrivers;
use FastyBird\Core\WebSockets\Commands;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Helpers;
use FastyBird\Core\WebSockets\PushMessages;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Subscribers;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Core\WebSockets\Topics\Drivers as TopicsDrivers;
use FastyBird\Core\WebSockets\Wamp;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Override;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use React;
use stdClass;
use Symfony\Component\EventDispatcher as ComponentEventDispatcher;
use function assert;
use function interface_exists;
use function is_bool;
use function is_string;
use function krsort;
use function ksort;
use function ltrim;
use function sprintf;
use function strval;
use const SORT_NUMERIC;
use const SORT_STRING;

/**
 * WebSockets: the WebSocket server, WAMP routing and controllers, the client and topic storage,
 * the WS server command and its event bridges
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.webSockets and reads its
 * fbCore > webSockets section, so its services are fbCore.webSockets.*.
 *
 * It also registers Http\Routing\LinkGenerator, the WAMP link generator, which still lives in
 * the Http namespace (#460 moves it). Each storage-driver option names a service; its default
 * is the '@'-form of its memory driver's name. Any other service is wired as a reference, which
 * resolves when the container is completed, so a driver from the services: section, processed
 * after every extension's loadConfiguration(), works too (#565). The WS server command and the
 * client subscriber follow the HTTP server's in the
 * console and Symfony subscriber collections, so the composite registers them through a second
 * hook, loadServerProcess(), after the Http child (census section 5.3).
 */
final class WebSocketsExtension extends DI\CompilerExtension
{

	// Tags a service whose createRouter() contributes WAMP routes; beforeCompile() collects them into
	// the WAMP router. Module/Devices produces it. A tag renamed on one side only makes the routes
	// vanish without an error, so both sides use this constant.
	public const string ROUTES_TAG = 'fastybird.core.webSockets.routes';

	// Set by beforeCompile() on every WebSockets controller service, and looked up at runtime by
	// WebSockets\Controllers\ControllerFactory. Both sides use this constant for the same reason as
	// ROUTES_TAG.
	public const string CONTROLLER_TAG = 'fastybird.core.webSockets.controller';

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'storage' => Schema\Expect::structure([
				'clients' => Schema\Expect::structure([
					'driver' => Schema\Expect::string('@fbCore.webSockets.clients.driver.memory'),
					'ttl' => Schema\Expect::int(0),
				]),
				'topics' => Schema\Expect::structure([
					'driver' => Schema\Expect::string('@fbCore.webSockets.wamp.topics.driver.memory'),
					'ttl' => Schema\Expect::int(0),
				]),
			]),
			'server' => Schema\Expect::structure([
				'httpHost' => Schema\Expect::string('localhost'),
				'port' => Schema\Expect::int(8_080),
				'address' => Schema\Expect::string('0.0.0.0'),
				'secured' => Schema\Expect::structure([
					'enable' => Schema\Expect::bool(false),
					'sslSettings' => Schema\Expect::array([]),
				]),
			]),
			'routes' => Schema\Expect::array([]),
			'mapping' => Schema\Expect::array([]),
			'loop' => Schema\Expect::anyOf(
				Schema\Expect::string(),
				Schema\Expect::type(DI\Definitions\Statement::class),
			)->nullable(),
			'access' => Schema\Expect::structure([
				'keys' => Schema\Expect::string()->default(null),
				'origins' => Schema\Expect::string()->default(null),
			]),
		]);
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$controllerFactory = $builder->addDefinition($this->prefix('controllers.factory'))
			->setType(Controllers\IControllerFactory::class)
			->setFactory(Controllers\ControllerFactory::class);

		if ($configuration->mapping) {
			$controllerFactory->addSetup('setMapping', [$configuration->mapping]);
		}

		if ($builder->getByType(Clients\ClientProvider::class) === null) {
			$builder->addDefinition($this->prefix('clients.factory'))
				->setType(Clients\ClientFactory::class);
		}

		$builder->addDefinition($this->prefix('clients.driver.memory'))
			->setType(ClientsDrivers\InMemory::class);

		$clientsDriver = self::driverServiceName($configuration->storage->clients->driver);

		$clientsStorageDriver = $clientsDriver === $this->prefix('clients.driver.memory')
			? $builder->getDefinition($this->prefix('clients.driver.memory'))
			: new DI\Definitions\Reference($clientsDriver);

		$builder->addDefinition($this->prefix('clients.storage'))
			->setType(Clients\Storage::class)
			->setArguments(['ttl' => $configuration->storage->clients->ttl])
			->addSetup(
				'?->setStorageDriver(?)',
				['@' . $this->prefix('clients.storage'), $clientsStorageDriver],
			);

		$router = $builder->addDefinition($this->prefix('routing.router'))
			->setType(Wamp\WampRouter::class)
			->setFactory(Wamp\RouteList::class);

		foreach ($configuration->routes as $mask => $action) {
			$router->addSetup(
				sprintf('$service[] = new %s(?, ?);', Wamp\WampRoute::class),
				[$mask, $action],
			);
		}

		$builder->addDefinition($this->prefix('routing.generator'))
			->setType(Routing\LinkGenerator::class);

		$builder->addDefinition($this->prefix('server.wrapper'))
			->setType(Server\Wrapper::class);

		$flashApplication = $builder->addDefinition($this->prefix('server.flashWrapper'))
			->setType(Server\FlashWrapper::class);

		$flashApplication->addSetup('?->addAllowedAccess(?, \'80\')', [
			$flashApplication,
			$configuration->server->httpHost,
		]);
		$flashApplication->addSetup('?->addAllowedAccess(?, ?)', [
			$flashApplication,
			$configuration->server->httpHost,
			strval($configuration->server->port),
		]);

		$handlers = $builder->addDefinition($this->prefix('server.handlers'))
			->setType(Server\Handlers::class);

		if ($configuration->loop === null) {
			$loop = $builder->getByType(React\EventLoop\LoopInterface::class) === null
				? $builder->addDefinition($this->prefix('server.loop'))
				->setType(React\EventLoop\LoopInterface::class)
				->setFactory('React\EventLoop\Factory::create')
				: $builder->getDefinitionByType(React\EventLoop\LoopInterface::class);
		} else {
			$loop = is_string($configuration->loop)
				? new DI\Definitions\Statement($configuration->loop)
				: $configuration->loop;
		}

		$serverConfiguration = $builder->addDefinition($this->prefix('server.configuration'))
			->setType(Server\Configuration::class)
			->setArguments([
				'port' => $configuration->server->port,
				'address' => $configuration->server->address,
				'enableSSL' => $configuration->server->secured->enable,
				'sslSettings' => $configuration->server->secured->sslSettings,
			]);

		if ($builder->findByType(Log\LoggerInterface::class) === []) {
			$builder->addDefinition($this->prefix('server.logger'))
				->setType(Helpers\Console::class);
		}

		$builder->addDefinition($this->prefix('server.runtime'))
			->setType(Server\ServerRuntime::class)
			->setArguments([$handlers, $loop, $serverConfiguration]);

		$topicsDriver = self::driverServiceName($configuration->storage->topics->driver);

		$wampStorageDriver = $topicsDriver === $this->prefix('wamp.topics.driver.memory')
			? $builder->addDefinition($this->prefix('wamp.topics.driver.memory'))
			->setType(TopicsDrivers\InMemory::class)
			: new DI\Definitions\Reference($topicsDriver);

		$builder->addDefinition($this->prefix('wamp.topics.storage'))
			->setType(Topics\Storage::class)
			->setArguments(['ttl' => $configuration->storage->topics->ttl])
			->addSetup(
				'?->setStorageDriver(?)',
				['@' . $this->prefix('wamp.topics.storage'), $wampStorageDriver],
			);

		$builder->addDefinition($this->prefix('wamp.application'))
			->setType(Controllers\WampApplication::class);

		$builder->addDefinition($this->prefix('wamp.serializer'))
			->setType(Encoding\PushMessageSerializer::class);

		$builder->addDefinition($this->prefix('wamp.pushRegistry'))
			->setType(PushMessages\ConsumersRegistry::class);

		if ($builder->getByType(Clients\ClientProvider::class) !== null) {
			$builder->removeDefinition($builder->getByType(Clients\ClientProvider::class));
		}

		$builder->addDefinition($this->prefix('wamp.clientsFactory'))
			->setType(Clients\WampClientFactory::class);

		$builder->addDefinition($this->prefix('wamp.subscribers.onServerStart'))
			->setType(Subscribers\OnServerStartHandler::class);
	}

	/**
	 * The second half of loadConfiguration(), which the composite calls after the Http child:
	 * the WS server command and the client subscriber. The command's exchangeFactories are set
	 * in beforeCompile(), once every extension has registered its services (#566).
	 */
	public function loadServerProcess(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('commands.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\WsServer::class);

		$builder->addDefinition($this->prefix('subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Client::class)
			->setArgument('wsKeys', $configuration->access->keys)
			->setArgument('allowedOrigins', $configuration->access->origins);
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 * @throws Exceptions\Logic
	 */
	#[Override]
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * WEBSOCKETS -- router assembly, controller injection, event bridges
		 *
		 * The Application::class-presence guard below is preserved from WebSocketsExtension
		 * (added in PR #450, this session's ipub/websockets-wamp absorption) -- spec section 6
		 * calls this out by name as logic that must be preserved, not just relocated.
		 */

		$webSocketsRouter = $builder->getDefinition($this->prefix('routing.router'));
		$routersFactories = [];

		foreach ($builder->findByTag(self::ROUTES_TAG) as $tagRouterService => $tagPriority) {
			if (is_bool($tagPriority)) {
				$tagPriority = 100;
			}

			$routersFactories[$tagPriority][$tagRouterService] = $tagRouterService;
		}

		if ($routersFactories !== []) {
			krsort($routersFactories, SORT_NUMERIC);

			foreach ($routersFactories as $priority => $items) {
				ksort($items, SORT_STRING);
				$routersFactories[$priority] = $items;
			}

			foreach ($routersFactories as $items) {
				foreach ($items as $routerService) {
					$webSocketsRouter->addSetup('offsetSet', [
						null,
						new DI\Definitions\Statement(['@' . $routerService, 'createRouter']),
					]);
				}
			}
		}

		$allControllers = [];

		foreach ($builder->findByType(Controllers\RequestController::class) as $def) {
			$allControllers[$def->getType()] = $def;
		}

		foreach ($allControllers as $def) {
			// WebSockets\Controllers\ControllerFactory looks controllers up by this tag at runtime
			$def->addTag('nette.inject')->addTag(self::CONTROLLER_TAG, $def->getType());
		}

		if (
			interface_exists('Symfony\Component\EventDispatcher\EventDispatcherInterface')
			&& $builder->getByType(ComponentEventDispatcher\EventDispatcherInterface::class) !== null
		) {
			$dispatcher = $builder->getDefinition(
				$builder->getByType(ComponentEventDispatcher\EventDispatcherInterface::class),
			);

			// Preserved guard (PR #450): the base Application service is genuinely optional --
			// nothing in this extension registers it directly, only whichever extension embeds
			// the WAMP controller-dispatch framework does. Wiring events onto a service that was
			// never defined would be a hard MissingServiceException at compile time.
			$applicationType = $builder->getByType(Controllers\Application::class);

			if ($applicationType !== null) {
				$application = $builder->getDefinition($applicationType);
				assert($application instanceof DI\Definitions\ServiceDefinition);

				$application->addSetup('?->onOpen[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(Events\OpenEvent::class),
				]);
				$application->addSetup('?->onClose[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(Events\CloseEvent::class),
				]);
				$application->addSetup('?->onMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(
						Events\MessageEvent::class,
					),
				]);
				$application->addSetup('?->onError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(Events\ErrorEvent::class),
				]);
			}

			$server = $builder->getDefinition($builder->getByType(Server\ServerRuntime::class));
			assert($server instanceof DI\Definitions\ServiceDefinition);
			$server->addSetup('?->onCreate[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(Events\CreateEvent::class),
			]);
			$server->addSetup('?->onStart[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(Events\StartEvent::class),
			]);
			$server->addSetup('?->onStop[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(Events\StopEvent::class),
			]);

			$serverWrapper = $builder->getDefinition($builder->getByType(Server\Wrapper::class));
			assert($serverWrapper instanceof DI\Definitions\ServiceDefinition);
			$serverWrapper->addSetup('?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(
					Events\ClientConnectEvent::class,
				),
			]);
			$serverWrapper->addSetup(
				'?->onClientDisconnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
				[
					'@self', $dispatcher, new PhpGenerator\Literal(Events\ClientDisconnectEvent::class),
				],
			);
			$serverWrapper->addSetup('?->onClientError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(Events\ClientErrorEvent::class),
			]);
			$serverWrapper->addSetup('?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(
					Events\IncommingMessageEvent::class,
				),
			]);
			$serverWrapper->addSetup(
				'?->onAfterIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
				[
					'@self', $dispatcher, new PhpGenerator\Literal(Events\AfterIncommingMessageEvent::class),
				],
			);

			// WAMP's own event bridge -- WampApplication is unconditionally registered by this
			// extension (unlike base Application above), so no presence guard is needed here;
			// preserved from WebSocketsWAMPExtension::beforeCompile().
			$wampApplication = $builder->getDefinition(
				$builder->getByType(Controllers\WampApplication::class),
			);
			assert($wampApplication instanceof DI\Definitions\ServiceDefinition);
			$wampApplication->addSetup('?->onPush[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(Events\PushEvent::class),
			]);
		}

		$pushRegistry = $builder->getDefinition(
			$builder->getByType(PushMessages\ConsumersRegistry::class),
		);

		foreach ($builder->findByType(PushMessages\IConsumer::class) as $consumer) {
			$pushRegistry->addSetup('?->addConsumer(?)', [$pushRegistry, $consumer]);
		}

		$wsServerServer = $builder->getDefinitionByType(Server\ServerRuntime::class);
		$wsServerServer->addSetup('$service->onStart[] = ?', [
			'@' . $this->prefix('wamp.subscribers.onServerStart'),
		]);

		// Collected here, not in loadServerProcess(): an exchange registered after fbCore, such
		// as RedisDb or RabbitMQ in config/local.neon, does not exist yet during
		// loadConfiguration() (#566)
		$serverCommand = $builder->getDefinition($this->prefix('commands.server'));
		assert($serverCommand instanceof DI\Definitions\ServiceDefinition);
		$serverCommand->setArgument('exchangeFactories', $builder->findByType(Exchange\Factory::class));

		/**
		 * WS SERVER PLUGIN -- events bridge (fails loudly if the event dispatcher is missing,
		 * preserved from WsServerExtension::beforeCompile())
		 */

		if ($builder->getByType(PsrEventDispatcher\EventDispatcherInterface::class) === null) {
			throw new Exceptions\Logic(sprintf(
				'Service of type "%s" is needed. Please register it.',
				PsrEventDispatcher\EventDispatcherInterface::class,
			));
		}

		$wsServerDispatcher = $builder->getDefinition(
			$builder->getByType(PsrEventDispatcher\EventDispatcherInterface::class),
		);
		$socketWrapperServiceName = $builder->getByType(Server\Wrapper::class);
		assert(is_string($socketWrapperServiceName));
		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup(
			'?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self', $wsServerDispatcher, new PhpGenerator\Literal(Events\ClientConnected::class),
			],
		);
		$socketWrapperService->addSetup(
			'?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self', $wsServerDispatcher, new PhpGenerator\Literal(Events\IncomingMessage::class),
			],
		);
	}

	/**
	 * The service a storage-driver option names: a reference, "@name" like the defaults, or a
	 * bare "name". NEON reads a quoted "@name" as a literal string, which nette/di escapes to
	 * "@@name"; the option can only name a service, so that form is read the same way.
	 */
	private static function driverServiceName(mixed $driver): string
	{
		// The schema declares both options as strings
		assert(is_string($driver));

		return ltrim($driver, '@');
	}

}
