<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Ramsey\Uuid;

/**
 * Application identity interface
 */
interface UserIdentity
{

	public function getId(): Uuid\UuidInterface;

	/**
	 * @return array<string>
	 */
	public function getRoles(): array;

}
