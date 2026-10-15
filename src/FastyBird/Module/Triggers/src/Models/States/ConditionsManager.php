<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Module\Triggers\States;
use Nette\Utils;
use function array_merge;

/**
 * Condition states manager
 */
final readonly class ConditionsManager
{

	public function __construct(
		protected CoreDocuments\DocumentFactory $documentFactory,
		protected ConditionsWriter|null $manager = null,
		protected Publisher\MessagePublisher|null $publisher = null,
	)
	{
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws TriggersExceptions\NotImplemented
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 */
	public function create(
		Entities\Conditions\Condition $condition,
		array|Utils\ArrayHash $values,
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
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @throws TriggersExceptions\NotImplemented
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 */
	public function update(
		Entities\Conditions\Condition $condition,
		States\Condition $state,
		array|Utils\ArrayHash $values,
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
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
			Triggers\Types\RoutingKey::CONDITION_DOCUMENT_UPDATED->value,
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
