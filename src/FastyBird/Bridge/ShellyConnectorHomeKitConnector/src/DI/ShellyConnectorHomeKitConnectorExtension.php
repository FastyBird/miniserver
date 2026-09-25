<?php declare(strict_types = 1);

/**
 * ShellyConnectorHomeKitConnectorExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\DI;

use Contributte\Translation;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Builders;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Commands;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Controllers;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Protocol;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Router;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas;
use FastyBird\Core\Boot;
use FastyBird\Core\DI as CoreDI;
use FastyBird\Core\Documents;
use FastyBird\Core\Http\Routing;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use function array_keys;
use function array_pop;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Shelly devices to HomeKit connector bridge extension
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ShellyConnectorHomeKitConnectorExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbShellyConnectorHomeKitConnectorBridge';

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
			'apiPrefix' => Schema\Expect::bool(true),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(ShellyConnectorHomeKitConnector\Logger::class)
			->setAutowired(false);

		/**
		 * BUILDERS
		 */

		$builder->addDefinition($this->prefix('builder'), new DI\Definitions\ServiceDefinition())
			->setType(Builders\Builder::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MAPPING CONFIGURATION
		 */

		$builder->addDefinition($this->prefix('mapping.builder'), new DI\Definitions\ServiceDefinition())
			->setType(Mapping\Builder::class);

		/**
		 * ROUTE MIDDLEWARES & ROUTING
		 */

		$builder->addDefinition($this->prefix('router.api.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		/**
		 * API CONTROLLERS
		 */

		$builder->addDefinition(
			$this->prefix('controllers.bridges'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\BridgesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Shelly::class);

		$builder->addDefinition($this->prefix('schemas.channel.lightbulb'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Lightbulb::class);

		$builder->addDefinition($this->prefix('schemas.channel.outlet'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Outlet::class);

		$builder->addDefinition($this->prefix('schemas.channel.relay'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Relay::class);

		$builder->addDefinition($this->prefix('schemas.channel.valve'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Valve::class);

		$builder->addDefinition($this->prefix('schemas.channel.windowCovering'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\WindowCovering::class);

		$builder->addDefinition($this->prefix('schemas.channel.inputButton'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\InputButton::class);

		$builder->addDefinition($this->prefix('schemas.channel.inputSwitch'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\InputSwitch::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Shelly::class);

		$builder->addDefinition($this->prefix('hydrators.channel.lightbulb'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Lightbulb::class);

		$builder->addDefinition($this->prefix('hydrators.channel.outlet'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Outlet::class);

		$builder->addDefinition($this->prefix('hydrators.channel.relay'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Relay::class);

		$builder->addDefinition($this->prefix('hydrators.channel.valve'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Valve::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.windowCovering'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\WindowCovering::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.inputButton'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\InputButton::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.inputSwitch'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\InputSwitch::class);

		/**
		 * HOMEKIT PROTOCOL
		 */

		$builder->addDefinition($this->prefix('protocol.accessory.factory.shelly'))
			->setType(Protocol\Accessories\ShellyFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.inputButton'))
			->setType(Protocol\Services\InputButtonFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.inputSwitch'))
			->setType(Protocol\Services\InputSwitchFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.lightbulb'))
			->setType(Protocol\Services\LightbulbFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.outlet'))
			->setType(Protocol\Services\OutletFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.relay'))
			->setType(Protocol\Services\RelayFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.valve'))
			->setType(Protocol\Services\ValveFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.windowCovering'))
			->setType(Protocol\Services\WindowCoveringFactory::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.build'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Build::class)
			->setArguments([
				'logger' => $logger,
			]);
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

		// nettrine/orm 0.10 tags the per-manager MappingDriverChain, not an AttributeDriver, so
		// there is no service left to call addPaths() on. MappingHelper is nettrine's own
		// entry point for contributing a mapping to a manager from another extension.
		NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
			'default',
			'FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities',
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities',
		);

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(CoreDI\CoreExtension::DRIVER_TAG);

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
					Documents\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$documentAttributeDriverChainService->addSetup('addDriver', [
						$documentAttributeDriverService,
						'FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents',
					]);
				}
			}
		}

		/**
		 * API ROUTER
		 */

		$routerService = $builder->getDefinitionByType(Routing\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
			]);
		}
	}

	/**
	 * @return array<string>
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . '/../Translations/',
		];
	}

}
