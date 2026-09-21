<?php declare(strict_types = 1);

/**
 * RoutingMap.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           13.02.24
 */

namespace FastyBird\Core\Documents\Mapping;

use Attribute;

/**
 * Document discriminator map definition
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
