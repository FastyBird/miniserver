<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

use DateTimeInterface;

/**
 * Document updated date trait
 *
 * @property-read DateTimeInterface|null $updatedAt
 */
trait HasUpdatedAt
{

	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

}
