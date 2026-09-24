<?php declare(strict_types = 1);

namespace FastyBird\Core\DI;

use Casbin;
use DateInvalidTimeZoneException;
use DateTimeZone;
use Doctrine;
use FastyBird\Core\Boot;
use FastyBird\Core\Clients as WsServerClients;
use FastyBird\Core\Commands as HttpServerCommands;
use FastyBird\Core\Commands as WsServerCommands;
use FastyBird\Core\Configuration;
use FastyBird\Core\Controllers as WebSocketsControllers;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Documents as ExchangeDocuments;
use FastyBird\Core\Encoding as JsonApiEncoding;
use FastyBird\Core\Encoding as WebSocketsEncoding;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Events as SimpleAuthEvents;
use FastyBird\Core\Events\AfterIncommingMessageEvent;
use FastyBird\Core\Events\ClientConnected;
use FastyBird\Core\Events\ClientConnectEvent;
use FastyBird\Core\Events\ClientDisconnectEvent;
use FastyBird\Core\Events\ClientErrorEvent;
use FastyBird\Core\Events\CloseEvent;
use FastyBird\Core\Events\CreateEvent;
use FastyBird\Core\Events\ErrorEvent;
use FastyBird\Core\Events\IncomingMessage;
use FastyBird\Core\Events\IncommingMessageEvent;
use FastyBird\Core\Events\MessageEvent;
use FastyBird\Core\Events\OpenEvent;
use FastyBird\Core\Events\PushEvent;
use FastyBird\Core\Events\StartEvent;
use FastyBird\Core\Events\StopEvent;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Helpers as DoctrineCrudHelpers;
use FastyBird\Core\Helpers as JsonApiHelpers;
use FastyBird\Core\Helpers as ToolsHelpers;
use FastyBird\Core\Helpers as WsServerHelpers;
use FastyBird\Core\Http as WebServerHttp;
use FastyBird\Core\Mapping as DoctrineCrudMapping;
use FastyBird\Core\Mapping as SimpleAuthMapping;
use FastyBird\Core\Mapping\DoctrineTimestampable\Driver\Timestampable;
use FastyBird\Core\Messaging as ExchangeMessaging;
use FastyBird\Core\Messaging as WebSocketsMessaging;
use FastyBird\Core\Middleware as SimpleAuthMiddleware;
use FastyBird\Core\Middleware as WebServerMiddleware;
use FastyBird\Core\Middleware\JsonApi\JsonApi;
use FastyBird\Core\Persistence as DoctrineCrudPersistence;
use FastyBird\Core\Persistence as JsonApiPersistence;
use FastyBird\Core\Routing;
use FastyBird\Core\Schemas as JsonApiSchemas;
use FastyBird\Core\Schemas as ToolsSchemas;
use FastyBird\Core\Security as SimpleAuthSecurity;
use FastyBird\Core\Server as HttpServerServer;
use FastyBird\Core\Server as WsServerServer;
use FastyBird\Core\Services as DateTimeFactoryServices;
use FastyBird\Core\Services as PhoneServices;
use FastyBird\Core\Services as SimpleAuthServices;
use FastyBird\Core\Subscribers;
use FastyBird\Core\Subscribers as ApplicationSubscribers;
use FastyBird\Core\Subscribers as DoctrineTimestampableSubscribers;
use FastyBird\Core\Subscribers as HttpServerSubscribers;
use FastyBird\Core\Subscribers as PhoneSubscribers;
use FastyBird\Core\Subscribers as SimpleAuthSubscribers;
use FastyBird\Core\Subscribers as WsServerSubscribers;
use FastyBird\Core\Topics\WsServer\Drivers\InMemory;
use FastyBird\Core\Topics\WsServer\Storage;
use FastyBird\Core\Types\Phone\Phone;
use FastyBird\Core\UI;
use FastyBird\Core\Utilities\Tools\DateTimeProvider;
use libphonenumber;
use Monolog;
use Nette;
use Nette\Application;
use Nette\Application as NetteApplication;
use Nette\Bootstrap;
use Nette\Caching;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Nettrine\Migrations as NettrineMigrations;
use Nettrine\ORM as NettrineORM;
use Override;
use Psr\EventDispatcher as WsServerEventDispatcher;
use Psr\Log;
use React;
use Sentry;
use stdClass;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher;
use Symfony\Contracts\EventDispatcher as SymfonyEventDispatcherContracts;
use function array_values;
use function assert;
use function class_alias;
use function class_exists;
use function getenv;
use function in_array;
use function interface_exists;
use function is_bool;
use function is_dir;
use function is_file;
use function is_string;
use function krsort;
use function ksort;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;
use const SORT_NUMERIC;
use const SORT_STRING;

if (!class_exists('Nette\PhpGenerator\Literal')) {
	class_alias('Nette\PhpGenerator\PhpLiteral', 'Nette\PhpGenerator\Literal');
}

/**
 * FastyBird Core -- consolidated DI extension
 *
 * Registers every service Core provides in one pass: application bootstrapping, the
 * exchange, authentication and authorization, shared tooling, date/time handling, entity
 * CRUD and timestamping, JSON:API, phone number handling, and the WebSocket, WAMP and web
 * servers. See docs/superpowers/specs/2026-09-20-core-consolidation-design.md section 6.
 */
final class CoreExtension extends DI\CompilerExtension
{

	public const string NAME = 'fbCore';

	public const string DRIVER_TAG = 'fastybird.application.attribute.driver';

	public const string CONSUMER_STATE = 'consumer_state';

	public const string CONSUMER_ROUTING_KEY = 'consumer_routing_key';

	// Wire-level tag string, not a namespace -- Module/Devices (not migrated by this plan)
	// still produces this exact string at src/FastyBird/Module/Devices/src/DI/DevicesExtension.php,
	// so the value must stay byte-for-byte what WebSocketsExtension used, not the
	// fastybird.core.* convention the rest of this file's own tags use.
	public const string TAG_WEBSOCKETS_ROUTES = 'ipub.websockets.routes';

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
				'documents' => Schema\Expect::structure([
					// No default previously -- ->required() forced every container that loads
					// fbCore (now literally every container in the repo, see below) to supply a
					// mapping even when it owns zero JSON:API documents. An empty map is a
					// perfectly valid "this package/container has none" answer.
					'mapping' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string())
						->default([]),
					'excludePaths' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string()),
				]),
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
			'dateTimeFactory' => Schema\Expect::structure([
				'timeZone' => Schema\Expect::string('UTC'),
				'system' => Schema\Expect::bool(true),
				'frozen' => Schema\Expect::anyOf(Schema\Expect::float(), Schema\Expect::mixed()),
			]),
			'doctrineTimestampable' => Schema\Expect::structure([
				'lazyAssociation' => Schema\Expect::bool(false),
				'autoMapField' => Schema\Expect::bool(true),
				'dbFieldType' => Schema\Expect::string('datetime_immutable'),
			]),
			'jsonApi' => Schema\Expect::structure([
				'meta' => Schema\Expect::structure([
					'author' => Schema\Expect::anyOf(Schema\Expect::string(), Schema\Expect::array())
						->default('FastyBird team'),
					'copyright' => Schema\Expect::string()->default(null)->nullable(),
				]),
			]),
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
		 * APPLICATION
		 */

		if ($configuration->application->logging->rotatingFile->enabled === true) {
			$builder->addDefinition(
				$this->prefix('application.logger.handler.rotatingFile'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\RotatingFileHandler::class)
				->setArguments([
					'filename' => FB_LOGS_DIR . DIRECTORY_SEPARATOR . $configuration->application->logging->rotatingFile->filename,
					'maxFiles' => 10,
					'level' => $configuration->application->logging->rotatingFile->level,
				]);
		}

		if ($configuration->application->logging->stdOut->enabled === true) {
			$builder->addDefinition(
				$this->prefix('application.logger.handler.stdOut'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\StreamHandler::class)
				->setArguments([
					'stream' => 'php://stdout',
					'level' => $configuration->application->logging->stdOut->level,
				]);
		}

		$consoleHandler = null;

		if ($configuration->application->logging->console->enabled) {
			$consoleHandler = $builder->addDefinition(
				$this->prefix('application.logger.handler.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SymfonyMonolog\Handler\ConsoleHandler::class);
		}

		$builder->addDefinition($this->prefix('application.cache.psr6'), new DI\Definitions\ServiceDefinition())
			->setType(ArrayAdapter::class);

		$builder->addDefinition($this->prefix('application.eventLoop.wrapper'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Application\Wrapper::class);

		$builder->addDefinition($this->prefix('application.eventLoop.status'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Application\Status::class);

		if ($configuration->application->logging->console->enabled) {
			$builder->addDefinition(
				$this->prefix('application.subscribers.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(ApplicationSubscribers\Application\Console::class)
				->setArguments([
					'handler' => $consoleHandler,
					'level' => $configuration->application->logging->console->level,
				]);
		}

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition(
				$this->prefix('application.subscribers.entityDiscriminator'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(ApplicationSubscribers\Application\EntityDiscriminator::class);
		}

		$builder->addDefinition(
			$this->prefix('application.subscribers.eventLoop'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(ApplicationSubscribers\Application\EventLoopLifeCycle::class);

		$builder->addDefinition($this->prefix('application.ui.templateFactory'), new DI\Definitions\ServiceDefinition())
			->setType(UI\Application\TemplateFactory::class);

		$builder->addDefinition($this->prefix('application.ui.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Nette\Application\Routers\RouteList::class);

		$metadataCache = $builder->addDefinition(
			$this->prefix('application.document.cache'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Caching\Cache::class)
			->setArguments(['namespace' => 'metadata_class_metadata'])
			->setAutowired(false);

		$builder->addDefinition('document.factory', new DI\Definitions\ServiceDefinition())
			->setType(ApplicationDocuments\DocumentFactory::class);

		$attributeDriver = $builder->addDefinition(
			'document.mapping.attributeDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(ApplicationDocuments\Mapping\Driver\AttributeDriver::class)
			->setArguments(['paths' => array_values($configuration->application->documents->mapping)])
			->addSetup('addExcludePaths', [$configuration->application->documents->excludePaths])
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriver = $builder->addDefinition(
			'document.mapping.mappingDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(ApplicationDocuments\Mapping\Driver\MappingDriverChain::class);

		$builder->addDefinition('document.mapping.classMetadataFactory', new DI\Definitions\ServiceDefinition())
			->setType(ApplicationDocuments\Mapping\ClassMetadataFactory::class)
			->setArguments(['driver' => $mappingDriver, 'cache' => $metadataCache]);

		foreach ($configuration->application->documents->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new Exceptions\InvalidState(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$mappingDriver->addSetup('addDriver', [$attributeDriver, $namespace]);
		}

		/**
		 * EXCHANGE
		 */

		$builder->addDefinition($this->prefix('exchange.consumer'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeMessaging\Exchange\Consumers\Container::class);

		$builder->addDefinition($this->prefix('exchange.publisher'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeMessaging\Exchange\Publisher\Container::class);

		$builder->addDefinition($this->prefix('exchange.publisher.async'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeMessaging\Exchange\Publisher\Async\Container::class);

		$builder->addDefinition($this->prefix('exchange.entityFactory'), new DI\Definitions\ServiceDefinition())
			->setType(ExchangeDocuments\RoutingDocumentFactory::class);

		/**
		 * SIMPLE AUTH
		 */

		if ($configuration->simpleAuth->token->signature !== '') {
			$builder->addDefinition($this->prefix('simpleAuth.auth'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthServices\SimpleAuth\Auth::class);

			$builder->addDefinition($this->prefix('simpleAuth.token.builder'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSecurity\SimpleAuth\TokenBuilder::class)
				->setArgument('tokenSignature', $configuration->simpleAuth->token->signature)
				->setArgument('tokenIssuer', $configuration->simpleAuth->token->issuer);

			$builder->addDefinition($this->prefix('simpleAuth.token.reader'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSecurity\SimpleAuth\TokenReader::class);

			$builder->addDefinition($this->prefix('simpleAuth.token.validator'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSecurity\SimpleAuth\TokenValidator::class)
				->setArgument('tokenSignature', $configuration->simpleAuth->token->signature)
				->setArgument('tokenIssuer', $configuration->simpleAuth->token->issuer);

			if ($configuration->simpleAuth->services->identity) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.security.identityFactory'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SimpleAuthSecurity\SimpleAuth\IdentityFactory::class);
			}

			$builder->addDefinition(
				$this->prefix('simpleAuth.security.userStorage'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SimpleAuthSecurity\SimpleAuth\UserStorage::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.annotationChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SimpleAuthSecurity\SimpleAuth\Access\AnnotationChecker::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.latteChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SimpleAuthSecurity\SimpleAuth\Access\LatteChecker::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.linkChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SimpleAuthSecurity\SimpleAuth\Access\LinkChecker::class);

			if ($configuration->simpleAuth->enable->casbin->database) {
				$adapter = $builder->addDefinition(
					$this->prefix('simpleAuth.casbin.adapter'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(DoctrineCrudPersistence\SimpleAuth\Models\Casbin\Adapter::class);

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
					->setType(SimpleAuthSubscribers\SimpleAuth\Policy::class);
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
				->setType(SimpleAuthSecurity\SimpleAuth\EnforcerFactory::class)
				->setArguments(['modelFile' => $modelFile, 'adapter' => $adapter]);

			if ($configuration->simpleAuth->enable->middleware) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.middleware.access'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SimpleAuthMiddleware\SimpleAuth\Authorization::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.middleware.user'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SimpleAuthMiddleware\SimpleAuth\User::class);
			}

			if ($configuration->simpleAuth->enable->doctrine->mapping) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.driver'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SimpleAuthMapping\SimpleAuth\Driver\Owner::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.subscriber'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SimpleAuthSubscribers\SimpleAuth\User::class);
			}

			if ($configuration->simpleAuth->enable->doctrine->models) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.tokensRepository'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(DoctrineCrudPersistence\SimpleAuth\Models\Tokens\Repository::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.tokensManager'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(DoctrineCrudPersistence\SimpleAuth\Models\Tokens\Manager::class);
			}

			if ($configuration->simpleAuth->enable->casbin->database) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.policiesRepository'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(DoctrineCrudPersistence\SimpleAuth\Models\Policies\Repository::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.policiesManager'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(DoctrineCrudPersistence\SimpleAuth\Models\Policies\Manager::class);
			}

			if ($configuration->simpleAuth->enable->nette->application) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.nette.application'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(SimpleAuthSubscribers\SimpleAuth\Application::class);
			}
		}

		/**
		 * TOOLS
		 */

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition($this->prefix('tools.helpers.database'), new DI\Definitions\ServiceDefinition())
				->setType(ToolsHelpers\Tools\Database::class);
		}

		$builder->addDefinition(
			$this->prefix('tools.utilities.doctrineDateProvider'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(DateTimeProvider::class);

		$builder->addDefinition($this->prefix('tools.schemas.validator'), new DI\Definitions\ServiceDefinition())
			->setType(ToolsSchemas\Tools\Validator::class);

		if (interface_exists('\Sentry\ClientInterface')) {
			$builder->addDefinition($this->prefix('tools.helpers.sentry'), new DI\Definitions\ServiceDefinition())
				->setType(ToolsHelpers\Tools\Sentry::class);
		}

		// Preserved from ToolsExtension::loadConfiguration() -- the DSN can come from the OS
		// environment directly (both $_ENV and getenv(), containers set it either way), and only
		// falls back to the NEON-configured value if neither is present. Dropping this fallback
		// would silently disable Sentry for every deployment that wires the DSN via environment
		// only, which is how the original packages -- and this repo's own docker/ setup -- do it.
		if (
			isset($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& is_string($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& $_ENV['FB_APP_PARAMETER__SENTRY_DSN'] !== ''
		) {
			$sentryDSN = $_ENV['FB_APP_PARAMETER__SENTRY_DSN'];
		} elseif (
			getenv('FB_APP_PARAMETER__SENTRY_DSN') !== false
			&& getenv('FB_APP_PARAMETER__SENTRY_DSN') !== ''
		) {
			$sentryDSN = getenv('FB_APP_PARAMETER__SENTRY_DSN');
		} elseif ($configuration->tools->sentry->dsn !== null) {
			$sentryDSN = $configuration->tools->sentry->dsn;
		} else {
			$sentryDSN = null;
		}

		if (is_string($sentryDSN) && $sentryDSN !== '') {
			$builder->addDefinition($this->prefix('tools.sentry.handler'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\Monolog\Handler::class)
				->setArgument('level', $configuration->tools->sentry->level);

			$sentryClientBuilderService = $builder->addDefinition(
				$this->prefix('tools.sentry.clientBuilder'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setFactory('Sentry\ClientBuilder::create')
				->setArguments([['dsn' => $sentryDSN]]);

			$builder->addDefinition($this->prefix('tools.sentry.client'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\ClientInterface::class)
				// @phpstan-ignore argument.type (Nette ServiceDefinition::setFactory() accepts a [service, method] callable array at runtime)
				->setFactory([$sentryClientBuilderService, 'getClient']);

			$builder->addDefinition($this->prefix('tools.sentry.hub'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\State\Hub::class);
		}

		/**
		 * DATE TIME FACTORY
		 */

		if (!in_array($configuration->dateTimeFactory->timeZone, DateTimeZone::listIdentifiers(), true)) {
			throw new Exceptions\InvalidArgument('Timezone have to be valid PHP timezone string');
		}

		if ($configuration->dateTimeFactory->system) {
			$builder->addDefinition(
				$this->prefix('dateTimeFactory.datetime.system'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(DateTimeFactoryServices\DateTimeFactory\SystemClock::class)
				->setArgument('timeZone', new DateTimeZone($configuration->dateTimeFactory->timeZone))
				->setAutowired($configuration->dateTimeFactory->frozen === null);
		}

		if ($configuration->dateTimeFactory->frozen !== null) {
			$builder->addDefinition(
				$this->prefix('dateTimeFactory.datetime.frozen'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(DateTimeFactoryServices\DateTimeFactory\FrozenClock::class)
				->setArguments([
					'timestamp' => $configuration->dateTimeFactory->frozen,
					'timeZone' => new DateTimeZone($configuration->dateTimeFactory->timeZone),
				]);
		}

		/**
		 * DOCTRINE CRUD
		 */

		$builder->addDefinition($this->prefix('doctrineCrud.entity.mapper'))
			->setType(DoctrineCrudMapping\DoctrineCrud\EntityMapper::class)
			->setAutowired(false);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.entity.creator'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\Create\IEntityCreator::class)
			->setAutowired(false)
			->getResultDefinition()
			->setType(DoctrineCrudPersistence\DoctrineCrud\Crud\Create\EntityCreator::class);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.entity.updater'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\Update\IEntityUpdater::class)
			->setAutowired(false)
			->getResultDefinition()
			->setFactory(DoctrineCrudPersistence\DoctrineCrud\Crud\Update\EntityUpdater::class);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.entity.deleter'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\Delete\IEntityDeleter::class)
			->setAutowired(false)
			->getResultDefinition()
			->setFactory(DoctrineCrudPersistence\DoctrineCrud\Crud\Delete\EntityDeleter::class);

		$builder->addFactoryDefinition($this->prefix('doctrineCrud.crud'))
			->setImplement(DoctrineCrudPersistence\DoctrineCrud\Crud\IEntityCrudFactory::class)
			->getResultDefinition()
			->setType(DoctrineCrudPersistence\DoctrineCrud\Crud\EntityCrud::class)
			->setArguments([
				new PhpGenerator\Literal('$entityName'),
				'@' . $this->prefix('doctrineCrud.entity.mapper'),
				'@' . $this->prefix('doctrineCrud.entity.creator'),
				'@' . $this->prefix('doctrineCrud.entity.updater'),
				'@' . $this->prefix('doctrineCrud.entity.deleter'),
			]);

		/**
		 * CONFIGURATION (SimpleAuth + DoctrineTimestampable settings, combined -- see
		 * SIMPLE AUTH above for why this is registered unconditionally rather than only
		 * inside the `$configuration->simpleAuth->token->signature !== ''` gate: the
		 * DoctrineTimestampable half of this data must always be available)
		 */

		$builder->addDefinition($this->prefix('configuration'))
			->setType(Configuration\Configuration::class)
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
		 * DOCTRINE TIMESTAMPABLE
		 */

		$builder->addDefinition($this->prefix('doctrineTimestampable.driver'))
			->setType(Timestampable::class);

		$builder->addDefinition($this->prefix('doctrineTimestampable.subscriber'))
			->setType(DoctrineTimestampableSubscribers\DoctrineTimestampable\TimestampableSubscriber::class);

		/**
		 * DOCTRINE MIGRATIONS
		 *
		 * Registered only where nettrineMigrations itself is -- isolated per-package unit tests
		 * boot only Core's own internal config, not the application's config/common.neon that
		 * declares that extension, so its Doctrine\Migrations\Metadata\Storage\
		 * TableMetadataStorageConfiguration service the subscriber is autowired against would
		 * otherwise never exist for them to compile against.
		 *
		 * This keys on the extension being registered, not on findByType() against that service:
		 * MigrationsExtension declares it with setFactory() and no setType(), so at this point in
		 * loadConfiguration() the definition carries no resolvable type yet and findByType() always
		 * returns [], which left the subscriber never registered in production (#515).
		 */

		if ($this->compiler->getExtensions(NettrineMigrations\DI\MigrationsExtension::class) !== []) {
			$builder->addDefinition($this->prefix('doctrineMigrations.subscriber'))
				->setType(Subscribers\DoctrineMigrations\SchemaSubscriber::class);
		}

		/**
		 * JSON:API
		 */

		$builder->addDefinition($this->prefix('jsonApi.builder'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApiEncoding\JsonApi\Builder::class)
			->setArgument('metaAuthor', $configuration->jsonApi->meta->author)
			->setArgument('metaCopyright', $configuration->jsonApi->meta->copyright);

		$builder->addDefinition($this->prefix('jsonApi.middlewares.jsonapi'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApi::class);

		$builder->addDefinition($this->prefix('jsonApi.hydrators.container'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApiPersistence\JsonApi\Hydrators\Container::class);

		$builder->addDefinition($this->prefix('jsonApi.schemas.container'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApiEncoding\JsonApi\SchemaContainer::class);

		if (class_exists('\IPub\DoctrineCrud\Mapping\Annotation\Crud')) {
			$builder->addDefinition($this->prefix('jsonApi.helpers.crudReader'), new DI\Definitions\ServiceDefinition())
				->setType(JsonApiHelpers\JsonApi\CrudReader::class);
		}

		/**
		 * PHONE
		 */

		$builder->addDefinition($this->prefix('phone.libphone.utils'))
			->setType(libphonenumber\PhoneNumberUtil::class)
			->setFactory('libphonenumber\PhoneNumberUtil::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.geoCoder'))
			->setType(libphonenumber\geocoding\PhoneNumberOfflineGeocoder::class)
			->setFactory('libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.shortNumber'))
			->setType(libphonenumber\ShortNumberInfo::class)
			->setFactory('libphonenumber\ShortNumberInfo::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.mapper.carrier'))
			->setType(libphonenumber\PhoneNumberToCarrierMapper::class)
			->setFactory('libphonenumber\PhoneNumberToCarrierMapper::getInstance');

		$builder->addDefinition($this->prefix('phone.libphone.mapper.timezone'))
			->setType(libphonenumber\PhoneNumberToTimeZonesMapper::class)
			->setFactory('libphonenumber\PhoneNumberToTimeZonesMapper::getInstance');

		$builder->addDefinition($this->prefix('phone.phone'))
			->setType(PhoneServices\Phone\Phone::class);

		$builder->addDefinition($this->prefix('phone.doctrinePhone.subscriber'))
			->setType(PhoneSubscribers\Phone\PhoneObjectSubscriber::class);

		/**
		 * WEBSOCKETS (base + WAMP)
		 */

		$controllerFactory = $builder->addDefinition($this->prefix('webSockets.controllers.factory'))
			->setType(WebSocketsControllers\WebSockets\Controller\IControllerFactory::class)
			->setFactory(WebSocketsControllers\WebSockets\Controller\ControllerFactory::class);

		if ($configuration->webSockets->mapping) {
			$controllerFactory->addSetup('setMapping', [$configuration->webSockets->mapping]);
		}

		if ($builder->getByType(WsServerClients\WsServer\IClientFactory::class) === null) {
			$builder->addDefinition($this->prefix('wsServer.clients.factory'))
				->setType(WsServerClients\WsServer\ClientFactory::class);
		}

		$builder->addDefinition($this->prefix('wsServer.clients.driver.memory'))
			->setType(WsServerClients\WsServer\Drivers\InMemory::class);

		$clientsStorageDriver = $configuration->webSockets->storage->clients->driver === '@wsServer.clients.driver.memory'
			? $builder->getDefinition($this->prefix('wsServer.clients.driver.memory'))
			: $builder->getDefinition($configuration->webSockets->storage->clients->driver);

		$builder->addDefinition($this->prefix('wsServer.clients.storage'))
			->setType(WsServerClients\WsServer\Storage::class)
			->setArguments(['ttl' => $configuration->webSockets->storage->clients->ttl])
			->addSetup(
				'?->setStorageDriver(?)',
				['@' . $this->prefix('wsServer.clients.storage'), $clientsStorageDriver],
			);

		$router = $builder->addDefinition($this->prefix('webSockets.routing.router'))
			->setType(Routing\IWampRouter::class)
			->setFactory(Routing\RouteList::class);

		foreach ($configuration->webSockets->routes as $mask => $action) {
			$router->addSetup(
				sprintf('$service[] = new %s(?, ?);', Routing\WampRoute::class),
				[$mask, $action],
			);
		}

		$builder->addDefinition($this->prefix('webSockets.routing.generator'))
			->setType(Routing\LinkGenerator::class);

		$builder->addDefinition($this->prefix('wsServer.server.wrapper'))
			->setType(WsServerServer\WsServer\Wrapper::class);

		$flashApplication = $builder->addDefinition($this->prefix('wsServer.server.flashWrapper'))
			->setType(WsServerServer\WsServer\FlashWrapper::class);

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
			->setType(WsServerServer\WsServer\Handlers::class);

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
			->setType(WsServerServer\WsServer\Configuration::class)
			->setArguments([
				'port' => $configuration->webSockets->server->port,
				'address' => $configuration->webSockets->server->address,
				'enableSSL' => $configuration->webSockets->server->secured->enable,
				'sslSettings' => $configuration->webSockets->server->secured->sslSettings,
			]);

		if ($builder->findByType(Log\LoggerInterface::class) === []) {
			$builder->addDefinition($this->prefix('wsServer.server.logger'))
				->setType(WsServerHelpers\WsServer\Console::class);
		}

		$builder->addDefinition($this->prefix('wsServer.server.server'))
			->setType(WsServerServer\WsServer\Server::class)
			->setArguments([$handlers, $loop, $serverConfiguration]);

		$wampStorageDriver = $configuration->webSockets->storage->topics->driver === '@wsServer.wamp.topics.driver.memory'
			? $builder->addDefinition($this->prefix('wsServer.wamp.topics.driver.memory'))
			->setType(InMemory::class)
			: $builder->getDefinition($this->prefix('wsServer.wamp.topics.driver.memory'));

		$builder->addDefinition($this->prefix('wsServer.wamp.topics.storage'))
			->setType(Storage::class)
			->setArguments(['ttl' => $configuration->webSockets->storage->topics->ttl])
			->addSetup(
				'?->setStorageDriver(?)',
				['@' . $this->prefix('wsServer.wamp.topics.storage'), $wampStorageDriver],
			);

		$builder->addDefinition($this->prefix('webSockets.wamp.application'))
			->setType(WebSocketsControllers\WebSockets\WampApplication::class);

		$builder->addDefinition($this->prefix('webSockets.wamp.serializer'))
			->setType(WebSocketsEncoding\WebSockets\PushMessageSerializer::class);

		$builder->addDefinition($this->prefix('webSockets.wamp.pushRegistry'))
			->setType(WebSocketsMessaging\WebSockets\PushMessages\ConsumersRegistry::class);

		if ($builder->getByType(WsServerClients\WsServer\IClientFactory::class) !== null) {
			$builder->removeDefinition($builder->getByType(WsServerClients\WsServer\IClientFactory::class));
		}

		$builder->addDefinition($this->prefix('wsServer.wamp.clientsFactory'))
			->setType(WsServerClients\WsServer\WampClientFactory::class);

		$builder->addDefinition($this->prefix('wsServer.wamp.subscribers.onServerStart'))
			->setType(WsServerSubscribers\WsServer\OnServerStartHandler::class);

		/**
		 * HTTP SERVER
		 */

		$builder->addDefinition(
			$this->prefix('httpServer.routing.responseFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(WebServerHttp\ServerResponseFactory::class);

		$builder->addDefinition($this->prefix('httpServer.routing.router'), new DI\Definitions\ServiceDefinition())
			->setType(Routing\ServerRouter::class);

		$builder->addDefinition($this->prefix('httpServer.commands.server'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerCommands\HttpServer::class)
			->setArguments([
				'serverAddress' => $configuration->httpServer->server->address,
				'serverPort' => $configuration->httpServer->server->port,
				'serverCertificate' => $configuration->httpServer->server->certificate,
			]);

		$builder->addDefinition($this->prefix('httpServer.middlewares.cors'), new DI\Definitions\ServiceDefinition())
			->setType(WebServerMiddleware\WebServer\Cors::class)
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
			->setType(WebServerMiddleware\WebServer\StaticFiles::class)
			->setArgument('publicRoot', $configuration->httpServer->static->publicRoot)
			->setArgument('enabled', $configuration->httpServer->static->enabled);

		$builder->addDefinition($this->prefix('httpServer.middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(WebServerMiddleware\WebServer\Router::class);

		$builder->addDefinition($this->prefix('httpServer.application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerServer\HttpServer\Application::class);

		$builder->addDefinition($this->prefix('httpServer.server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerServer\HttpServer\Factory::class);

		$builder->addDefinition($this->prefix('httpServer.subscribers.server'), new DI\Definitions\ServiceDefinition())
			->setType(HttpServerSubscribers\HttpServer\Server::class);

		/**
		 * WS SERVER (Plugin/WsServer's own registrations)
		 */

		$builder->addDefinition($this->prefix('wsServer.commands.wsServer'), new DI\Definitions\ServiceDefinition())
			->setType(WsServerCommands\WsServer::class)
			->setArguments(['exchangeFactories' => $builder->findByType(ExchangeMessaging\Exchange\Factory::class)]);

		$builder->addDefinition($this->prefix('wsServer.subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(WsServerSubscribers\WsServer\Client::class)
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

		if ($builder->getByType(WsServerEventDispatcher\EventDispatcherInterface::class) === null) {
			$builder->addDefinition($this->prefix('application.eventDispatcher'))
				->setType(EventDispatcher\EventDispatcher::class);
		}

		/**
		 * DOCTRINE CRUD -- entity CRUD services, removed when Doctrine ORM is absent
		 *
		 * loadConfiguration() unconditionally registers doctrineCrud.entity.{mapper,creator,
		 * updater,deleter} and doctrineCrud.crud: EntityMapper/EntityCreator/EntityUpdater all
		 * take a non-nullable Doctrine\Persistence\ManagerRegistry constructor argument, which
		 * only exists in containers that also register nettrineOrm/nettrineDbal. Several
		 * fbCore-using containers (RabbitMq, RedisDb, RedisDbCache among them) don't, so those
		 * five otherwise-unconditional definitions failed to compile at all. Whether
		 * ManagerRegistry ends up registered can depend on another extension's own
		 * loadConfiguration() -- order-dependent within that phase -- so this can only be
		 * decided reliably here, in beforeCompile(), after every extension's loadConfiguration()
		 * has already run.
		 */

		if ($builder->getByType(Doctrine\Persistence\ManagerRegistry::class) === null) {
			foreach ([
				'doctrineCrud.entity.mapper',
				'doctrineCrud.entity.creator',
				'doctrineCrud.entity.updater',
				'doctrineCrud.entity.deleter',
				'doctrineCrud.crud',
			] as $doctrineCrudServiceName) {
				$builder->removeDefinition($this->prefix($doctrineCrudServiceName));
			}
		}

		/**
		 * APPLICATION -- loggers, routes, UI
		 */

		if (
			$configuration->application->logging->rotatingFile->enabled === true
			|| $configuration->application->logging->stdOut->enabled === true
		) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));
			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			if ($configuration->application->logging->rotatingFile->enabled === true) {
				$monologLoggerService->addSetup('?->pushHandler(?)', [
					'@self',
					$builder->getDefinition($this->prefix('application.logger.handler.rotatingFile')),
				]);
			}

			if ($configuration->application->logging->stdOut->enabled === true) {
				$monologLoggerService->addSetup('?->pushHandler(?)', [
					'@self',
					$builder->getDefinition($this->prefix('application.logger.handler.stdOut')),
				]);
			}
		}

		// EntityDiscriminator used to be attached here by hand. nettrine/orm 0.10's EventPass
		// finds every service typed Doctrine\Common\EventSubscriber and registers it on its
		// ContainerEventManager itself, so doing it here too would subscribe it twice.

		$appRouterServiceName = $builder->getByType(Application\Routers\RouteList::class);
		assert(is_string($appRouterServiceName));
		$appRouterService = $builder->getDefinition($appRouterServiceName);
		assert($appRouterService instanceof DI\Definitions\ServiceDefinition);
		$appRouterService->addSetup([Routing\AppRouter::class, 'createRouter'], [$appRouterService]);

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof DI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'App' => 'FastyBird\Core\Presenters\Application\*Presenter',
			]]);
		}

		$templateFactoryService = $builder->getDefinitionByType(UI\Application\TemplateFactory::class);
		assert($templateFactoryService instanceof DI\Definitions\ServiceDefinition);
		$templateFactoryService->addSetup('registerLayout', [
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
			. 'templates' . DIRECTORY_SEPARATOR . '@layout.latte',
		]);

		/**
		 * EXCHANGE -- consumer/publisher proxy assembly
		 */

		$consumerProxyServiceName = $builder->getByType(ExchangeMessaging\Exchange\Consumers\Container::class);

		if ($consumerProxyServiceName !== null) {
			$consumerProxyService = $builder->getDefinition($consumerProxyServiceName);
			assert($consumerProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(ExchangeMessaging\Exchange\Consumers\Consumer::class) as $consumerService) {
				if (
					$consumerService->getType() !== ExchangeMessaging\Exchange\Consumers\Container::class
					&& ($consumerService->getAutowired() === true || !is_bool($consumerService->getAutowired()))
				) {
					$consumerService->setAutowired(false);
					$consumerStatus = $consumerService->getTag(self::CONSUMER_STATE);
					assert(is_bool($consumerStatus) || $consumerStatus === null);
					$consumerRoutingKey = $consumerService->getTag(self::CONSUMER_ROUTING_KEY);
					assert(is_string($consumerRoutingKey) || $consumerRoutingKey === null);

					$consumerProxyService->addSetup('?->register(?, ?, ?)', [
						'@self',
						$consumerService,
						$consumerRoutingKey ?? null,
						$consumerStatus ?? true,
					]);
				}
			}
		}

		$publisherProxyServiceName = $builder->getByType(ExchangeMessaging\Exchange\Publisher\Container::class);

		if ($publisherProxyServiceName !== null) {
			$publisherProxyService = $builder->getDefinition($publisherProxyServiceName);
			assert($publisherProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(ExchangeMessaging\Exchange\Publisher\Publisher::class) as $publisherService) {
				if (
					$publisherService->getType() !== ExchangeMessaging\Exchange\Publisher\Container::class
					&& ($publisherService->getAutowired() === true || !is_bool($publisherService->getAutowired()))
				) {
					$publisherService->setAutowired(false);
					$publisherProxyService->addSetup('?->register(?)', ['@self', $publisherService]);
				}
			}
		}

		$asyncPublisherProxyServiceName = $builder->getByType(
			ExchangeMessaging\Exchange\Publisher\Async\Container::class,
		);

		if ($asyncPublisherProxyServiceName !== null) {
			$asyncPublisherProxyService = $builder->getDefinition($asyncPublisherProxyServiceName);
			assert($asyncPublisherProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(
				ExchangeMessaging\Exchange\Publisher\Async\Publisher::class,
			) as $publisherService) {
				if (
					$publisherService->getType() !== ExchangeMessaging\Exchange\Publisher\Async\Container::class
					&& ($publisherService->getAutowired() === true || !is_bool($publisherService->getAutowired()))
				) {
					$publisherService->setAutowired(false);
					$asyncPublisherProxyService->addSetup('?->register(?)', ['@self', $publisherService]);
				}
			}
		}

		/**
		 * SIMPLE AUTH -- user context fallback, Doctrine mapping, Nette Application event bridge
		 */

		$userContextServiceName = $builder->getByType(SimpleAuthSecurity\SimpleAuth\User::class);

		// Mirrors the signature !== '' gate around the "SIMPLE AUTH" block in
		// loadConfiguration() above: this fallback's constructor needs IUserStorage, which only
		// exists if that block ran and registered simpleAuth.security.userStorage. Without this
		// gate, containers that never configure SimpleAuth (signature === '') would still get an
		// unconditional fallback User service whose dependency was never registered, replacing
		// "signature is missing" with a confusing "IUserStorage not found" deep in DI resolution.
		if ($userContextServiceName === null && $configuration->simpleAuth->token->signature !== '') {
			$builder->addDefinition($this->prefix('simpleAuth.security.user'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuthSecurity\SimpleAuth\User::class);
		}

		if (
			$configuration->simpleAuth->enable->doctrine->models
			|| $configuration->simpleAuth->enable->casbin->database
		) {
			NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
				'default',
				'FastyBird\Core\Entities\SimpleAuth',
				__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR . 'SimpleAuth',
			);
		}

		if ($configuration->simpleAuth->enable->nette->application) {
			if (
				$builder->getByType(SymfonyEventDispatcherContracts\EventDispatcherInterface::class) !== null
				&& $builder->getByType(NetteApplication\Application::class) !== null
			) {
				$dispatcher = $builder->getDefinition(
					$builder->getByType(SymfonyEventDispatcherContracts\EventDispatcherInterface::class),
				);
				$application = $builder->getDefinition($builder->getByType(NetteApplication\Application::class));
				assert($application instanceof DI\Definitions\ServiceDefinition);

				$application->addSetup('?->onRequest[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(SimpleAuthEvents\PresenterRequest::class),
				]);
				$application->addSetup('?->onResponse[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(SimpleAuthEvents\PresenterResponse::class),
				]);
			}
		}

		/**
		 * TOOLS -- Sentry handler wiring
		 */

		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class);

		if ($sentryHandlerServiceName !== null) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));
			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);
			$sentryHandlerService = $builder->getDefinition($this->prefix('tools.sentry.handler'));
			assert($sentryHandlerService instanceof DI\Definitions\ServiceDefinition);
			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
		}

		/**
		 * DOCTRINE CRUD -- custom DATE_FORMAT string function
		 */

		// throw:true here unconditionally required Doctrine ORM's EntityManagerInterface in
		// *every* container using fbCore -- including the several packages (CouchDb, RabbitMq,
		// RedisDb, RedisDbCache among them) whose test containers never wire nettrineOrm at all.
		// The Sentry handler lookup just above uses the same "look, act only if found" pattern
		// this now matches; Doctrine's DATE_FORMAT function only needs registering when an
		// EntityManager actually exists to register it on.
		$entityManagerServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class);

		if ($entityManagerServiceName !== null) {
			$entityManagerService = $builder->getDefinition($entityManagerServiceName);

			if ($entityManagerService instanceof DI\Definitions\ServiceDefinition) {
				$entityManagerService->addSetup('?->getConfiguration()->addCustomStringFunction(?, ?)', [
					'@self',
					'DATE_FORMAT',
					DoctrineCrudHelpers\DoctrineCrud\StringFunctions\DateFormat::class,
				]);
			}
		}

		/**
		 * DOCTRINE TIMESTAMPABLE + DOCTRINE PHONE -- EventManager subscriber wiring
		 */

		// Same fix as the DATE_FORMAT block above and for the same reason: throw:true made this
		// unconditional for every fbCore container, including the several packages that never
		// wire Doctrine ORM at all.
		$emServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class);

		if ($emServiceName !== null) {
			$emService = $builder->getDefinition($emServiceName);
			assert($emService instanceof DI\Definitions\ServiceDefinition);
			$emService->addSetup('?->getEventManager()->addEventSubscriber(?)', [
				'@self',
				$builder->getDefinition($this->prefix('doctrineTimestampable.subscriber')),
			]);
			$emService->addSetup('?->getEventManager()->addEventSubscriber(?)', [
				'@self',
				$builder->getDefinition($this->prefix('phone.doctrinePhone.subscriber')),
			]);
		}

		/**
		 * JSON:API -- schema/hydrator assembly
		 */

		$schemaContainerServiceName = $builder->getByType(JsonApiEncoding\JsonApi\SchemaContainer::class, true);
		$schemaContainerService = $builder->getDefinition($schemaContainerServiceName);
		assert($schemaContainerService instanceof DI\Definitions\ServiceDefinition);

		foreach ($builder->findByType(JsonApiSchemas\JsonApi\JsonApi::class) as $schemasService) {
			$schemaContainerService->addSetup('add', [$schemasService]);
		}

		$hydratorContainerServiceName = $builder->getByType(
			JsonApiPersistence\JsonApi\Hydrators\Container::class,
			true,
		);
		$hydratorContainerService = $builder->getDefinition($hydratorContainerServiceName);
		assert($hydratorContainerService instanceof DI\Definitions\ServiceDefinition);

		foreach ($builder->findByType(JsonApiPersistence\JsonApi\Hydrators\Hydrator::class) as $hydratorService) {
			$hydratorContainerService->addSetup('add', [$hydratorService]);
		}

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

		foreach ($builder->findByType(WebSocketsControllers\WebSockets\Controller\IController::class) as $def) {
			$allControllers[$def->getType()] = $def;
		}

		foreach ($allControllers as $def) {
			// Wire-level tag string, not a namespace -- Controllers/WebSockets/Controller/
			// ControllerFactory::create() (this same package, Task 15) still consumes this exact
			// string via findByTag(), so it must stay byte-for-byte what WebSocketsExtension used.
			$def->addTag('nette.inject')->addTag('ipub.websockets.controller', $def->getType());
		}

		if (
			interface_exists('Symfony\Component\EventDispatcher\EventDispatcherInterface')
			&& $builder->getByType(EventDispatcher\EventDispatcherInterface::class) !== null
		) {
			$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

			// Preserved guard (PR #450): the base Application service is genuinely optional --
			// nothing in this extension registers it directly, only whichever extension embeds
			// the WAMP controller-dispatch framework does. Wiring events onto a service that was
			// never defined would be a hard MissingServiceException at compile time.
			$applicationType = $builder->getByType(WebSocketsControllers\WebSockets\Application::class);

			if ($applicationType !== null) {
				$application = $builder->getDefinition($applicationType);
				assert($application instanceof DI\Definitions\ServiceDefinition);

				$application->addSetup('?->onOpen[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(OpenEvent::class),
				]);
				$application->addSetup('?->onClose[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(CloseEvent::class),
				]);
				$application->addSetup('?->onMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(
						MessageEvent::class,
					),
				]);
				$application->addSetup('?->onError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self', $dispatcher, new PhpGenerator\Literal(ErrorEvent::class),
				]);
			}

			$server = $builder->getDefinition($builder->getByType(WsServerServer\WsServer\Server::class));
			assert($server instanceof DI\Definitions\ServiceDefinition);
			$server->addSetup('?->onCreate[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(CreateEvent::class),
			]);
			$server->addSetup('?->onStart[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(StartEvent::class),
			]);
			$server->addSetup('?->onStop[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(StopEvent::class),
			]);

			$serverWrapper = $builder->getDefinition($builder->getByType(WsServerServer\WsServer\Wrapper::class));
			assert($serverWrapper instanceof DI\Definitions\ServiceDefinition);
			$serverWrapper->addSetup('?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(
					ClientConnectEvent::class,
				),
			]);
			$serverWrapper->addSetup(
				'?->onClientDisconnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
				[
					'@self', $dispatcher, new PhpGenerator\Literal(ClientDisconnectEvent::class),
				],
			);
			$serverWrapper->addSetup('?->onClientError[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(ClientErrorEvent::class),
			]);
			$serverWrapper->addSetup('?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(
					IncommingMessageEvent::class,
				),
			]);
			$serverWrapper->addSetup(
				'?->onAfterIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
				[
					'@self', $dispatcher, new PhpGenerator\Literal(AfterIncommingMessageEvent::class),
				],
			);

			// WAMP's own event bridge -- WampApplication is unconditionally registered by this
			// extension (unlike base Application above), so no presence guard is needed here;
			// preserved from WebSocketsWAMPExtension::beforeCompile().
			$wampApplication = $builder->getDefinition(
				$builder->getByType(WebSocketsControllers\WebSockets\WampApplication::class),
			);
			assert($wampApplication instanceof DI\Definitions\ServiceDefinition);
			$wampApplication->addSetup('?->onPush[] = function() {?->dispatch(new ?(...func_get_args()));}', [
				'@self', $dispatcher, new PhpGenerator\Literal(PushEvent::class),
			]);
		}

		$pushRegistry = $builder->getDefinition(
			$builder->getByType(WebSocketsMessaging\WebSockets\PushMessages\ConsumersRegistry::class),
		);

		foreach ($builder->findByType(WebSocketsMessaging\WebSockets\PushMessages\IConsumer::class) as $consumer) {
			$pushRegistry->addSetup('?->addConsumer(?)', [$pushRegistry, $consumer]);
		}

		$wsServerServer = $builder->getDefinitionByType(WsServerServer\WsServer\Server::class);
		$wsServerServer->addSetup('$service->onStart[] = ?', [
			'@' . $this->prefix('wsServer.wamp.subscribers.onServerStart'),
		]);

		/**
		 * WS SERVER PLUGIN -- events bridge (fails loudly if the event dispatcher is missing,
		 * preserved from WsServerExtension::beforeCompile())
		 */

		if ($builder->getByType(WsServerEventDispatcher\EventDispatcherInterface::class) === null) {
			throw new Exceptions\Logic(sprintf(
				'Service of type "%s" is needed. Please register it.',
				WsServerEventDispatcher\EventDispatcherInterface::class,
			));
		}

		$wsServerDispatcher = $builder->getDefinition(
			$builder->getByType(WsServerEventDispatcher\EventDispatcherInterface::class),
		);
		$socketWrapperServiceName = $builder->getByType(WsServerServer\WsServer\Wrapper::class);
		assert(is_string($socketWrapperServiceName));
		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup(
			'?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self', $wsServerDispatcher, new PhpGenerator\Literal(ClientConnected::class),
			],
		);
		$socketWrapperService->addSetup(
			'?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self', $wsServerDispatcher, new PhpGenerator\Literal(IncomingMessage::class),
			],
		);
	}

	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		parent::afterCompile($class);

		// Preserved from DoctrinePhoneExtension::afterCompile() -- registers the 'phone' DBAL
		// type. Entities map columns to it by name (Module/Triggers Entities\Notifications\Sms),
		// so without this every test that loads the Triggers metadata fails.
		$initialize = $class->getMethod('initialize');
		$initialize->addBody(
			'if (!Doctrine\DBAL\Types\Type::hasType(\'' . Phone::PHONE . '\')) {'
			. ' Doctrine\DBAL\Types\Type::addType('
			. '\'' . Phone::PHONE . '\', \'' . Phone::class . '\''
			. '); }',
		);
	}

}
