<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Mapping;

use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;

/**
 * Categories mapping configuration
 */
final readonly class Categories implements Mapping\Mapping
{

	/**
	 * @param array<Mapping\Categories\Category> $categories
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Categories\Category::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $categories,
	)
	{
	}

	/**
	 * @return array<Mapping\Categories\Category>
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}

	public function findByCategory(Types\Category $type): Mapping\Categories\Category|null
	{
		foreach ($this->categories as $category) {
			if ($category->getCategory() === $type) {
				return $category;
			}
		}

		return null;
	}

}
