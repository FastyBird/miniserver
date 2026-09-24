<?php declare(strict_types = 1);

/**
 * CloudDeviceDataPoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Queue\Messages;

use FastyBird\Core\Values\Types;
use Orisai\ObjectMapper;

/**
 * Discovered cloud device data point message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class CloudDeviceDataPoint implements Message
{

	/**
	 * @param array<string> $range
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $device,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $code,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\DataType::class)]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private Types\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $unit,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private array $range,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|null $min,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|null $max,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|null $step,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|null $scale,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $queryable,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $settable,
	)
	{
	}

	public function getDevice(): string
	{
		return $this->device;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDataType(): Types\DataType
	{
		return $this->dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	/**
	 * @return array<string>
	 */
	public function getRange(): array
	{
		return $this->range;
	}

	public function getMin(): float|int|null
	{
		return $this->min;
	}

	public function getMax(): float|int|null
	{
		return $this->max;
	}

	public function getStep(): float|int|null
	{
		return $this->step;
	}

	public function getScale(): float|int|null
	{
		return $this->scale;
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
	 * @return array<int, array<int, (string|int|float|null)>>|array<string>|null
	 */
	public function getFormat(): array|null
	{
		if (
			(
				$this->getDataType() === Types\DataType::CHAR
				|| $this->getDataType() === Types\DataType::UCHAR
				|| $this->getDataType() === Types\DataType::SHORT
				|| $this->getDataType() === Types\DataType::USHORT
				|| $this->getDataType() === Types\DataType::INT
				|| $this->getDataType() === Types\DataType::UINT
				|| $this->getDataType() === Types\DataType::FLOAT
			) && (
				$this->getMin() !== null
				|| $this->getMax() !== null
			)
		) {
			return [
				[
					Types\DataTypeShort::FLOAT->value,
					$this->getMin(),
				],
				[
					Types\DataTypeShort::FLOAT->value,
					$this->getMax(),
				],
			];
		} elseif (
			$this->getDataType() === Types\DataType::ENUM
			&& $this->getRange() !== []
		) {
			return $this->getRange();
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'device' => $this->getDevice(),

			'code' => $this->getCode(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->value,
			'unit' => $this->getUnit(),
			'range' => $this->getRange(),
			'min' => $this->getMin(),
			'max' => $this->getMax(),
			'step' => $this->getStep(),
			'scale' => $this->getScale(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isSettable(),
		];
	}

}
