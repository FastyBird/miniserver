<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;

/**
 * Document discriminator column definition
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DiscriminatorColumn implements MappingAttribute
{

	public function __construct(public string $name, public string|null $type = null)
	{
	}

}
