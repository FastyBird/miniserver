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
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Formats as ToolsFormats;
use FastyBird\Core\Tools\Transformers as ToolsTransformers;
use FastyBird\Core\Tools\Utilities as ToolsUtilities;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use IPub\DoctrineTimestampable;
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

abstract class Property implements Entity,
	EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use TEntity;
	use TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	private const MATCH_MAC_ADDRESS = '/^([0-9a-fA-F][0-9a-fA-F]){1}(:([0-9a-fA-F][0-9a-fA-F])){5,7}$/';

	#[ORM\Id]
	#[ORM\Column(name: 'property_id', type: Uuid\Doctrine\UuidBinaryType::NAME)]
	#[ORM\CustomIdGenerator(class: Uuid\Doctrine\UuidGenerator::class)]
	protected Uuid\UuidInterface $id;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(
		name: 'property_category',
		type: 'string',
		length: 100,
		nullable: false,
		enumType: Types\PropertyCategory::class,
		options: ['default' => Types\PropertyCategory::GENERIC],
	)]
	protected Types\PropertyCategory $category;

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'property_identifier', type: 'string', length: 50, nullable: false)]
	protected string $identifier;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_name', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $name = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_settable', type: 'boolean', length: 1, nullable: false, options: ['default' => false])]
	protected bool $settable = false;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(
		name: 'property_queryable',
		type: 'boolean',
		length: 1,
		nullable: false,
		options: ['default' => false],
	)]
	protected bool $queryable = false;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(
		name: 'property_data_type',
		type: 'string',
		length: 100,
		nullable: false,
		enumType: MetadataTypes\DataType::class,
		options: ['default' => MetadataTypes\DataType::UNKNOWN],
	)]
	protected MetadataTypes\DataType $dataType;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_unit', type: 'string', length: 20, nullable: true, options: ['default' => null])]
	protected string|null $unit = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_format', type: 'text', nullable: true, options: ['default' => null])]
	protected string|null $format = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_invalid', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $invalid = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_scale', type: 'integer', nullable: true, options: ['default' => null])]
	protected int|null $scale = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_step', type: 'float', nullable: true, options: ['default' => null])]
	protected float|null $step = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_value', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $value = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_default', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $default = null;

	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'property_value_transformer', type: 'string', nullable: true, options: ['default' => null])]
	protected string|null $valueTransformer = null;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->category = Types\PropertyCategory::GENERIC;
		$this->dataType = MetadataTypes\DataType::UNKNOWN;

		// Static property can not be set or read from device/channel property
		if (static::getType() === Types\PropertyType::VARIABLE->value) {
			$this->settable = false;
			$this->queryable = false;
		}
	}

	abstract public static function getType(): string;

	public function getCategory(): Types\PropertyCategory
	{
		return $this->category;
	}

	public function setCategory(Types\PropertyCategory $category): void
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

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function setDataType(MetadataTypes\DataType $dataType): void
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
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFormat(): ToolsFormats\StringEnum|ToolsFormats\NumberRange|ToolsFormats\CombinedEnum|null
	{
		return $this->buildFormat($this->format);
	}

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|Utils\ArrayHash|null>|array<int, array<int, string|array<int, string|int|float|bool>|Utils\ArrayHash|null>>|null $format
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setFormat(
		array|string|ToolsFormats\StringEnum|ToolsFormats\NumberRange|ToolsFormats\CombinedEnum|null $format,
	): void
	{
		if (
			$format instanceof ToolsFormats\StringEnum
			|| $format instanceof ToolsFormats\NumberRange
			|| $format instanceof ToolsFormats\CombinedEnum
		) {
			$format = $format->toArray();
		}

		if (is_string($format)) {
			if ($this->buildFormat($format) === null) {
				throw new Exceptions\InvalidArgument('Provided property format is not valid');
			}

			$this->format = $format;

			return;
		} elseif (is_array($format)) {
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

				throw new Exceptions\InvalidArgument('Provided property format is not valid');
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

				throw new Exceptions\InvalidArgument('Provided property format is not valid');
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
			$this->dataType === MetadataTypes\DataType::CHAR
			|| $this->dataType === MetadataTypes\DataType::UCHAR
			|| $this->dataType === MetadataTypes\DataType::SHORT
			|| $this->dataType === MetadataTypes\DataType::USHORT
			|| $this->dataType === MetadataTypes\DataType::INT
			|| $this->dataType === MetadataTypes\DataType::UINT
		) {
			if (is_numeric($this->invalid)) {
				return intval($this->invalid);
			}

			return null;
		} elseif ($this->dataType === MetadataTypes\DataType::FLOAT) {
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
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($this->value === null) {
			return null;
		}

		try {
			return ToolsUtilities\Value::transformToScale(
				ToolsUtilities\Value::normalizeValue(
					ToolsUtilities\Value::transformDataType(
						$this->value,
						$this->getDataType(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
				$this->getDataType(),
				$this->getScale(),
			);
		} catch (Exceptions\InvalidArgument | ToolsExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setValue(bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value): void
	{
		try {
			$value = ToolsUtilities\Value::flattenValue(
				ToolsUtilities\Value::normalizeValue(
					ToolsUtilities\Value::transformFromScale(
						ToolsUtilities\Value::transformDataType(
							ToolsUtilities\Value::flattenValue($value),
							$this->getDataType(),
						),
						$this->getDataType(),
						$this->getScale(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
			);
		} catch (ToolsExceptions\InvalidValue) {
			$value = null;
		}

		if ($value !== null && $this->getIdentifier() === Types\DevicePropertyIdentifier::IP_ADDRESS->value) {
			if (!is_string($value)) {
				throw new Exceptions\InvalidArgument(
					'Provided property value is not valid value for IP address property',
				);
			}

			if (preg_match(self::MATCH_IP_ADDRESS, $value) === 1) {
				$this->value = $value;
			} else {
				throw new Exceptions\InvalidArgument(
					'Provided property value is not valid value for IP address property',
				);
			}
		} elseif (
			$value !== null
			&& $this->getIdentifier() === Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value
		) {
			if (!is_string($value)) {
				throw new Exceptions\InvalidArgument(
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
				throw new Exceptions\InvalidArgument(
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
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($this->default === null) {
			return null;
		}

		try {
			return ToolsUtilities\Value::transformToScale(
				ToolsUtilities\Value::normalizeValue(
					ToolsUtilities\Value::transformDataType(
						$this->default,
						$this->getDataType(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
				$this->getDataType(),
				$this->getScale(),
			);
		} catch (Exceptions\InvalidArgument | ToolsExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setDefault(
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $default,
	): void
	{
		try {
			$default = ToolsUtilities\Value::flattenValue(
				ToolsUtilities\Value::normalizeValue(
					ToolsUtilities\Value::transformFromScale(
						ToolsUtilities\Value::transformDataType(
							ToolsUtilities\Value::flattenValue($default),
							$this->getDataType(),
						),
						$this->getDataType(),
						$this->getScale(),
					),
					$this->getDataType(),
					$this->getFormat(),
				),
			);
		} catch (ToolsExceptions\InvalidValue) {
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
	 * @throws Exceptions\InvalidState
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

	public function setValueTransformer(
		string|ToolsTransformers\EquationTransformer|Uuid\UuidInterface|null $valueTransformer,
	): void
	{
		if ($valueTransformer instanceof Uuid\UuidInterface) {
			$this->valueTransformer = $valueTransformer->toString();

		} elseif ($valueTransformer instanceof ToolsTransformers\EquationTransformer) {
			$this->valueTransformer = in_array(
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
			) ? $valueTransformer->getValue() : null;

		} elseif ($valueTransformer !== null) {
			if (Uuid\Uuid::isValid($valueTransformer)) {
				$this->valueTransformer = $valueTransformer;

			} elseif (
				preg_match(Metadata\Constants::VALUE_EQUATION_TRANSFORMER, $valueTransformer) === 1
				&& in_array(
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
				$this->valueTransformer = $valueTransformer;
			}
		} else {
			$this->valueTransformer = null;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
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
			'default' => ToolsUtilities\Value::flattenValue($this->getDefault()),
			'value_transformer' => $this->getValueTransformer() !== null ? strval($this->getValueTransformer()) : null,
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];

		if (static::getType() === Types\PropertyType::VARIABLE->value) {
			return array_merge($data, [
				'value' => ToolsUtilities\Value::flattenValue($this->getValue()),
			]);
		} elseif (static::getType() === Types\PropertyType::DYNAMIC->value) {
			return array_merge($data, [
				'settable' => $this->isSettable(),
				'queryable' => $this->isQueryable(),
			]);
		}

		return $data;
	}

	/**
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildFormat(
		string|null $format,
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
			if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $format) === 1) {
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
			if (preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new ToolsFormats\CombinedEnum($format);
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new ToolsFormats\StringEnum($format);
			}
		}

		return null;
	}

	public function getSource(): MetadataTypes\Sources\Module
	{
		return MetadataTypes\Sources\Module::DEVICES;
	}

}
