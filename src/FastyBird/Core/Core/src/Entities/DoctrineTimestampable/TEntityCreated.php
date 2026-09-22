<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\DoctrineTimestampable;

use DateTimeInterface;
use FastyBird\Core\Mapping\DoctrineTimestampable\Annotation as IPub;

/**
 * Doctrine timestampable creating entity
 */
trait TEntityCreated
{

	#[IPub\Timestampable(on: 'create')]
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
