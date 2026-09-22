<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

use DateTimeInterface;

/**
 * Data document updated at interface
 *
 * @property-read DateTimeInterface|null $updatedAt
 */
interface UpdatedAt
{

	public function getUpdatedAt(): DateTimeInterface|null;

}
