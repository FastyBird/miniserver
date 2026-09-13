<?php declare(strict_types = 1);

/**
 * Owner.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Mapping
 * @since          0.1.0
 *
 * @date           05.02.24
 */

namespace FastyBird\Core\SimpleAuth\Mapping\Attribute;

use Attribute;
use Doctrine\ORM\Mapping as ORMMapping;

/**
 * Entity owner attribute for Doctrine2
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
