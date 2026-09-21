<?php declare(strict_types = 1);

/**
 * TelevisionSpeaker.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           26.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;

/**
 * Viera television speaker service
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TelevisionSpeaker extends HomeKitProtocol\Services\Generic
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function recalculateCharacteristics(
		HomeKitProtocol\Characteristics\Characteristic|null $characteristic = null,
	): void
	{
		if ($characteristic === null || $characteristic->getExpectedValue() !== null) {
			return;
		}

		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::VOLUME_SELECTOR->value) {
			if ($characteristic->getValue() !== null) {
				if (ToolsUtilities\Value::toString($characteristic->getValue(), true) === '0') {
					$volumeKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_VOLUME_UP,
					);
					$volumeKeyCharacteristic?->setActualValue(null);
					$volumeKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (ToolsUtilities\Value::toString($characteristic->getValue()) === '1') {
					$volumeKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_VOLUME_DOWN,
					);
					$volumeKeyCharacteristic?->setActualValue(null);
					$volumeKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);
				}
			}

			$characteristic->setActualValue(null);
			$characteristic->setExpectedValue(null);
		}
	}

}
