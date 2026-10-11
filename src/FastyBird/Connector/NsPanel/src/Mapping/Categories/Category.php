<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Mapping\Categories;

use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Basic category interface
 */
final readonly class Category implements Mapping\Mapping
{

	/**
	 * @param array<Types\Group> $requiredCapabilities
	 * @param array<Types\Group> $optionalCapabilities
	 */
	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Category::class)]
		private Types\Category $category,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Group::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $requiredCapabilities = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Group::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $optionalCapabilities = [],
	)
	{
	}

	public function getCategory(): Types\Category
	{
		return $this->category;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return array<Types\Group>
	 */
	public function getRequiredCapabilitiesGroups(): array
	{
		return $this->requiredCapabilities;
	}

	/**
	 * @return array<Types\Group>
	 */
	public function getOptionalCapabilitiesGroups(): array
	{
		return $this->optionalCapabilities;
	}

	/**
	 * @return array<string, string|array<string>>
	 */
	public function toArray(): array
	{
		return [
			'category' => $this->getCategory()->value,
			'description' => $this->getDescription(),
			'requiredCapabilities' => array_map(
				static fn (Types\Group $group) => $group->value,
				$this->getRequiredCapabilitiesGroups(),
			),
			'optionalCapabilities' => array_map(
				static fn (Types\Group $group) => $group->value,
				$this->getOptionalCapabilitiesGroups(),
			),
		];
	}

}
