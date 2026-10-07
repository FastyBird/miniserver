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
use FastyBird\Core\Boot;
use FastyBird\Core\Documents;
use FastyBird\Core\Documents\DI as DocumentsDI;
use FastyBird\Core\Exchange\DI as ExchangeDI;
use FastyBird\Core\Http\Routing as HttpRouting;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Controllers as WebSocketsControllers;
use FastyBird\Core\WebSockets\DI as WebSocketsDI;
use FastyBird\Core\WebSockets\Routing as WebSocketsRouting;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Caching as UiCaching;
use FastyBird\Module\Ui\Commands;
use FastyBird\Module\Ui\Consumers;
use FastyBird\Module\Ui\Controllers as UiControllers;
use FastyBird\Module\Ui\Hydrators;
use FastyBird\Module\Ui\Middleware;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Router;
use FastyBird\Module\Ui\Schemas;
use FastyBird\Module\Ui\Subscribers;
use Nette\Bootstrap;
use Nette\Caching as NetteCaching;
use Nette\DI as NetteDI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use function array_keys;
use function array_pop;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * UI module
 *
 * @package        FastyBird:UIModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class UiExtension extends NetteDI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbUiModule';

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

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'apiPrefix' => Schema\Expect::bool(true),
		]);
	}

	/**
	 * @throws NetteDI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Ui\Logger::class)
			->setAutowired(false);

		/**
		 * MODULE CACHING
		 */

		$configurationRepositoryCache = $builder->addDefinition(
			$this->prefix('caching.configuration.repository'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(NetteCaching\Cache::class)
			->setArguments([
				'namespace' => Sources\Module::UI->value . '_configuration_repository',
			])
			->setAutowired(false);

		$configurationBuilderCache = $builder->addDefinition(
			$this->prefix('caching.configuration.builder'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(NetteCaching\Cache::class)
			->setArguments([
				'namespace' => Sources\Module::UI->value . '_configuration_builder',
			])
			->setAutowired(false);

		$builder->addDefinition(
			$this->prefix('caching.container'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(UiCaching\Container::class)
			->setArguments([
				'configurationBuilderCache' => $configurationBuilderCache,
				'configurationRepositoryCache' => $configurationRepositoryCache,
			]);

		/**
		 * ROUTE MIDDLEWARES & ROUTING
		 */

		$builder->addDefinition($this->prefix('middleware.access'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('router.api.routes'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		$builder->addDefinition($this->prefix('router.sockets.routes'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Router\SocketRoutes::class)
			->addTag(WebSocketsDI\WebSocketsExtension::ROUTES_TAG);

		$builder->addDefinition($this->prefix('router.validator'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		/**
		 * MODELS - DOCTRINE
		 */

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.dashboards'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.dashboards'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.tabs'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Tabs\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.tabs'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Tabs\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.groups'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Groups\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.groups'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Groups\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.widgets'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.widgets'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.dataSources'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\DataSources\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.dataSources'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\DataSources\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.displays'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Displays\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.displays'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Displays\Manager::class);

		/**
		 * MODELS - CONFIGURATION
		 */

		$builder->addDefinition(
			$this->prefix('models.configuration.builder'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Builder::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.dashboards'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Dashboards\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.tabs'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Dashboards\Tabs\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.groups'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Groups\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.widgets'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Widgets\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.dataSources'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Widgets\DataSources\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.displays'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Widgets\Displays\Repository::class);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.entities'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition(
			$this->prefix('subscribers.dashboardEntity'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\DashboardEntity::class);

		// Enables the SocketsBridge once the WebSocket server is created: after Devices', before
		// DevicesModuleUiModule's, in production's order (census T3, #658)
		$builder->addDefinition(
			$this->prefix('subscribers.enableSocketsBridge'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\EnableSocketsBridge::class)
			->addTag(WebSocketsDI\WebSocketsExtension::SERVER_CREATED_LISTENER_TAG, -20);

		/**
		 * API CONTROLLERS
		 */

		$builder->addDefinition($this->prefix('controllers.dashboards'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UiControllers\DashboardsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.tabs'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UiControllers\TabsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.groups'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UiControllers\GroupsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.widgets'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UiControllers\WidgetsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.dataSources'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UiControllers\DataSourcesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.display'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UiControllers\DisplayV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * WEBSOCKETS CONTROLLERS
		 */

		$builder->addDefinition($this->prefix('controllers.exchange'), new NetteDI\Definitions\ServiceDefinition())
			->setType(UiControllers\ExchangeV1::class)
			->setArguments([
				'logger' => $logger,
			])
			->addTag('nette.inject');

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.dashboard'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Dashboards\Dashboard::class);

		$builder->addDefinition($this->prefix('schemas.tabs'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Dashboards\Tabs\Tab::class);

		$builder->addDefinition($this->prefix('schemas.group'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Groups\Group::class);

		$builder->addDefinition(
			$this->prefix('schemas.widgets.analogActuator'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\AnalogActuator::class);

		$builder->addDefinition(
			$this->prefix('schemas.widgets.analogSensor'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\AnalogSensor::class);

		$builder->addDefinition(
			$this->prefix('schemas.widgets.digitalActuator'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DigitalActuator::class);

		$builder->addDefinition(
			$this->prefix('schemas.widgets.digitalSensor'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DigitalSensor::class);

		$builder->addDefinition(
			$this->prefix('schemas.display.analogValue'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\Display\AnalogValue::class);

		$builder->addDefinition($this->prefix('schemas.display.button'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Button::class);

		$builder->addDefinition(
			$this->prefix('schemas.display.chartGraph'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\Display\ChartGraph::class);

		$builder->addDefinition(
			$this->prefix('schemas.display.digitalValue'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\Display\DigitalValue::class);

		$builder->addDefinition($this->prefix('schemas.display.gauge'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Gauge::class);

		$builder->addDefinition(
			$this->prefix('schemas.display.groupedButton'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\Display\GroupedButton::class);

		$builder->addDefinition($this->prefix('schemas.display.slider'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Slider::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSource.generic'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\Generic::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.dashboard'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Dashboards\Dashboard::class);

		$builder->addDefinition($this->prefix('hydrators.tabs'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Dashboards\Tabs\Tab::class);

		$builder->addDefinition($this->prefix('hydrators.group'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Groups\Group::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.analogActuator'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\AnalogActuator::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.analogSensor'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\AnalogSensor::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.digitalActuator'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DigitalActuator::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.digitalSensor'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DigitalSensor::class);

		$builder->addDefinition(
			$this->prefix('hydrators.display.analogValue'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\Displays\AnalogValue::class);

		$builder->addDefinition($this->prefix('hydrators.display.button'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Button::class);

		$builder->addDefinition(
			$this->prefix('hydrators.display.chartGraph'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\Displays\ChartGraph::class);

		$builder->addDefinition(
			$this->prefix('hydrators.display.digitalValue'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\Displays\DigitalValue::class);

		$builder->addDefinition($this->prefix('hydrators.display.gauge'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Gauge::class);

		$builder->addDefinition(
			$this->prefix('hydrators.display.groupedButton'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\Displays\GroupedButton::class);

		$builder->addDefinition($this->prefix('hydrators.display.slider'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Slider::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.generic'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\Generic::class);

		/**
		 * COMMANDS
		 */

		// Console commands
		$builder->addDefinition($this->prefix('commands.initialize'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * COMMUNICATION EXCHANGE
		 */

		if (
			$builder->findByType(WebSocketsRouting\LinkGenerator::class) !== []
			&& $builder->findByType(Topics\Storage::class) !== []
		) {
			$builder->addDefinition(
				$this->prefix('exchange.consumer.socketsBridge'),
				new NetteDI\Definitions\ServiceDefinition(),
			)
				->setType(Consumers\SocketsBridge::class)
				->setArguments([
					'logger' => $logger,
				])
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);
		}
	}

	/**
	 * @throws NetteDI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * DOCTRINE ENTITIES
		 */

		// nettrine/orm 0.10 tags the per-manager MappingDriverChain, not an AttributeDriver, so
		// there is no service left to call addPaths() on. MappingHelper is nettrine's own
		// entry point for contributing a mapping to a manager from another extension.
		NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
			'default',
			'FastyBird\Module\Ui\Entities',
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities',
		);

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(DocumentsDI\DocumentsExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$documentAttributeDriverServiceName = array_pop($services);

			$documentAttributeDriverService = $builder->getDefinition($documentAttributeDriverServiceName);

			if ($documentAttributeDriverService instanceof NetteDI\Definitions\ServiceDefinition) {
				$documentAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Documents']],
				);

				$documentAttributeDriverChainService = $builder->getDefinitionByType(
					Documents\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof NetteDI\Definitions\ServiceDefinition) {
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

		$routerService = $builder->getDefinitionByType(HttpRouting\Router::class);

		if ($routerService instanceof NetteDI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
			]);
		}

		/**
		 * WEBSOCKETS
		 */

		try {
			$wsControllerFactoryService = $builder->getDefinitionByType(
				WebSocketsControllers\ControllerFactory::class,
			);
			assert($wsControllerFactoryService instanceof NetteDI\Definitions\ServiceDefinition);

			$wsControllerFactoryService->addSetup(
				'setMapping',
				[
					[
						'UiModule' => ['FastyBird\\Module\\Ui\\Controllers', '*', '*V1'],
					],
				],
			);

		} catch (NetteDI\MissingServiceException) {
			// Extension is not registered
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
