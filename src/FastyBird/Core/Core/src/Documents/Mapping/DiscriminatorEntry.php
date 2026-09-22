<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;

/**
 * Document discriminator item attribute
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DiscriminatorEntry implements MappingAttribute
{

	public function __construct(public string $name)
	{
	}

}
