<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;

/**
 * Doctrine timestampable creating entity interface
 */
interface IEntityCreated
{

	public function setCreatedAt(DateTimeInterface $createdAt): void;

	public function getCreatedAt(): DateTimeInterface|null;

}
