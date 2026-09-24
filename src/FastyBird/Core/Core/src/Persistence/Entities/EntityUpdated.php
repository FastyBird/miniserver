<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Entities;

use DateTimeInterface;

/**
 * Interface for entities that record when they were last updated
 */
interface EntityUpdated
{

	public function setUpdatedAt(DateTimeInterface $updatedAt): void;

	public function getUpdatedAt(): DateTimeInterface|null;

}
