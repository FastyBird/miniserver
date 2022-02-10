<?php declare(strict_types = 1);

/**
 * ChannelPropertiesManager.php
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
use FastyBird\MiniServer\Exceptions;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use Nette;
use Nette\Utils;

/**
 * Channel property states manager
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertiesManager implements DevicesModuleModels\States\IChannelPropertiesManager
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStatesManager */
	private RedisDbStoragePluginModels\IStatesManager $statesManager;

	public function __construct(
		RedisDbStoragePluginModels\StatesManagerFactory $statesManagerFactory
	) {
		$this->statesManager = $statesManagerFactory->create(States\ChannelProperty::class);
	}

	public function create(
		DevicesModuleEntities\Channels\Properties\IProperty $property,
		Utils\ArrayHash $values
	): States\IChannelProperty {
		/** @var States\IChannelProperty $createdState */
		$createdState = $this->statesManager->create(
			$property->getId(),
			$values
		);

		return $createdState;
	}

	public function update(
		DevicesModuleEntities\Channels\Properties\IProperty $property,
		DevicesModuleStates\IChannelProperty $state,
		Utils\ArrayHash $values
	): States\IChannelProperty {
		if (!$state instanceof States\IChannelProperty) {
			throw new Exceptions\InvalidArgumentException('Provided state entity is not valid instance');
		}

		/** @var States\IChannelProperty $updatedState */
		$updatedState = $this->statesManager->update(
			$state,
			$values
		);

		return $updatedState;
	}

	public function delete(
		DevicesModuleEntities\Channels\Properties\IProperty $property,
		DevicesModuleStates\IChannelProperty $state
	): bool {
		if (!$state instanceof States\IChannelProperty) {
			return false;
		}

		return $this->statesManager->delete($state);
	}

}
