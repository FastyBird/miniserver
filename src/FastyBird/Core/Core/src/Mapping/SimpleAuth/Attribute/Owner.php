<?php declare(strict_types = 1);

namespace FastyBird\Core\Mapping\SimpleAuth\Attribute;

use Attribute;
use Doctrine\ORM\Mapping as ORMMapping;

/**
 * Property attribute marking a Doctrine entity field to be stamped with the acting user on create or update
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
