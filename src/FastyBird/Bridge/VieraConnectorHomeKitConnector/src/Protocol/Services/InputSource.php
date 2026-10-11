<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Override;
use function assert;

/**
 * Viera input source service
 */
final class InputSource extends HomeKitProtocol\Services\Generic
{

	#[Override]
	public function recalculateCharacteristics(
		HomeKitProtocol\Characteristics\Characteristic|null $characteristic = null,
	): void
	{
		if ($characteristic === null || $characteristic->getExpectedValue() !== null) {
			return;
		}

		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::TARGET_VISIBILITY_STATE->value) {
			if ($characteristic->getValue() !== null) {
				$currentVisibilityState = $this->findCharacteristic(
					HomeKitTypes\CharacteristicType::CURRENT_VISIBILITY_STATE,
				);
				assert($currentVisibilityState instanceof HomeKitProtocol\Characteristics\Characteristic);

				$currentVisibilityState->setExpectedValue($characteristic->getValue());
			}
		}
	}

}
