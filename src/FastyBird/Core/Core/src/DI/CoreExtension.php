<?php declare(strict_types = 1);

namespace FastyBird\Core\DI;

use DateInvalidTimeZoneException;
use FastyBird\Core\Api\DI\ApiExtension;
use FastyBird\Core\Boot;
use FastyBird\Core\Clock\DI\ClockExtension;
use FastyBird\Core\Configuration;
use FastyBird\Core\Documents\DI\DocumentsExtension;
use FastyBird\Core\EventLoop;
use FastyBird\Core\EventLoop\Subscribers as EventLoopSubscribers;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange\DI\ExchangeExtension;
use FastyBird\Core\Http\DI\HttpExtension;
use FastyBird\Core\Logging\DI\LoggingExtension;
use FastyBird\Core\Persistence\DI\PersistenceExtension;
use FastyBird\Core\Phone\DI\PhoneExtension;
use FastyBird\Core\Presenters;
use FastyBird\Core\Security\DI\SecurityExtension;
use FastyBird\Core\UI;
use FastyBird\Core\Values\DI\ValuesExtension;
use FastyBird\Core\WebSockets\DI\WebSocketsExtension;
use Monolog;
use Nette;
use Nette\Application;
use Nette\Bootstrap;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Override;
use Psr\EventDispatcher as PsrEventDispatcher;
use ReflectionClass;
use stdClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher as ComponentEventDispatcher;
use function assert;
use function is_string;
use const DIRECTORY_SEPARATOR;

/**
 * FastyBird Core -- the composite DI extension
 *
 * The only Core extension registered with the compiler (as fbCore). Every capability is a
 * child extension -- Logging, Persistence, Documents, Exchange, Security, Values, Clock, Api,
 * Phone, WebSockets and Http -- and this class keeps only the composition and the root runtime:
 * the event loop, the Nette UI and route list, the presenter mapping, the PSR-6 array cache, the
 * event-dispatcher fallback and the Configuration service.
 *
 * nette/di cannot register an extension while the container is compiling, so the children
 * are not registered: this class owns them and forwards each lifecycle call to them at the
 * position the capability's code held in the inline extension, which keeps the definition
 * order. Each child runs under this extension's name, so its services keep their fbCore.*
 * names and its configuration stays at today's fbCore path (Epic #459 section 3.1, census
 * docs/superpowers/plans/2026-09-27-core-e4-di-census.md section 5).
 */
final class CoreExtension extends DI\CompilerExtension
{

	public const string NAME = 'fbCore';

	public const string DRIVER_TAG = 'fastybird.application.attribute.driver';

	public const string CONSUMER_STATE = 'consumer_state';

	public const string CONSUMER_ROUTING_KEY = 'consumer_routing_key';

	// Tags a service whose createRouter() contributes WAMP routes; WebSocketsExtension::beforeCompile()
	// collects them into the WAMP router. Module/Devices produces it. A tag renamed on one side only makes
	// the routes vanish without an error, so both sides use this constant.
	public const string TAG_WEBSOCKETS_ROUTES = 'ipub.websockets.routes';

	// Set by WebSocketsExtension::beforeCompile() on every WebSockets controller service, and looked up at
	// runtime by WebSockets\Controllers\ControllerFactory. Both sides use this constant for the
	// same reason as TAG_WEBSOCKETS_ROUTES.
	public const string TAG_WEBSOCKETS_CONTROLLER = 'ipub.websockets.controller';

	private readonly LoggingExtension $logging;

	private readonly DocumentsExtension $documents;

	private readonly ExchangeExtension $exchange;

	private readonly PersistenceExtension $persistence;

	private readonly SecurityExtension $security;

	private readonly ApiExtension $api;

	private readonly HttpExtension $http;

	private readonly WebSocketsExtension $webSockets;

	private readonly ClockExtension $clock;

	private readonly ValuesExtension $values;

	private readonly PhoneExtension $phone;

	public function __construct()
	{
		$this->logging = new LoggingExtension();
		$this->documents = new DocumentsExtension();
		$this->exchange = new ExchangeExtension();
		$this->persistence = new PersistenceExtension();
		$this->security = new SecurityExtension();
		$this->api = new ApiExtension();
		$this->http = new HttpExtension();
		$this->webSockets = new WebSocketsExtension();
		$this->clock = new ClockExtension();
		$this->values = new ValuesExtension();
		$this->phone = new PhoneExtension();
	}

	public static function register(
		Boot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	/**
	 * The compiler asks only the extensions registered with it for their initialization, and
	 * the children are not registered, so their bodies are appended to this extension's own
	 */
	#[Override]
	public function getInitialization(): PhpGenerator\Closure
	{
		$initialization = new PhpGenerator\Closure();
		$initialization->setBody(parent::getInitialization()->getBody());

		foreach ($this->children() as $child) {
			$initialization->setBody($initialization->getBody() . $child->getInitialization()->getBody());
		}

		return $initialization;
	}

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'application' => Schema\Expect::structure([
				'logging' => Schema\Expect::structure([
					'rotatingFile' => Schema\Expect::structure([
						'enabled' => Schema\Expect::bool(true),
						'level' => Schema\Expect::int(Monolog\Level::Info),
						'filename' => Schema\Expect::string('app.log'),
					]),
					'stdOut' => Schema\Expect::structure([
						'enabled' => Schema\Expect::bool(false),
						'level' => Schema\Expect::int(Monolog\Level::Info),
					]),
					'console' => Schema\Expect::structure([
						'enabled' => Schema\Expect::bool(false),
						'level' => Schema\Expect::int(Monolog\Level::Info),
					]),
				]),
				'documents' => $this->documents->getConfigSchema(),
			]),
			'simpleAuth' => $this->security->getConfigSchema(),
			'tools' => Schema\Expect::structure([
				'sentry' => Schema\Expect::structure([
					'dsn' => Schema\Expect::string()->nullable(),
					'level' => Schema\Expect::int(Monolog\Level::Warning),
				]),
			]),
			'dateTimeFactory' => $this->clock->getConfigSchema(),
			'doctrineTimestampable' => $this->persistence->getConfigSchema(),
			'jsonApi' => $this->api->getConfigSchema(),
			'webSockets' => Schema\Expect::structure([
				'storage' => Schema\Expect::structure([
					'clients' => Schema\Expect::structure([
						'driver' => Schema\Expect::string('@wsServer.clients.driver.memory'),
						'ttl' => Schema\Expect::int(0),
					]),
					'topics' => Schema\Expect::structure([
						'driver' => Schema\Expect::string('@wsServer.wamp.topics.driver.memory'),
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
			]),
			'httpServer' => $this->http->getConfigSchema(),
			'wsServer' => Schema\Expect::structure([
				'access' => Schema\Expect::structure([
					'keys' => Schema\Expect::string()->default(null),
					'origins' => Schema\Expect::string()->default(null),
				]),
			]),
		]);
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * CHILD EXTENSIONS -- the compiler, under this extension's name
		 *
		 * The compiler records the class file of every registered extension as a container
		 * dependency, so that editing one rebuilds the container in debug mode. The children are
		 * not registered, so their files are added here.
		 */

		$childFiles = [];

		foreach ($this->children() as $child) {
			$child->setCompiler($this->compiler, $this->name);

			$childFile = (new ReflectionClass($child))->getFileName();

			if ($childFile !== false) {
				$childFiles[] = $childFile;
			}
		}

		$this->compiler->addDependencies($childFiles);

		// Logging reads two sections until the keys are renamed (#557), so it gets the whole
		// configuration (census section 6)
		$this->logging->setConfig($configuration);

		assert($configuration->application instanceof stdClass);
		assert($configuration->application->documents instanceof stdClass);
		$this->documents->setConfig($configuration->application->documents);

		assert($configuration->simpleAuth instanceof stdClass);
		$this->security->setConfig($configuration->simpleAuth);

		assert($configuration->dateTimeFactory instanceof stdClass);
		$this->clock->setConfig($configuration->dateTimeFactory);

		assert($configuration->doctrineTimestampable instanceof stdClass);
		$this->persistence->setConfig($configuration->doctrineTimestampable);

		assert($configuration->jsonApi instanceof stdClass);
		$this->api->setConfig($configuration->jsonApi);

		assert($configuration->httpServer instanceof stdClass);
		$this->http->setConfig($configuration->httpServer);

		// WebSockets reads two sections until the keys are renamed (#557), so it gets the whole
		// configuration (census section 6)
		$this->webSockets->setConfig($configuration);

		/**
		 * LOGGING -- the handlers, the console subscriber and Sentry
		 */

		$this->logging->loadConfiguration();

		/**
		 * APPLICATION
		 */

		$builder->addDefinition($this->prefix('application.cache.psr6'), new DI\Definitions\ServiceDefinition())
			->setType(ArrayAdapter::class);

		$builder->addDefinition($this->prefix('application.eventLoop.wrapper'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Wrapper::class);

		$builder->addDefinition($this->prefix('application.eventLoop.status'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Status::class);

		/**
		 * PERSISTENCE -- the entity discriminator, the helpers and entity CRUD
		 */

		$this->persistence->loadConfiguration();

		/**
		 * APPLICATION, continued
		 */

		$builder->addDefinition(
			$this->prefix('application.subscribers.eventLoop'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(EventLoopSubscribers\EventLoopLifeCycle::class);

		$builder->addDefinition($this->prefix('application.ui.templateFactory'), new DI\Definitions\ServiceDefinition())
			->setType(UI\TemplateFactory::class);

		$builder->addDefinition($this->prefix('application.ui.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Nette\Application\Routers\RouteList::class);

		/**
		 * DOCUMENTS
		 */

		$this->documents->loadConfiguration();

		/**
		 * EXCHANGE
		 */

		$this->exchange->loadConfiguration();

		/**
		 * SECURITY
		 */

		$this->security->loadConfiguration();

		/**
		 * VALUES
		 */

		$this->values->loadConfiguration();

		/**
		 * DATE TIME FACTORY
		 */

		$this->clock->loadConfiguration();

		/**
		 * CONFIGURATION (SimpleAuth + DoctrineTimestampable settings, combined -- see
		 * SecurityExtension's schema for why this is registered unconditionally rather than only
		 * inside the `$configuration->simpleAuth->token->signature !== ''` gate: the
		 * DoctrineTimestampable half of this data must always be available)
		 */

		$builder->addDefinition($this->prefix('configuration'))
			->setType(Configuration::class)
			->setArguments([
				'tokenIssuer' => $configuration->simpleAuth->token->issuer,
				'tokenSignature' => $configuration->simpleAuth->token->signature,
				'enableMiddleware' => $configuration->simpleAuth->enable->middleware,
				'enableDoctrineMapping' => $configuration->simpleAuth->enable->doctrine->mapping,
				'enableDoctrineModels' => $configuration->simpleAuth->enable->doctrine->models,
				'enableNetteApplication' => $configuration->simpleAuth->enable->nette->application,
				'applicationSignInUrl' => $configuration->simpleAuth->application->signInUrl,
				'applicationHomeUrl' => $configuration->simpleAuth->application->homeUrl,
				'lazyAssociation' => $configuration->doctrineTimestampable->lazyAssociation,
				'autoMapField' => $configuration->doctrineTimestampable->autoMapField,
				'dbFieldType' => $configuration->doctrineTimestampable->dbFieldType,
			]);

		/**
		 * PERSISTENCE, continued -- timestampable and the schema subscriber
		 *
		 * The second Persistence hook: these subscribers follow Security's in definition order,
		 * which nettrine's EventPass turns into listener order (census section 5.3).
		 */

		$this->persistence->loadTimestampable();

		/**
		 * JSON:API
		 */

		$this->api->loadConfiguration();

		/**
		 * PHONE
		 */

		$this->phone->loadConfiguration();

		/**
		 * WEBSOCKETS (base + WAMP), LinkGenerator included
		 */

		$this->webSockets->loadConfiguration();

		/**
		 * HTTP SERVER
		 */

		$this->http->loadConfiguration();

		/**
		 * WEBSOCKETS, continued -- the WS server command and client subscriber
		 *
		 * The second WebSockets hook: the command follows the HTTP server's in the console
		 * collection, and the subscriber follows the HTTP server's in the Symfony one (census
		 * section 5.3).
		 */

		$this->webSockets->loadServerProcess();
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 * @throws Exceptions\Logic
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * EVENT DISPATCHER -- default fallback
		 *
		 * Pre-merge, the WS server event bridge (now WebSocketsExtension::beforeCompile(), which
		 * runs after this; preserved from WsServerExtension, itself a separate opt-in extension)
		 * could unconditionally require a
		 * Psr\EventDispatcher\EventDispatcherInterface because every app config that registered
		 * fbWsServerPlugin also registered contributteEvents (Contributte\EventDispatcher) --
		 * two independent, always-paired entries in the same extensions: list. fbCore is now the
		 * single universal extension every container in the repo loads, including single-package
		 * test containers that have no reason to also load contributteEvents, so that pairing no
		 * longer holds. Register a default here, but only if nothing has already provided one --
		 * production still wires contributteEvents itself, and registering a second
		 * EventDispatcherInterface-typed service unconditionally would reintroduce the exact
		 * ambiguous-autowiring failure this same class of bug already caused for the router and
		 * response factory. Symfony\Component\EventDispatcher\EventDispatcherInterface extends
		 * Symfony\Contracts\EventDispatcher\EventDispatcherInterface extends
		 * Psr\EventDispatcher\EventDispatcherInterface, so one concrete Symfony dispatcher
		 * satisfies every later lookup, whichever of the two interfaces is asked for.
		 */

		if ($builder->getByType(PsrEventDispatcher\EventDispatcherInterface::class) === null) {
			$builder->addDefinition($this->prefix('application.eventDispatcher'))
				->setType(ComponentEventDispatcher\EventDispatcher::class);
		}

		/**
		 * PERSISTENCE -- entity CRUD removal without Doctrine ORM, DATE_FORMAT, and the
		 * timestampable subscription on the entity manager
		 */

		$this->persistence->beforeCompile();

		/**
		 * LOGGING -- the Monolog handlers, rotating file and stdout, then Sentry
		 */

		$this->logging->beforeCompile();

		/**
		 * APPLICATION -- routes, UI
		 */

		$appRouterServiceName = $builder->getByType(Application\Routers\RouteList::class);
		assert(is_string($appRouterServiceName));
		$appRouterService = $builder->getDefinition($appRouterServiceName);
		assert($appRouterService instanceof DI\Definitions\ServiceDefinition);
		$appRouterService->addSetup([Presenters\AppRouter::class, 'createRouter'], [$appRouterService]);

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof DI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'App' => 'FastyBird\Core\Presenters\*Presenter',
			]]);
		}

		$templateFactoryService = $builder->getDefinitionByType(UI\TemplateFactory::class);
		assert($templateFactoryService instanceof DI\Definitions\ServiceDefinition);
		$templateFactoryService->addSetup('registerLayout', [
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
			. 'templates' . DIRECTORY_SEPARATOR . '@layout.latte',
		]);

		/**
		 * EXCHANGE -- consumer/publisher proxy assembly
		 */

		$this->exchange->beforeCompile();

		/**
		 * SECURITY -- user context fallback, Doctrine mapping, Nette Application event bridge
		 */

		$this->security->beforeCompile();

		/**
		 * PHONE -- its subscriber on the entity manager, after the Timestampable one that
		 * PersistenceExtension::beforeCompile() added above (D2, #564)
		 */

		$this->phone->beforeCompile();

		/**
		 * JSON:API -- schema/hydrator assembly
		 */

		$this->api->beforeCompile();

		/**
		 * WEBSOCKETS -- router assembly, controller injection, event bridges
		 */

		$this->webSockets->beforeCompile();
	}

	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		parent::afterCompile($class);

		$this->phone->afterCompile($class);
	}

	/**
	 * @return list<DI\CompilerExtension>
	 */
	private function children(): array
	{
		return [
			$this->logging,
			$this->persistence,
			$this->documents,
			$this->exchange,
			$this->security,
			$this->values,
			$this->clock,
			$this->api,
			$this->phone,
			$this->webSockets,
			$this->http,
		];
	}

}
