<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Override;

/**
 * HAP thermostat service
 */
final class Thermostat extends HomeKitProtocol\Services\Generic
{

	#[Override]
	public function getCharacteristics(): array
	{
		$characteristics = parent::getCharacteristics();

		foreach ($characteristics as $characteristic) {
			if (
				$characteristic->getName() === HomeKitTypes\CharacteristicType::TEMPERATURE_DISPLAY_UNITS->value
				&& $characteristic->getValue() === null
			) {
				$validValues = $characteristic->getValidValues();

				$characteristic->setActualValue($validValues !== null ? $validValues[0] : null);
				$characteristic->setExpectedValue(null);
			}
		}

		return $characteristics;
	}

}
