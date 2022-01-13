<?php declare(strict_types = 1);

/**
 * TriggerConditionRepository.php
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

use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use FastyBird\TriggersModule\Entities as TriggersModuleEntities;
use FastyBird\TriggersModule\Models as TriggersModuleModels;
use Nette;

/**
 * Trigger condition state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerConditionRepository implements TriggersModuleModels\States\IConditionRepository
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStateRepository */
	private RedisDbStoragePluginModels\IStateRepository $stateRepository;

	public function __construct(
		RedisDbStoragePluginModels\StateRepositoryFactory $stateRepositoryFactory
	) {
		$this->stateRepository = $stateRepositoryFactory->create(States\TriggerCondition::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOne(
		TriggersModuleEntities\Conditions\ICondition $condition
	): ?States\ITriggerCondition {
		/** @var States\ITriggerCondition $state */
		$state = $this->stateRepository->findOne($condition->getId());

		return $state;
	}

}
