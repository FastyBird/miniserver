<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Ramsey\Uuid;

/**
 * Channel property state repository
 */
class ChannelPropertiesRepository implements DevicesModels\States\Channels\Reader
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesRepository<States\ChannelProperty> */
	private RedisDbModels\States\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\StatesRepositoryFactory<States\ChannelProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\ChannelProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function find(Uuid\UuidInterface $id): States\ChannelProperty|null
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
