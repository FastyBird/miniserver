<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities;

use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @property Uuid\UuidInterface $id
 */
trait TEntity
{

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

}
