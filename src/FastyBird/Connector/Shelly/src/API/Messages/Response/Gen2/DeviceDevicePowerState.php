<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use function array_merge;
use function is_string;

/**
 * Generation 2 device power state message
 */
final class DeviceDevicePowerState extends DeviceState implements API\Messages\Message
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: BatteryStateBlock::class),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly BatteryStateBlock|string $battery,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: ExternalPowerStateBlock::class),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Shelly\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly ExternalPowerStateBlock|string $external,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::DEVICE_POWER;
	}

	public function getBattery(): BatteryStateBlock|string
	{
		return $this->battery;
	}

	public function getExternal(): ExternalPowerStateBlock|string
	{
		return $this->external;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'battery' => is_string($this->getBattery()) ? $this->getBattery() : $this->getBattery()->toArray(),
				'external' => is_string($this->getExternal()) ? $this->getExternal() : $this->getExternal()->toArray(),
			],
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toState(): array
	{
		return array_merge(
			parent::toArray(),
			$this->getBattery() instanceof BatteryStateBlock ? $this->getBattery()->toState() : [],
			$this->getExternal() instanceof ExternalPowerStateBlock ? $this->getExternal()->toState() : [],
		);
	}

}
