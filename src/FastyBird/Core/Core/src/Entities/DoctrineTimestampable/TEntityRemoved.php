<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;
use FastyBird\Core\Mapping\DoctrineTimestampable\Annotation as IPub;

/**
 * Adds a deletedAt property, stamped automatically when the entity is soft-deleted
 */
trait TEntityRemoved
{

	#[IPub\Timestampable(on: 'delete')]
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
