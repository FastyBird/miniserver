<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;

/**
 * Document definition
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass implements MappingAttribute
{

}
