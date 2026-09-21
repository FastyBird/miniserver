<?php declare(strict_types = 1);

/**
 * BasicCharacteristic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 * @since          1.0.0
 *
 * @date           25.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Characteristics;

use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Basic characteristic mapping configuration
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class BasicCharacteristic implements Characteristic
{

	/**
	 * @param array<HomeKitTypes\CharacteristicType> $require
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 */
	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: HomeKitTypes\CharacteristicType::class)]
		private HomeKitTypes\CharacteristicType $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $channel = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $property = null,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $nullable = false,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\BackedEnumValue(class: HomeKitTypes\CharacteristicType::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $require = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\StringValue(notEmpty: true),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\ArrayOf(
					item: new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\IntValue(),
						new ObjectMapper\Rules\FloatValue(),
						new ObjectMapper\Rules\StringValue(notEmpty: true),
						new ObjectMapper\Rules\ArrayOf(
							item: new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\ArrayEnumValue(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									cases: [MetadataTypes\DataTypeShort::CHAR->value, MetadataTypes\DataTypeShort::UCHAR->value, MetadataTypes\DataTypeShort::SHORT->value, MetadataTypes\DataTypeShort::USHORT->value, MetadataTypes\DataTypeShort::INT->value, MetadataTypes\DataTypeShort::UINT->value, MetadataTypes\DataTypeShort::FLOAT->value, MetadataTypes\DataTypeShort::BOOLEAN->value, MetadataTypes\DataTypeShort::STRING->value, MetadataTypes\DataTypeShort::BUTTON->value, MetadataTypes\DataTypeShort::SWITCH->value, MetadataTypes\DataTypeShort::COVER->value],
								),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\IntValue(),
								new ObjectMapper\Rules\FloatValue(),
								new ObjectMapper\Rules\BoolValue(),
							]),
							key: new ObjectMapper\Rules\IntValue(unsigned: true),
							minItems: 2,
							maxItems: 2,
						),
						new ObjectMapper\Rules\NullValue(castEmptyString: true),
					]),
					key: new ObjectMapper\Rules\IntValue(unsigned: true),
					minItems: 3,
					maxItems: 3,
				),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				item: new ObjectMapper\Rules\AnyOf([
					new ObjectMapper\Rules\IntValue(),
					new ObjectMapper\Rules\FloatValue(),
					new ObjectMapper\Rules\ArrayOf(
						item: new ObjectMapper\Rules\AnyOf([
							new ObjectMapper\Rules\ArrayEnumValue(
								// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
								cases: [MetadataTypes\DataTypeShort::CHAR->value, MetadataTypes\DataTypeShort::UCHAR->value, MetadataTypes\DataTypeShort::SHORT->value, MetadataTypes\DataTypeShort::USHORT->value, MetadataTypes\DataTypeShort::INT->value, MetadataTypes\DataTypeShort::UINT->value, MetadataTypes\DataTypeShort::FLOAT->value],
							),
							new ObjectMapper\Rules\IntValue(),
							new ObjectMapper\Rules\FloatValue(),
						]),
						key: new ObjectMapper\Rules\IntValue(unsigned: true),
						minItems: 2,
						maxItems: 2,
					),
					new ObjectMapper\Rules\NullValue(castEmptyString: true),
				]),
				key: new ObjectMapper\Rules\IntValue(unsigned: true),
				minItems: 2,
				maxItems: 2,
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|array|null $format = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private bool|float|int|string|null $value = null,
	)
	{
	}

	public function getType(): HomeKitTypes\CharacteristicType
	{
		return $this->type;
	}

	public function getChannel(): string|null
	{
		return $this->channel;
	}

	public function getProperty(): string|null
	{
		return $this->property;
	}

	public function isNullable(): bool
	{
		return $this->nullable;
	}

	/**
	 * @return array<HomeKitTypes\CharacteristicType>
	 */
	public function getRequire(): array
	{
		return $this->require;
	}

	/**
	 * @return string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null
	 */
	public function getFormat(): string|array|null
	{
		return $this->format;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

}
