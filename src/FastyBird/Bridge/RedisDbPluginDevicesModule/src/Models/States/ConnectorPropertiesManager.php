<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette\Utils;
use Override;
use Ramsey\Uuid;

/**
 * Connector property states manager
 */
final class ConnectorPropertiesManager implements DevicesModels\States\Connectors\Writer
{

	/** @var RedisDbModels\States\StatesManager<States\ConnectorProperty> */
	private RedisDbModels\States\StatesManager $statesManager;

	/**
	 * @param RedisDbModels\States\StatesManagerFactory<States\ConnectorProperty> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\ConnectorProperty::class);
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	#[Override]
	public function create(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\ConnectorProperty
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	#[Override]
	public function update(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\ConnectorProperty|false
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	#[Override]
	public function delete(Uuid\UuidInterface $id): bool
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
