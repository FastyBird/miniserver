<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;

/**
 * Interface for entities that record when they were soft-deleted
 */
interface IEntityRemoved
{

	public function setDeletedAt(DateTimeInterface $deletedAt): void;

	public function getDeletedAt(): DateTimeInterface|null;

}
