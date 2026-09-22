<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\Application\Mapping;

use Attribute;
use Doctrine\ORM\Mapping as ORMMapping;

/**
 * Class attribute registering an entity under a discriminator map entry
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DiscriminatorEntry implements ORMMapping\MappingAttribute
{

	public function __construct(public string $name)
	{
	}

}
