<?php declare(strict_types = 1);

/**
 * PropertiesManager.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\MiniServer\Models;

use FastyBird\DateTimeFactory;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\States as DevicesModuleStates;
use FastyBird\MiniServer\Exceptions;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use FastyBird\RedisDbStoragePlugin\States as RedisDbStoragePluginStates;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Properties states manager
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PropertiesManager implements DevicesModuleModels\States\IPropertiesManager
{

	/** @var RedisDbStoragePluginModels\IStatesManager */
	private RedisDbStoragePluginModels\IStatesManager $statesManager;

	/** @var DateTimeFactory\DateTimeFactory */
	private DateTimeFactory\DateTimeFactory $dateFactory;

	public function __construct(
		RedisDbStoragePluginModels\StatesManagerFactory $statesManagerFactory,
		DateTimeFactory\DateTimeFactory $dateFactory
	) {
		$this->statesManager = $statesManagerFactory->create(States\Property::class);
		$this->dateFactory = $dateFactory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values
	): DevicesModuleStates\IProperty {
		$values->offsetSet('created', $this->dateFactory->getNow());

		/** @var DevicesModuleStates\IProperty $property */
		$property = $this->statesManager->create($id, $values);

		return $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update(
		DevicesModuleStates\IProperty $state,
		Utils\ArrayHash $values
	): DevicesModuleStates\IProperty {
		if (!$state instanceof RedisDbStoragePluginStates\IState) {
			throw new Exceptions\InvalidArgumentException(sprintf('Provided state is not instance of %s', RedisDbStoragePluginStates\IState::class));
		}

		$values->offsetSet('updated', $this->dateFactory->getNow());

		/** @var DevicesModuleStates\IProperty $property */
		$property = $this->statesManager->update($state, $values);

		return $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateState(
		DevicesModuleStates\IProperty $state,
		Utils\ArrayHash $values
	): DevicesModuleStates\IProperty {
		if (!$state instanceof RedisDbStoragePluginStates\IState) {
			throw new Exceptions\InvalidArgumentException(sprintf('Provided state is not instance of %s', RedisDbStoragePluginStates\IState::class));
		}

		$values->offsetSet('updated', $this->dateFactory->getNow());

		/** @var DevicesModuleStates\IProperty $property */
		$property = $this->statesManager->update($state, $values);

		return $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(DevicesModuleStates\IProperty $state): bool
	{
		if (!$state instanceof RedisDbStoragePluginStates\IState) {
			throw new Exceptions\InvalidArgumentException(sprintf('Provided state is not instance of %s', RedisDbStoragePluginStates\IState::class));
		}

		return $this->statesManager->delete($state);
	}

}
