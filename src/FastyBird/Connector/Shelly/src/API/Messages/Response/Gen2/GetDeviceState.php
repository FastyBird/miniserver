<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use Exception;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;
use function array_filter;
use function array_map;
use function array_merge;

/**
 * Generation 2 device state message
 */
final readonly class GetDeviceState implements API\Messages\Message
{

	/**
	 * @param array<int, DeviceSwitchState> $switches
	 * @param array<int, DeviceCoverState> $covers
	 * @param array<int, DeviceInputState> $inputs
	 * @param array<int, DeviceLightState> $lights
	 * @param array<int, DeviceTemperatureState> $temperature
	 * @param array<int, DeviceHumidityState> $humidity
	 * @param array<int, DeviceDevicePowerState> $devicePower
	 * @param array<int, DeviceScriptState> $scripts
	 * @param array<int, DeviceSmokeState> $smoke
	 * @param array<int, DeviceVoltmeterState> $voltmeters
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSwitchState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::SWITCH->value)]
		private array $switches = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceCoverState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::COVER->value)]
		private array $covers = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceInputState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::INPUT->value)]
		private array $inputs = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceLightState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::LIGHT->value)]
		private array $lights = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceTemperatureState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::TEMPERATURE->value)]
		private array $temperature = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceHumidityState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::HUMIDITY->value)]
		private array $humidity = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceDevicePowerState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::DEVICE_POWER->value)]
		private array $devicePower = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceScriptState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::SCRIPT->value)]
		private array $scripts = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceSmokeState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::SMOKE->value)]
		private array $smoke = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceVoltmeterState::class),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::VOLTMETER->value)]
		private array $voltmeters = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: EthernetState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::ETHERNET->value)]
		private EthernetState|null $ethernet = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: WifiState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\ComponentType::WIFI->value)]
		private WifiState|null $wifi = null,
	)
	{
	}

	/**
	 * @return array<DeviceSwitchState>
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

	public function findSwitch(int $id): DeviceSwitchState|null
	{
		foreach ($this->switches as $switch) {
			if ($switch->getId() === $id) {
				return $switch;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceCoverState>
	 */
	public function getCovers(): array
	{
		return $this->covers;
	}

	public function findCover(int $id): DeviceCoverState|null
	{
		foreach ($this->covers as $cover) {
			if ($cover->getId() === $id) {
				return $cover;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceInputState>
	 */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	public function findInput(int $id): DeviceInputState|null
	{
		foreach ($this->inputs as $input) {
			if ($input->getId() === $id) {
				return $input;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceLightState>
	 */
	public function getLights(): array
	{
		return $this->lights;
	}

	public function findLight(int $id): DeviceLightState|null
	{
		foreach ($this->lights as $light) {
			if ($light->getId() === $id) {
				return $light;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceTemperatureState>
	 */
	public function getTemperature(): array
	{
		return $this->temperature;
	}

	public function findTemperature(int $id): DeviceTemperatureState|null
	{
		foreach ($this->temperature as $temperature) {
			if ($temperature->getId() === $id) {
				return $temperature;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceHumidityState>
	 */
	public function getHumidity(): array
	{
		return $this->humidity;
	}

	public function findHumidity(int $id): DeviceHumidityState|null
	{
		foreach ($this->humidity as $humidity) {
			if ($humidity->getId() === $id) {
				return $humidity;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceDevicePowerState>
	 */
	public function getDevicePower(): array
	{
		return $this->devicePower;
	}

	public function findDevicePower(int $id): DeviceDevicePowerState|null
	{
		foreach ($this->devicePower as $devicePower) {
			if ($devicePower->getId() === $id) {
				return $devicePower;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceScriptState>
	 */
	public function getScripts(): array
	{
		return $this->scripts;
	}

	public function findScript(int $id): DeviceScriptState|null
	{
		foreach ($this->scripts as $scripts) {
			if ($scripts->getId() === $id) {
				return $scripts;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceSmokeState>
	 */
	public function getSmoke(): array
	{
		return $this->smoke;
	}

	public function findSmoke(int $id): DeviceSmokeState|null
	{
		foreach ($this->smoke as $smoke) {
			if ($smoke->getId() === $id) {
				return $smoke;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceVoltmeterState>
	 */
	public function getVoltmeters(): array
	{
		return $this->voltmeters;
	}

	public function findVoltmeter(int $id): DeviceVoltmeterState|null
	{
		foreach ($this->voltmeters as $voltmeter) {
			if ($voltmeter->getId() === $id) {
				return $voltmeter;
			}
		}

		return null;
	}

	/**
	 * @return array<DeviceState>
	 */
	public function getComponents(): array
	{
		return array_filter(
			array_merge(
				$this->getSwitches(),
				$this->getCovers(),
				$this->getLights(),
				$this->getInputs(),
				$this->getTemperature(),
				$this->getHumidity(),
				$this->getVoltmeters(),
				$this->getScripts(),
				$this->getDevicePower(),
				$this->getSmoke(),
				[$this->getEthernet()],
				[$this->getWifi()],
			),
			static fn (DeviceState|null $item): bool => $item !== null,
		);
	}

	public function findComponent(Types\ComponentType $type, int $id): DeviceState|null
	{
		return match ($type) {
			Types\ComponentType::SWITCH => $this->findSwitch($id),
			Types\ComponentType::COVER => $this->findCover($id),
			Types\ComponentType::LIGHT => $this->findLight($id),
			Types\ComponentType::INPUT => $this->findInput($id),
			Types\ComponentType::TEMPERATURE => $this->findTemperature($id),
			Types\ComponentType::HUMIDITY => $this->findHumidity($id),
			Types\ComponentType::VOLTMETER => $this->findVoltmeter($id),
			Types\ComponentType::SCRIPT => $this->findScript($id),
			Types\ComponentType::DEVICE_POWER => $this->findDevicePower($id),
			Types\ComponentType::SMOKE => $this->findSmoke($id),
			default => null,
		};
	}

	public function getEthernet(): EthernetState|null
	{
		return $this->ethernet;
	}

	public function getWifi(): WifiState|null
	{
		return $this->wifi;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			Types\ComponentType::SWITCH->value => array_map(
				static fn (DeviceSwitchState $state): array => $state->toArray(),
				$this->getSwitches(),
			),
			Types\ComponentType::COVER->value => array_map(
				static fn (DeviceCoverState $state): array => $state->toArray(),
				$this->getCovers(),
			),
			Types\ComponentType::INPUT->value => array_map(
				static fn (DeviceInputState $state): array => $state->toArray(),
				$this->getInputs(),
			),
			Types\ComponentType::LIGHT->value => array_map(
				static fn (DeviceLightState $state): array => $state->toArray(),
				$this->getLights(),
			),
			Types\ComponentType::TEMPERATURE->value => array_map(
				static fn (DeviceTemperatureState $state): array => $state->toArray(),
				$this->getTemperature(),
			),
			Types\ComponentType::HUMIDITY->value => array_map(
				static fn (DeviceHumidityState $state): array => $state->toArray(),
				$this->getHumidity(),
			),
			Types\ComponentType::DEVICE_POWER->value => array_map(
				static fn (DeviceDevicePowerState $state): array => $state->toArray(),
				$this->getDevicePower(),
			),
			Types\ComponentType::SCRIPT->value => array_map(
				static fn (DeviceScriptState $state): array => $state->toArray(),
				$this->getScripts(),
			),
			Types\ComponentType::SMOKE->value => array_map(
				static fn (DeviceSmokeState $state): array => $state->toArray(),
				$this->getSmoke(),
			),
			Types\ComponentType::VOLTMETER->value => array_map(
				static fn (DeviceVoltmeterState $state): array => $state->toArray(),
				$this->getVoltmeters(),
			),
			Types\ComponentType::ETHERNET->value => $this->getEthernet()?->toArray(),
			Types\ComponentType::WIFI->value => $this->getWifi()?->toArray(),
		];
	}

}
