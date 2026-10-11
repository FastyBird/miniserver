<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           20.01.24
 */

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
 * Connector property asynchronous states repository
 *
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertiesRepository implements DevicesModels\States\Connectors\Async\Reader
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\Async\StatesRepository<States\ConnectorProperty> */
	private RedisDbModels\States\Async\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\Async\StatesRepositoryFactory<States\ConnectorProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\Async\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\ConnectorProperty::class);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
