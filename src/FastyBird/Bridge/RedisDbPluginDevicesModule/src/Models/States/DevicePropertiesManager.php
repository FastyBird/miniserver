<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Override;
use Ramsey\Uuid;

/**
 * Device property states manager
 */
final class DevicePropertiesManager implements DevicesModels\States\Devices\Writer
{

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
	 * @param array<string, mixed> $values
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	#[Override]
	public function create(Uuid\UuidInterface $id, array $values): States\DeviceProperty
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	#[Override]
	public function update(Uuid\UuidInterface $id, array $values): States\DeviceProperty|false
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	#[Override]
	public function delete(Uuid\UuidInterface $id): bool
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
