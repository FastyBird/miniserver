<?php declare(strict_types = 1);

/**
 * DiscriminatorMap.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use FastyBird\Core\Documents;

/**
 * Document discriminator map definition
 *
 * @template T of Documents\Document
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
