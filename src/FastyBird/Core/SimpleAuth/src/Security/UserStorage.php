<?php declare(strict_types = 1);

/**
 * User.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           29.08.20
 */

namespace FastyBird\Core\SimpleAuth\Security;

use FastyBird\Core\SimpleAuth\Security;

/**
 * Application user storage
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class UserStorage implements Security\IUserStorage
{

	private IIdentity|null $identity = null;

	public function isAuthenticated(): bool
	{
		return $this->getIdentity() !== null;
	}

	public function getIdentity(): Security\IIdentity|null
	{
		return $this->identity;
	}

	public function setIdentity(Security\IIdentity|null $identity = null): void
	{
		$this->identity = $identity;
	}

}
