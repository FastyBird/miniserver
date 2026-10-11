<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Mapping\Capabilities;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Basic group interface
 */
readonly class Group implements Mapping\Mapping
{

	/**
	 * @param array<Types\Category> $categories
	 * @param array<Mapping\Capabilities\Capability> $capabilities
	 */
	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Group::class)]
		private Types\Group $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Category::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $categories = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Capabilities\Capability::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $capabilities = [],
	)
	{
	}

	public function getType(): Types\Group
	{
		return $this->type;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return array<Types\Category>
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}

	/**
	 * @return array<Mapping\Capabilities\Capability>
	 */
	public function getCapabilities(): array
	{
		return $this->capabilities;
	}

	/**
	 * @return array<string, array<array<string, array<array<string, array<int, array<int, string>|string>|float|int|string|null>>|bool|string>|string>|string>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return [
			'type' => $this->getType()->value,
			'description' => $this->getDescription(),
			'categories' => array_map(
				static fn (Types\Category $category): string => $category->value,
				$this->getCategories(),
			),
			'capabilities' => array_map(
				static fn (Capability $capability): array => $capability->toArray(),
				$this->getCapabilities(),
			),
		];
	}

}
