<?php declare(strict_types = 1);

/**
 * Capability.php
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

namespace FastyBird\Connector\NsPanel\Protocol\Capabilities;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use Ramsey\Uuid;
use SplObjectStorage;
use function array_filter;
use function array_key_first;
use function array_map;
use function array_merge;
use function array_reduce;
use function in_array;
use function sprintf;

/**
 * NS Panel device capability
 *
 * A NS Panel device capability contains multiple attributes.
 * For example, a Temperature capability has the attribute Temperature.
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Capability
{

	use Nette\SmartObject;

	/** @var SplObjectStorage<Protocol\Attributes\Attribute, null> */
	private SplObjectStorage $attributes;

	/** @var SplObjectStorage<Protocol\Configurations\Configuration, null> */
	private SplObjectStorage $configurations;

	/**
	 * @param array<int, Types\Configuration> $requiredConfigurations
	 * @param array<int, Types\Configuration> $optionalConfigurations
	 * @param array<int, Types\Attribute> $requiredAttributes
	 * @param array<int, Types\Attribute> $optionalAttributes
	 */
	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly Types\Capability $type,
		private readonly Types\Permission $permission,
		private readonly Protocol\Devices\Device $device,
		private readonly string|null $name = null,
		private readonly array $requiredConfigurations = [],
		private readonly array $optionalConfigurations = [],
		private readonly array $requiredAttributes = [],
		private readonly array $optionalAttributes = [],
	)
	{
		$this->attributes = new SplObjectStorage();
		$this->configurations = new SplObjectStorage();
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): Types\Capability
	{
		return $this->type;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getPermission(): Types\Permission
	{
		return $this->permission;
	}

	public function getDevice(): Protocol\Devices\Device
	{
		return $this->device;
	}

	/**
	 * @return array<string>
	 */
	public function getAllowedConfigurationsTypes(): array
	{
		return array_merge(
			array_map(
				static fn (Types\Configuration $configuration): string => $configuration->value,
				$this->requiredConfigurations,
			),
			array_map(
				static fn (Types\Configuration $configuration): string => $configuration->value,
				$this->optionalConfigurations,
			),
		);
	}

	/**
	 * @return array<Protocol\Configurations\Configuration>
	 */
	public function getConfigurations(): array
	{
		$configurations = [];

		$this->configurations->rewind();

		foreach ($this->configurations as $configuration) {
			$configurations[] = $configuration;
		}

		return $configurations;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addConfiguration(Protocol\Configurations\Configuration $configuration): void
	{
		if (!in_array($configuration->getType()->value, $this->getAllowedConfigurationsTypes(), true)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Configuration: %s is not allowed for capability: %s',
				$configuration->getType()->value,
				$this->getType()->value,
			));
		}

		$this->configurations->offsetSet($configuration);
	}

	public function findConfiguration(Types\Configuration $type): Protocol\Configurations\Configuration|null
	{
		$this->configurations->rewind();

		foreach ($this->configurations as $configuration) {
			if ($configuration->getType() === $type) {
				return $configuration;
			}
		}

		return null;
	}

	/**
	 * @return array<Types\Attribute>
	 */
	public function getAllowedAttributesTypes(): array
	{
		return array_merge(
			$this->requiredAttributes,
			$this->optionalAttributes,
		);
	}

	/**
	 * @return array<Protocol\Attributes\Attribute>
	 */
	public function getAttributes(): array
	{
		$attributes = [];

		$this->attributes->rewind();

		foreach ($this->attributes as $attribute) {
			$attributes[] = $attribute;
		}

		return $attributes;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addAttribute(Protocol\Attributes\Attribute $attribute): void
	{
		if (!in_array($attribute->getType(), $this->getAllowedAttributesTypes(), true)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Attribute: %s is not allowed for capability: %s',
				$attribute->getType()->value,
				$this->getType()->value,
			));
		}

		$this->attributes->offsetSet($attribute);
	}

	public function findAttribute(Types\Attribute|Uuid\UuidInterface $type): Protocol\Attributes\Attribute|null
	{
		$this->attributes->rewind();

		foreach ($this->attributes as $attribute) {
			if (
				($type instanceof Types\Attribute && $attribute->getType() === $type)
				|| ($type instanceof Uuid\UuidInterface && $attribute->getId()->equals($type))
			) {
				return $attribute;
			}
		}

		return null;
	}

	/**
	 * @interal
	 */
	public function recalculateAttributes(
		Protocol\Attributes\Attribute|null $attribute = null,
	): void
	{
		// Nothing to do here
	}

	/**
	 * Create a NS Panel representation of this capability
	 * Used for API device capability description
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toDefinition(): array
	{
		$definition = [
			'capability' => $this->getType()->value,
			'permission' => $this->getPermission()->value,
		];

		if ($this->getName() !== null) {
			$definition['name'] = $this->getName();
		}

		if ($this->getConfigurations() !== []) {
			$definition['configuration'] = array_filter(
				array_map(
					static fn (Protocol\Configurations\Configuration $configuration): array|null => $configuration->toDefinition(),
					$this->getConfigurations(),
				),
				static fn (array|null $item): bool => $item !== null,
			);
		}

		return $definition;
	}

	/**
	 * Create a NS Panel representation of this capability
	 * Used for API device capability description
	 *
	 * @return array<string, mixed>
	 */
	public function toState(): array
	{
		if ($this->getName() !== null) {
			return [
				$this->getType()->value => [
					$this->getName() => array_reduce(
						$this->getAttributes(),
						static function (array $carry, Protocol\Attributes\Attribute $attribute): array {
							$state = $attribute->toState();
							$key = array_key_first($state);

							$carry[$key] = $state[$key];

							return $carry;
						},
						[],
					),
				],
			];
		}

		return [
			$this->getType()->value => array_reduce(
				$this->getAttributes(),
				static function (array $carry, Protocol\Attributes\Attribute $attribute): array {
					$state = $attribute->toState();
					$key = array_key_first($state);

					$carry[$key] = $state[$key];

					return $carry;
				},
				[],
			),
		];
	}

}
