<?php declare(strict_types = 1);

/**
 * SupportedDetectionLowerSetPointScale.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           05.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Thermostat lower set point scale configuration
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SupportedDetectionLowerSetPointScale extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		string $value,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Configuration::SUPPORTED_LOWER_SET_POINT_VALUE_SCALE,
			ValuesTypes\DataType::ENUM,
			$capability,
			$value,
			[
				NsPanelTypes\Payloads\TemperatureScale::CELSIUS->value,
				NsPanelTypes\Payloads\TemperatureScale::FAHRENHEIT->value,
			],
		);
	}

	public function toDefinition(): array|null
	{
		if ($this->getValue() === null) {
			return null;
		}

		return [
			'supported' => [
				'name' => 'lowerSetpoint',
				'value' => [
					'scale' => $this->getValue(),
				],
			],
		];
	}

}
