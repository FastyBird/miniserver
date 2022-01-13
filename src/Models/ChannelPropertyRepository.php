<?php declare(strict_types = 1);

/**
 * ChannelPropertyRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           12.01.22
 */

namespace FastyBird\MiniServer\Models;

use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use Nette;

/**
 * Channel property state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyRepository implements DevicesModuleModels\States\IChannelPropertyRepository
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStateRepository */
	protected RedisDbStoragePluginModels\IStateRepository $stateRepository;

	public function __construct(
		RedisDbStoragePluginModels\StateRepositoryFactory $stateRepositoryFactory
	) {
		$this->stateRepository = $stateRepositoryFactory->create(States\ChannelProperty::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOne(
		DevicesModuleEntities\Channels\Properties\IProperty $property
	): ?States\IChannelProperty {
		/** @var States\IChannelProperty $state */
		$state = $this->stateRepository->findOne($property->getId());

		return $state;
	}

}
