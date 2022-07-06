<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.2.0
 *
 * @date           29.03.22
 */

namespace FastyBird\MiniServer\Models\States;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\States as DevicesModuleStates;
use FastyBird\MiniServer\Exceptions;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use Nette;
use Nette\Utils;

/**
 * Connector property states manager
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertiesManager implements DevicesModuleModels\States\IConnectorPropertiesManager
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStatesManager */
	private RedisDbStoragePluginModels\IStatesManager $statesManager;

	public function __construct(
		RedisDbStoragePluginModels\StatesManagerFactory $statesManagerFactory
	) {
		$this->statesManager = $statesManagerFactory->create(States\ConnectorProperty::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function create(
		$property,
		Utils\ArrayHash $values
	): States\IConnectorProperty {
		/** @var States\IConnectorProperty $createdState */
		$createdState = $this->statesManager->create(
			$property->getId(),
			$values
		);

		return $createdState;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update(
		$property,
		DevicesModuleStates\IConnectorProperty $state,
		Utils\ArrayHash $values
	): States\IConnectorProperty {
		if (!$state instanceof States\IConnectorProperty) {
			throw new Exceptions\InvalidArgumentException('Provided state entity is not valid instance');
		}

		/** @var States\IConnectorProperty $updatedState */
		$updatedState = $this->statesManager->update(
			$state,
			$values
		);

		return $updatedState;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(
		$property,
		DevicesModuleStates\IConnectorProperty $state
	): bool {
		if (!$state instanceof States\IConnectorProperty) {
			return false;
		}

		return $this->statesManager->delete($state);
	}

}
