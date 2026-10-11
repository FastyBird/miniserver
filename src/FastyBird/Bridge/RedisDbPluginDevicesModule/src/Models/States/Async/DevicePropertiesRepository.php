<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States\Async;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use InvalidArgumentException;
use Nette;
use Ramsey\Uuid;
use React\Promise;

/**
 * Device property asynchronous states repository
 */
class DevicePropertiesRepository implements DevicesModels\States\Devices\Async\Reader
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\Async\StatesRepository<States\DeviceProperty> */
	private RedisDbModels\States\Async\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\Async\StatesRepositoryFactory<States\DeviceProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\Async\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\DeviceProperty::class);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
