<?php declare(strict_types = 1);

/**
 * Television.php
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
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use TypeError;
use ValueError;
use function array_unique;

/**
 * Viera television service
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Television extends HomeKitProtocol\Services\Generic
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

		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::POWER_MODE_SELECTION->value) {
			if ($characteristic->getValue() === Payloads\Button::CLICKED->value) {
				$characteristic->setExpectedValue(Payloads\Button::CLICKED->value);
			} else {
				$characteristic->setActualValue(null);
				$characteristic->setExpectedValue(null);
			}
		} elseif ($characteristic->getName() === HomeKitTypes\CharacteristicType::REMOTE_KEY->value) {
			if ($characteristic->getValue() !== null) {
				if (Utilities\Value::toString($characteristic->getValue()) === '0') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_REWIND,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '1') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_FAST_FORWARD,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '2') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_NEXT_TRACK,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '3') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_PREVIOUS_TRACK,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '4') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_UP,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '5') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_DOWN,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '6') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_LEFT,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '7') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_RIGHT,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '8') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_SELECT,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '9') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_EXIT,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '10') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_EXIT,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '11') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_PLAY_PAUSE,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);

				} elseif (Utilities\Value::toString($characteristic->getValue()) === '15') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_INFORMATION,
					);
					$remoteKeyCharacteristic?->setActualValue(null);
					$remoteKeyCharacteristic?->setExpectedValue(Payloads\Button::CLICKED->value);
				}
			}

			$characteristic->setActualValue(null);
			$characteristic->setExpectedValue(null);
		}
	}

	/**
	 * Create a HAP representation of Television Service
	 *
	 * @return array<string, array<array<string, array<int|string>|bool|float|int|string|null>|int|null>|bool|int|string|null>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toHap(): array
	{
		$hapRepresentation = parent::toHap();

		$linkedServices = [];

		$inputSources = $this->getAccessory()->findServices(HomeKitTypes\ServiceType::INPUT_SOURCE);

		foreach ($inputSources as $inputSource) {
			$linkedServices[] = $this->getAccessory()->getIidManager()->getIid($inputSource);
		}

		$speakers = $this->getAccessory()->findServices(HomeKitTypes\ServiceType::TELEVISION_SPEAKER);

		foreach ($speakers as $speaker) {
			$linkedServices[] = $this->getAccessory()->getIidManager()->getIid($speaker);
		}

		$hapRepresentation[HomeKitTypes\Representation::LINKED->value] = array_unique($linkedServices);

		return $hapRepresentation;
	}

}
