<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Lan;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * Device info entity
 */
final readonly class DeviceEventData implements API\Messages\Message
{

	private const string VALUE_ON = 'on';

	private const string VALUE_OFF = 'off';

	private const string VALUE_STAY = 'stay';

	/**
	 * @param array<SwitchState> $switchesStates
	 */
	public function __construct(
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
		private string|null $ssid = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $rssi = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $bssid = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $signalStrength = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('sledOnline')]
		private string|null $statusLed = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $switch = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF, self::VALUE_STAY]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $startup = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue([self::VALUE_ON, self::VALUE_OFF]),
			new ObjectMapper\Rules\NullValue(),
		])]
		private string|null $pulse = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(500, 3_599_500, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $pulseWidth = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(0, 255, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $mode = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(0, 100, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|null $brightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(0, 254, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('brightmin')]
		private int|null $minimumBrightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(1, 255, true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('brightmax')]
		private int|null $maximumBrightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('subDevId')]
		private string|null $subDeviceId = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: SwitchState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('switches')]
		private array $switchesStates = [],
	)
	{
	}

	public function isSwitch(): bool
	{
		return $this->getSwitch() !== null && $this->getBrightness() === null;
	}

	public function isSwitches(): bool
	{
		return $this->getSwitchesStates() !== [];
	}

	public function isLight(): bool
	{
		return $this->getBrightness() !== null;
	}

	public function getSsid(): string|null
	{
		return $this->ssid;
	}

	public function getRssi(): int|null
	{
		return $this->rssi ?? $this->signalStrength;
	}

	public function getFirmwareVersion(): string|null
	{
		return $this->firmwareVersion;
	}

	public function getBssid(): string|null
	{
		return $this->bssid;
	}

	public function getStatusLed(): bool|null
	{
		return $this->statusLed === null ? null : $this->statusLed === self::VALUE_ON;
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

	public function getMode(): int|null
	{
		return $this->mode;
	}

	public function getBrightness(): int|null
	{
		return $this->brightness;
	}

	public function getMinimumBrightness(): int|null
	{
		return $this->minimumBrightness;
	}

	public function getMaximumBrightness(): int|null
	{
		return $this->maximumBrightness;
	}

	public function getSubDeviceId(): string|null
	{
		return $this->subDeviceId;
	}

	/**
	 * @return array<SwitchState>
	 */
	public function getSwitchesStates(): array
	{
		return $this->switchesStates;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'rssi' => $this->getRssi(),
			'ssid' => $this->getSsid(),
			'bssid' => $this->getBssid(),
			'firmware_version' => $this->getFirmwareVersion(),
			'status_led' => $this->getStatusLed(),
			// Device specific
			'switch' => $this->getSwitch(),
			'startup' => $this->getStartup(),
			'pulse' => $this->getPulse(),
			'pulse_width' => $this->getPulseWidth(),
			'brightness' => $this->getBrightness(),
			'minimum_brightness' => $this->getMinimumBrightness(),
			'maximum_brightness' => $this->getMaximumBrightness(),
			'mode' => $this->getMode(),
			'sub_device_id' => $this->getSubDeviceId(),
			'switches' => [
				'states' => array_map(
					static fn (SwitchState $state): array => $state->toArray(),
					$this->getSwitchesStates(),
				),
			],
		];
	}

}
