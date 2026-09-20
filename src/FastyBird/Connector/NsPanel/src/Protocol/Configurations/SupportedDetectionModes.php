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
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Types\Metadata as MetadataTypes;
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
			static fn ($item) => trim($item) !== '' && Types\Payloads\ThermostatDetectionMode::tryFrom($item) !== null,
		);

		$allowedValues = [];

		if ($capability->getName() === Types\ThermostatModeDetection::TEMPERATURE->value) {
			$allowedValues = [
				Types\Payloads\ThermostatDetectionMode::COMFORT->value,
				Types\Payloads\ThermostatDetectionMode::COLD->value,
				Types\Payloads\ThermostatDetectionMode::HOT->value,
			];
		} elseif ($capability->getName() === Types\ThermostatModeDetection::HUMIDITY->value) {
			$allowedValues = [
				Types\Payloads\ThermostatDetectionMode::COMFORT->value,
				Types\Payloads\ThermostatDetectionMode::DRY->value,
				Types\Payloads\ThermostatDetectionMode::WET->value,
			];
		}

		parent::__construct(
			$id,
			Types\Configuration::SUPPORTED_MODES,
			MetadataTypes\DataType::STRING,
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
