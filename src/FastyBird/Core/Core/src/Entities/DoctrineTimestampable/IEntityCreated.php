<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;

/**
 * Interface for entities that record when they were created
 */
interface IEntityCreated
{

	public function setCreatedAt(DateTimeInterface $createdAt): void;

	public function getCreatedAt(): DateTimeInterface|null;

}
