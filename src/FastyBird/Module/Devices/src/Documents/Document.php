<?php declare(strict_types = 1);

/**
 * Document.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           22.06.24
 */

namespace FastyBird\Module\Devices\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;

/**
 * Base document interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Document extends Documents\Document
{

	public function getId(): Uuid\UuidInterface;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

	public function getSource(): Sources\Source;

}
