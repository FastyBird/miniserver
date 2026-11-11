<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;

/**
 * Viera services mapping configuration
 */
final readonly class Services implements Mapping\Mapping
{

	/**
	 * @param array<int<0, max>, Mapping\Services\Television|Mapping\Services\TelevisionSpeaker|Mapping\Services\InputSource> $services
	 */
	public function __construct(private array $services)
	{
	}

	/**
	 * @return array<Mapping\Services\Service>
	 */
	public function getServices(): array
	{
		return $this->services;
	}

}
