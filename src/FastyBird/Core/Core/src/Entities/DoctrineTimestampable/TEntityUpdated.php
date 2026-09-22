<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;
use FastyBird\Core\Mapping\DoctrineTimestampable\Annotation as IPub;

/**
 * Adds an updatedAt property, stamped automatically whenever the entity is modified
 */
trait TEntityUpdated
{

	#[IPub\Timestampable(on: 'update')]
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
