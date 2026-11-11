<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping\Accessories;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Types as ShellyConnectorHomeKitConnectorTypes;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Documents;
use Override;
use TypeError;
use ValueError;

/**
 * Rollers accessories mapping configuration
 */
final readonly class RollerAccessory extends Accessory
{

	/**
	 * @param 'roller' $type
	 * @param array<int<0, max>, non-empty-string> $models
	 * @param array<int<0, max>, HomeKitTypes\AccessoryCategory> $categories
	 * @param array<int<0, max>, Mapping\Services\WindowCoveringService> $services
	 */
	public function __construct(
		private string $type,
		#[Documents\Mapping\RefuseBlankItems]
		private array $models,
		private array $categories,
		private array $services,
	)
	{
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getType(): ShellyConnectorHomeKitConnectorTypes\DeviceType
	{
		return ShellyConnectorHomeKitConnectorTypes\DeviceType::from($this->type);
	}

	/**
	 * @return array<string>
	 */
	#[Override]
	public function getModels(): array
	{
		return $this->models;
	}

	/**
	 * @return array<HomeKitTypes\AccessoryCategory>
	 */
	#[Override]
	public function getCategories(): array
	{
		return $this->categories;
	}

	/**
	 * @return array<Mapping\Services\Service>
	 */
	#[Override]
	public function getServices(): array
	{
		return $this->services;
	}

}
