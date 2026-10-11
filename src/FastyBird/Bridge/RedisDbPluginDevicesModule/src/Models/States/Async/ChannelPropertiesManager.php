<?php declare(strict_types = 1);

/**
 * ChannelPropertiesManager.php
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
use Nette\Utils;
use Ramsey\Uuid;
use React\Promise;

/**
 * Channel property asynchronous states manager
 *
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertiesManager implements DevicesModels\States\Channels\Async\Writer
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\Async\StatesManager<States\ChannelProperty> */
	private RedisDbModels\States\Async\StatesManager $statesManager;

	/**
	 * @param RedisDbModels\States\Async\StatesManagerFactory<States\ChannelProperty> $statesManagerFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\Async\StatesManagerFactory $statesManagerFactory,
		private readonly int $database = 0,
	)
	{
		$this->statesManager = $statesManagerFactory->create(States\ChannelProperty::class);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): Promise\PromiseInterface
	{
		return $this->statesManager->create($id, $values, $this->database);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): Promise\PromiseInterface
	{
		return $this->statesManager->update($id, $values, $this->database);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		return $this->statesManager->delete($id, $this->database);
	}

}
