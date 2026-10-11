<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States\Async;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use InvalidArgumentException;
use Nette;
use Override;
use Ramsey\Uuid;
use React\Promise;

/**
 * Channel property asynchronous states repository
 */
class ChannelPropertiesRepository implements DevicesModels\States\Channels\Async\Reader
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\Async\StatesRepository<States\ChannelProperty> */
	private RedisDbModels\States\Async\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\Async\StatesRepositoryFactory<States\ChannelProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\Async\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\ChannelProperty::class);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	#[Override]
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
