<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
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

	public function getCategory(): HomeKitTypes\AccessoryCategory;

	public function getChannel(): string|null;

	public function getIndexStart(): int|null;

	public function isMultiple(): bool;

	/**
	 * @return array<Mapping\Characteristics\Characteristic>
	 */
	public function getCharacteristics(): array;

}
