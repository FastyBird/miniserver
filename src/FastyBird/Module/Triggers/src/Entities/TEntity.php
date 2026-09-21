<?php declare(strict_types = 1);

/**
 * TEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Triggers\Entities;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Uuid\UuidInterface $id
 */
trait TEntity
{

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getPlainId(): string
	{
		return $this->id->toString();
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::TRIGGERS;
	}

}
