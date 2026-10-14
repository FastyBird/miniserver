<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Devices\Async;

use DateTimeInterface;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;
use React\Promise;
use Throwable;

/**
 * Asynchronous device property states manager
 */
final readonly class Manager
{

	public function __construct(
		private Models\States\Devices\Manager $fallback,
		private Writer|null $manager = null,
	)
	{
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return Promise\PromiseInterface<States\DeviceProperty>
	 *
	 * @interal
	 */
	public function create(
		Documents\Devices\Properties\Dynamic $property,
		array|Utils\ArrayHash $values,
	): Promise\PromiseInterface
	{
		if ($this->manager === null) {
			try {
				try {
					return Promise\resolve($this->fallback->create($property, $values));
				} catch (Throwable $ex) {
					return Promise\reject($ex);
				}
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		return $this->manager->create($property->getId(), $values);
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return Promise\PromiseInterface<States\DeviceProperty|bool>
	 *
	 * @interal
	 */
	public function update(
		Documents\Devices\Properties\Dynamic $property,
		States\DeviceProperty $state,
		array|Utils\ArrayHash $values,
	): Promise\PromiseInterface
	{
		if ($this->manager === null) {
			try {
				try {
					return Promise\resolve($this->fallback->update($property, $state, $values));
				} catch (Throwable $ex) {
					return Promise\reject($ex);
				}
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		$deferred = new Promise\Deferred();

		$this->manager->update($property->getId(), $values)
			->then(static function (States\DeviceProperty|false $result) use ($deferred, $state): void {
				if ($result === false) {
					$deferred->resolve(false);

					return;
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
					$deferred->resolve($result);

					return;
				}

				$deferred->resolve(false);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @interal
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		if ($this->manager === null) {
			try {
				try {
					return Promise\resolve($this->fallback->delete($id));
				} catch (Throwable $ex) {
					return Promise\reject($ex);
				}
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		return $this->manager->delete($id);
	}

}
