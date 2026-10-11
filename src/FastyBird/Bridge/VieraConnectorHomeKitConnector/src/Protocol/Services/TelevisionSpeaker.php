<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use Override;

/**
 * Viera television speaker service
 */
final class TelevisionSpeaker extends HomeKitProtocol\Services\Generic
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function recalculateCharacteristics(
		HomeKitProtocol\Characteristics\Characteristic|null $characteristic = null,
	): void
	{
		if ($characteristic === null || $characteristic->getExpectedValue() !== null) {
			return;
		}

		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::VOLUME_SELECTOR->value) {
			if ($characteristic->getValue() !== null) {
				if (Utilities\Value::toString($characteristic->getValue(), true) === '0') {
					$volumeKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_VOLUME_UP,
					);
					$volumeKeyCharacteristic?->setActualValue(null);
					$volumeKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '1') {
					$volumeKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_VOLUME_DOWN,
					);
					$volumeKeyCharacteristic?->setActualValue(null);
					$volumeKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);
				}
			}

			$characteristic->setActualValue(null);
			$characteristic->setExpectedValue(null);
		}
	}

}
