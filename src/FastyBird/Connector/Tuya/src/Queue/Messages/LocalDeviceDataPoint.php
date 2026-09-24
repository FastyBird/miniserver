<?php declare(strict_types = 1);

/**
 * LocalDeviceDataPoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           29.08.22
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
final readonly class LocalDeviceDataPoint implements Message
{

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
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $format,
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

	public function getFormat(): string|null
	{
		return $this->format;
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
			'device' => $this->getDevice(),

			'code' => $this->getCode(),
			'name' => $this->getName(),
			'data_type' => $this->getDataType()->value,
			'unit' => $this->getUnit(),
			'format' => $this->getFormat(),
			'min' => $this->getMin(),
			'max' => $this->getMax(),
			'step' => $this->getStep(),
			'queryable' => $this->isQueryable(),
			'settable' => $this->isSettable(),
		];
	}

}
