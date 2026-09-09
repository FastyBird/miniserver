<?php declare(strict_types = 1);

/**
 * UiExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           02.12.20
 */

namespace FastyBird\Module\Ui\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\DI as ApplicationDI;
use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Exchange\DI as ExchangeDI;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Caching;
use FastyBird\Module\Ui\Commands;
use FastyBird\Module\Ui\Consumers;
use FastyBird\Module\Ui\Controllers;
use FastyBird\Module\Ui\Hydrators;
use FastyBird\Module\Ui\Middleware;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Router;
use FastyBird\Module\Ui\Schemas;
use FastyBird\Module\Ui\Subscribers;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette\Bootstrap;
use Nette\Caching as NetteCaching;
use Nette\DI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use function array_keys;
use function array_pop;
use function assert;
use function class_exists;
use const DIRECTORY_SEPARATOR;

/**
 * UI module
 *
 * @package        FastyBird:UIModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class UiExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbUiModule';

	public static function register(
		ApplicationBoot\Configurator $config,
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
			'apiPrefix' => Schema\Expect::bool(true),
		]);
	}

	/**
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(Ui\Logger::class)
			->setAutowired(false);

		/**
		 * MODULE CACHING
		 */

		$configurationRepositoryCache = $builder->addDefinition(
			$this->prefix('caching.configuration.repository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(NetteCaching\Cache::class)
			->setArguments([
				'namespace' => MetadataTypes\Sources\Module::UI->value . '_configuration_repository',
			])
			->setAutowired(false);

		$configurationBuilderCache = $builder->addDefinition(
			$this->prefix('caching.configuration.builder'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(NetteCaching\Cache::class)
			->setArguments([
				'namespace' => MetadataTypes\Sources\Module::UI->value . '_configuration_builder',
			])
			->setAutowired(false);

		$builder->addDefinition(
			$this->prefix('caching.container'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Caching\Container::class)
			->setArguments([
				'configurationBuilderCache' => $configurationBuilderCache,
				'configurationRepositoryCache' => $configurationRepositoryCache,
			]);

		/**
		 * ROUTE MIDDLEWARES & ROUTING
		 */

		$builder->addDefinition($this->prefix('middleware.access'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('router.api.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		$builder->addDefinition($this->prefix('router.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		/**
		 * MODELS - DOCTRINE
		 */

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.dashboards'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.dashboards'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.tabs'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Tabs\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.tabs'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Tabs\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.groups'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Groups\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.groups'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Groups\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.widgets'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.widgets'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.dataSources'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\DataSources\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.dataSources'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\DataSources\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.displays'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Displays\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.displays'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Displays\Manager::class);

		/**
		 * MODELS - CONFIGURATION
		 */

		$builder->addDefinition(
			$this->prefix('models.configuration.builder'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Builder::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.dashboards'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Dashboards\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.tabs'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Dashboards\Tabs\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.groups'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Groups\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.widgets'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Widgets\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.dataSources'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Widgets\DataSources\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.displays'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Widgets\Displays\Repository::class);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('subscribers.dashboardEntity'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\DashboardEntity::class);

		/**
		 * API CONTROLLERS
		 */

		$builder->addDefinition($this->prefix('controllers.dashboards'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DashboardsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.tabs'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\TabsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.groups'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\GroupsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.widgets'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\WidgetsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.dataSources'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DataSourcesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.display'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DisplayV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * WEBSOCKETS CONTROLLERS
		 */

		if (class_exists('IPub\WebSockets\DI\WebSocketsExtension')) {
			$builder->addDefinition($this->prefix('controllers.exchange'), new DI\Definitions\ServiceDefinition())
				->setType(Controllers\ExchangeV1::class)
				->setArguments([
					'logger' => $logger,
				])
				->addTag('nette.inject');
		}

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.dashboard'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Dashboards\Dashboard::class);

		$builder->addDefinition($this->prefix('schemas.tabs'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Dashboards\Tabs\Tab::class);

		$builder->addDefinition($this->prefix('schemas.group'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Groups\Group::class);

		$builder->addDefinition($this->prefix('schemas.widgets.analogActuator'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\AnalogActuator::class);

		$builder->addDefinition($this->prefix('schemas.widgets.analogSensor'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\AnalogSensor::class);

		$builder->addDefinition(
			$this->prefix('schemas.widgets.digitalActuator'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DigitalActuator::class);

		$builder->addDefinition($this->prefix('schemas.widgets.digitalSensor'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\DigitalSensor::class);

		$builder->addDefinition($this->prefix('schemas.display.analogValue'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\AnalogValue::class);

		$builder->addDefinition($this->prefix('schemas.display.button'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Button::class);

		$builder->addDefinition($this->prefix('schemas.display.chartGraph'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\ChartGraph::class);

		$builder->addDefinition($this->prefix('schemas.display.digitalValue'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\DigitalValue::class);

		$builder->addDefinition($this->prefix('schemas.display.gauge'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Gauge::class);

		$builder->addDefinition($this->prefix('schemas.display.groupedButton'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\GroupedButton::class);

		$builder->addDefinition($this->prefix('schemas.display.slider'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Slider::class);

		$builder->addDefinition($this->prefix('schemas.dataSource.generic'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\DataSources\Generic::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.dashboard'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Dashboards\Dashboard::class);

		$builder->addDefinition($this->prefix('hydrators.tabs'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Dashboards\Tabs\Tab::class);

		$builder->addDefinition($this->prefix('hydrators.group'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Groups\Group::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.analogActuator'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\AnalogActuator::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.analogSensor'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\AnalogSensor::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.digitalActuator'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DigitalActuator::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.digitalSensor'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DigitalSensor::class);

		$builder->addDefinition($this->prefix('hydrators.display.analogValue'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\AnalogValue::class);

		$builder->addDefinition($this->prefix('hydrators.display.button'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Button::class);

		$builder->addDefinition($this->prefix('hydrators.display.chartGraph'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\ChartGraph::class);

		$builder->addDefinition($this->prefix('hydrators.display.digitalValue'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\DigitalValue::class);

		$builder->addDefinition($this->prefix('hydrators.display.gauge'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Gauge::class);

		$builder->addDefinition(
			$this->prefix('hydrators.display.groupedButton'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\Displays\GroupedButton::class);

		$builder->addDefinition($this->prefix('hydrators.display.slider'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Slider::class);

		$builder->addDefinition($this->prefix('hydrators.dataSources.generic'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\DataSources\Generic::class);

		/**
		 * COMMANDS
		 */

		// Console commands
		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * COMMUNICATION EXCHANGE
		 */

		if (
			$builder->findByType('IPub\WebSockets\Router\LinkGenerator') !== []
			&& $builder->findByType('IPub\WebSocketsWAMP\Topics\IStorage') !== []
		) {
			$builder->addDefinition(
				$this->prefix('exchange.consumer.socketsBridge'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Consumers\SocketsBridge::class)
				->setArguments([
					'logger' => $logger,
				])
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);
		}
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * DOCTRINE ENTITIES
		 */

		$services = $builder->findByTag(NettrineORM\DI\OrmAttributesExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$ormAttributeDriverServiceName = array_pop($services);

			$ormAttributeDriverService = $builder->getDefinition($ormAttributeDriverServiceName);

			if ($ormAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$ormAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']],
				);

				$ormAttributeDriverChainService = $builder->getDefinitionByType(
					Persistence\Mapping\Driver\MappingDriverChain::class,
				);

				if ($ormAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$ormAttributeDriverChainService->addSetup('addDriver', [
						$ormAttributeDriverService,
						'FastyBird\Module\Ui\Entities',
					]);
				}
			}
		}

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(ApplicationDI\ApplicationExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$documentAttributeDriverServiceName = array_pop($services);

			$documentAttributeDriverService = $builder->getDefinition($documentAttributeDriverServiceName);

			if ($documentAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$documentAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Documents']],
				);

				$documentAttributeDriverChainService = $builder->getDefinitionByType(
					ApplicationDocuments\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$documentAttributeDriverChainService->addSetup('addDriver', [
						$documentAttributeDriverService,
						'FastyBird\Module\Ui\Documents',
					]);
				}
			}
		}

		/**
		 * ROUTES
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
			]);
		}

		/**
		 * WEBSOCKETS
		 */

		if (class_exists('IPub\WebSockets\DI\WebSocketsExtension')) {
			try {
				$wsControllerFactoryService = $builder->getDefinitionByType(
					'IPub\WebSockets\Application\Controller\IControllerFactory',
				);
				assert($wsControllerFactoryService instanceof DI\Definitions\ServiceDefinition);

				$wsControllerFactoryService->addSetup(
					'setMapping',
					[
						[
							'UiModule' => ['FastyBird\\Module\\Ui\\Controllers', '*', '*V1'],
						],
					],
				);

				$consumerService = $builder->getDefinitionByType(ExchangeConsumers\Container::class);
				assert($consumerService instanceof DI\Definitions\ServiceDefinition);

				$wsServerService = $builder->getDefinitionByType('IPub\WebSockets\Server\Server');
				assert($wsServerService instanceof DI\Definitions\ServiceDefinition);

				$wsServerService->addSetup(
					'?->onCreate[] = function() {?->enable(?);}',
					[
						'@self',
						$consumerService,
						Consumers\SocketsBridge::class,
					],
				);

			} catch (DI\MissingServiceException) {
				// Extension is not registered
			}
		}
	}

	/**
	 * @return array<string>
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . '/../Translations',
		];
	}

}
