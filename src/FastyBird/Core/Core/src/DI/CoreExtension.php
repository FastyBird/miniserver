<?php declare(strict_types = 1);

namespace FastyBird\Core\DI;

use Casbin;
use DateInvalidTimeZoneException;
use FastyBird\Core\Api\DI\ApiExtension;
use FastyBird\Core\Boot;
use FastyBird\Core\Clock\DI\ClockExtension;
use FastyBird\Core\Configuration;
use FastyBird\Core\Documents\DI\DocumentsExtension;
use FastyBird\Core\EventLoop;
use FastyBird\Core\EventLoop\Subscribers as EventLoopSubscribers;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange;
use FastyBird\Core\Exchange\DI\ExchangeExtension;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Commands as HttpCommands;
use FastyBird\Core\Http\Middleware as HttpMiddleware;
use FastyBird\Core\Http\Routing as HttpRouting;
use FastyBird\Core\Http\Server as HttpServer;
use FastyBird\Core\Http\Subscribers as HttpSubscribers;
use FastyBird\Core\Logging\DI\LoggingExtension;
use FastyBird\Core\Persistence\DI\PersistenceExtension;
use FastyBird\Core\Phone\DI\PhoneExtension;
use FastyBird\Core\Presenters;
use FastyBird\Core\Presenters\Events as PresentersEvents;
use FastyBird\Core\Security\Access;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Mapping\Driver as MappingDriver;
use FastyBird\Core\Security\Middleware as SecurityMiddleware;
use FastyBird\Core\Security\Models\Casbin as ModelsCasbin;
use FastyBird\Core\Security\Models\Policies;
use FastyBird\Core\Security\Models\Tokens;
use FastyBird\Core\Security\Services as SecurityServices;
use FastyBird\Core\Security\Subscribers as SecuritySubscribers;
use FastyBird\Core\UI;
use FastyBird\Core\Values\DI\ValuesExtension;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Clients\Drivers as ClientsDrivers;
use FastyBird\Core\WebSockets\Commands as WebSocketsCommands;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding as WebSocketsEncoding;
use FastyBird\Core\WebSockets\Events as WebSocketsEvents;
use FastyBird\Core\WebSockets\Helpers as WebSocketsHelpers;
use FastyBird\Core\WebSockets\PushMessages;
use FastyBird\Core\WebSockets\Server as WebSocketsServer;
use FastyBird\Core\WebSockets\Subscribers as WebSocketsSubscribers;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Core\WebSockets\Topics\Drivers as TopicsDrivers;
use FastyBird\Core\WebSockets\Wamp;
use Monolog;
use Nette;
use Nette\Application;
use Nette\Application as NetteApplication;
use Nette\Bootstrap;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use Override;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use React;
use ReflectionClass;
use stdClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher as ComponentEventDispatcher;
use Symfony\Contracts\EventDispatcher as ContractsEventDispatcher;
use function assert;
use function interface_exists;
use function is_bool;
use function is_file;
use function is_string;
use function krsort;
use function ksort;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;
use const SORT_NUMERIC;
use const SORT_STRING;

/**
 * FastyBird Core -- the composite DI extension
 *
 * The only Core extension registered with the compiler (as fbCore). It registers every
 * service Core provides: application bootstrapping, the exchange, authentication and
 * authorization, shared tooling, date/time handling, entity CRUD and timestamping, JSON:API,
 * phone number handling, and the WebSocket, WAMP and web servers. Some capabilities are
 * delegated to child extensions, the rest is still registered inline.
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

	// Tags a service whose createRouter() contributes WAMP routes; beforeCompile() below collects
	// them into the WAMP router. Module/Devices produces it. A tag renamed on one side only makes
	// the routes vanish without an error, so both sides use this constant.
	public const string TAG_WEBSOCKETS_ROUTES = 'ipub.websockets.routes';

	// Set by beforeCompile() below on every WebSockets controller service, and looked up at
	// runtime by WebSockets\Controllers\ControllerFactory. Both sides use this constant for the
	// same reason as TAG_WEBSOCKETS_ROUTES.
	public const string TAG_WEBSOCKETS_CONTROLLER = 'ipub.websockets.controller';

	private readonly LoggingExtension $logging;

	private readonly DocumentsExtension $documents;

	private readonly ExchangeExtension $exchange;

	private readonly PersistenceExtension $persistence;

	private readonly ApiExtension $api;

	private readonly ClockExtension $clock;

	private readonly ValuesExtension $values;

	private readonly PhoneExtension $phone;

	public function __construct()
	{
		$this->logging = new LoggingExtension();
		$this->documents = new DocumentsExtension();
		$this->exchange = new ExchangeExtension();
		$this->persistence = new PersistenceExtension();
		$this->api = new ApiExtension();
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
			'simpleAuth' => Schema\Expect::structure([
				// SimpleAuth used to be its own separate, opt-in Nette extension
				// (fbSimpleAuth/SimpleAuthExtension) that a container's own config chose to
				// register -- or not. fbCore is now the single universal extension every
				// container in the repo loads (production and every package's tests alike), so
				// there is no longer a way to simply not register SimpleAuth. An empty-string
				// default keeps container compilation possible for containers that never
				// configure it; the "SIMPLE AUTH" block in loadConfiguration() below is gated on
				// this same signature being non-empty, so an unconfigured signature never
				// reaches TokenBuilder/TokenValidator or gets used for real token signing -- it
				// simply means none of SimpleAuth's services are registered at all, matching
				// pre-merge behaviour for containers that never opted into fbSimpleAuth.
				'token' => Schema\Expect::structure([
					'issuer' => Schema\Expect::string(),
					'signature' => Schema\Expect::string(''),
				]),
				'enable' => Schema\Expect::structure([
					'middleware' => Schema\Expect::bool(false),
					'doctrine' => Schema\Expect::structure([
						'mapping' => Schema\Expect::bool(false),
						'models' => Schema\Expect::bool(false),
					]),
					'casbin' => Schema\Expect::structure([
						'database' => Schema\Expect::bool(false),
					]),
					'nette' => Schema\Expect::structure([
						'application' => Schema\Expect::bool(false),
					]),
				]),
				'application' => Schema\Expect::structure([
					'signInUrl' => Schema\Expect::string(),
					'homeUrl' => Schema\Expect::string('/'),
				]),
				'services' => Schema\Expect::structure([
					'identity' => Schema\Expect::bool(false),
				]),
				'casbin' => Schema\Expect::structure([
					'model' => Schema\Expect::string(
						// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
						__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'model.conf',
					),
					'policy' => Schema\Expect::string(),
				]),
			]),
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
			'httpServer' => Schema\Expect::structure([
				'static' => Schema\Expect::structure([
					'publicRoot' => Schema\Expect::string()->nullable(),
					'enabled' => Schema\Expect::bool(false),
				]),
				'server' => Schema\Expect::structure([
					'address' => Schema\Expect::string('127.0.0.1'),
					'port' => Schema\Expect::int(8_000),
					'certificate' => Schema\Expect::string()->nullable(),
				]),
				'cors' => Schema\Expect::structure([
					'enabled' => Schema\Expect::bool(false),
					'allow' => Schema\Expect::structure([
						'origin' => Schema\Expect::string('*'),
						'methods' => Schema\Expect::arrayOf('string')->default([
							'GET',
							'POST',
							'PATCH',
							'DELETE',
							'OPTIONS',
						]),
						'credentials' => Schema\Expect::bool(true),
						'headers' => Schema\Expect::arrayOf('string')->default([
							'Content-Type',
							'Authorization',
							'X-Requested-With',
						]),
					]),
				]),
			]),
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

		assert($configuration->dateTimeFactory instanceof stdClass);
		$this->clock->setConfig($configuration->dateTimeFactory);

		assert($configuration->doctrineTimestampable instanceof stdClass);
		$this->persistence->setConfig($configuration->doctrineTimestampable);

		assert($configuration->jsonApi instanceof stdClass);
		$this->api->setConfig($configuration->jsonApi);

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
		 * SIMPLE AUTH
		 */

		if ($configuration->simpleAuth->token->signature !== '') {
			$builder->addDefinition($this->prefix('simpleAuth.auth'), new DI\Definitions\ServiceDefinition())
				->setType(SecurityServices\Auth::class);

			$builder->addDefinition($this->prefix('simpleAuth.token.builder'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\TokenBuilder::class)
				->setArgument('tokenSignature', $configuration->simpleAuth->token->signature)
				->setArgument('tokenIssuer', $configuration->simpleAuth->token->issuer);

			$builder->addDefinition($this->prefix('simpleAuth.token.reader'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\TokenReader::class);

			$builder->addDefinition($this->prefix('simpleAuth.token.validator'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\TokenValidator::class)
				->setArgument('tokenSignature', $configuration->simpleAuth->token->signature)
				->setArgument('tokenIssuer', $configuration->simpleAuth->token->issuer);

			if ($configuration->simpleAuth->services->identity) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.security.identityFactory'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Identity\IdentityFactory::class);
			}

			$builder->addDefinition(
				$this->prefix('simpleAuth.security.userStorage'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Identity\UserStorage::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.annotationChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Access\AnnotationChecker::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.latteChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Access\LatteChecker::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.linkChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Access\LinkChecker::class);

			if ($configuration->simpleAuth->enable->casbin->database) {
				$adapter = $builder->addDefinition(
					$this->prefix('simpleAuth.casbin.adapter'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(ModelsCasbin\Adapter::class);

				// Adapter::__construct only stores the DBAL connection; every method that
				// actually queries it (loadPolicy, savePolicy, ...) runs later, on demand.
				// Autowiring still resolves the constructor argument eagerly, though, which
				// forces nettrineDbal.connections.default.connection to exist merely because
				// something -- transitively -- asked for an EnforcerFactory. In production
				// (Tracy debug bar + AccountsModule's UserPanel + nettrine/dbal's own Tracy
				// connection panel all present at once) that eager build closes a real cycle:
				// tracy.bar -> fbAccountsModule.security.userPanel -> security.user ->
				// this enforcerFactory -> this adapter -> nettrineDbal's connection, whose own
				// ConnectionPanel::initialize() setup autowires an optional Tracy\Bar argument
				// and calls back into tracy.bar while it is still being constructed. A PHP 8.4
				// lazy ghost defers the constructor (and therefore the connection lookup) until
				// something actually calls a method on the adapter, which happens outside that
				// call stack, breaking the cycle without touching nettrine/dbal or Tracy.
				$adapter->lazy = true;

				$builder->addDefinition(
					$this->prefix('simpleAuth.casbin.subscriber'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SecuritySubscribers\Policy::class);
			} else {
				$policyFile = $configuration->simpleAuth->casbin->policy;

				if (!is_string($policyFile) || !is_file($policyFile)) {
					throw new Exceptions\Logic('Casbin policy file is not configured');
				}

				$adapter = $builder->addDefinition(
					$this->prefix('simpleAuth.casbin.adapter'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Casbin\Persist\Adapters\FileAdapter::class)
					->setArguments(['filePath' => $policyFile]);
			}

			$modelFile = $configuration->simpleAuth->casbin->model;

			if (!is_string($modelFile) || !is_file($modelFile)) {
				throw new Exceptions\Logic('Casbin model file is not configured');
			}

			$builder->addDefinition(
				$this->prefix('simpleAuth.casbin.enforcerFactory'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Identity\EnforcerFactory::class)
				->setArguments(['modelFile' => $modelFile, 'adapter' => $adapter]);

			if ($configuration->simpleAuth->enable->middleware) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.middleware.access'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SecurityMiddleware\Authorization::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.middleware.user'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SecurityMiddleware\User::class);
			}

			if ($configuration->simpleAuth->enable->doctrine->mapping) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.driver'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(MappingDriver\Owner::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.subscriber'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SecuritySubscribers\User::class);
			}

			if ($configuration->simpleAuth->enable->doctrine->models) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.tokensRepository'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Tokens\Repository::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.tokensManager'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Tokens\Manager::class);
			}

			if ($configuration->simpleAuth->enable->casbin->database) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.policiesRepository'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Policies\Repository::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.policiesManager'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Policies\Manager::class);
			}

			if ($configuration->simpleAuth->enable->nette->application) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.nette.application'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SecuritySubscribers\Application::class);
			}
		}

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
		 * SIMPLE AUTH above for why this is registered unconditionally rather than only
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
		 * WEBSOCKETS (base + WAMP)
		 */

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
			->setType(HttpRouting\LinkGenerator::class);

		$builder->addDefinition($this->prefix('wsServer.server.wrapper'))
			->setType(WebSocketsServer\Wrapper::class);

		$flashApplication = $builder->addDefinition($this->prefix('wsServer.server.flashWrapper'))
			->setType(WebSocketsServer\FlashWrapper::class);

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
			->setType(WebSocketsServer\Handlers::class);

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
			->setType(WebSocketsServer\Configuration::class)
			->setArguments([
				'port' => $configuration->webSockets->server->port,
				'address' => $configuration->webSockets->server->address,
				'enableSSL' => $configuration->webSockets->server->secured->enable,
				'sslSettings' => $configuration->webSockets->server->secured->sslSettings,
			]);

		if ($builder->findByType(Log\LoggerInterface::class) === []) {
			$builder->addDefinition($this->prefix('wsServer.server.logger'))
				->setType(WebSocketsHelpers\Console::class);
		}

		$builder->addDefinition($this->prefix('wsServer.server.server'))
			->setType(WebSocketsServer\ServerRuntime::class)
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
			->setType(WebSocketsEncoding\PushMessageSerializer::class);

		$builder->addDefinition($this->prefix('webSockets.wamp.pushRegistry'))
			->setType(PushMessages\ConsumersRegistry::class);

		if ($builder->getByType(Clients\ClientProvider::class) !== null) {
			$builder->removeDefinition($builder->getByType(Clients\ClientProvider::class));
		}

		$builder->addDefinition($this->prefix('wsServer.wamp.clientsFactory'))
			->setType(Clients\WampClientFactory::class);

		$builder->addDefinition($this->prefix('wsServer.wamp.subscribers.onServerStart'))
			->setType(WebSocketsSubscribers\OnServerStartHandler::class);

		/**
		 * HTTP SERVER
		 */

		$builder->addDefinition(
			$this->prefix('httpServer.routing.responseFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Http\ServerResponseFactory::class);

		$builder->addDefinition($this->prefix('httpServer.routing.router'), new DI\Definitions\ServiceDefinition())
			->setType(HttpRouting\ServerRouter::class);

		$builder->addDefinition($this->prefix('httpServer.commands.server'), new DI\Definitions\ServiceDefinition())
			->setType(HttpCommands\HttpServer::class)
			->setArguments([
				'serverAddress' => $configuration->httpServer->server->address,
				'serverPort' => $configuration->httpServer->server->port,
				'serverCertificate' => $configuration->httpServer->server->certificate,
			]);

		$builder->addDefinition($this->prefix('httpServer.middlewares.cors'), new DI\Definitions\ServiceDefinition())
			->setType(HttpMiddleware\Cors::class)
			->setArguments([
				'enabled' => $configuration->httpServer->cors->enabled,
				'allowOrigin' => $configuration->httpServer->cors->allow->origin,
				'allowMethods' => $configuration->httpServer->cors->allow->methods,
				'allowCredentials' => $configuration->httpServer->cors->allow->credentials,
				'allowHeaders' => $configuration->httpServer->cors->allow->headers,
			]);

		$builder->addDefinition(
			$this->prefix('httpServer.middlewares.staticFiles'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(HttpMiddleware\StaticFiles::class)
			->setArgument('publicRoot', $configuration->httpServer->static->publicRoot)
			->setArgument('enabled', $configuration->httpServer->static->enabled);

		$builder->addDefinition($this->prefix('httpServer.middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(HttpMiddleware\Router::class);

		$builder->addDefinition($this->prefix('httpServer.application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServer\Application::class);

		$builder->addDefinition($this->prefix('httpServer.server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServer\Factory::class);

		$builder->addDefinition($this->prefix('httpServer.subscribers.server'), new DI\Definitions\ServiceDefinition())
			->setType(HttpSubscribers\Server::class);

		/**
		 * WS SERVER (Plugin/WsServer's own registrations)
		 */

		$builder->addDefinition($this->prefix('wsServer.commands.wsServer'), new DI\Definitions\ServiceDefinition())
			->setType(WebSocketsCommands\WsServer::class)
			->setArguments(['exchangeFactories' => $builder->findByType(Exchange\Factory::class)]);

		$builder->addDefinition($this->prefix('wsServer.subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(WebSocketsSubscribers\Client::class)
			->setArgument('wsKeys', $configuration->wsServer->access->keys)
			->setArgument('allowedOrigins', $configuration->wsServer->access->origins);
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
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * EVENT DISPATCHER -- default fallback
		 *
		 * Pre-merge, the WS server event bridge below (preserved from WsServerExtension, itself
		 * a separate opt-in extension) could unconditionally require a
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
		 * satisfies every lookup below, whichever of the two interfaces is asked for.
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
		 * SIMPLE AUTH -- user context fallback, Doctrine mapping, Nette Application event bridge
		 */

		$userContextServiceName = $builder->getByType(Identity\User::class);

		// Mirrors the signature !== '' gate around the "SIMPLE AUTH" block in
		// loadConfiguration() above: this fallback's constructor needs IUserStorage, which only
		// exists if that block ran and registered simpleAuth.security.userStorage. Without this
		// gate, containers that never configure SimpleAuth (signature === '') would still get an
		// unconditional fallback User service whose dependency was never registered, replacing
		// "signature is missing" with a confusing "IUserStorage not found" deep in DI resolution.
		if ($userContextServiceName === null && $configuration->simpleAuth->token->signature !== '') {
			$builder->addDefinition($this->prefix('simpleAuth.security.user'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\User::class);
		}

		if (
			$configuration->simpleAuth->enable->doctrine->models
			|| $configuration->simpleAuth->enable->casbin->database
		) {
			NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
				'default',
				'FastyBird\Core\Security\Entities',
				__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Security' . DIRECTORY_SEPARATOR . 'Entities',
			);
		}

		if ($configuration->simpleAuth->enable->nette->application) {
			if (
				$builder->getByType(ContractsEventDispatcher\EventDispatcherInterface::class) !== null
				&& $builder->getByType(NetteApplication\Application::class) !== null
			) {
				$dispatcher = $builder->getDefinition(
					$builder->getByType(ContractsEventDispatcher\EventDispatcherInterface::class),
				);
				$application = $builder->getDefinition($builder->getByType(NetteApplication\Application::class));
				assert($application instanceof DI\Definitions\ServiceDefinition);

				$application->addSetup('?->onRequest[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(PresentersEvents\PresenterRequest::class),
				]);
				$application->addSetup('?->onResponse[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(PresentersEvents\PresenterResponse::class),
				]);
			}
		}

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
		 *
		 * The Application::class-presence guard below is preserved from WebSocketsExtension
		 * (added in PR #450, this session's ipub/websockets-wamp absorption) -- spec section 6
		 * calls this out by name as logic that must be preserved, not just relocated.
		 */

		$webSocketsRouter = $builder->getDefinition($this->prefix('webSockets.routing.router'));
		$routersFactories = [];

		foreach ($builder->findByTag(self::TAG_WEBSOCKETS_ROUTES) as $tagRouterService => $tagPriority) {
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
			$def->addTag('nette.inject')->addTag(self::TAG_WEBSOCKETS_CONTROLLER, $def->getType());
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
					'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\OpenEvent::class),
				]);
				$application->addSetup('?->onClose[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\CloseEvent::class),
				]);
				$application->addSetup('?->onMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(
						WebSocketsEvents\MessageEvent::class,
					),
				]);
				$application->addSetup('?->onError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\ErrorEvent::class),
				]);
			}

			$server = $builder->getDefinition($builder->getByType(WebSocketsServer\ServerRuntime::class));
			assert($server instanceof DI\Definitions\ServiceDefinition);
			$server->addSetup('?->onCreate[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\CreateEvent::class),
			]);
			$server->addSetup('?->onStart[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\StartEvent::class),
			]);
			$server->addSetup('?->onStop[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\StopEvent::class),
			]);

			$serverWrapper = $builder->getDefinition($builder->getByType(WebSocketsServer\Wrapper::class));
			assert($serverWrapper instanceof DI\Definitions\ServiceDefinition);
			$serverWrapper->addSetup('?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(
					WebSocketsEvents\ClientConnectEvent::class,
				),
			]);
			$serverWrapper->addSetup(
				'?->onClientDisconnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
				[
					'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\ClientDisconnectEvent::class),
				],
			);
			$serverWrapper->addSetup('?->onClientError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\ClientErrorEvent::class),
			]);
			$serverWrapper->addSetup('?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(
					WebSocketsEvents\IncommingMessageEvent::class,
				),
			]);
			$serverWrapper->addSetup(
				'?->onAfterIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
				[
					'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\AfterIncommingMessageEvent::class),
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
				'@self', $dispatcher, new PhpGenerator\Literal(WebSocketsEvents\PushEvent::class),
			]);
		}

		$pushRegistry = $builder->getDefinition(
			$builder->getByType(PushMessages\ConsumersRegistry::class),
		);

		foreach ($builder->findByType(PushMessages\IConsumer::class) as $consumer) {
			$pushRegistry->addSetup('?->addConsumer(?)', [$pushRegistry, $consumer]);
		}

		$wsServerServer = $builder->getDefinitionByType(WebSocketsServer\ServerRuntime::class);
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
		$socketWrapperServiceName = $builder->getByType(WebSocketsServer\Wrapper::class);
		assert(is_string($socketWrapperServiceName));
		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup(
			'?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self', $wsServerDispatcher, new PhpGenerator\Literal(WebSocketsEvents\ClientConnected::class),
			],
		);
		$socketWrapperService->addSetup(
			'?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self', $wsServerDispatcher, new PhpGenerator\Literal(WebSocketsEvents\IncomingMessage::class),
			],
		);
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
			$this->values,
			$this->clock,
			$this->api,
			$this->phone,
		];
	}

}
