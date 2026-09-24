<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Entities;

use DateTimeInterface;
use FastyBird\Core\Persistence\Mapping\Annotation;

/**
 * Adds an updatedAt property, stamped automatically whenever the entity is modified
 */
trait HasEntityUpdated
{

	#[Annotation\Timestampable(on: 'update')]
	protected DateTimeInterface|null $updatedAt = null;

	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(DateTimeInterface $updatedAt): void
	{
		$this->updatedAt = $updatedAt;
	}

}
