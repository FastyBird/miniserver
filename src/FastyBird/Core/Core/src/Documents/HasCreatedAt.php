<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

use DateTimeInterface;

/**
 * Document created date trait
 *
 * @property-read DateTimeInterface|null $createdAt
 */
trait HasCreatedAt
{

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

}
