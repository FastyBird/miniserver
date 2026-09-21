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

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Documents;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
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
		protected readonly ApplicationDocuments\DocumentFactory $documentFactory,
		protected readonly IConditionsManager|null $manager = null,
		protected readonly ExchangePublisher\Publisher|null $publisher = null,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	public function create(
		Entities\Conditions\Condition $condition,
		Utils\ArrayHash $values,
	): States\Condition
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Condition state manager is not registered');
		}

		$createdState = $this->manager->create($condition->getId(), $values);

		$this->publishEntity($condition, $createdState);

		return $createdState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	public function update(
		Entities\Conditions\Condition $condition,
		States\Condition $state,
		Utils\ArrayHash $values,
	): States\Condition
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Condition state manager is not registered');
		}

		$updatedState = $this->manager->update($condition->getId(), $values);

		if ($updatedState === false) {
			return $state;
		}

		$this->publishEntity($condition, $updatedState);

		return $updatedState;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 */
	public function delete(
		Entities\Conditions\Condition $condition,
		States\Condition $state,
	): bool
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Condition state manager is not registered');
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
	 * @throws ApplicationExceptions\MalformedInput
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
				Documents\Conditions\Condition::class,
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
