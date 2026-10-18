<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents;

use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Transformers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Override;
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
 */
#[CoreDocuments\Mapping\MappedSuperclass]
abstract class Property implements DevicesDocuments\Document, CoreDocuments\Owner, CoreDocuments\CreatedAt, CoreDocuments\UpdatedAt
{

	use CoreDocuments\HasOwner;
	use CoreDocuments\HasCreatedAt;
	use CoreDocuments\HasUpdatedAt;

	/**
	 * @param string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null $format
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly DevicesTypes\PropertyCategory $category,
		private readonly string $identifier,
		private readonly string|null $name,
		private readonly ValuesTypes\DataType $dataType,
		private readonly string|null $unit = null,
		private readonly string|array|null $format = null,
		private readonly float|int|string|null $invalid = null,
		private readonly int|null $scale = null,
		private readonly int|float|null $step = null,
		private readonly bool|float|int|string|null $default = null,
		private readonly Uuid\UuidInterface|string|null $valueTransformer = null,
		protected readonly Uuid\UuidInterface|null $owner = null,
		protected readonly DateTimeInterface|null $createdAt = null,
		protected readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public static function getType(): string;

	public function getCategory(): DevicesTypes\PropertyCategory
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

	public function getDataType(): ValuesTypes\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFormat(): Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		try {
				return Utilities\Value::normalizeValue(
					$this->default,
					$this->getDataType(),
					$this->getFormat(),
				);
		} catch (ValuesExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function getValueTransformer(): Uuid\UuidInterface|string|null
	{
		if ($this->valueTransformer === null) {
			return null;
		}

		if ($this->valueTransformer instanceof Uuid\UuidInterface) {
			return $this->valueTransformer;
		}

		if (preg_match(Transformers\EquationTransformer::PATTERN, $this->valueTransformer) === 1) {
			if (
				in_array(
					$this->dataType,
					[
						ValuesTypes\DataType::CHAR,
						ValuesTypes\DataType::UCHAR,
						ValuesTypes\DataType::SHORT,
						ValuesTypes\DataType::USHORT,
						ValuesTypes\DataType::INT,
						ValuesTypes\DataType::UINT,
						ValuesTypes\DataType::FLOAT,
					],
					true,
				)
			) {
				return $this->valueTransformer;
			}

			throw new DevicesExceptions\InvalidState('Equation transformer is allowed only for numeric data type');
		}

		return null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
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
			'default' => Utilities\Value::flattenValue($this->getDefault()),
			'value_transformer' => $this->getValueTransformer() !== null ? strval($this->getValueTransformer()) : null,

			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	#[Override]
	public function getSource(): Sources\Source
	{
		return Sources\Module::DEVICES;
	}

	/**
	 * @param string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null $format
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildFormat(
		array|string|null $format,
	): Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null
	{
		if ($format === null) {
			return null;
		}

		if (
			in_array(
				$this->dataType,
				[
					ValuesTypes\DataType::CHAR,
					ValuesTypes\DataType::UCHAR,
					ValuesTypes\DataType::SHORT,
					ValuesTypes\DataType::USHORT,
					ValuesTypes\DataType::INT,
					ValuesTypes\DataType::UINT,
					ValuesTypes\DataType::FLOAT,
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

			if (preg_match(Devices\Entities\Property::VALUE_FORMAT_NUMBER_RANGE, $format) === 1) {
				return new Formats\NumberRange($format);
			}
		} elseif (
			in_array(
				$this->dataType,
				[
					ValuesTypes\DataType::ENUM,
					ValuesTypes\DataType::BUTTON,
					ValuesTypes\DataType::SWITCH,
					ValuesTypes\DataType::COVER,
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

			if (preg_match(Devices\Entities\Property::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new Formats\CombinedEnum($format);
			} elseif (preg_match(Devices\Entities\Property::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new Formats\StringEnum($format);
			}
		}

		return null;
	}

}
