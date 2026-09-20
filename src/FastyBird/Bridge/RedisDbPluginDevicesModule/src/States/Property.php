<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\States;

use DateTimeInterface;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;
use function is_bool;

/**
 * Property state
 *
 * @package        FastyBird:RedisDbPluginDevicesModuleBridge!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Property extends RedisDbStates\State implements DevicesStates\Property
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\Payloads\Button::class),
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\Payloads\Switcher::class),
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\Payloads\Cover::class),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::ACTUAL_VALUE_FIELD)]
		private readonly bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $actualValue = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\Payloads\Button::class),
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\Payloads\Switcher::class),
			new ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\Payloads\Cover::class),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::EXPECTED_VALUE_FIELD)]
		private readonly bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $expectedValue = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\BoolValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::PENDING_FIELD)]
		private readonly bool|DateTimeInterface $pending = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName(self::VALID_FIELD)]
		private readonly bool $valid = false,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::CREATED_AT_FIELD)]
		private readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::UPDATED_AT_FIELD)]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id);
	}

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

	public function getActualValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		return $this->actualValue;
	}

	public function getExpectedValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		return $this->expectedValue;
	}

	public function isPending(): bool
	{
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function getPending(): bool|DateTimeInterface
	{
		return $this->pending;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public static function getCreateFields(): array
	{
		return [
			0 => 'id',
			self::ACTUAL_VALUE_FIELD => null,
			self::EXPECTED_VALUE_FIELD => null,
			self::PENDING_FIELD => false,
			self::VALID_FIELD => false,
			self::CREATED_AT_FIELD => null,
			self::UPDATED_AT_FIELD => null,
		];
	}

	public static function getUpdateFields(): array
	{
		return [
			self::ACTUAL_VALUE_FIELD,
			self::EXPECTED_VALUE_FIELD,
			self::PENDING_FIELD,
			self::VALID_FIELD,
			self::UPDATED_AT_FIELD,
		];
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			self::ACTUAL_VALUE_FIELD => ToolsUtilities\Value::flattenValue($this->getActualValue()),
			self::EXPECTED_VALUE_FIELD => ToolsUtilities\Value::flattenValue($this->getExpectedValue()),
			self::PENDING_FIELD => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			self::VALID_FIELD => $this->isValid(),
			self::CREATED_AT_FIELD => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			self::UPDATED_AT_FIELD => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		]);
	}

}
