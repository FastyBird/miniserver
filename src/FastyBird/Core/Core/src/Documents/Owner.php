<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents;

use Ramsey\Uuid;

/**
 * Data document owner interface
 */
interface Owner
{

	public function getOwner(): Uuid\UuidInterface|null;

}
