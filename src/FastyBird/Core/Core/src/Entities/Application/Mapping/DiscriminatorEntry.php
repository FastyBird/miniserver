<?php declare(strict_types = 1);

/**
 * DiscriminatorEntry.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Doctrine
 * @since          1.0.0
 *
 * @date           06.02.24
 */

namespace FastyBird\Core\Entities\Application\Mapping;

use Attribute;
use Doctrine\ORM\Mapping as ORMMapping;

/**
 * Entity discriminator item attribute for Doctrine2
 *
 * @package        FastyBird:Application!
 * @subpackage     Doctrine
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DiscriminatorEntry implements ORMMapping\MappingAttribute
{

	public function __construct(public string $name)
	{
	}

}
