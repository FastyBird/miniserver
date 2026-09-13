<?php declare(strict_types = 1);

/**
 * User.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Security
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Security;

use FastyBird\Core\SimpleAuth\Security as SimpleAuthSecurity;
use FastyBird\Module\Accounts\Entities;
use Ramsey\Uuid;

/**
 * Application user
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class User extends SimpleAuthSecurity\User
{

	public function getId(): Uuid\UuidInterface|null
	{
		return $this->getAccount()?->getId();
	}

	public function getAccount(): Entities\Accounts\Account|null
	{
		if ($this->isLoggedIn()) {
			$identity = $this->getIdentity();

			if ($identity instanceof Entities\Identities\Identity) {
				return $identity->getAccount();
			}
		}

		return null;
	}

	public function getName(): string
	{
		if ($this->isLoggedIn()) {
			$account = $this->getAccount();

			return $account?->getName() ?? 'Registered';
		}

		return 'Guest';
	}

}
