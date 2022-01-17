<?php declare(strict_types = 1);

/**
 * DevicePropertyRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.2.0
 *
 * @date           12.01.22
 */

namespace FastyBird\MiniServer\Models\States;

use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use Nette;

/**
 * Device property state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyRepository implements DevicesModuleModels\States\IDevicePropertyRepository
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStateRepository */
	protected RedisDbStoragePluginModels\IStateRepository $stateRepository;

	public function __construct(
		RedisDbStoragePluginModels\StateRepositoryFactory $stateRepositoryFactory
	) {
		$this->stateRepository = $stateRepositoryFactory->create(States\DeviceProperty::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOne(
		DevicesModuleEntities\Devices\Properties\IProperty $property
	): ?States\IDeviceProperty {
		/** @var States\IDeviceProperty $state */
		$state = $this->stateRepository->findOne($property->getId());

		return $state;
	}

}
