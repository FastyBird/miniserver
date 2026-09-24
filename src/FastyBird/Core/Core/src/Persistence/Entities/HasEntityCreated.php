<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Entities;

use DateTimeInterface;
use FastyBird\Core\Persistence\Mapping\Annotation;

/**
 * Adds a createdAt property, stamped automatically on entity creation
 */
trait HasEntityCreated
{

	#[Annotation\Timestampable(on: 'create')]
	protected DateTimeInterface|null $createdAt = null;

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function setCreatedAt(DateTimeInterface $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

}
