<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Module\Devices\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Doctrine entity was updated
 */
final class EntityUpdated extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Entity $entity)
	{
	}

	public function getEntity(): Entities\Entity
	{
		return $this->entity;
	}

}
