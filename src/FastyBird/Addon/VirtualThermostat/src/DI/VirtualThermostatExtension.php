<?php declare(strict_types = 1);

/**
 * VirtualThermostatExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Addon\VirtualThermostat\DI;

use Contributte\Translation;
use FastyBird\Addon\VirtualThermostat;
use FastyBird\Addon\VirtualThermostat\Commands;
use FastyBird\Addon\VirtualThermostat\Drivers;
use FastyBird\Addon\VirtualThermostat\Helpers;
use FastyBird\Addon\VirtualThermostat\Hydrators;
use FastyBird\Addon\VirtualThermostat\Schemas;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents;
use FastyBird\Core\Documents\DI as DocumentsDI;
use Nette\Bootstrap;
use Nette\DI as NetteDI;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use const DIRECTORY_SEPARATOR;

/**
 * Virtual connector
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class VirtualThermostatExtension extends NetteDI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbVirtualThermostatAddon';

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

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new NetteDI\Definitions\ServiceDefinition())
			->setType(VirtualThermostat\Logger::class)
			->setAutowired(false);

		/**
		 * DRIVERS
		 */

		$builder->addFactoryDefinition($this->prefix('drivers.thermostat'))
			->setImplement(Drivers\ThermostatFactory::class)
			->getResultDefinition()
			->setType(Drivers\Thermostat::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition(
			$this->prefix('schemas.device'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Device::class);

		$builder->addDefinition($this->prefix('schemas.channel.actors'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Actors::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.configuration'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Configuration::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.preset'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Preset::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.sensors'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Sensors::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.state'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\State::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.device'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Device::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.actors'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Actors::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.configuration'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Configuration::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.preset'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Preset::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.sensors'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Sensors::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.state'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\State::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.device'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.install'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);
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
			'FastyBird\Addon\VirtualThermostat\Entities',
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
						'FastyBird\Addon\VirtualThermostat\Documents',
					]);
				}
			}
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
