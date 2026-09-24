<?php declare(strict_types = 1);

/**
 * VirtualThermostatAddonHomeKitConnectorExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\DI;

use Contributte\Translation;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Commands;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Controllers;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Router;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas;
use FastyBird\Core\Boot as ApplicationBoot;
use FastyBird\Core\DI as CoreDI;
use FastyBird\Core\Documents;
use FastyBird\Core\Routing as SlimRouterRouting;
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
 * Virtual thermostat HomeKit connector bridge extension
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class VirtualThermostatAddonHomeKitConnectorExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbVirtualThermostatAddonHomeKitConnectorBridge';

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

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(VirtualThermostatAddonHomeKitConnector\Logger::class)
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

		$builder->addDefinition($this->prefix('schemas.device.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Thermostat::class);

		$builder->addDefinition($this->prefix('schemas.channel.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Thermostat::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.device.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Thermostat::class);

		$builder->addDefinition($this->prefix('hydrators.channel.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Thermostat::class);

		/**
		 * HOMEKIT PROTOCOL
		 */

		$builder->addDefinition($this->prefix('protocol.accessory.factory.thermostat'))
			->setType(Protocol\Accessories\ThermostatFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.thermostat'))
			->setType(Protocol\Services\ThermostatFactory::class);

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
			'FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities',
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
						'FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents',
					]);
				}
			}
		}

		/**
		 * API ROUTER
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

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
