<?php declare(strict_types = 1);

/**
 * IUserStorage.php
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

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Security\SimpleAuth as Security;

/**
 * Application user storage
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IUserStorage
{

	public function isAuthenticated(): bool;

	public function setIdentity(Security\IIdentity|null $identity): void;

	public function getIdentity(): Security\IIdentity|null;

}
