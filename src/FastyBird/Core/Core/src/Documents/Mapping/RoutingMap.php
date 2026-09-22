<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;

/**
 * Document discriminator map definition
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class RoutingMap implements MappingAttribute
{

	/**
	 * @param array<string> $value
	 */
	public function __construct(public array $value)
	{
	}

}
