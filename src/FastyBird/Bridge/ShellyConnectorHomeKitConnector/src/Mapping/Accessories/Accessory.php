<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping\Accessories;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * Basic accessory interface
 */
abstract readonly class Accessory implements Mapping\Mapping
{

	/**
	 * @return array<string>
	 */
	abstract public function getModels(): array;

	/**
	 * @return array<HomeKitTypes\AccessoryCategory>
	 */
	abstract public function getCategories(): array;

	/**
	 * @return array<Mapping\Services\Service>
	 */
	abstract public function getServices(): array;

	/**
	 * @return array<Mapping\Services\Service>
	 */
	public function findForCategory(HomeKitTypes\AccessoryCategory $category): array
	{
		$services = [];

		foreach ($this->getServices() as $service) {
			if ($service->getCategory() === $category) {
				$services[] = $service;
			}
		}

		return $services;
	}

}
