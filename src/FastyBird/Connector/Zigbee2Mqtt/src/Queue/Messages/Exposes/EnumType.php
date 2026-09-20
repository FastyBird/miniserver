<?php declare(strict_types = 1);

/**
 * EnumType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Orisai\ObjectMapper;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Enum type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EnumType extends Type
{

	/**
	 * @param array<int, string> $values
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Types\ExposeType::ENUM->value])]
		private readonly string $type,
		string $name,
		string $label,
		string $property,
		int $access,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private readonly array $values = [],
	)
	{
		parent::__construct($name, $label, $property, $access);
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getType(): Types\ExposeType
	{
		return Types\ExposeType::from($this->type);
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return MetadataTypes\DataType::ENUM;
	}

	/**
	 * @return array<int, string>
	 */
	public function getValues(): array
	{
		return $this->values;
	}

	public function getFormat(): array|null
	{
		/** @var array<int, string> $values */
		$values = $this->getValues();

		return $values === [] ? null : $values;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'values' => $this->getValues(),
			],
		);
	}

}
