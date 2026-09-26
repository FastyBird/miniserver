<?php declare(strict_types = 1);

/**
 * DevicesModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           05.11.22
 */

namespace FastyBird\Automator\DevicesModule\DI;

use FastyBird\Automator\DevicesModule\Hydrators;
use FastyBird\Automator\DevicesModule\Schemas;
use FastyBird\Automator\DevicesModule\Subscribers;
use FastyBird\Core\Boot;
use FastyBird\Core\DI as CoreDI;
use FastyBird\Core\Documents;
use Nette\Bootstrap;
use Nette\DI as NetteDI;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use const DIRECTORY_SEPARATOR;

/**
 * Devices module automator
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicesModuleExtension extends NetteDI\CompilerExtension
{

	public const NAME = 'fbDevicesModuleAutomator';

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

		$builder->addDefinition(
			$this->prefix('schemas.actions.deviceProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Actions\DevicePropertyAction::class);

		$builder->addDefinition(
			$this->prefix('schemas.actions.channelProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Actions\ChannelPropertyAction::class);

		$builder->addDefinition(
			$this->prefix('schemas.conditions.channelProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Conditions\ChannelPropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('schemas.conditions.deviceProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Conditions\DevicePropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('hydrators.actions.deviceProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Actions\DevicePropertyAction::class);

		$builder->addDefinition(
			$this->prefix('hydrators.actions.channelProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Actions\ChannelPropertyAction::class);

		$builder->addDefinition(
			$this->prefix('hydrators.conditions.channelProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Conditions\ChannelPropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('hydrators.conditions.deviceProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Conditions\DevicePropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('subscribers.actions'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\ActionEntity::class);

		$builder->addDefinition(
			$this->prefix('subscribers.conditions'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\ConditionEntity::class);
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
			'FastyBird\Automator\DevicesModule\Entities',
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
						'FastyBird\Automator\DevicesModule\Documents',
					]);
				}
			}
		}
	}

}
