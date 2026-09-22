<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;

/**
 * Document definition
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class InheritanceType implements MappingAttribute
{

	public function __construct(public string $type)
	{
	}

}
