<?php declare(strict_types = 1);

/**
 * IIdentity.php
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

use Ramsey\Uuid;

/**
 * Application identity interface
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IIdentity
{

	public function getId(): Uuid\UuidInterface;

	/**
	 * @return array<string>
	 */
	public function getRoles(): array;

}
