<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;
use function array_map;
use function sprintf;
use function strval;

/**
 * Third-party device & Sub-device state definition
 */
final readonly class State implements Message
{

	/**
	 * @param array<States\ToggleState> $toggle
	 */
	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\PowerState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::POWER->value)]
		private States\PowerState|null $power = null,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\MappedObjectValue(States\ToggleState::class),
			key: new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\StringValue(),
				new ObjectMapper\Rules\IntValue(),
			]),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::TOGGLE->value)]
		private array $toggle = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Brightness::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::BRIGHTNESS->value)]
		private States\Brightness|null $brightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\ColorTemperature::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::COLOR_TEMPERATURE->value)]
		private States\ColorTemperature|null $colorTemperature = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\ColorRgb::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::COLOR_RGB->value)]
		private States\ColorRgb|null $colorRgb = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Percentage::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::PERCENTAGE->value)]
		private States\Percentage|null $percentage = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\MotorControl::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_CONTROL->value)]
		private States\MotorControl|null $motorControl = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\MotorReverse::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_REVERSE->value)]
		private States\MotorReverse|null $motorReverse = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\MotorCalibration::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_CALIBRATION->value)]
		private States\MotorCalibration|null $motorCalibration = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Startup::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::STARTUP->value)]
		private States\Startup|null $startup = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Detect::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::DETECT->value)]
		private States\Detect|null $detect = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Humidity::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::HUMIDITY->value)]
		private States\Humidity|null $humidity = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Temperature::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::TEMPERATURE->value)]
		private States\Temperature|null $temperature = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Battery::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::BATTERY->value)]
		private States\Battery|null $battery = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Press::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::PRESS->value)]
		private States\Press|null $press = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Rssi::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::RSSI->value)]
		private States\Rssi|null $rssi = null,
	)
	{
	}

	/**
	 * @return array<string, States\State>
	 */
	public function getStates(): array
	{
		$states = [];

		foreach (Types\Capability::cases() as $capability) {
			switch ($capability) {
				case Types\Capability::POWER:
					if ($this->power !== null) {
						$states[$capability->value] = $this->power;
					}

					break;
				case Types\Capability::TOGGLE:
					foreach ($this->toggle as $name => $state) {
						$states[sprintf('%s_%s', $capability->value, strval($name))] = $state;
					}

					break;
				case Types\Capability::BRIGHTNESS:
					if ($this->brightness !== null) {
						$states[$capability->value] = $this->brightness;
					}

					break;
				case Types\Capability::COLOR_TEMPERATURE:
					if ($this->colorTemperature !== null) {
						$states[$capability->value] = $this->colorTemperature;
					}

					break;
				case Types\Capability::COLOR_RGB:
					if ($this->colorRgb !== null) {
						$states[$capability->value] = $this->colorRgb;
					}

					break;
				case Types\Capability::PERCENTAGE:
					if ($this->percentage !== null) {
						$states[$capability->value] = $this->percentage;
					}

					break;
				case Types\Capability::MOTOR_CONTROL:
					if ($this->motorControl !== null) {
						$states[$capability->value] = $this->motorControl;
					}

					break;
				case Types\Capability::MOTOR_REVERSE:
					if ($this->motorReverse !== null) {
						$states[$capability->value] = $this->motorReverse;
					}

					break;
				case Types\Capability::MOTOR_CALIBRATION:
					if ($this->motorCalibration !== null) {
						$states[$capability->value] = $this->motorCalibration;
					}

					break;
				case Types\Capability::STARTUP:
					if ($this->startup !== null) {
						$states[$capability->value] = $this->startup;
					}

					break;
				case Types\Capability::DETECT:
					if ($this->detect !== null) {
						$states[$capability->value] = $this->detect;
					}

					break;
				case Types\Capability::HUMIDITY:
					if ($this->humidity !== null) {
						$states[$capability->value] = $this->humidity;
					}

					break;
				case Types\Capability::TEMPERATURE:
					if ($this->temperature !== null) {
						$states[$capability->value] = $this->temperature;
					}

					break;
				case Types\Capability::BATTERY:
					if ($this->battery !== null) {
						$states[$capability->value] = $this->battery;
					}

					break;
				case Types\Capability::PRESS:
					if ($this->press !== null) {
						$states[$capability->value] = $this->press;
					}

					break;
				case Types\Capability::RSSI:
					if ($this->rssi !== null) {
						$states[$capability->value] = $this->rssi;
					}

					break;
			}
		}

		return $states;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_map(
			static fn (States\State $state): array => $state->toArray(),
			$this->getStates(),
		);
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();

		foreach (Types\Capability::cases() as $capability) {
			switch ($capability) {
				case Types\Capability::POWER:
					if ($this->power !== null) {
						$json->{$capability->value} = $this->power->toJson();
					}

					break;
				case Types\Capability::TOGGLE:
					if ($this->toggle !== []) {
						$json->{$capability->value} = new stdClass();

						foreach ($this->toggle as $name => $state) {
							$json->{$capability->value}->{$name} = $state->toJson();
						}
					}

					break;
				case Types\Capability::BRIGHTNESS:
					if ($this->brightness !== null) {
						$json->{$capability->value} = $this->brightness->toJson();
					}

					break;
				case Types\Capability::COLOR_TEMPERATURE:
					if ($this->colorTemperature !== null) {
						$json->{$capability->value} = $this->colorTemperature->toJson();
					}

					break;
				case Types\Capability::COLOR_RGB:
					if ($this->colorRgb !== null) {
						$json->{$capability->value} = $this->colorRgb->toJson();
					}

					break;
				case Types\Capability::PERCENTAGE:
					if ($this->percentage !== null) {
						$json->{$capability->value} = $this->percentage->toJson();
					}

					break;
				case Types\Capability::MOTOR_CONTROL:
					if ($this->motorControl !== null) {
						$json->{$capability->value} = $this->motorControl->toJson();
					}

					break;
				case Types\Capability::MOTOR_REVERSE:
					if ($this->motorReverse !== null) {
						$json->{$capability->value} = $this->motorReverse->toJson();
					}

					break;
				case Types\Capability::MOTOR_CALIBRATION:
					if ($this->motorCalibration !== null) {
						$json->{$capability->value} = $this->motorCalibration->toJson();
					}

					break;
				case Types\Capability::STARTUP:
					if ($this->startup !== null) {
						$json->{$capability->value} = $this->startup->toJson();
					}

					break;
				case Types\Capability::DETECT:
					if ($this->detect !== null) {
						$json->{$capability->value} = $this->detect->toJson();
					}

					break;
				case Types\Capability::HUMIDITY:
					if ($this->humidity !== null) {
						$json->{$capability->value} = $this->humidity->toJson();
					}

					break;
				case Types\Capability::TEMPERATURE:
					if ($this->temperature !== null) {
						$json->{$capability->value} = $this->temperature->toJson();
					}

					break;
				case Types\Capability::BATTERY:
					if ($this->battery !== null) {
						$json->{$capability->value} = $this->battery->toJson();
					}

					break;
				case Types\Capability::PRESS:
					if ($this->press !== null) {
						$json->{$capability->value} = $this->press->toJson();
					}

					break;
				case Types\Capability::RSSI:
					if ($this->rssi !== null) {
						$json->{$capability->value} = $this->rssi->toJson();
					}

					break;
			}
		}

		return $json;
	}

}
