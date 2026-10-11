<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels;

use DateTimeInterface;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;

/**
 * Channel property states manager
 */
final class Manager
{

	use Nette\SmartObject;

	public function __construct(private readonly IManager|null $manager = null)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws Exceptions\InvalidState
	 *
	 * @interal
	 */
	public function create(
		Documents\Channels\Properties\Dynamic $property,
		Utils\ArrayHash $values,
	): States\ChannelProperty
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Channel properties state manager is not registered');
		}

		try {
			return $this->manager->create($property->getId(), $values);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('State could not be created: ' . $ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws Exceptions\InvalidState
	 *
	 * @interal
	 */
	public function update(
		Documents\Channels\Properties\Dynamic $property,
		States\ChannelProperty $state,
		Utils\ArrayHash $values,
	): States\ChannelProperty|false
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Channel properties state manager is not registered');
		}

		try {
			$result = $this->manager->update($property->getId(), $values);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('State could not be updated: ' . $ex->getMessage(), $ex->getCode(), $ex);
		}

		if ($result === false) {
			return false;
		}

		if (
			[
				States\Property::ACTUAL_VALUE_FIELD => $state->getActualValue(),
				States\Property::EXPECTED_VALUE_FIELD => $state->getExpectedValue(),
				States\Property::PENDING_FIELD => $state->getPending() instanceof DateTimeInterface
					? $state->getPending()->format(DateTimeInterface::ATOM)
					: $state->getPending(),
				States\Property::VALID_FIELD => $state->isValid(),
			] !== [
				States\Property::ACTUAL_VALUE_FIELD => $result->getActualValue(),
				States\Property::EXPECTED_VALUE_FIELD => $result->getExpectedValue(),
				States\Property::PENDING_FIELD => $result->getPending() instanceof DateTimeInterface
					? $result->getPending()->format(DateTimeInterface::ATOM)
					: $result->getPending(),
				States\Property::VALID_FIELD => $result->isValid(),
			]
		) {
			return $result;
		}

		return false;
	}

	/**
	 * @throws Exceptions\NotImplemented
	 * @throws Exceptions\InvalidState
	 *
	 * @interal
	 */
	public function delete(Uuid\UuidInterface $id): bool
	{
		if ($this->manager === null) {
			throw new Exceptions\NotImplemented('Channel properties state manager is not registered');
		}

		try {
			return $this->manager->delete($id);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('State could not be deleted: ' . $ex->getMessage(), $ex->getCode(), $ex);
		}
	}

}
