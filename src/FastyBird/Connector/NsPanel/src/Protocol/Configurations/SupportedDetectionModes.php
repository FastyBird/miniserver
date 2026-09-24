<?php declare(strict_types = 1);

/**
 * SupportedDetectionModes.php
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
use function array_filter;
use function assert;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function trim;

/**
 * Thermostat supported modes configuration
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SupportedDetectionModes extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		string $value,
	)
	{
		$value = array_filter(
			explode(',', $value),
			static fn ($item) => trim($item) !== '' && NsPanelTypes\Payloads\ThermostatDetectionMode::tryFrom($item) !== null,
		);

		$allowedValues = [];

		if ($capability->getName() === NsPanelTypes\ThermostatModeDetection::TEMPERATURE->value) {
			$allowedValues = [
				NsPanelTypes\Payloads\ThermostatDetectionMode::COMFORT->value,
				NsPanelTypes\Payloads\ThermostatDetectionMode::COLD->value,
				NsPanelTypes\Payloads\ThermostatDetectionMode::HOT->value,
			];
		} elseif ($capability->getName() === NsPanelTypes\ThermostatModeDetection::HUMIDITY->value) {
			$allowedValues = [
				NsPanelTypes\Payloads\ThermostatDetectionMode::COMFORT->value,
				NsPanelTypes\Payloads\ThermostatDetectionMode::DRY->value,
				NsPanelTypes\Payloads\ThermostatDetectionMode::WET->value,
			];
		}

		parent::__construct(
			$id,
			NsPanelTypes\Configuration::SUPPORTED_MODES,
			ValuesTypes\DataType::STRING,
			$capability,
			implode(',', array_filter($value, static fn (string $item): bool => in_array($item, $allowedValues, true))),
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function getValue(): array
	{
		assert(is_string($this->value));

		return array_filter(explode(',', $this->value), static fn ($item) => trim($item) !== '');
	}

}
