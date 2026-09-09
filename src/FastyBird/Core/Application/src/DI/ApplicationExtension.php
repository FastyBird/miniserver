<?php declare(strict_types = 1);

/**
 * ApplicationExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           16.06.24
 */

namespace FastyBird\Core\Application\DI;

use FastyBird\Core\Application\Boot;
use FastyBird\Core\Application\Documents;
use FastyBird\Core\Application\EventLoop;
use FastyBird\Core\Application\Exceptions;
use FastyBird\Core\Application\Router;
use FastyBird\Core\Application\Subscribers;
use FastyBird\Core\Application\UI;
use Monolog;
use Nette;
use Nette\Application;
use Nette\Bootstrap;
use Nette\Caching;
use Nette\DI;
use Nette\Schema;
use stdClass;
use Symfony\Bridge\Monolog as SymfonyMonolog;
use function array_values;
use function assert;
use function class_exists;
use function is_dir;
use function is_string;
use function sprintf;
use const DIRECTORY_SEPARATOR;

/**
 * FastyBird application
 *
 * @package        FastyBird:Application!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApplicationExtension extends DI\CompilerExtension
{

	public const NAME = 'fbApplication';

	public const DRIVER_TAG = 'fastybird.application.attribute.driver';

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

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'logging' => Schema\Expect::structure(
				[
					'rotatingFile' => Schema\Expect::structure(
						[
							'enabled' => Schema\Expect::bool(true),
							'level' => Schema\Expect::int(Monolog\Level::Info),
							'filename' => Schema\Expect::string('app.log'),
						],
					),
					'stdOut' => Schema\Expect::structure(
						[
							'enabled' => Schema\Expect::bool(false),
							'level' => Schema\Expect::int(Monolog\Level::Info),
						],
					),
					'console' => Schema\Expect::structure(
						[
							'enabled' => Schema\Expect::bool(false),
							'level' => Schema\Expect::int(Monolog\Level::Info),
						],
					),
				],
			),
			'documents' => Schema\Expect::structure([
				'mapping' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string())->required(),
				'excludePaths' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string()),
			]),
		]);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * LOGGERS
		 */

		if ($configuration->logging->rotatingFile->enabled === true) {
			$builder->addDefinition(
				$this->prefix('logger.handler.rotatingFile'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\RotatingFileHandler::class)
				->setArguments([
					'filename' => FB_LOGS_DIR . DIRECTORY_SEPARATOR . $configuration->logging->rotatingFile->filename,
					'maxFiles' => 10,
					'level' => $configuration->logging->rotatingFile->level,
				]);
		}

		if ($configuration->logging->stdOut->enabled === true) {
			$builder->addDefinition($this->prefix('logger.handler.stdOut'), new DI\Definitions\ServiceDefinition())
				->setType(Monolog\Handler\StreamHandler::class)
				->setArguments([
					'stream' => 'php://stdout',
					'level' => $configuration->logging->stdOut->level,
				]);
		}

		$consoleHandler = null;

		if ($configuration->logging->console->enabled) {
			$consoleHandler = $builder->addDefinition(
				$this->prefix('logger.handler.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SymfonyMonolog\Handler\ConsoleHandler::class);
		}

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('eventLoop.wrapper'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Wrapper::class);

		$builder->addDefinition($this->prefix('eventLoop.status'), new DI\Definitions\ServiceDefinition())
			->setType(EventLoop\Status::class);

		/**
		 * SUBSCRIBERS
		 */

		if ($configuration->logging->console->enabled) {
			$builder->addDefinition($this->prefix('subscribers.console'), new DI\Definitions\ServiceDefinition())
				->setType(Subscribers\Console::class)
				->setArguments([
					'handler' => $consoleHandler,
					'level' => $configuration->logging->console->level,
				]);
		}

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition(
				$this->prefix('subscribers.entityDiscriminator'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Subscribers\EntityDiscriminator::class);
		}

		$builder->addDefinition($this->prefix('subscribers.eventLoop'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\EventLoopLifeCycle::class);

		/**
		 * UI
		 */

		$builder->addDefinition($this->prefix('ui.templateFactory'), new DI\Definitions\ServiceDefinition())
			->setType(UI\TemplateFactory::class);

		$builder->addDefinition($this->prefix('ui.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Nette\Application\Routers\RouteList::class);

		/**
		 * DOCUMENTS SERVICES
		 */

		$metadataCache = $builder->addDefinition(
			$this->prefix('document.cache'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Caching\Cache::class)
			->setArguments([
				'namespace' => 'metadata_class_metadata',
			])
			->setAutowired(false);

		$builder->addDefinition('document.factory', new DI\Definitions\ServiceDefinition())
			->setType(Documents\DocumentFactory::class);

		$attributeDriver = $builder->addDefinition(
			'document.mapping.attributeDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Documents\Mapping\Driver\AttributeDriver::class)
			->setArguments([
				'paths' => array_values($configuration->documents->mapping),
			])
			->addSetup('addExcludePaths', [$configuration->documents->excludePaths])
			->addTag(self::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriver = $builder->addDefinition(
			'document.mapping.mappingDriver',
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Documents\Mapping\Driver\MappingDriverChain::class);

		$builder->addDefinition('document.mapping.classMetadataFactory', new DI\Definitions\ServiceDefinition())
			->setType(Documents\Mapping\ClassMetadataFactory::class)
			->setArguments([
				'driver' => $mappingDriver,
				'cache' => $metadataCache,
			]);

		foreach ($configuration->documents->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new Exceptions\InvalidState(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$mappingDriver->addSetup('addDriver', [$attributeDriver, $namespace]);
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Nette\DI\NotAllowedDuringResolvingException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * LOGGERS
		 */

		if (
			$configuration->logging->rotatingFile->enabled === true
			|| $configuration->logging->stdOut->enabled === true
		) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));

			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			if ($configuration->logging->rotatingFile->enabled === true) {
				$rotatingFileHandler = $builder->getDefinition($this->prefix('logger.handler.rotatingFile'));

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $rotatingFileHandler]);
			}

			if ($configuration->logging->stdOut->enabled === true) {
				$stdOutHandler = $builder->getDefinition($this->prefix('logger.handler.stdOut'));

				$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $stdOutHandler]);
			}
		}

		/**
		 * DOCTRINE
		 */

		if (
			class_exists('\Doctrine\DBAL\Connection')
			&& class_exists('\Doctrine\ORM\EntityManager')
			&& $builder->getByType('\Doctrine\ORM\EntityManagerInterface') !== null
		) {
			$emService = $builder->getDefinitionByType('\Doctrine\ORM\EntityManagerInterface');
			assert($emService instanceof DI\Definitions\ServiceDefinition);

			$emService
				->addSetup('?->getEventManager()->addEventSubscriber(?)', [
					'@self',
					$builder->getDefinitionByType(Subscribers\EntityDiscriminator::class),
				]);
		}

		/**
		 * ROUTES
		 */

		$appRouterServiceName = $builder->getByType(Application\Routers\RouteList::class);
		assert(is_string($appRouterServiceName));
		$appRouterService = $builder->getDefinition($appRouterServiceName);
		assert($appRouterService instanceof DI\Definitions\ServiceDefinition);

		$appRouterService->addSetup([Router\AppRouter::class, 'createRouter'], [$appRouterService]);

		/**
		 * UI
		 */

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof DI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'App' => 'FastyBird\Core\Application\Presenters\*Presenter',
			]]);
		}

		$templateFactoryService = $builder->getDefinitionByType(UI\TemplateFactory::class);
		assert($templateFactoryService instanceof DI\Definitions\ServiceDefinition);

		$templateFactoryService->addSetup(
			'registerLayout',
			[
				__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
				. 'templates' . DIRECTORY_SEPARATOR . '@layout.latte',
			],
		);
	}

}
