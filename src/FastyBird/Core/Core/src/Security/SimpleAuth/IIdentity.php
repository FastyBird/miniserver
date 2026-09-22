<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use Ramsey\Uuid;

/**
 * Application identity interface
 */
interface IIdentity
{

	public function getId(): Uuid\UuidInterface;

	/**
	 * @return array<string>
	 */
	public function getRoles(): array;

}
