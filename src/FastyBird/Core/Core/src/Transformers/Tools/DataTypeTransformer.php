<?php declare(strict_types = 1);

namespace FastyBird\Core\Transformers\Tools;

use Contributte\Monolog;
use DateTimeInterface;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use function boolval;
use function in_array;

/**
 * Compatible data type value transformer
 */
final readonly class DataTypeTransformer
{

	public function __construct(
		private bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		private MetadataTypes\DataType $source,
		private MetadataTypes\DataType $destination,
	)
	{
	}

	public function convert(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($this->destination === $this->source) {
			return $this->value;
		}

		if (
			in_array(
				$this->destination,
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
			&& in_array(
				$this->source,
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
			return $this->value;
		}

		if ($this->destination === MetadataTypes\DataType::BOOLEAN) {
			if (
				$this->source === MetadataTypes\DataType::SWITCH
				&& (
					$this->value instanceof MetadataTypes\Payloads\Switcher
					|| $this->value === null
				)
			) {
				return $this->value === MetadataTypes\Payloads\Switcher::ON;
			} elseif (
				$this->source === MetadataTypes\DataType::BUTTON
				&& (
					$this->value instanceof MetadataTypes\Payloads\Button
					|| $this->value === null
				)
			) {
				return $this->value === MetadataTypes\Payloads\Button::PRESSED;
			} elseif (
				$this->source === MetadataTypes\DataType::COVER
				&& (
					$this->value instanceof MetadataTypes\Payloads\Cover
					|| $this->value === null
				)
			) {
				return $this->value === MetadataTypes\Payloads\Cover::OPEN;
			}
		}

		if ($this->source === MetadataTypes\DataType::BOOLEAN) {
			if ($this->destination === MetadataTypes\DataType::SWITCH) {
				return boolval($this->value)
					? MetadataTypes\Payloads\Switcher::ON
					: MetadataTypes\Payloads\Switcher::OFF;
			} elseif ($this->destination === MetadataTypes\DataType::BUTTON) {
				return boolval($this->value)
					? MetadataTypes\Payloads\Button::PRESSED
					: MetadataTypes\Payloads\Button::RELEASED;
			} elseif ($this->destination === MetadataTypes\DataType::COVER) {
				return boolval($this->value)
					? MetadataTypes\Payloads\Cover::OPEN
					: MetadataTypes\Payloads\Cover::CLOSE;
			}
		}

		Monolog\LoggerHolder::getInstance()->getLogger()->warning(
			'Parent property value could not be transformed to mapped property value',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'type' => 'data-type-transformer',
				'source_data_type' => $this->source,
				'destination_data_type' => $this->destination,
				'value' => ToolsUtilities\Value::flattenValue($this->value),
			],
		);

		return $this->value;
	}

}
