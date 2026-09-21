<?php declare(strict_types = 1);

/**
 * SensorRange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Parsed sensor range message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SensorRange implements API\Messages\Message
{

	/**
	 * @param array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null $format
	 */
	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\DataType::class)]
		#[ObjectMapper\Modifiers\FieldName('data_type')]
		private MetadataTypes\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\StringValue(notEmpty: true)),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\IntValue()),
			new ObjectMapper\Rules\ArrayOf(new ObjectMapper\Rules\FloatValue()),
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\ArrayOf(
					new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\ArrayOf(
							new ObjectMapper\Rules\AnyOf([
								new ObjectMapper\Rules\BoolValue(),
								new ObjectMapper\Rules\StringValue(notEmpty: true),
								new ObjectMapper\Rules\NullValue(castEmptyString: true),
							]),
							new ObjectMapper\Rules\IntValue(),
						),
						new ObjectMapper\Rules\NullValue(),
					]),
					new ObjectMapper\Rules\IntValue(),
				),
				new ObjectMapper\Rules\IntValue(),
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private array|null $format,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|string|null $invalid,
	)
	{
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null
	 */
	public function getFormat(): array|null
	{
		return $this->format;
	}

	public function getInvalid(): float|int|string|null
	{
		return $this->invalid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'data_type' => $this->dataType->value,
			'format' => $this->getFormat(),
			'invalid' => $this->getInvalid(),
		];
	}

}
