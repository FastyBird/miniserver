<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Entities;

use DateTimeInterface;
use FastyBird\Core\Persistence\Mapping\Annotation;

/**
 * Adds a deletedAt property, stamped automatically when the entity is soft-deleted
 */
trait TEntityRemoved
{

	#[Annotation\Timestampable(on: 'delete')]
	protected DateTimeInterface|null $deletedAt = null;

	public function getDeletedAt(): DateTimeInterface|null
	{
		return $this->deletedAt;
	}

	public function setDeletedAt(DateTimeInterface $deletedAt): void
	{
		$this->deletedAt = $deletedAt;
	}

}
