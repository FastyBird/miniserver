<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping\Characteristics;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * Basic characteristic interface
 */
interface Characteristic extends Mapping\Mapping
{

	public function getType(): HomeKitTypes\CharacteristicType;

	public function getChannel(): string|null;

	/**
	 * @return string|array<string>|null
	 */
	public function getProperty(): string|array|null;

	public function isNullable(): bool;

	/**
	 * @return array<HomeKitTypes\CharacteristicType>
	 */
	public function getRequire(): array;

	/**
	 * @return string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null
	 */
	public function getFormat(): string|array|null;

	public function getValue(): float|bool|int|string|null;

}
