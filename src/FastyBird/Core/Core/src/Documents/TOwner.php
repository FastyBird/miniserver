<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

use Ramsey\Uuid;

/**
 * Document owner trait
 *
 * @property-read Uuid\UuidInterface|null $owner
 */
trait TOwner
{

	public function getOwner(): Uuid\UuidInterface|null
	{
		return $this->owner;
	}

}
