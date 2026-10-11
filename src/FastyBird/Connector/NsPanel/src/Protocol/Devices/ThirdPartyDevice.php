<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Devices;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use Override;
use Ramsey\Uuid;

/**
 * NS panel third party device
 */
final class ThirdPartyDevice extends Device
{

	public bool $provisioned = false;

	public bool $corrupted = false;

	/**
	 * @param array<int, Types\Capability> $requiredCapabilities
	 * @param array<int, Types\Capability> $optionalCapabilities
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		string $identifier,
		private readonly string|null $gatewayIdentifier,
		Uuid\UuidInterface $parent,
		Uuid\UuidInterface $connector,
		Types\Category $category,
		string $name,
		string $manufacturer,
		string $model,
		string $firmwareVersion,
		private readonly string $serviceAddress,
		public private(set) bool $online = false,
		array $requiredCapabilities = [],
		array $optionalCapabilities = [],
	)
	{
		parent::__construct(
			$id,
			$identifier,
			$parent,
			$connector,
			$category,
			$name,
			$manufacturer,
			$model,
			$firmwareVersion,
			$requiredCapabilities,
			$optionalCapabilities,
		);
	}

	public function getGatewayIdentifier(): string|null
	{
		return $this->gatewayIdentifier;
	}

	public function getServiceAddress(): string
	{
		return $this->serviceAddress;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function toRepresentation(): array
	{
		$representation = parent::toRepresentation();

		$representation['service_address'] = $this->getServiceAddress();
		$representation['online'] = $this->online;

		return $representation;
	}

}
