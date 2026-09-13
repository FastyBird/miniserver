<?php declare(strict_types = 1);

/**
 * PlainIdentity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           15.07.20
 */

namespace FastyBird\Core\SimpleAuth\Security;

use FastyBird\Core\SimpleAuth\Exceptions;
use FastyBird\Core\SimpleAuth\Security;
use Nette;
use Ramsey\Uuid;

/**
 * System basic plain identity
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
