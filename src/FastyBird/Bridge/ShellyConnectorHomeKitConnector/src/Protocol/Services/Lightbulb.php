<?php declare(strict_types = 1);

/**
 * Lightbulb.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Values\Transformers;
use FastyBird\Module\Devices\Types as DevicesTypes;
use function is_float;
use function is_int;

/**
 * Shelly light bulb service
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Lightbulb extends HomeKitProtocol\Services\Generic
{

	public function recalculateCharacteristics(
		HomeKitProtocol\Characteristics\Characteristic|null $characteristic = null,
	): void
	{
		if ($characteristic !== null) {
			if (
				$characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_RED->value
				|| $characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_GREEN->value
				|| $characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_BLUE->value
				|| $characteristic->getName() === HomeKitTypes\CharacteristicType::COLOR_WHITE->value
			) {
				$this->calculateRgbToHsb();

			} elseif (
				$characteristic->getName() === HomeKitTypes\CharacteristicType::HUE->value
				|| $characteristic->getName() === HomeKitTypes\CharacteristicType::SATURATION->value
				|| $characteristic->getName() === HomeKitTypes\CharacteristicType::BRIGHTNESS->value
			) {
				$this->calculateHsbToRgb();
			}

			return;
		}

		if (
			$this->hasCharacteristic(HomeKitTypes\CharacteristicType::COLOR_RED)
			&& $this->hasCharacteristic(HomeKitTypes\CharacteristicType::COLOR_GREEN)
			&& $this->hasCharacteristic(HomeKitTypes\CharacteristicType::COLOR_BLUE)
		) {
			$this->calculateRgbToHsb();
		}
	}

	private function calculateRgbToHsb(): void
	{
		$redCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_RED);
		$greenCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_GREEN);
		$blueCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_BLUE);
		// Optional white channel
		$whiteCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_WHITE);

		if (
			is_int($redCharacteristic?->getValue())
			&& is_int($greenCharacteristic?->getValue())
			&& is_int($blueCharacteristic?->getValue())
		) {
			$rgb = new Transformers\RgbTransformer(
				$redCharacteristic->getValue(),
				$greenCharacteristic->getValue(),
				$blueCharacteristic->getValue(),
				is_int($whiteCharacteristic?->getValue()) ? $whiteCharacteristic->getValue() : null,
			);

			$hsb = $rgb->toHsb();

		} else {
			$hsb = new Transformers\HsbTransformer(0, 0, 0);
		}

		$hue = $this->findCharacteristic(HomeKitTypes\CharacteristicType::HUE);

		if (
			$hue?->getProperty() !== null
			&& (
				$hue->getProperty()::getType() === DevicesTypes\PropertyType::DYNAMIC->value
				|| $hue->getProperty()::getType() === DevicesTypes\PropertyType::VARIABLE->value
			)
		) {
			$hue->setActualValue($hsb->getHue());
			$hue->setExpectedValue(null);
		} elseif (
			$hue?->getProperty() !== null
			&& $hue->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
		) {
			$hue->setExpectedValue($hsb->getHue());
		}

		$saturation = $this->findCharacteristic(HomeKitTypes\CharacteristicType::SATURATION);

		if (
			$saturation?->getProperty() !== null
			&& (
				$saturation->getProperty()::getType() === DevicesTypes\PropertyType::DYNAMIC->value
				|| $saturation->getProperty()::getType() === DevicesTypes\PropertyType::VARIABLE->value
			)
		) {
			$saturation->setActualValue($hsb->getSaturation());
			$saturation->setExpectedValue(null);
		} elseif (
			$saturation?->getProperty() !== null
			&& $saturation->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
		) {
			$saturation->setExpectedValue($hsb->getSaturation());
		}

		$brightness = $this->findCharacteristic(HomeKitTypes\CharacteristicType::BRIGHTNESS);

		if (
			$brightness?->getProperty() !== null
			&& (
				$brightness->getProperty()::getType() === DevicesTypes\PropertyType::DYNAMIC->value
				|| $brightness->getProperty()::getType() === DevicesTypes\PropertyType::VARIABLE->value
			)
		) {
			$brightness->setActualValue($hsb->getBrightness());
			$brightness->setExpectedValue(null);
		}
	}

	private function calculateHsbToRgb(): void
	{
		$hueCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::HUE);
		$saturationCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::SATURATION);
		$brightnessCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::BRIGHTNESS);

		if (
			(
				is_int($hueCharacteristic?->getValue())
				|| is_float($hueCharacteristic?->getValue())
			)
			&& (
				is_int($saturationCharacteristic?->getValue())
				|| is_float($saturationCharacteristic?->getValue())
			)
			&& is_int($brightnessCharacteristic?->getValue())
		) {
			$brightness = $brightnessCharacteristic->getValue();

			// If brightness is controlled with separate property, we will use 100% brightness for calculation
			if (
				$brightnessCharacteristic->getProperty() !== null
				&& $brightnessCharacteristic->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
			) {
				$brightness = 100;
			}

			$hsb = new Transformers\HsbTransformer(
				$hueCharacteristic->getValue(),
				$saturationCharacteristic->getValue(),
				$brightness,
			);

			$rgb = $this->hasCharacteristic(HomeKitTypes\CharacteristicType::COLOR_WHITE)
				? $hsb->toRgbw($brightnessCharacteristic->getValue())
				: $hsb->toRgb();

		} else {
			$rgb = new Transformers\RgbTransformer(0, 0, 0);
		}

		$red = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_RED);

		if (
			$red?->getProperty() !== null
			&& (
				$red->getProperty()::getType() === DevicesTypes\PropertyType::DYNAMIC->value
				|| $red->getProperty()::getType() === DevicesTypes\PropertyType::VARIABLE->value
			)
		) {
			$red->setActualValue($rgb->getRed());
			$red->setExpectedValue(null);
		} elseif (
			$red?->getProperty() !== null
			&& $red->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
		) {
			$red->setExpectedValue($rgb->getRed());
		}

		$green = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_GREEN);

		if (
			$green?->getProperty() !== null
			&& (
				$green->getProperty()::getType() === DevicesTypes\PropertyType::DYNAMIC->value
				|| $green->getProperty()::getType() === DevicesTypes\PropertyType::VARIABLE->value
			)
		) {
			$green->setActualValue($rgb->getGreen());
			$green->setExpectedValue(null);
		} elseif (
			$green?->getProperty() !== null
			&& $green->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
		) {
			$green->setExpectedValue($rgb->getGreen());
		}

		$blue = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_BLUE);

		if (
			$blue?->getProperty() !== null
			&& (
				$blue->getProperty()::getType() === DevicesTypes\PropertyType::DYNAMIC->value
				|| $blue->getProperty()::getType() === DevicesTypes\PropertyType::VARIABLE->value
			)
		) {
			$blue->setActualValue($rgb->getBlue());
			$blue->setExpectedValue(null);
		} elseif (
			$blue?->getProperty() !== null
			&& $blue->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
		) {
			$blue->setExpectedValue($rgb->getBlue());
		}

		$white = $this->findCharacteristic(HomeKitTypes\CharacteristicType::COLOR_WHITE);

		if (
			$white?->getProperty() !== null
			&& (
				$white->getProperty()::getType() === DevicesTypes\PropertyType::DYNAMIC->value
				|| $white->getProperty()::getType() === DevicesTypes\PropertyType::VARIABLE->value
			)
		) {
			$white->setActualValue($rgb->getWhite());
			$white->setExpectedValue(null);
		} elseif (
			$white?->getProperty() !== null
			&& $white->getProperty()::getType() === DevicesTypes\PropertyType::MAPPED->value
		) {
			$white->setExpectedValue($rgb->getWhite());
		}
	}

}
