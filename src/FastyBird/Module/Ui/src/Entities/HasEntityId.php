<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities;

use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @property Uuid\UuidInterface $id
 */
trait HasEntityId
{

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

}
