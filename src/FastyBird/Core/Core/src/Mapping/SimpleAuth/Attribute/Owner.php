<?php declare(strict_types = 1);

namespace FastyBird\Core\Mapping\SimpleAuth\Attribute;

use Attribute;
use Doctrine\ORM\Mapping as ORMMapping;

/**
 * Entity owner attribute for Doctrine2
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Owner implements ORMMapping\MappingAttribute
{

	/** @var string|array<string> */
	public string|array $field;

	public mixed $value;

	/** @var array<mixed>|null */
	public array|null $association = null;

	public function __construct(public readonly string $on = 'create')
	{
	}

}
