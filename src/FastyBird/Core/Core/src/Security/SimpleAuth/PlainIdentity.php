<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth as Security;
use Nette;
use Ramsey\Uuid;

/**
 * System basic plain identity
 */
class PlainIdentity implements Security\IIdentity
{

	use Nette\SmartObject;

	private Uuid\UuidInterface $id;

	/**
	 * @param array<string> $roles
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(string $id, private readonly array $roles = [])
	{
		if (!Uuid\Uuid::isValid($id)) {
			throw new Exceptions\InvalidArgument('User identifier have to be valid UUID string');
		}

		$this->id = Uuid\Uuid::fromString($id);
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	/**
	 * @return array<string>
	 */
	public function getRoles(): array
	{
		return $this->roles;
	}

}
