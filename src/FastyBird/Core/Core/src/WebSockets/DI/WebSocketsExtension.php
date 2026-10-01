<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI;

use FastyBird\Core\DI\CoreExtension;
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
use function sprintf;
use function strval;
use const SORT_NUMERIC;
use const SORT_STRING;

/**
 * WebSockets: the WebSocket server, WAMP routing and controllers, the client and topic storage,
 * the WS server command and its event bridges
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs under the composite's name, so its services are
 * fbCore.webSockets.* and fbCore.wsServer.*. It reads two sections, fbCore > webSockets and
 * fbCore > wsServer, so until the keys are renamed (#557) it is given the composite's whole
 * configuration, whose schema declares both.
 *
 * It also registers Http\Routing\LinkGenerator, the WAMP link generator, which still lives in
 * the Http namespace (#460 moves it). The storage-driver sentinels are kept as they are (D3,
 * #565). The WS server command and the client subscriber follow the HTTP server's in the
 * console and Symfony subscriber collections, so the composite registers them through a second
 * hook, loadServerProcess(), after the Http child (census section 5.3).
 */
final class WebSocketsExtension extends DI\CompilerExtension
{

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

		$controllerFactory = $builder->addDefinition($this->prefix('webSockets.controllers.factory'))
			->setType(Controllers\IControllerFactory::class)
			->setFactory(Controllers\ControllerFactory::class);

		if ($configuration->webSockets->mapping) {
			$controllerFactory->addSetup('setMapping', [$configuration->webSockets->mapping]);
		}

		if ($builder->getByType(Clients\ClientProvider::class) === null) {
			$builder->addDefinition($this->prefix('wsServer.clients.factory'))
				->setType(Clients\ClientFactory::class);
		}

		$builder->addDefinition($this->prefix('wsServer.clients.driver.memory'))
			->setType(ClientsDrivers\InMemory::class);

		$clientsStorageDriver = $configuration->webSockets->storage->clients->driver === '@wsServer.clients.driver.memory'
			? $builder->getDefinition($this->prefix('wsServer.clients.driver.memory'))
			: $builder->getDefinition($configuration->webSockets->storage->clients->driver);

		$builder->addDefinition($this->prefix('wsServer.clients.storage'))
			->setType(Clients\Storage::class)
			->setArguments(['ttl' => $configuration->webSockets->storage->clients->ttl])
			->addSetup(
				'?->setStorageDriver(?)',
				['@' . $this->prefix('wsServer.clients.storage'), $clientsStorageDriver],
			);

		$router = $builder->addDefinition($this->prefix('webSockets.routing.router'))
			->setType(Wamp\WampRouter::class)
			->setFactory(Wamp\RouteList::class);

		foreach ($configuration->webSockets->routes as $mask => $action) {
			$router->addSetup(
				sprintf('$service[] = new %s(?, ?);', Wamp\WampRoute::class),
				[$mask, $action],
			);
		}

		$builder->addDefinition($this->prefix('webSockets.routing.generator'))
			->setType(Routing\LinkGenerator::class);

		$builder->addDefinition($this->prefix('wsServer.server.wrapper'))
			->setType(Server\Wrapper::class);

		$flashApplication = $builder->addDefinition($this->prefix('wsServer.server.flashWrapper'))
			->setType(Server\FlashWrapper::class);

		$flashApplication->addSetup('?->addAllowedAccess(?, \'80\')', [
			$flashApplication,
			$configuration->webSockets->server->httpHost,
		]);
		$flashApplication->addSetup('?->addAllowedAccess(?, ?)', [
			$flashApplication,
			$configuration->webSockets->server->httpHost,
			strval($configuration->webSockets->server->port),
		]);

		$handlers = $builder->addDefinition($this->prefix('wsServer.server.handlers'))
			->setType(Server\Handlers::class);

		if ($configuration->webSockets->loop === null) {
			$loop = $builder->getByType(React\EventLoop\LoopInterface::class) === null
				? $builder->addDefinition($this->prefix('wsServer.server.loop'))
				->setType(React\EventLoop\LoopInterface::class)
				->setFactory('React\EventLoop\Factory::create')
				: $builder->getDefinitionByType(React\EventLoop\LoopInterface::class);
		} else {
			$loop = is_string($configuration->webSockets->loop)
				? new DI\Definitions\Statement($configuration->webSockets->loop)
				: $configuration->webSockets->loop;
		}

		$serverConfiguration = $builder->addDefinition($this->prefix('wsServer.server.configuration'))
			->setType(Server\Configuration::class)
			->setArguments([
				'port' => $configuration->webSockets->server->port,
				'address' => $configuration->webSockets->server->address,
				'enableSSL' => $configuration->webSockets->server->secured->enable,
				'sslSettings' => $configuration->webSockets->server->secured->sslSettings,
			]);

		if ($builder->findByType(Log\LoggerInterface::class) === []) {
			$builder->addDefinition($this->prefix('wsServer.server.logger'))
				->setType(Helpers\Console::class);
		}

		$builder->addDefinition($this->prefix('wsServer.server.server'))
			->setType(Server\ServerRuntime::class)
			->setArguments([$handlers, $loop, $serverConfiguration]);

		$wampStorageDriver = $configuration->webSockets->storage->topics->driver === '@wsServer.wamp.topics.driver.memory'
			? $builder->addDefinition($this->prefix('wsServer.wamp.topics.driver.memory'))
			->setType(TopicsDrivers\InMemory::class)
			: $builder->getDefinition($this->prefix('wsServer.wamp.topics.driver.memory'));

		$builder->addDefinition($this->prefix('wsServer.wamp.topics.storage'))
			->setType(Topics\Storage::class)
			->setArguments(['ttl' => $configuration->webSockets->storage->topics->ttl])
			->addSetup(
				'?->setStorageDriver(?)',
				['@' . $this->prefix('wsServer.wamp.topics.storage'), $wampStorageDriver],
			);

		$builder->addDefinition($this->prefix('webSockets.wamp.application'))
			->setType(Controllers\WampApplication::class);

		$builder->addDefinition($this->prefix('webSockets.wamp.serializer'))
			->setType(Encoding\PushMessageSerializer::class);

		$builder->addDefinition($this->prefix('webSockets.wamp.pushRegistry'))
			->setType(PushMessages\ConsumersRegistry::class);

		if ($builder->getByType(Clients\ClientProvider::class) !== null) {
			$builder->removeDefinition($builder->getByType(Clients\ClientProvider::class));
		}

		$builder->addDefinition($this->prefix('wsServer.wamp.clientsFactory'))
			->setType(Clients\WampClientFactory::class);

		$builder->addDefinition($this->prefix('wsServer.wamp.subscribers.onServerStart'))
			->setType(Subscribers\OnServerStartHandler::class);
	}

	/**
	 * The second half of loadConfiguration(), which the composite calls after the Http child:
	 * the WS server command and the client subscriber. The command's exchangeFactories are
	 * still resolved here, during loadConfiguration() (D4, #566).
	 */
	public function loadServerProcess(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('wsServer.commands.wsServer'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\WsServer::class)
			->setArguments(['exchangeFactories' => $builder->findByType(Exchange\Factory::class)]);

		$builder->addDefinition($this->prefix('wsServer.subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Client::class)
			->setArgument('wsKeys', $configuration->wsServer->access->keys)
			->setArgument('allowedOrigins', $configuration->wsServer->access->origins);
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

		$webSocketsRouter = $builder->getDefinition($this->prefix('webSockets.routing.router'));
		$routersFactories = [];

		foreach ($builder->findByTag(CoreExtension::TAG_WEBSOCKETS_ROUTES) as $tagRouterService => $tagPriority) {
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
			$def->addTag('nette.inject')->addTag(CoreExtension::TAG_WEBSOCKETS_CONTROLLER, $def->getType());
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
			'@' . $this->prefix('wsServer.wamp.subscribers.onServerStart'),
		]);

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

}
