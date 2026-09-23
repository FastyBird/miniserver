<?php declare(strict_types = 1);

namespace FastyBird\Core\Formats\Tools;

use BackedEnum;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette\Utils;
use Override;
use TypeError;
use ValueError;
use function count;
use function explode;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_string;
use function str_contains;
use function strval;
use function trim;

/**
 * Combined enum value format item
 */
final class CombinedEnumItem
{

	private MetadataTypes\DataTypeShort|null $dataType;

	private string|int|float|bool $value;

	/**
	 * @param string|array<int, string|int|float|bool|null> $item
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function __construct(string|array $item)
	{
		$dataType = null;

		if (is_string($item)) {
			if (str_contains($item, '|')) {
				$parts = explode('|', $item) + [null, null];

				if (
					$parts[0] === null
					|| MetadataTypes\DataTypeShort::tryFrom(
						Utils\Strings::lower($parts[0]),
					) === null
				) {
					throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
				}

				$dataType = MetadataTypes\DataTypeShort::from(Utils\Strings::lower($parts[0]));
				$this->value = trim(strval($parts[1]));
			} else {
				$this->value = trim($item);
			}
		} elseif (count($item) === 2) {
			if ($item[0] !== null) {
				if (
					!is_string($item[0])
					|| MetadataTypes\DataTypeShort::tryFrom(
						Utils\Strings::lower($item[0]),
					) === null
				) {
					throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
				}

				$dataType = MetadataTypes\DataTypeShort::from(Utils\Strings::lower($item[0]));
			}

			if ($item[1] === null) {
				throw new Exceptions\InvalidArgument('Provided value is not valid for combined enum format');
			}

			$this->value = is_string($item[1]) ? trim($item[1]) : $item[1];

		} elseif (count($item) === 1) {
			$this->value = trim(strval($item[0]));

		} else {
			throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
		}

		if ($dataType !== null && !$this->validateDataType($dataType)) {
			throw new Exceptions\InvalidArgument('Provided format is not valid for combined enum format');
		}

		$this->dataType = $dataType;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): float|bool|int|string|MetadataTypes\Payloads\Payload
	{
		if ($this->dataType === null) {
			return $this->value;
		}

		if (
			$this->dataType === MetadataTypes\DataTypeShort::CHAR
			|| $this->dataType === MetadataTypes\DataTypeShort::UCHAR
			|| $this->dataType === MetadataTypes\DataTypeShort::SHORT
			|| $this->dataType === MetadataTypes\DataTypeShort::USHORT
			|| $this->dataType === MetadataTypes\DataTypeShort::INT
			|| $this->dataType === MetadataTypes\DataTypeShort::UINT
		) {
			return intval($this->value);
		} elseif ($this->dataType === MetadataTypes\DataTypeShort::FLOAT) {
			return floatval($this->value);
		} elseif ($this->dataType === MetadataTypes\DataTypeShort::STRING) {
			return strval($this->value);
		} elseif ($this->dataType === MetadataTypes\DataTypeShort::BOOLEAN) {
			return in_array(Utils\Strings::lower(strval($this->value)), ['true', 't', 'yes', 'y', '1', 'on'], true);
		} elseif ($this->dataType === MetadataTypes\DataTypeShort::BUTTON) {
			if (MetadataTypes\Payloads\Button::tryFrom(Utils\Strings::lower(strval($this->value))) !== null) {
				return MetadataTypes\Payloads\Button::from(Utils\Strings::lower(strval($this->value)));
			}

			throw new Exceptions\InvalidState('Combined enum value is not valid');
		} elseif ($this->dataType === MetadataTypes\DataTypeShort::SWITCH) {
			if (MetadataTypes\Payloads\Switcher::tryFrom(Utils\Strings::lower(strval($this->value))) !== null) {
				return MetadataTypes\Payloads\Switcher::from(Utils\Strings::lower(strval($this->value)));
			}

			throw new Exceptions\InvalidState('Combined enum value is not valid');
		} elseif ($this->dataType === MetadataTypes\DataTypeShort::COVER) {
			if (MetadataTypes\Payloads\Cover::tryFrom(Utils\Strings::lower(strval($this->value))) !== null) {
				return MetadataTypes\Payloads\Cover::from(Utils\Strings::lower(strval($this->value)));
			}

			throw new Exceptions\InvalidState('Combined enum value is not valid');
		}

		throw new Exceptions\InvalidState('Combined enum value is not valid');
	}

	public function getDataType(): MetadataTypes\DataTypeShort|null
	{
		return $this->dataType;
	}

	/**
	 * @return array<int, string|int|float|bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toArray(): array
	{
		$value = $this->getValue();

		$flattenValue = $value instanceof BackedEnum ? strval($value->value) : $value;

		if ($this->dataType === null) {
			return [
				strval($this->value),
			];
		}

		return [
			$this->dataType->value,
			$flattenValue,
		];
	}

	private function validateDataType(MetadataTypes\DataTypeShort $dataType): bool
	{
		return in_array($dataType, [
			MetadataTypes\DataTypeShort::CHAR,
			MetadataTypes\DataTypeShort::UCHAR,
			MetadataTypes\DataTypeShort::SHORT,
			MetadataTypes\DataTypeShort::USHORT,
			MetadataTypes\DataTypeShort::INT,
			MetadataTypes\DataTypeShort::UINT,
			MetadataTypes\DataTypeShort::FLOAT,
			MetadataTypes\DataTypeShort::BOOLEAN,
			MetadataTypes\DataTypeShort::STRING,
			MetadataTypes\DataTypeShort::BUTTON,
			MetadataTypes\DataTypeShort::SWITCH,
			MetadataTypes\DataTypeShort::COVER,
		], true);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function __toString(): string
	{
		return implode('|', $this->toArray());
	}

}
