<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\States;

use DateTimeInterface;
use FastyBird\Core\Values\Types\Payloads;

/**
 * A change to a property state, as the property state managers' set() and write() take it
 *
 * Each field is either given or absent. A given field is stored, and a null one clears what is
 * stored; an absent field leaves what is stored unchanged. Presence is the field's key, never a
 * marker value, so every value is an ordinary value, `Utilities\Value::NOT_SET`'s 'N/A' included.
 * The fields keep the order in which they were first given.
 */
final readonly class PropertyUpdate
{

	/**
	 * @param array<string, bool|float|int|string|DateTimeInterface|Payloads\Payload|null> $fields
	 */
	private function __construct(private array $fields)
	{
	}

	/**
	 * A change that gives no field yet
	 */
	public static function create(): self
	{
		return new self([]);
	}

	public function withActualValue(bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value): self
	{
		return $this->with(Property::ACTUAL_VALUE_FIELD, $value);
	}

	public function withExpectedValue(bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value): self
	{
		return $this->with(Property::EXPECTED_VALUE_FIELD, $value);
	}

	/**
	 * Pending since the given time, stored as its ATOM string, or not pending (false) or pending (true)
	 */
	public function withPending(bool|DateTimeInterface $pending): self
	{
		return $this->with(
			Property::PENDING_FIELD,
			$pending instanceof DateTimeInterface ? $pending->format(DateTimeInterface::ATOM) : $pending,
		);
	}

	public function withValid(bool $valid): self
	{
		return $this->with(Property::VALID_FIELD, $valid);
	}

	public function isEmpty(): bool
	{
		return $this->fields === [];
	}

	/**
	 * The given fields, keyed by the `Property::*_FIELD` names, in the order they were first given
	 *
	 * @return array<string, bool|float|int|string|DateTimeInterface|Payloads\Payload|null>
	 */
	public function toArray(): array
	{
		return $this->fields;
	}

	private function with(
		string $field,
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value,
	): self
	{
		$fields = $this->fields;
		$fields[$field] = $value;

		return new self($fields);
	}

}
