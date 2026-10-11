<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use FastyBird\Connector\Sonoff\Types;

/**
 * Device state mapper
 *
 * @method string|null getStatusLed()
 * @method string|null getFirmwareVersion()
 * @method string|null getSsid()
 * @method int|null getRssi()
 */
trait TDevice
{

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function toStates(): array
	{
		return [
			Types\ParameterType::DEVICE->value => [
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::STATUS_LED->value,
					Types\PropertyParameter::VALUE->value => $this->getStatusLed(),
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::FIRMWARE_VERSION->value,
					Types\PropertyParameter::VALUE->value => $this->getFirmwareVersion(),
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::SSID->value,
					Types\PropertyParameter::VALUE->value => $this->getSsid(),
				],
				[
					Types\PropertyParameter::NAME->value => Types\Parameter::RSSI->value,
					Types\PropertyParameter::VALUE->value => $this->getRssi(),
				],
			],
		];
	}

}
