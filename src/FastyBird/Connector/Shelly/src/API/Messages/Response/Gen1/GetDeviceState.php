<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use Exception;
use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * Generation 1 device state message
 */
final readonly class GetDeviceState implements API\Messages\Message
{

	/**
	 * @param array<int, DeviceRelayState> $relays
	 * @param array<int, DeviceRollerState> $rollers
	 * @param array<int, DeviceInputState> $inputs
	 * @param array<int, DeviceLightState> $lights
	 * @param array<int, DeviceMeterState> $meters
	 * @param array<int, DeviceEmeterState> $emeters
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceRelayState::class),
		)]
		private array $relays = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceRollerState::class),
		)]
		private array $rollers = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceInputState::class),
		)]
		private array $inputs = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceLightState::class),
		)]
		private array $lights = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceMeterState::class),
		)]
		private array $meters = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceEmeterState::class),
		)]
		private array $emeters = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(WifiStaState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private WifiStaState|null $wifi = null,
	)
	{
	}

	/**
	 * @return array<int, DeviceRelayState>
	 */
	public function getRelays(): array
	{
		return $this->relays;
	}

	/**
	 * @return array<int, DeviceRollerState>
	 */
	public function getRollers(): array
	{
		return $this->rollers;
	}

	/**
	 * @return array<int, DeviceInputState>
	 */
	public function getInputs(): array
	{
		return $this->inputs;
	}

	/**
	 * @return array<int, DeviceLightState>
	 */
	public function getLights(): array
	{
		return $this->lights;
	}

	/**
	 * @return array<int, DeviceMeterState>
	 */
	public function getMeters(): array
	{
		return $this->meters;
	}

	/**
	 * @return array<int, DeviceEmeterState>
	 */
	public function getEmeters(): array
	{
		return $this->emeters;
	}

	public function getWifi(): WifiStaState|null
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
			'relays' => array_map(
				static fn (DeviceRelayState $state): array => $state->toArray(),
				$this->getRelays(),
			),
			'rollers' => array_map(
				static fn (DeviceRollerState $state): array => $state->toArray(),
				$this->getRollers(),
			),
			'inputs' => array_map(
				static fn (DeviceInputState $state): array => $state->toArray(),
				$this->getInputs(),
			),
			'lights' => array_map(
				static fn (DeviceLightState $state): array => $state->toArray(),
				$this->getLights(),
			),
			'meters' => array_map(
				static fn (DeviceMeterState $state): array => $state->toArray(),
				$this->getMeters(),
			),
			'emeters' => array_map(
				static fn (DeviceEmeterState $state): array => $state->toArray(),
				$this->getEmeters(),
			),
			'wifi' => $this->getWifi()?->toArray(),
		];
	}

}
