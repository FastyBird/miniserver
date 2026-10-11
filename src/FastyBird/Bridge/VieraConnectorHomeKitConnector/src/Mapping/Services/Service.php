<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * Basic service interface
 */
interface Service extends Mapping\Mapping
{

	public function getType(): HomeKitTypes\ServiceType;

	/**
	 * @return class-string
	 */
	public function getClass(): string;

	public function getChannel(): string|null;

	public function isMultiple(): bool;

	/**
	 * @return array<Mapping\Characteristics\BasicCharacteristic>
	 */
	public function getCharacteristics(): array;

}
