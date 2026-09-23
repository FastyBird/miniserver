<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth as Security;
use Override;
use Ramsey\Uuid;

/**
 * System basic plain identity
 */
final class PlainIdentity implements Security\IIdentity
{

	private Uuid\UuidInterface $id;

	/**
	 * @param array<string> $roles
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function __construct(string $id, private readonly array $roles = [])
	{
		if (!Uuid\Uuid::isValid($id)) {
			throw new Exceptions\InvalidArgument('User identifier have to be valid UUID string');
		}

		$this->id = Uuid\Uuid::fromString($id);
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	/**
	 * @return array<string>
	 */
	#[Override]
	public function getRoles(): array
	{
		return $this->roles;
	}

}
