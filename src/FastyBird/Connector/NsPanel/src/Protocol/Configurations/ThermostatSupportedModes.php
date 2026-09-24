<?php declare(strict_types = 1);

/**
 * ThermostatSupportedModes.php
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
class ThermostatSupportedModes extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		string $value,
	)
	{
		$value = array_filter(
			explode(',', $value),
			static fn ($item) => trim($item) !== '' && NsPanelTypes\Payloads\ThermostatMode::tryFrom($item) !== null,
		);

		parent::__construct(
			$id,
			NsPanelTypes\Configuration::SUPPORTED_MODES,
			ValuesTypes\DataType::STRING,
			$capability,
			implode(',', $value),
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
