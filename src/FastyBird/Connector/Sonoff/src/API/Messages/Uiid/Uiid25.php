<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use Override;
use function array_merge;

/**
 * UIID25 params entity
 */
final readonly class Uiid25 implements Uuid
{

	use MapsDeviceStates {
		MapsDeviceStates::toStates as toDeviceState;
	}

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
			new ObjectMapper\Rules\IntValue(min: 1, max: 2, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(castBoolLike: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private bool|null $water,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(castBoolLike: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightswitch')]
		private bool|null $lightSwitch,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 1, max: 3, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightmode')]
		private int|null $lightMode,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightRcolor')]
		private int|null $lightRedColor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightGcolor')]
		private int|null $lightGreenColor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightBcolor')]
		private int|null $lightBlueColor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true, castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('lightbright')]
		private int|null $lightBrightness,
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

	public function getState(): int|null
	{
		return $this->state;
	}

	public function getWater(): bool|null
	{
		return $this->water;
	}

	public function getLightSwitch(): bool|null
	{
		return $this->lightSwitch;
	}

	public function getLightMode(): int|null
	{
		return $this->lightMode;
	}

	public function getLightRedColor(): int|null
	{
		return $this->lightRedColor;
	}

	public function getLightGreenColor(): int|null
	{
		return $this->lightGreenColor;
	}

	public function getLightBlueColor(): int|null
	{
		return $this->lightBlueColor;
	}

	public function getLightBrightness(): int|null
	{
		return $this->lightBrightness;
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
			'state' => $this->getState(),
			'water' => $this->getWater(),
			'light_switch' => $this->getLightSwitch(),
			'light_mode' => $this->getLightMode(),
			'light_red_color' => $this->getLightRedColor(),
			'light_green_color' => $this->getLightGreenColor(),
			'light_blue_color' => $this->getLightBlueColor(),
			'light_brightness' => $this->getLightBrightness(),
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
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::OTHER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::STATE->value,
						Types\PropertyParameter::VALUE->value => $this->getState(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::OTHER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::WATER->value,
						Types\PropertyParameter::VALUE->value => $this->getWater(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::OTHER->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::LIGHT_SWITCH->value,
						Types\PropertyParameter::VALUE->value => $this->getLightSwitch(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::LIGHT_MODE->value,
						Types\PropertyParameter::VALUE->value => $this->getLightMode(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::LIGHT_RED_COLOR->value,
						Types\PropertyParameter::VALUE->value => $this->getLightRedColor(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::LIGHT_GREEN_BLUE->value,
						Types\PropertyParameter::VALUE->value => $this->getLightGreenColor(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::LIGHT_BLUE_COLOR->value,
						Types\PropertyParameter::VALUE->value => $this->getLightBlueColor(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
					[
						Types\PropertyParameter::NAME->value => Types\Parameter::LIGHT_BRIGHTNESS->value,
						Types\PropertyParameter::VALUE->value => $this->getLightBrightness(),
						Types\PropertyParameter::GROUP->value => Types\ParameterGroup::LIGHT->value,
					],
				],
			],
		);
	}

}
