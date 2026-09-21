<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           27.11.23
 */

namespace FastyBird\Module\Devices\Documents;

use DateTimeInterface;
use FastyBird\Core\Constants\Constants as MetadataConstants;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as ToolsExceptions;
use FastyBird\Core\Formats\Tools as ToolsFormats;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function strval;

/**
 * Channel property document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[ApplicationDocuments\Mapping\MappedSuperclass]
abstract class Property implements Documents\Document, ApplicationDocuments\Owner, ApplicationDocuments\CreatedAt, ApplicationDocuments\UpdatedAt
{

	use ApplicationDocuments\TOwner;
	use ApplicationDocuments\TCreatedAt;
	use ApplicationDocuments\TUpdatedAt;

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\PropertyCategory::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\PropertyCategory::class),
		])]
		private readonly Types\PropertyCategory $category,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\DataType::class),
			new ObjectMapper\Rules\InstanceOfValue(type: MetadataTypes\DataType::class),
		])]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private readonly MetadataTypes\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $unit = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\StringValue(notEmpty: true),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\ArrayOf(
					item: new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\IntValue(),
						new ObjectMapper\Rules\FloatValue(),
						new ObjectMapper\Rules\StringValue(notEmpty: true),
						new ObjectMapper\Rules\ArrayOf(
							item: new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\ArrayEnumValue(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									cases: [MetadataTypes\DataTypeShort::CHAR->value, MetadataTypes\DataTypeShort::UCHAR->value, MetadataTypes\DataTypeShort::SHORT->value, MetadataTypes\DataTypeShort::USHORT->value, MetadataTypes\DataTypeShort::INT->value, MetadataTypes\DataTypeShort::UINT->value, MetadataTypes\DataTypeShort::FLOAT->value, MetadataTypes\DataTypeShort::BOOLEAN->value, MetadataTypes\DataTypeShort::STRING->value, MetadataTypes\DataTypeShort::BUTTON->value, MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\DataTypeShort::COVER->value],
								),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\IntValue(),
								new ObjectMapper\Rules\FloatValue(),
								new ObjectMapper\Rules\BoolValue(),
							]),
							key: new ObjectMapper\Rules\IntValue(unsigned: true),
							minItems: 2,
							maxItems: 2,
						),
						new ObjectMapper\Rules\NullValue(castEmptyString: true),
					]),
					key: new ObjectMapper\Rules\IntValue(unsigned: true),
					minItems: 3,
					maxItems: 3,
				),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\AnyOf([
					new ObjectMapper\Rules\IntValue(),
					new ObjectMapper\Rules\FloatValue(),
					new ObjectMapper\Rules\ArrayOf(
						item: new ObjectMapper\Rules\AnyOf([
							new ObjectMapper\Rules\ArrayEnumValue(
								// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
								cases: [MetadataTypes\DataTypeShort::CHAR->value, MetadataTypes\DataTypeShort::UCHAR->value, MetadataTypes\DataTypeShort::SHORT->value, MetadataTypes\DataTypeShort::USHORT->value, MetadataTypes\DataTypeShort::INT->value, MetadataTypes\DataTypeShort::UINT->value, MetadataTypes\DataTypeShort::FLOAT->value],
							),
							new ObjectMapper\Rules\IntValue(),
							new ObjectMapper\Rules\FloatValue(),
						]),
						key: new ObjectMapper\Rules\IntValue(unsigned: true),
						minItems: 2,
						maxItems: 2,
					),
					new ObjectMapper\Rules\NullValue(castEmptyString: true),
				]),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
				minItems: 2,
				maxItems: 2,
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|array|null $format = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly float|int|string|null $invalid = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|null $scale = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly int|float|null $step = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|null $default = null,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\UuidValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('value_transformer')]
		private readonly Uuid\UuidInterface|string|null $valueTransformer = null,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('created_at')]
		protected readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		protected readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public static function getType(): string;

	public function getCategory(): Types\PropertyCategory
	{
		return $this->category;
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFormat(): ToolsFormats\StringEnum|ToolsFormats\NumberRange|ToolsFormats\CombinedEnum|null
	{
		return $this->buildFormat($this->format);
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	public function getScale(): int|null
	{
		return $this->scale;
	}

	public function getStep(): int|float|null
	{
		return $this->step;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		try {
				return ToolsUtilities\Value::normalizeValue(
					$this->default,
					$this->getDataType(),
					$this->getFormat(),
				);
		} catch (ToolsExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getValueTransformer(): Uuid\UuidInterface|string|null
	{
		if ($this->valueTransformer === null) {
			return null;
		}

		if ($this->valueTransformer instanceof Uuid\UuidInterface) {
			return $this->valueTransformer;
		}

		if (preg_match(MetadataConstants::VALUE_EQUATION_TRANSFORMER, $this->valueTransformer) === 1) {
			if (
				in_array(
					$this->dataType,
					[
						MetadataTypes\DataType::CHAR,
						MetadataTypes\DataType::UCHAR,
						MetadataTypes\DataType::SHORT,
						MetadataTypes\DataType::USHORT,
						MetadataTypes\DataType::INT,
						MetadataTypes\DataType::UINT,
						MetadataTypes\DataType::FLOAT,
					],
					true,
				)
			) {
				return $this->valueTransformer;
			}

			throw new Exceptions\InvalidState('Equation transformer is allowed only for numeric data type');
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),
			'source' => $this->getSource()->value,
			'category' => $this->getCategory()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->value,
			'unit' => $this->getUnit(),
			'format' => $this->getFormat()?->getValue(),
			'invalid' => $this->getInvalid(),
			'scale' => $this->getScale(),
			'step' => $this->getStep(),
			'default' => ToolsUtilities\Value::flattenValue($this->getDefault()),
			'value_transformer' => $this->getValueTransformer() !== null ? strval($this->getValueTransformer()) : null,

			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::DEVICES;
	}

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildFormat(
		array|string|null $format,
	): ToolsFormats\StringEnum|ToolsFormats\NumberRange|ToolsFormats\CombinedEnum|null
	{
		if ($format === null) {
			return null;
		}

		if (
			in_array(
				$this->dataType,
				[
					MetadataTypes\DataType::CHAR,
					MetadataTypes\DataType::UCHAR,
					MetadataTypes\DataType::SHORT,
					MetadataTypes\DataType::USHORT,
					MetadataTypes\DataType::INT,
					MetadataTypes\DataType::UINT,
					MetadataTypes\DataType::FLOAT,
				],
				true,
			)
		) {
			if (is_array($format)) {
				$format = implode(':', array_map(static function ($item): string {
					if (is_array($item)) {
						return implode(
							'|',
							array_map(
								static fn ($part): bool|int|float|string|null => is_array($part) ? implode(
									$part,
								) : $part,
								$item,
							),
						);
					}

					return strval($item);
				}, $format));
			}

			if (preg_match(MetadataConstants::VALUE_FORMAT_NUMBER_RANGE, $format) === 1) {
				return new ToolsFormats\NumberRange($format);
			}
		} elseif (
			in_array(
				$this->dataType,
				[
					MetadataTypes\DataType::ENUM,
					MetadataTypes\DataType::BUTTON,
					MetadataTypes\DataType::SWITCH,
					MetadataTypes\DataType::COVER,
				],
				true,
			)
		) {
			if (is_array($format)) {
				$format = implode(',', array_map(static function ($item): string {
					if (is_array($item)) {
						return (is_array($item[0]) ? implode('|', $item[0]) : $item[0])
							. ':' . (is_array($item[1]) ? implode('|', $item[1]) : ($item[1] ?? ''))
							. (
							isset($item[2])
								? ':' . (is_array($item[2]) ? implode('|', $item[2]) : $item[2])
								: ''
							);
					}

					return strval($item);
				}, $format));
			}

			if (preg_match(MetadataConstants::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new ToolsFormats\CombinedEnum($format);
			} elseif (preg_match(MetadataConstants::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new ToolsFormats\StringEnum($format);
			}
		}

		return null;
	}

}
