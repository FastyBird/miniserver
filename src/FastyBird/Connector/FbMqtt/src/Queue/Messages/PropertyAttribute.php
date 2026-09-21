<?php declare(strict_types = 1);

/**
 * PropertyAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\FbMqtt\Queue\Messages;

use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
use Orisai\ObjectMapper;
use TypeError;
use ValueError;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function is_numeric;
use function str_contains;
use function strtolower;

/**
 * Device or channel property attribute
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertyAttribute implements Message
{

	use Nette\SmartObject;

	public const NAME = 'name';

	public const SETTABLE = 'settable';

	public const QUERYABLE = 'queryable';

	public const DATA_TYPE = 'data-type';

	public const FORMAT = 'format';

	public const UNIT = 'unit';

	public const ALLOWED_ATTRIBUTES = [
		self::NAME,
		self::SETTABLE,
		self::QUERYABLE,
		self::DATA_TYPE,
		self::FORMAT,
		self::UNIT,
	];

	public const FORMAT_ALLOWED_PAYLOADS = [
		'rgb',
		'hsv',
	];

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: self::ALLOWED_ATTRIBUTES)]
		private readonly string $attribute,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $value,
	)
	{
	}

	public function getAttribute(): string
	{
		return $this->attribute;
	}

	/**
	 * @return string|array<string>|array<float>|array<null>|bool|MetadataTypes\DataType|null
	 *
	 * @throws Exceptions\ParseMessage
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): string|array|bool|MetadataTypes\DataType|null
	{
		$value = $this->parseValue();

		if ($value === null) {
			return null;
		}

		if (
			$this->attribute === self::SETTABLE
			|| $this->attribute === self::QUERYABLE
		) {
			return $value === FbMqtt\Constants::PAYLOAD_BOOL_TRUE_VALUE;
		}

		return $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\ParseMessage
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toArray(): array
	{
		return [
			'attribute' => $this->getAttribute(),
			'value' => $this->getValue(),
		];
	}

	/**
	 * @return string|array<string>|array<float>|array<null>|MetadataTypes\DataType|null
	 *
	 * @throws Exceptions\ParseMessage
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function parseValue(): MetadataTypes\DataType|string|array|null
	{
		if (
			$this->getAttribute() === self::SETTABLE
			|| $this->getAttribute() === self::QUERYABLE
		) {
			return $this->value === FbMqtt\Constants::PAYLOAD_BOOL_TRUE_VALUE
				? FbMqtt\Constants::PAYLOAD_BOOL_TRUE_VALUE
				: FbMqtt\Constants::PAYLOAD_BOOL_FALSE_VALUE;
		} elseif ($this->getAttribute() === self::NAME) {
			return Helpers\Payload::cleanName($this->value);
		} elseif ($this->getAttribute() === self::DATA_TYPE) {
			if (MetadataTypes\DataType::tryFrom($this->value) === null) {
				throw new Exceptions\ParseMessage('Provided payload is not valid');
			}

			return MetadataTypes\DataType::from($this->value);
		} elseif ($this->getAttribute() === self::FORMAT) {
			if (str_contains($this->value, ':')) {
				[$start, $end] = explode(':', $this->value) + [null, null];

				$start = $start === '' ? null : $start;
				$end = $end === '' ? null : $end;

				if ($start !== null && is_numeric($start) === false) {
					throw new Exceptions\ParseMessage('Provided payload is not valid');
				}

				if ($end !== null && is_numeric($end) === false) {
					throw new Exceptions\ParseMessage('Provided payload is not valid');
				}

				if ($start !== null) {
					$start = (float) $start;
				}

				if ($end !== null) {
					$end = (float) $end;
				}

				if ($start !== null && $end !== null && $start > $end) {
					throw new Exceptions\ParseMessage('Provided payload is not valid');
				}

				return [$start, $end];
			} elseif (str_contains($this->value, ',')) {
				$value = array_filter(
					array_map('trim', explode(',', strtolower($this->value))),
					static fn ($item): bool => $item !== '',
				);

				$value = array_values($value);

				return array_unique($value);
			} elseif ($this->value === FbMqtt\Constants::VALUE_NOT_SET || $this->value === '') {
				return null;
			} elseif (!in_array($this->value, self::FORMAT_ALLOWED_PAYLOADS, true)) {
				throw new Exceptions\ParseMessage('Provided payload is not valid');
			}
		} else {
			return $this->value === FbMqtt\Constants::VALUE_NOT_SET || $this->value === '' ? null : $this->value;
		}

		return null;
	}

}
