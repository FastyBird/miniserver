<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;

/**
 * Doctrine timestampable removing entity interface
 */
interface IEntityRemoved
{

	public function setDeletedAt(DateTimeInterface $deletedAt): void;

	public function getDeletedAt(): DateTimeInterface|null;

}
