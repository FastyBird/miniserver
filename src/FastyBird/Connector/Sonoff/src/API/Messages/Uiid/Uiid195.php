<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use Override;
use function array_merge;

/**
 * UIID195 params entity
 */
final readonly class Uiid195 implements Uuid
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('fwVersion')]
		private string|null $firmwareVersion,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private float|null $temperature,
	)
	{
	}

	public function getFirmwareVersion(): string|null
	{
		return $this->firmwareVersion;
	}

	public function getTemperature(): float|null
	{
		return $this->temperature;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'firmware_version' => $this->getFirmwareVersion(),
			'temperature' => $this->getTemperature(),
		];
	}

	#[Override]
	public function toStates(): array
	{
		return array_merge(
			[
				Types\ParameterType::DEVICE->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::FIRMWARE_VERSION->value,
						Types\PropertyParameter::VALUE->value => $this->getFirmwareVersion(),
					],
				],
			],
			[
				Types\ParameterType::CHANNEL->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::TEMPERATURE->value,
						Types\PropertyParameter::VALUE->value => $this->getTemperature(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::SENSOR->value,
					],
				],
			],
		);
	}

}
