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
 * Action states manager
 */
final readonly class ActionsManager
{

	public function __construct(
		protected CoreDocuments\DocumentFactory $documentFactory,
		protected ActionsWriter|null $manager = null,
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
		Entities\Actions\Action $action,
		array|Utils\ArrayHash $values,
	): States\Action
	{
		if ($this->manager === null) {
			throw new TriggersExceptions\NotImplemented('Action state manager is not registered');
		}

		$createdState = $this->manager->create($action->getId(), $values);

		$this->publishEntity($action, $createdState);

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
		Entities\Actions\Action $action,
		States\Action $state,
		array|Utils\ArrayHash $values,
	): States\Action
	{
		if ($this->manager === null) {
			throw new TriggersExceptions\NotImplemented('Action state manager is not registered');
		}

		$updatedState = $this->manager->update($action->getId(), $values);

		if ($updatedState === false) {
			return $state;
		}

		$this->publishEntity($action, $updatedState);

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
		Entities\Actions\Action $action,
		States\Action $state,
	): bool
	{
		if ($this->manager === null) {
			throw new TriggersExceptions\NotImplemented('Action state manager is not registered');
		}

		$result = $this->manager->delete($action->getId());

		if ($result) {
			$this->publishEntity($action, null);
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
		Entities\Actions\Action $action,
		States\Action|null $state,
	): void
	{
		if ($this->publisher === null) {
			return;
		}

		$this->publisher->publish(
			$action->getSource(),
			Triggers\Types\RoutingKey::ACTION_DOCUMENT_UPDATED->value,
			$this->documentFactory->create(
				TriggersDocuments\Actions\Action::class,
				array_merge(
					$action->toArray(),
					[
						'is_triggered' => !($state === null) && $state->isTriggered(),
					],
				),
			),
		);
	}

}
