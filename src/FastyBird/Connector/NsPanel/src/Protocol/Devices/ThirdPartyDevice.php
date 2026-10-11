<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Devices;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use Ramsey\Uuid;

/**
 * NS panel third party device
 */
class ThirdPartyDevice extends Device
{

	private bool $provisioned = false;

	private bool $corrupted = false;

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
		private bool $online = false,
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

	public function isOnline(): bool
	{
		return $this->online;
	}

	public function setOnline(bool $online): void
	{
		$this->online = $online;
	}

	public function isProvisioned(): bool
	{
		return $this->provisioned;
	}

	public function setProvisioned(bool $provisioned): void
	{
		$this->provisioned = $provisioned;
	}

	public function setCorrupted(bool $corrupted): void
	{
		$this->corrupted = $corrupted;
	}

	public function isCorrupted(): bool
	{
		return $this->corrupted;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toRepresentation(): array
	{
		$representation = parent::toRepresentation();

		$representation['service_address'] = $this->getServiceAddress();
		$representation['online'] = $this->isOnline();

		return $representation;
	}

}
