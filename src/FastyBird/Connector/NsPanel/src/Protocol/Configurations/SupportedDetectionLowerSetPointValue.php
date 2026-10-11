<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Override;
use Ramsey\Uuid;

/**
 * Thermostat lower set point value configuration
 */
class SupportedDetectionLowerSetPointValue extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		float|int $value,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Configuration::SUPPORTED_LOWER_SET_POINT_VALUE_VALUE,
			ValuesTypes\DataType::FLOAT,
			$capability,
			$value,
		);
	}

	#[Override]
	public function toDefinition(): array|null
	{
		if ($this->getValue() === null) {
			return null;
		}

		return [
			'supported' => [
				'name' => 'lowerSetpoint',
				'value' => [
					'value' => $this->getValue(),
				],
			],
		];
	}

}
