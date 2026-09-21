<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           05.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
use Ramsey\Uuid;
use function array_pop;
use function explode;
use function is_array;

/**
 * Represents a NS Panel device capability configuration row
 *
 * Configuration is some definition of the capability, maximum allowed value
 * or capability unit. Configuration rows are contained in capabilities.
 * Each configuration has a unique identifier and a set of properties,
 * like format, min and max values, valid values and others.
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Configuration
{

	use Nette\SmartObject;

	/**
	 * @param float|int|bool|string|array<int, string>|null $value
	 * @param array<int, int|string>|null $validValues
	 */
	public function __construct(
		protected readonly Uuid\UuidInterface $id,
		protected readonly Types\Configuration $type,
		protected readonly MetadataTypes\DataType $dataType,
		protected readonly Protocol\Capabilities\Capability $capability,
		protected readonly float|int|bool|string|array|null $value,
		protected readonly array|null $validValues = [],
		protected readonly int|null $maxLength = null,
		protected readonly float|null $minValue = null,
		protected readonly float|null $maxValue = null,
		protected readonly float|null $minStep = null,
		protected readonly string|null $unit = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): Types\Configuration
	{
		return $this->type;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return float|bool|int|string|array<int, mixed>|null
	 */
	public function getValue(): float|bool|int|string|array|null
	{
		return $this->value;
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

	public function getMaxLength(): int|null
	{
		return $this->maxLength;
	}

	public function getMinStep(): float|null
	{
		return $this->minStep;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	public function getCapability(): Protocol\Capabilities\Capability
	{
		return $this->capability;
	}

	/**
	 * Create a NS Panel representation of this attribute
	 * Used for API device capability attribute state publication
	 *
	 * @return array<mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toDefinition(): array|null
	{
		if ($this->getValue() === null) {
			return null;
		}

		// Split the name by underscore into parts
		$keys = explode('_', $this->getType()->value);

		// Create the array recursively from the keys
		$result = $this->getValue();

		while ($key = array_pop($keys)) {
			$result = [$key => $result];
		}

		if (!is_array($result)) {
			throw new Exceptions\InvalidState('Configuration representation could not be created.');
		}

		return $result;
	}

}
