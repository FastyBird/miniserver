<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Devices\Entities;

use FastyBird\Core\Entities\DoctrineCrud;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Ramsey\Uuid;

/**
 * Base entity interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Entity extends DoctrineCrud\IEntity
{

	public function getId(): Uuid\UuidInterface;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

	public function getSource(): MetadataTypes\Sources\Source;

}
