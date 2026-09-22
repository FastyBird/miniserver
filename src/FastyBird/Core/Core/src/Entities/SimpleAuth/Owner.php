<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\SimpleAuth;

/**
 * Entity owner interface
 */
interface Owner
{

	public function setOwnerId(string|null $ownerId): void;

	public function getOwnerId(): string|null;

}
