<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           04.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Devices;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use Ramsey\Uuid;
use SplObjectStorage;
use function array_key_first;
use function array_map;
use function array_merge;
use function array_reduce;
use function in_array;
use function sprintf;

/**
 * NS panel device
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device
{

	use Nette\SmartObject;

	/** @var SplObjectStorage<Protocol\Capabilities\Capability, null> */
	protected SplObjectStorage $capabilities;

	/**
	 * @param array<int, Types\Capability> $requiredCapabilities
	 * @param array<int, Types\Capability> $optionalCapabilities
	 */
	public function __construct(
		protected readonly Uuid\UuidInterface $id,
		protected readonly string $identifier,
		protected readonly Uuid\UuidInterface $parent,
		protected readonly Uuid\UuidInterface $connector,
		protected readonly Types\Category $category,
		protected readonly string $name,
		protected readonly string $manufacturer,
		protected readonly string $model,
		protected readonly string $firmwareVersion,
		protected readonly array $requiredCapabilities = [],
		protected readonly array $optionalCapabilities = [],
	)
	{
		$this->capabilities = new SplObjectStorage();
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getParent(): Uuid\UuidInterface
	{
		return $this->parent;
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getCategory(): Types\Category
	{
		return $this->category;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getManufacturer(): string
	{
		return $this->manufacturer;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	/**
	 * @return array<Types\Capability>
	 */
	public function getAllowedACapabilitiesTypes(): array
	{
		return array_merge(
			$this->requiredCapabilities,
			$this->optionalCapabilities,
		);
	}

	/**
	 * @return array<Types\Capability>
	 */
	public function getRequiredCapabilities(): array
	{
		return $this->requiredCapabilities;
	}

	/**
	 * @return array<Types\Capability>
	 */
	public function getOptionalCapabilities(): array
	{
		return $this->optionalCapabilities;
	}

	/**
	 * @return array<Protocol\Capabilities\Capability>
	 */
	public function getCapabilities(): array
	{
		$capabilities = [];

		$this->capabilities->rewind();

		foreach ($this->capabilities as $capability) {
			$capabilities[] = $capability;
		}

		return $capabilities;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addCapability(Protocol\Capabilities\Capability $capability): void
	{
		if (!in_array($capability->getType(), $this->getAllowedACapabilitiesTypes(), true)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Capability: %s is not allowed for device in category: %s',
				$capability->getType()->value,
				$this->getCategory()->value,
			));
		}

		$this->capabilities->offsetSet($capability);
	}

	public function findCapability(Uuid\UuidInterface $id): Protocol\Capabilities\Capability|null
	{
		$this->capabilities->rewind();

		foreach ($this->capabilities as $capability) {
			if ($capability->getId()->equals($id)) {
				return $capability;
			}
		}

		return null;
	}

	/**
	 * @return array<Protocol\Capabilities\Capability>
	 */
	public function findCapabilities(Types\Capability $type, string|null $name = null): array
	{
		$capabilities = [];

		$this->capabilities->rewind();

		foreach ($this->capabilities as $capability) {
			if (
				$capability->getType() === $type
				&& ($name === null || $capability->getName() === $name)
			) {
				$capabilities[] = $capability;
			}
		}

		return $capabilities;
	}

	/**
	 * @interal
	 */
	public function recalculateCapabilities(): void
	{
		$this->capabilities->rewind();

		foreach ($this->capabilities as $capability) {
			$capability->recalculateAttributes();
		}
	}

	/**
	 * Create a NS Panel representation of this device
	 * Used for API device description
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toRepresentation(): array
	{
		return [
			'third_serial_number' => $this->getId()->toString(),
			'name' => $this->getName(),
			'display_category' => $this->getCategory()->value,
			'capabilities' => array_map(
				static fn (Protocol\Capabilities\Capability $capability): array => $capability->toDefinition(),
				$this->getCapabilities(),
			),
			'state' => array_reduce(
				$this->getCapabilities(),
				static function (array $carry, Protocol\Capabilities\Capability $capability): array {
					$state = $capability->toState();
					$key = array_key_first($state);

					$carry[$key] = $state[$key];

					return $carry;
				},
				[],
			),
			'tags' => [],
			'manufacturer' => $this->getManufacturer(),
			'model' => $this->getModel(),
			'firmware_version' => $this->getFirmwareVersion(),
		];
	}

}
