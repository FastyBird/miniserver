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
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\DI as ApplicationDI;
use FastyBird\Core\Application\Documents as ApplicationDocuments;
use Nette\Bootstrap;
use Nette\DI;
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
class DevicesModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbDevicesModuleAutomator';

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

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('schemas.actions.deviceProperty'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Actions\DevicePropertyAction::class);

		$builder->addDefinition(
			$this->prefix('schemas.actions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Actions\ChannelPropertyAction::class);

		$builder->addDefinition(
			$this->prefix('schemas.conditions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Conditions\ChannelPropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('schemas.conditions.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Conditions\DevicePropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('hydrators.actions.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Actions\DevicePropertyAction::class);

		$builder->addDefinition(
			$this->prefix('hydrators.actions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Actions\ChannelPropertyAction::class);

		$builder->addDefinition(
			$this->prefix('hydrators.conditions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Conditions\ChannelPropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('hydrators.conditions.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Conditions\DevicePropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('subscribers.actions'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\ActionEntity::class);

		$builder->addDefinition(
			$this->prefix('subscribers.conditions'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\ConditionEntity::class);
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
			'FastyBird\Automator\DevicesModule\Entities',
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities',
		);

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
						'FastyBird\Automator\DevicesModule\Documents',
					]);
				}
			}
		}
	}

}
