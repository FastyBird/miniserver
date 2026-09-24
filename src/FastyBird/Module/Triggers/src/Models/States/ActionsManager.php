<?php declare(strict_types = 1);

/**
 * ActionsManager.php
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
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
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
 * Action states manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionsManager
{

	use Nette\SmartObject;

	public function __construct(
		protected readonly CoreDocuments\DocumentFactory $documentFactory,
		protected readonly IActionsManager|null $manager = null,
		protected readonly ExchangePublisher\Publisher|null $publisher = null,
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
		Entities\Actions\Action $action,
		Utils\ArrayHash $values,
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
	 * @throws TriggersExceptions\NotImplemented
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	public function update(
		Entities\Actions\Action $action,
		States\Action $state,
		Utils\ArrayHash $values,
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
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
			Triggers\Constants::MESSAGE_BUS_ACTION_DOCUMENT_UPDATED_ROUTING_KEY,
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
