<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use Override;
use function array_merge;

/**
 * UIID15 params entity
 */
final readonly class Uiid15 implements Uuid
{

	use MapsDeviceStates {
		MapsDeviceStates::toStates as toDeviceState;
	}

	public const string DEVICE_TYPE_NORMAL = 'normal';

	public const string DEVICE_TYPE_TEMPERATURE = 'temperature';

	public const string DEVICE_TYPE_HUMIDITY = 'humidity';

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('sledOnline')]
		private string|null $statusLed,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('fwVersion')]
		private string|null $firmwareVersion,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('chipid')]
		private string|null $chipId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $ssid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $rssi,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $switch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF, self::VALUE_STAY]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $startup,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $pulse,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 500, max: 3_600_000, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $pulseWidth,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $mainSwitch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(
				[self::DEVICE_TYPE_NORMAL, self::DEVICE_TYPE_TEMPERATURE, self::DEVICE_TYPE_HUMIDITY],
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $deviceType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(['DHT11', 'DS18B20', 'AM2301', 'MS01', 'errorType']),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $sensorType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private float|null $currentHumidity,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private float|null $currentTemperature,
	)
	{
	}

	public function getStatusLed(): string|null
	{
		return $this->statusLed;
	}

	public function getFirmwareVersion(): string|null
	{
		return $this->firmwareVersion;
	}

	public function getChipId(): string|null
	{
		return $this->chipId;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|null
	{
		return $this->rssi;
	}

	public function getSwitch(): string|null
	{
		return $this->switch;
	}

	public function getStartup(): string|null
	{
		return $this->startup;
	}

	public function getPulse(): string|null
	{
		return $this->pulse;
	}

	public function getPulseWidth(): int|null
	{
		return $this->pulseWidth;
	}

	public function getMainSwitch(): string|null
	{
		return $this->mainSwitch;
	}

	public function getDeviceType(): string|null
	{
		return $this->deviceType;
	}

	public function getSensorType(): string|null
	{
		return $this->sensorType;
	}

	public function getCurrentTemperature(): float|null
	{
		return $this->currentTemperature;
	}

	public function getCurrentHumidity(): float|null
	{
		return $this->currentHumidity;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'status_led' => $this->getStatusLed(),
			'firmware_version' => $this->getFirmwareVersion(),
			'chip_id' => $this->getChipId(),
			'ssid' => $this->getSsid(),
			'rssi' => $this->getRssi(),
			'switch' => $this->getSwitch(),
			'startup' => $this->getStartup(),
			'pulse' => $this->getPulse(),
			'pulse_width' => $this->getPulseWidth(),
			'main_switch' => $this->getMainSwitch(),
			'device_type' => $this->getDeviceType(),
			'sensor_type' => $this->getSensorType(),
			'current_temperature' => $this->getCurrentTemperature(),
			'current_humidity' => $this->getCurrentHumidity(),
		];
	}

	#[Override]
	public function toStates(): array
	{
		return array_merge(
			$this->toDeviceState(),
			[
				Types\ParameterType::CHANNEL->value => [
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::SWITCH->value,
						Types\PropertyParameter::VALUE->value => $this->getSwitch(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::STARTUP->value,
						Types\PropertyParameter::VALUE->value => $this->getStartup(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::PULSE->value,
						Types\PropertyParameter::VALUE->value => $this->getPulse(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::PULSE_WIDTH->value,
						Types\PropertyParameter::VALUE->value => $this->getPulseWidth(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::MAIN_SWITCH->value,
						Types\PropertyParameter::VALUE->value => $this->getMainSwitch(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::DEVICE_TYPE->value,
						Types\PropertyParameter::VALUE->value => $this->getDeviceType(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::SENSOR_TYPE->value,
						Types\PropertyParameter::VALUE->value => $this->getSensorType(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::CURRENT_TEMPERATURE->value,
						Types\PropertyParameter::VALUE->value => $this->getCurrentTemperature(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::CURRENT_HUMIDITY->value,
						Types\PropertyParameter::VALUE->value => $this->getCurrentHumidity(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::THERMOSTAT->value,
					],
				],
			],
		);
	}

}
