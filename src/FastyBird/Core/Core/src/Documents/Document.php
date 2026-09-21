<?php declare(strict_types = 1);

/**
 * Document.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           31.05.22
 */

namespace FastyBird\Core\Documents;

use Orisai\ObjectMapper;

/**
 * Data document interface
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Document extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
