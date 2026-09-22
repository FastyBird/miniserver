<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;

/**
 * Document definition
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Document implements MappingAttribute
{

	/**
	 * @param class-string|null $entity
	 */
	public function __construct(public string|null $entity = null)
	{
	}

}
