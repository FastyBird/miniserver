<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;

/**
 * Doctrine timestampable modifying entity interface
 */
interface IEntityUpdated
{

	public function setUpdatedAt(DateTimeInterface $updatedAt): void;

	public function getUpdatedAt(): DateTimeInterface|null;

}
