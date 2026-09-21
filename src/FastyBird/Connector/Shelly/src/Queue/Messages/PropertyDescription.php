<?php declare(strict_types = 1);

/**
 * PropertyDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Queue\Messages;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Device or channel property description message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class PropertyDescription implements Message
{

	/**
	 * @param array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null $format
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\DataType::class)]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private MetadataTypes\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $unit = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\StringValue(notEmpty: true)),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\IntValue()),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\FloatValue()),
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\ArrayOf(
					new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\ArrayOf(
							new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\BoolValue(),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\NullValue(castEmptyString: true),
							]),
							new ObjectMapper\Rules\IntValue(),
						),
						new ObjectMapper\Rules\NullValue(),
					]),
					new ObjectMapper\Rules\IntValue(),
				),
				new ObjectMapper\Rules\IntValue(),
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private array|null $format = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private float|int|string|null $invalid = null,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $queryable = false,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $settable = false,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null
	 */
	public function getFormat(): mixed
	{
		return $this->format;
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->value,
			'unit' => $this->getUnit(),
			'format' => $this->getFormat(),
			'invalid' => $this->getInvalid(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isQueryable(),
		];
	}

}
