<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Transformers;

use Contributte\Monolog;
use DateTimeInterface;
use FastyBird\Core\Values\Types;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use function boolval;
use function in_array;

/**
 * Compatible data type value transformer
 */
final readonly class DataTypeTransformer
{

	public function __construct(
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value,
		private Types\DataType $source,
		private Types\DataType $destination,
	)
	{
	}

	public function convert(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		if ($this->destination === $this->source) {
			return $this->value;
		}

		if (
			in_array(
				$this->destination,
				[
					Types\DataType::CHAR,
					Types\DataType::UCHAR,
					Types\DataType::SHORT,
					Types\DataType::USHORT,
					Types\DataType::INT,
					Types\DataType::UINT,
					Types\DataType::FLOAT,
				],
				true,
			)
			&& in_array(
				$this->source,
				[
					Types\DataType::CHAR,
					Types\DataType::UCHAR,
					Types\DataType::SHORT,
					Types\DataType::USHORT,
					Types\DataType::INT,
					Types\DataType::UINT,
					Types\DataType::FLOAT,
				],
				true,
			)
		) {
			return $this->value;
		}

		if ($this->destination === Types\DataType::BOOLEAN) {
			if (
				$this->source === Types\DataType::SWITCH
				&& (
					$this->value instanceof Payloads\Switcher
					|| $this->value === null
				)
			) {
				return $this->value === Payloads\Switcher::ON;
			} elseif (
				$this->source === Types\DataType::BUTTON
				&& (
					$this->value instanceof Payloads\Button
					|| $this->value === null
				)
			) {
				return $this->value === Payloads\Button::PRESSED;
			} elseif (
				$this->source === Types\DataType::COVER
				&& (
					$this->value instanceof Payloads\Cover
					|| $this->value === null
				)
			) {
				return $this->value === Payloads\Cover::OPEN;
			}
		}

		if ($this->source === Types\DataType::BOOLEAN) {
			if ($this->destination === Types\DataType::SWITCH) {
				return boolval($this->value)
					? Payloads\Switcher::ON
					: Payloads\Switcher::OFF;
			} elseif ($this->destination === Types\DataType::BUTTON) {
				return boolval($this->value)
					? Payloads\Button::PRESSED
					: Payloads\Button::RELEASED;
			} elseif ($this->destination === Types\DataType::COVER) {
				return boolval($this->value)
					? Payloads\Cover::OPEN
					: Payloads\Cover::CLOSE;
			}
		}

		Monolog\LoggerHolder::getInstance()->getLogger()->warning(
			'Parent property value could not be transformed to mapped property value',
			[
				'source' => Sources\Module::DEVICES->value,
				'type' => 'data-type-transformer',
				'source_data_type' => $this->source,
				'destination_data_type' => $this->destination,
				'value' => Utilities\Value::flattenValue($this->value),
			],
		);

		return $this->value;
	}

}
