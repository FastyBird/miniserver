<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.08.20
 */

namespace FastyBird\Module\Devices\Entities;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Transformers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette\Utils;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;
use function assert;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_replace;
use function strtolower;
use function strval;

#[ORM\MappedSuperclass]
abstract class Property implements Entity,
	EntityParams,
	Entities\EntityCreated, Entities\EntityUpdated
{

	use TEntity;
	use TEntityParams;
	use Entities\HasEntityCreated;
	use Entities\HasEntityUpdated;

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	private const MATCH_MAC_ADDRESS = '/^([0-9a-fA-F][0-9a-fA-F]){1}(:([0-9a-fA-F][0-9a-fA-F])){5,7}$/';

	#[ORM\Id]
	#[ORM\Column(name: 'property_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'property_category',
		type: 'string',
		length: 100,
		nullable: false,
		enumType: DevicesTypes\PropertyCategory::class,
		options: ['default' => DevicesTypes\PropertyCategory::GENERIC],
	)]
	protected DevicesTypes\PropertyCategory $category;

	#[Attribute\Crud(required: true)]
	#[ORM\Column(name: 'property_identifier', type: 'string', length: 50, nullable: false)]
	protected string $identifier;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_name', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $name = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_settable', type: 'boolean', length: 1, nullable: false, options: ['default' => false])]
	protected bool $settable = false;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'property_queryable',
		type: 'boolean',
		length: 1,
		nullable: false,
		options: ['default' => false],
	)]
	protected bool $queryable = false;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(
		name: 'property_data_type',
		type: 'string',
		length: 100,
		nullable: false,
		enumType: ValuesTypes\DataType::class,
		options: ['default' => ValuesTypes\DataType::UNKNOWN],
	)]
	protected ValuesTypes\DataType $dataType;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_unit', type: 'string', length: 20, nullable: true, options: ['default' => null])]
	protected string|null $unit = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_format', type: 'text', nullable: true, options: ['default' => null])]
	protected string|null $format = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_invalid', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $invalid = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_scale', type: 'integer', nullable: true, options: ['default' => null])]
	protected int|null $scale = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_step', type: 'float', nullable: true, options: ['default' => null])]
	protected float|null $step = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_value', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $value = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_default', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $default = null;

	#[Attribute\Crud(writable: true)]
	#[ORM\Column(name: 'property_value_transformer', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $valueTransformer = null;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->category = DevicesTypes\PropertyCategory::GENERIC;
		$this->dataType = ValuesTypes\DataType::UNKNOWN;

		// Static property can not be set or read from device/channel property
		if (static::getType() === DevicesTypes\PropertyType::VARIABLE->value) {
			$this->settable = false;
			$this->queryable = false;
		}
	}

	abstract public static function getType(): string;

	public function getCategory(): DevicesTypes\PropertyCategory
	{
		return $this->category;
	}

	public function setCategory(DevicesTypes\PropertyCategory $category): void
	{
		$this->category = $category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): void
	{
		$this->name = $name;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	public function setSettable(bool $settable): void
	{
		$this->settable = $settable;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function setQueryable(bool $queryable): void
	{
		$this->queryable = $queryable;
	}

	public function getDataType(): ValuesTypes\DataType
	{
		return $this->dataType;
	}

	public function setDataType(ValuesTypes\DataType $dataType): void
	{
		$this->dataType = $dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	public function setUnit(string|null $unit): void
	{
		$this->unit = $unit;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFormat(): Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null
	{
		return $this->buildFormat($this->format);
	}

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|Utils\ArrayHash|null>|array<int, array<int, string|array<int, string|int|float|bool>|Utils\ArrayHash|null>>|null $format
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setFormat(
		array|string|Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format,
	): void
	{
		if (
			$format instanceof Formats\StringEnum
			|| $format instanceof Formats\NumberRange
			|| $format instanceof Formats\CombinedEnum
		) {
			$format = $format->toArray();
		}

		if (is_string($format)) {
			if ($this->buildFormat($format) === null) {
				throw new DevicesExceptions\InvalidArgument('Provided property format is not valid');
			}

			$this->format = $format;

			return;
		} elseif (is_array($format)) {
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
				$plainFormat = implode(':', array_map(static function ($item): string {
					if (is_array($item) || $item instanceof Utils\ArrayHash) {
						return implode(
							'|',
							array_map(
								static fn ($part): string|int|float => is_array($part) ? implode($part) : $part,
								(array) $item,
							),
						);
					}

					return strval($item);
				}, $format));

				if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $plainFormat) === 1) {
					$this->format = $plainFormat;

					return;
				}

				throw new DevicesExceptions\InvalidArgument('Provided property format is not valid');
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
				$plainFormat = implode(',', array_map(static function ($item): string {
					if (is_array($item) || $item instanceof Utils\ArrayHash) {
						return implode(
							':',
							array_map(
								static fn (string|array|int|float|bool|Utils\ArrayHash|null $part): string => is_array(
									$part,
								) || $part instanceof Utils\ArrayHash
									? implode('|', (array) $part)
									: ($part !== null ? strval(
										$part,
									) : ''),
								(array) $item,
							),
						);
					}

					return strval($item);
				}, $format));

				if (
					preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $plainFormat) === 1
					|| preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $plainFormat) === 1
				) {
					$this->format = $plainFormat;

					return;
				}

				throw new DevicesExceptions\InvalidArgument('Provided property format is not valid');
			}
		}

		$this->format = null;
	}

	public function getInvalid(): float|int|string|null
	{
		if ($this->invalid === null) {
			return null;
		}

		if (
			$this->dataType === ValuesTypes\DataType::CHAR
			|| $this->dataType === ValuesTypes\DataType::UCHAR
			|| $this->dataType === ValuesTypes\DataType::SHORT
			|| $this->dataType === ValuesTypes\DataType::USHORT
			|| $this->dataType === ValuesTypes\DataType::INT
			|| $this->dataType === ValuesTypes\DataType::UINT
		) {
			if (is_numeric($this->invalid)) {
				return intval($this->invalid);
			}

			return null;
		} elseif ($this->dataType === ValuesTypes\DataType::FLOAT) {
			if (is_numeric($this->invalid)) {
				return floatval($this->invalid);
			}

			return null;
		}

		return strval($this->invalid);
	}

	public function setInvalid(float|int|string|null $invalid): void
	{
		$this->invalid = $invalid !== null ? strval($invalid) : null;
	}

	public function getScale(): int|null
	{
		return $this->scale;
	}

	public function setScale(int|float|null $scale): void
	{
		$this->scale = $scale !== null ? intval($scale) : null;
	}

	public function getStep(): float|null
	{
		return $this->step;
	}

	public function setStep(float|null $step): void
	{
		$this->step = $step;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		if ($this->value === null) {
			return null;
		}

		try {
			return Utilities\Value::transformToScale(
				Utilities\Value::normalizeValue(
					Utilities\Value::transformDataType(
						$this->value,
						$this->getDataType(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
				$this->getDataType(),
				$this->getScale(),
			);
		} catch (DevicesExceptions\InvalidArgument | ValuesExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setValue(bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value): void
	{
		try {
			$value = Utilities\Value::flattenValue(
				Utilities\Value::normalizeValue(
					Utilities\Value::transformFromScale(
						Utilities\Value::transformDataType(
							Utilities\Value::flattenValue($value),
							$this->getDataType(),
						),
						$this->getDataType(),
						$this->getScale(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
			);
		} catch (ValuesExceptions\InvalidValue) {
			$value = null;
		}

		if ($value !== null && $this->getIdentifier() === DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value) {
			if (!is_string($value)) {
				throw new DevicesExceptions\InvalidArgument(
					'Provided property value is not valid value for IP address property',
				);
			}

			if (preg_match(self::MATCH_IP_ADDRESS, $value) === 1) {
				$this->value = $value;
			} else {
				throw new DevicesExceptions\InvalidArgument(
					'Provided property value is not valid value for IP address property',
				);
			}
		} elseif (
			$value !== null
			&& $this->getIdentifier() === DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value
		) {
			if (!is_string($value)) {
				throw new DevicesExceptions\InvalidArgument(
					'Provided property value is not valid value for MAC address property',
				);
			}

			$value = preg_replace('/[^a-zA-Z0-9]+/', '', $value);
			assert(is_string($value));
			$value = preg_replace('~(..)(?!$)\.?~', '\1:', $value);
			assert(is_string($value));

			if (preg_match(self::MATCH_MAC_ADDRESS, $value) === 1) {
				$this->value = strtolower($value);
			} else {
				throw new DevicesExceptions\InvalidArgument(
					'Provided property value is not valid value for MAC address property',
				);
			}
		} else {
			if (is_bool($value)) {
				$this->value = $value ? '1' : '0';

			} elseif ($value !== null) {
				$this->value = strval($value);

			} else {
				$this->value = null;
			}
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		if ($this->default === null) {
			return null;
		}

		try {
			return Utilities\Value::transformToScale(
				Utilities\Value::normalizeValue(
					Utilities\Value::transformDataType(
						$this->default,
						$this->getDataType(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
				$this->getDataType(),
				$this->getScale(),
			);
		} catch (DevicesExceptions\InvalidArgument | ValuesExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setDefault(
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $default,
	): void
	{
		try {
			$default = Utilities\Value::flattenValue(
				Utilities\Value::normalizeValue(
					Utilities\Value::transformFromScale(
						Utilities\Value::transformDataType(
							Utilities\Value::flattenValue($default),
							$this->getDataType(),
						),
						$this->getDataType(),
						$this->getScale(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
			);
		} catch (ValuesExceptions\InvalidValue) {
			$default = null;
		}

		if (is_bool($default)) {
			$this->default = $default ? '1' : '0';

		} elseif ($default !== null) {
			$this->default = strval($default);

		} else {
			$this->default = null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function getValueTransformer(): Uuid\UuidInterface|string|null
	{
		if ($this->valueTransformer === null) {
			return null;
		}

		if (Uuid\Uuid::isValid($this->valueTransformer)) {
			return Uuid\Uuid::fromString($this->valueTransformer);
		}

		if (preg_match(Metadata\Constants::VALUE_EQUATION_TRANSFORMER, $this->valueTransformer) === 1) {
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

	public function setValueTransformer(
		string|Transformers\EquationTransformer|Uuid\UuidInterface|null $valueTransformer,
	): void
	{
		if ($valueTransformer instanceof Uuid\UuidInterface) {
			$this->valueTransformer = $valueTransformer->toString();

		} elseif ($valueTransformer instanceof Transformers\EquationTransformer) {
			$this->valueTransformer = in_array(
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
			) ? $valueTransformer->getValue() : null;

		} elseif ($valueTransformer !== null) {
			if (Uuid\Uuid::isValid($valueTransformer)) {
				$this->valueTransformer = $valueTransformer;

			} elseif (
				preg_match(Metadata\Constants::VALUE_EQUATION_TRANSFORMER, $valueTransformer) === 1
				&& in_array(
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
				$this->valueTransformer = $valueTransformer;
			}
		} else {
			$this->valueTransformer = null;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function toArray(): array
	{
		$data = [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),
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
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];

		if (static::getType() === DevicesTypes\PropertyType::VARIABLE->value) {
			return array_merge($data, [
				'value' => Utilities\Value::flattenValue($this->getValue()),
			]);
		} elseif (static::getType() === DevicesTypes\PropertyType::DYNAMIC->value) {
			return array_merge($data, [
				'settable' => $this->isSettable(),
				'queryable' => $this->isQueryable(),
			]);
		}

		return $data;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildFormat(
		string|null $format,
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
			if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $format) === 1) {
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
			if (preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new Formats\CombinedEnum($format);
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new Formats\StringEnum($format);
			}
		}

		return null;
	}

	public function getSource(): Sources\Module
	{
		return Sources\Module::DEVICES;
	}

}
