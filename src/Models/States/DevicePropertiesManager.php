<?php declare(strict_types = 1);

/**
 * DevicePropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.2.0
 *
 * @date           17.01.22
 */

namespace FastyBird\MiniServer\Models\States;

use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\States as DevicesModuleStates;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use Nette;
use Nette\Utils;

/**
 * Device property states manager
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertiesManager implements DevicesModuleModels\States\IDevicePropertiesManager
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStatesManager */
	private RedisDbStoragePluginModels\IStatesManager $statesManager;

	public function __construct(
		RedisDbStoragePluginModels\StatesManagerFactory $statesManagerFactory
	) {
		$this->statesManager = $statesManagerFactory->create(States\DeviceProperty::class);
	}

	public function create(
		DevicesModuleEntities\Devices\Properties\IProperty $property,
		Utils\ArrayHash $values
	): States\IDeviceProperty {
		/** @var States\IDeviceProperty $createdState */
		$createdState = $this->statesManager->create(
			$property->getId(),
			$values
		);

		return $createdState;
	}

	public function update(
		DevicesModuleEntities\Devices\Properties\IProperty $property,
		DevicesModuleStates\IDeviceProperty $state,
		Utils\ArrayHash $values
	): States\IDeviceProperty {
		/** @var States\IDeviceProperty $updatedState */
		$updatedState = $this->statesManager->update(
			$state,
			$values
		);

		return $updatedState;
	}

	public function delete(
		DevicesModuleEntities\Devices\Properties\IProperty $property,
		DevicesModuleStates\IDeviceProperty $state
	): bool {
		return $this->statesManager->delete($state);
	}

}
