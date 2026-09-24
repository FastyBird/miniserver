<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Entities;

use DateTimeInterface;

/**
 * Interface for entities that record when they were created
 */
interface EntityCreated
{

	public function setCreatedAt(DateTimeInterface $createdAt): void;

	public function getCreatedAt(): DateTimeInterface|null;

}
