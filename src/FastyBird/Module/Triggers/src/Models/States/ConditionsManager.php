<?php declare(strict_types = 1);

/**
 * ConditionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\States;
use Nette;
use Nette\Utils;
use function array_merge;

/**
 * Condition states manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionsManager
{

	use Nette\SmartObject;

	public function __construct(
		protected readonly CoreDocuments\DocumentFactory $documentFactory,
		protected readonly IConditionsManager|null $manager = null,
		protected readonly Publisher\MessagePublisher|null $publisher = null,
	)
	{
	}

	/**
	 * @throws TriggersExceptions\NotImplemented
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	public function create(
		Entities\Conditions\Condition $condition,
		Utils\ArrayHash $values,
	): States\Condition
	{
		if ($this->manager === null) {
			throw new TriggersExceptions\NotImplemented('Condition state manager is not registered');
		}

		$createdState = $this->manager->create($condition->getId(), $values);

		$this->publishEntity($condition, $createdState);

		return $createdState;
	}

	/**
	 * @throws TriggersExceptions\NotImplemented
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	public function update(
		Entities\Conditions\Condition $condition,
		States\Condition $state,
		Utils\ArrayHash $values,
	): States\Condition
	{
		if ($this->manager === null) {
			throw new TriggersExceptions\NotImplemented('Condition state manager is not registered');
		}

		$updatedState = $this->manager->update($condition->getId(), $values);

		if ($updatedState === false) {
			return $state;
		}

		$this->publishEntity($condition, $updatedState);

		return $updatedState;
	}

	/**
	 * @throws TriggersExceptions\NotImplemented
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	public function delete(
		Entities\Conditions\Condition $condition,
		States\Condition $state,
	): bool
	{
		if ($this->manager === null) {
			throw new TriggersExceptions\NotImplemented('Condition state manager is not registered');
		}

		$result = $this->manager->delete($condition->getId());

		if ($result) {
			$this->publishEntity($condition, null);
		}

		return $result;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	private function publishEntity(
		Entities\Conditions\Condition $condition,
		States\Condition|null $state,
	): void
	{
		if ($this->publisher === null) {
			return;
		}

		$this->publisher->publish(
			$condition->getSource(),
			Triggers\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_UPDATED_ROUTING_KEY,
			$this->documentFactory->create(
				TriggersDocuments\Conditions\Condition::class,
				array_merge(
					$condition->toArray(),
					[
						'is_fulfilled' => !($state === null) && $state->isFulfilled(),
					],
				),
			),
		);
	}

}
