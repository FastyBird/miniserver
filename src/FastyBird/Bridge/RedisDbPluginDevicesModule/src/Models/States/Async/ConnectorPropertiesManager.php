<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States\Async;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use InvalidArgumentException;
use Nette\Utils;
use Override;
use Ramsey\Uuid;
use React\Promise;

/**
 * Connector property asynchronous states manager
 */
final class ConnectorPropertiesManager implements DevicesModels\States\Connectors\Async\Writer
{

	/** @var RedisDbModels\States\Async\StatesManager<States\ConnectorProperty> */
	private RedisDbModels\States\Async\StatesManager $statesManager;

	/**
	 * @param RedisDbModels\States\Async\StatesManagerFactory<States\ConnectorProperty> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\Async\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\ConnectorProperty::class);
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws InvalidArgumentException
	 */
	#[Override]
	public function create(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): Promise\PromiseInterface
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws InvalidArgumentException
	 */
	#[Override]
	public function update(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): Promise\PromiseInterface
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	#[Override]
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
