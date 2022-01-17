<?php declare(strict_types = 1);

/**
 * TriggerActionRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.2.0
 *
 * @date           12.01.22
 */

namespace FastyBird\MiniServer\Models\States;

use FastyBird\MiniServer\States;
use FastyBird\RedisDbStoragePlugin\Models as RedisDbStoragePluginModels;
use FastyBird\TriggersModule\Entities as TriggersModuleEntities;
use FastyBird\TriggersModule\Models as TriggersModuleModels;
use Nette;

/**
 * Trigger action state repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerActionRepository implements TriggersModuleModels\States\IActionRepository
{

	use Nette\SmartObject;

	/** @var RedisDbStoragePluginModels\IStateRepository */
	private RedisDbStoragePluginModels\IStateRepository $stateRepository;

	public function __construct(
		RedisDbStoragePluginModels\StateRepositoryFactory $stateRepositoryFactory
	) {
		$this->stateRepository = $stateRepositoryFactory->create(States\TriggerAction::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOne(
		TriggersModuleEntities\Actions\IAction $action
	): ?States\ITriggerAction {
		/** @var States\ITriggerAction $state */
		$state = $this->stateRepository->findOne($action->getId());

		return $state;
	}

}
