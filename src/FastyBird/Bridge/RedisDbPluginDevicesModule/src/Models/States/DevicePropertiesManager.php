<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Device property states manager
 */
class DevicePropertiesManager implements DevicesModels\States\Devices\Writer
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesManager<States\DeviceProperty> */
	private RedisDbModels\States\StatesManager $statesManager;

	/**
	 * @param RedisDbModels\States\StatesManagerFactory<States\DeviceProperty> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\DeviceProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\DeviceProperty
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\DeviceProperty|false
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	public function delete(Uuid\UuidInterface $id): bool
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
