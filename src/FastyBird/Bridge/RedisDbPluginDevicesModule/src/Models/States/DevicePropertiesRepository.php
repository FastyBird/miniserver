<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Ramsey\Uuid;

/**
 * Device property state repository
 */
class DevicePropertiesRepository implements DevicesModels\States\Devices\Reader
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesRepository<States\DeviceProperty> */
	private RedisDbModels\States\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\StatesRepositoryFactory<States\DeviceProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\DeviceProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function find(Uuid\UuidInterface $id): States\DeviceProperty|null
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
