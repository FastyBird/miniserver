<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use FastyBird\Core\Documents;

/**
 * Document discriminator map definition
 *
 * @template T of Documents\Document
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DiscriminatorMap implements MappingAttribute
{

	/**
	 * @param array<int|string, class-string<T>> $value
	 */
	public function __construct(public array $value)
	{
	}

}
