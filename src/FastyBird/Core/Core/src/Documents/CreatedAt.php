<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

use DateTimeInterface;

/**
 * Data document created at interface
 *
 * @property-read DateTimeInterface|null $createdAt
 */
interface CreatedAt
{

	public function getCreatedAt(): DateTimeInterface|null;

}
