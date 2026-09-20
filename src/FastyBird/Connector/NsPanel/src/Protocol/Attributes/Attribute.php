<?php declare(strict_types = 1);

/**
 * Attribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           04.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use DateTimeInterface;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
use Ramsey\Uuid;
use function implode;
use function in_array;
use function is_numeric;
use function is_string;
use function sprintf;
use function strlen;

/**
 * Represents a NS Panel device capability attribute, the smallest unit of the smart home
 *
 * Attribute is some measurement or state, like battery status or
 * the current temperature. Attributes are contained in capabilities.
 * Each attribute has a unique identifier and a set of properties,
 * like format, min and max values, valid values and others.
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Attribute
{

	use Nette\SmartObject;

	protected bool|float|int|string|null $actualValue = null;

	protected bool|float|int|string|null $expectedValue = null;

	protected DateTimeInterface|bool $pending = false;

	protected bool $valid = true;

	/**
	 * @param array<int, int|string>|null $validValues
	 */
	public function __construct(
		protected readonly Uuid\UuidInterface $id,
		protected readonly Types\Attribute $type,
		protected readonly MetadataTypes\DataType $dataType,
		protected readonly Protocol\Capabilities\Capability $capability,
		protected readonly array|null $validValues = null,
		protected readonly int|null $maxLength = null,
		protected readonly float|null $minValue = null,
		protected readonly float|null $maxValue = null,
		protected readonly float|null $minStep = null,
		protected readonly float|int|bool|string|null $defaultValue = null,
		protected readonly string|null $unit = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): Types\Attribute
	{
		return $this->type;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return array<int, int|string>|null
	 */
	public function getValidValues(): array|null
	{
		return $this->validValues;
	}

	public function getMinValue(): float|null
	{
		return $this->minValue;
	}

	public function getMaxValue(): float|null
	{
		return $this->maxValue;
	}

	public function getMinStep(): float|null
	{
		return $this->minStep;
	}

	public function getMaxLength(): int|null
	{
		return $this->maxLength;
	}

	public function getDefaultValue(): float|bool|int|string|null
	{
		return $this->defaultValue;
	}

	public function getValue(): bool|float|int|string|null
	{
		return $this->expectedValue ?? $this->actualValue;
	}

	public function getActualValue(): bool|float|int|string|null
	{
		return $this->actualValue;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function setActualValue(bool|float|int|string|null $value): void
	{
		if ($value === null) {
			$this->actualValue = $this->defaultValue;

			return;
		}

		if (!$this->validateValue($value)) {
			throw new Exceptions\InvalidArgument('Provided actual value is not valid');
		}

		$this->actualValue = $value;

		if ($this->getExpectedValue() === $this->actualValue) {
			$this->setExpectedValue(null);
		}
	}

	public function getExpectedValue(): bool|float|int|string|null
	{
		return $this->expectedValue;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function setExpectedValue(bool|float|int|string|null $value): void
	{
		if ($value === null) {
			$this->expectedValue = $value;
			$this->setPending(false);

			return;
		}

		if (!$this->validateValue($value)) {
			throw new Exceptions\InvalidArgument('Provided expected value is not valid');
		}

		$this->expectedValue = $value;

		if ($this->getActualValue() === $this->expectedValue) {
			$this->expectedValue = null;
			$this->setPending(false);
		}
	}

	public function setPending(DateTimeInterface|bool $pending): void
	{
		$this->pending = $pending;
	}

	public function isPending(): bool
	{
		return $this->pending instanceof DateTimeInterface || $this->pending === true;
	}

	public function setValid(bool $state): void
	{
		$this->valid = $state;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function getCapability(): Protocol\Capabilities\Capability
	{
		return $this->capability;
	}

	/**
	 * Create a NS Panel representation of this attribute
	 * Used for API device capability attribute state publication
	 *
	 * @return array<string, bool|float|int|string|null>
	 */
	public function toState(): array
	{
		return [
			$this->getType()->value => $this->getValue(),
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	protected function validateValue(bool|float|int|string|null $value): bool
	{
		if ($this->getMinValue() !== null) {
			if (!is_numeric($value)) {
				throw new Exceptions\InvalidArgument(sprintf('Provided value: %s is not valid number', $value));
			}

			if ($value < $this->getMinValue()) {
				throw new Exceptions\InvalidArgument(
					sprintf('Provided value: %f is under of allowed range: %f', $value, $this->getMinValue()),
				);
			}
		}

		if ($this->getMaxValue() !== null) {
			if (!is_numeric($value)) {
				throw new Exceptions\InvalidArgument(sprintf('Provided value: %s is not valid number', $value));
			}

			if ($value > $this->getMaxValue()) {
				throw new Exceptions\InvalidArgument(
					sprintf('Provided value: %f is over of allowed range: %f', $value, $this->getMaxValue()),
				);
			}
		}

		if ($this->getMinStep() !== null) {
			if (!is_numeric($value)) {
				throw new Exceptions\InvalidArgument(sprintf('Provided value: %s is not valid number', $value));
			}

			if ($value / $this->getMinStep() !== 0.0) {
				throw new Exceptions\InvalidArgument(
					sprintf('Provided value: %f is out of allowed steps: %f', $value, $this->getMinStep()),
				);
			}
		}

		if ($this->getValidValues() !== null) {
			if (!is_numeric($value) && !is_string($value)) {
				throw new Exceptions\InvalidArgument(sprintf('Provided value: %s is not valid', $value));
			}

			if (!in_array($value, $this->getValidValues(), true)) {
				throw new Exceptions\InvalidArgument(
					sprintf(
						'Provided value: %s is out of allowed range: %s',
						$value,
						implode(', ', $this->getValidValues()),
					),
				);
			}
		}

		if ($this->getMaxLength() !== null) {
			if (!is_string($value)) {
				throw new Exceptions\InvalidArgument(sprintf('Provided value: %s is not valid string', $value));
			}

			if (strlen($value) > $this->getMaxLength()) {
				throw new Exceptions\InvalidArgument(
					sprintf('Provided value: %s is out of allowed length: %d', $value, $this->getMaxLength()),
				);
			}
		}

		return true;
	}

}
