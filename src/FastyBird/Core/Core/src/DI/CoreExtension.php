<?php declare(strict_types = 1);

namespace FastyBird\Core\DI;

use DateInvalidTimeZoneException;
use FastyBird\Core\Api\DI as ApiDI;
use FastyBird\Core\Boot;
use FastyBird\Core\Clock\DI as ClockDI;
use FastyBird\Core\Configuration;
use FastyBird\Core\Documents\DI as DocumentsDI;
use FastyBird\Core\EventLoop;
use FastyBird\Core\EventLoop\Subscribers as EventLoopSubscribers;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange\DI\ExchangeExtension;
use FastyBird\Core\Http\DI as HttpDI;
use FastyBird\Core\Logging\DI as LoggingDI;
use FastyBird\Core\Persistence\DI as PersistenceDI;
use FastyBird\Core\Phone\DI\PhoneExtension;
use FastyBird\Core\Presenters;
use FastyBird\Core\Security\DI as SecurityDI;
use FastyBird\Core\UI;
use FastyBird\Core\Values\DI\ValuesExtension;
use FastyBird\Core\WebSockets\DI as WebSocketsDI;
use Nette;
use Nette\Application;
use Nette\Bootstrap;
use Nette\DI as NetteDI;
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
 * order (Epic #459 section 3.1, census docs/superpowers/plans/2026-09-27-core-e4-di-census.md
 * section 5). Each child runs under its capability's name, fbCore.<capability>, so its
 * services are fbCore.<capability>.<role>; the root services are fbCore.eventLoop.*,
 * fbCore.ui.*, fbCore.cache.psr6, fbCore.eventDispatcher and fbCore.configuration (census
 * section 2). Each configured child declares the schema of its own fbCore section, and this
 * class hands it exactly that subtree (census section 3).
 */
final class CoreExtension extends NetteDI\CompilerExtension
{

	public const string NAME = 'fbCore';

	private readonly LoggingDI\LoggingExtension $logging;

	private readonly DocumentsDI\DocumentsExtension $documents;

	private readonly ExchangeExtension $exchange;

	private readonly PersistenceDI\PersistenceExtension $persistence;

	private readonly SecurityDI\SecurityExtension $security;

	private readonly ApiDI\ApiExtension $api;

	private readonly HttpDI\HttpExtension $http;

	private readonly WebSocketsDI\WebSocketsExtension $webSockets;

	private readonly ClockDI\ClockExtension $clock;

	private readonly ValuesExtension $values;

	private readonly PhoneExtension $phone;

	public function __construct()
	{
		$this->logging = new LoggingDI\LoggingExtension();
		$this->documents = new DocumentsDI\DocumentsExtension();
		$this->exchange = new ExchangeExtension();
		$this->persistence = new PersistenceDI\PersistenceExtension();
		$this->security = new SecurityDI\SecurityExtension();
		$this->api = new ApiDI\ApiExtension();
		$this->http = new HttpDI\HttpExtension();
		$this->webSockets = new WebSocketsDI\WebSocketsExtension();
		$this->clock = new ClockDI\ClockExtension();
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
			NetteDI\Compiler $compiler,
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
			'logging' => $this->logging->getConfigSchema(),
			'documents' => $this->documents->getConfigSchema(),
			'security' => $this->security->getConfigSchema(),
			'clock' => $this->clock->getConfigSchema(),
			'persistence' => $this->persistence->getConfigSchema(),
			'api' => $this->api->getConfigSchema(),
			'webSockets' => $this->webSockets->getConfigSchema(),
			'http' => $this->http->getConfigSchema(),
		]);
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws NetteDI\MissingServiceException
	 * @throws NetteDI\NotAllowedDuringResolvingException
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
		 * CHILD EXTENSIONS -- the compiler, each child under its capability's name
		 *
		 * The compiler records the class file of every registered extension as a container
		 * dependency, so that editing one rebuilds the container in debug mode. The children are
		 * not registered, so their files are added here.
		 */

		$childFiles = [];

		foreach ($this->children() as $capability => $child) {
			$child->setCompiler($this->compiler, $this->prefix($capability));

			$childFile = (new ReflectionClass($child))->getFileName();

			if ($childFile !== false) {
				$childFiles[] = $childFile;
			}
		}

		$this->compiler->addDependencies($childFiles);

		// Every configured child gets exactly its own subtree (census section 3). Exchange,
		// Values and Phone read no configuration.

		assert($configuration->logging instanceof LoggingDI\Config);
		$this->logging->setConfig($configuration->logging);

		assert($configuration->documents instanceof DocumentsDI\Config);
		$this->documents->setConfig($configuration->documents);

		assert($configuration->security instanceof SecurityDI\Config);
		$this->security->setConfig($configuration->security);

		assert($configuration->clock instanceof ClockDI\Config);
		$this->clock->setConfig($configuration->clock);

		assert($configuration->persistence instanceof PersistenceDI\Config);
		$this->persistence->setConfig($configuration->persistence);

		assert($configuration->api instanceof ApiDI\Config);
		$this->api->setConfig($configuration->api);

		assert($configuration->http instanceof HttpDI\Config);
		$this->http->setConfig($configuration->http);

		assert($configuration->webSockets instanceof WebSocketsDI\Config);
		$this->webSockets->setConfig($configuration->webSockets);

		/**
		 * LOGGING -- the handlers, the console subscriber and Sentry
		 */

		$this->logging->loadConfiguration();

		/**
		 * APPLICATION
		 */

		// ArrayAdapter takes an optional PSR-20 clock. Core's clock is confined to first-party
		// services (#655), so this keeps reading the engine's time, as before #641
		$builder->addDefinition($this->prefix('cache.psr6'), new NetteDI\Definitions\ServiceDefinition())
			->setType(ArrayAdapter::class)
			->setArgument('clock', null);

		$builder->addDefinition($this->prefix('eventLoop.wrapper'), new NetteDI\Definitions\ServiceDefinition())
			->setType(EventLoop\Wrapper::class);

		$builder->addDefinition($this->prefix('eventLoop.status'), new NetteDI\Definitions\ServiceDefinition())
			->setType(EventLoop\Status::class);

		/**
		 * PERSISTENCE -- the entity discriminator, the helpers and entity CRUD
		 */

		$this->persistence->loadConfiguration();

		/**
		 * APPLICATION, continued
		 */

		$builder->addDefinition(
			$this->prefix('eventLoop.subscribers.lifeCycle'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(EventLoopSubscribers\EventLoopLifeCycle::class);

		$builder->addDefinition($this->prefix('ui.templateFactory'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UI\TemplateFactory::class);

		$builder->addDefinition($this->prefix('ui.routes'), new NetteDI\Definitions\ServiceDefinition())
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
		 * CONFIGURATION (the security and persistence.timestampable settings, combined -- see
		 * SecurityExtension's schema for why this is registered unconditionally rather than only
		 * inside the `$configuration->security->token->signature !== ''` gate: the
		 * timestampable half of this data must always be available)
		 */

		$timestampable = $configuration->persistence->timestampable;

		$builder->addDefinition($this->prefix('configuration'))
			->setType(Configuration::class)
			->setArguments([
				'tokenIssuer' => $configuration->security->token->issuer,
				'tokenSignature' => $configuration->security->token->signature,
				'enableMiddleware' => $configuration->security->enable->middleware,
				'enableDoctrineMapping' => $configuration->security->enable->doctrine->mapping,
				'enableDoctrineModels' => $configuration->security->enable->doctrine->models,
				'enableNetteApplication' => $configuration->security->enable->nette->application,
				'applicationSignInUrl' => $configuration->security->application->signInUrl,
				'applicationHomeUrl' => $configuration->security->application->homeUrl,
				'lazyAssociation' => $timestampable->lazyAssociation,
				'autoMapField' => $timestampable->autoMapField,
				'dbFieldType' => $timestampable->dbFieldType,
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
	 * @throws NetteDI\MissingServiceException
	 * @throws NetteDI\NotAllowedDuringResolvingException
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
			$builder->addDefinition($this->prefix('eventDispatcher'))
				->setType(ComponentEventDispatcher\EventDispatcher::class);
		}

		/**
		 * PERSISTENCE -- entity CRUD removal without Doctrine ORM, and DATE_FORMAT
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
		assert($appRouterService instanceof NetteDI\Definitions\ServiceDefinition);
		$appRouterService->addSetup([Presenters\AppRouter::class, 'createRouter'], [$appRouterService]);

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof NetteDI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'App' => 'FastyBird\Core\Presenters\*Presenter',
			]]);
		}

		$templateFactoryService = $builder->getDefinitionByType(UI\TemplateFactory::class);
		assert($templateFactoryService instanceof NetteDI\Definitions\ServiceDefinition);
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
	 * Every child, keyed by the capability name it runs under (fbCore.<capability>)
	 *
	 * @return array<string, NetteDI\CompilerExtension>
	 */
	private function children(): array
	{
		return [
			'logging' => $this->logging,
			'persistence' => $this->persistence,
			'documents' => $this->documents,
			'exchange' => $this->exchange,
			'security' => $this->security,
			'values' => $this->values,
			'clock' => $this->clock,
			'api' => $this->api,
			'phone' => $this->phone,
			'webSockets' => $this->webSockets,
			'http' => $this->http,
		];
	}

}
