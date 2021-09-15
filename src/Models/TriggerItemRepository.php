<?php declare(strict_types = 1);

/**
 * TriggerItemRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           15.09.21
 */

namespace FastyBird\MiniServer\Models;

use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use FastyBird\TriggersModule\Models as TriggersModuleModels;
use FastyBird\TriggersModule\States as TriggersModuleStates;
use Nette;
use Ramsey\Uuid;

/**
 * Trigger item state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerItemRepository implements TriggersModuleModels\States\ITriggerItemRepository
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStateRepository */
	private RedisDbStoragePluginModels\IStateRepository $stateRepository;

	public function __construct(
		RedisDbStoragePluginModels\StateRepositoryFactory $stateRepositoryFactory
	) {
		$this->stateRepository = $stateRepositoryFactory->create(States\TriggerItem::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOne(
		Uuid\UuidInterface $id
	): ?TriggersModuleStates\ITriggerItem {
		/** @var TriggersModuleStates\ITriggerItem $state */
		$state = $this->stateRepository->findOne($id);

		return $state;
	}

}
