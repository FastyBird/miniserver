<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Events;

use FastyBird\Module\Ui\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Doctrine entity was removed
 */
class EntityDeleted extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Entity $entity)
	{
	}

	public function getEntity(): Entities\Entity
	{
		return $this->entity;
	}

}
