<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           09.07.20
 */

namespace FastyBird\Core\SimpleAuth;

/**
 * Library constants
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * ACL
	 */

	// Permissions string delimiter
	public const PERMISSIONS_DELIMITER = ':';

	public const ACCESS_TOKEN_COOKIE = 'token';

	/**
	 * Security tokens
	 */

	public const TOKEN_URI_NAME = 'authorization';

	public const TOKEN_HEADER_NAME = 'authorization';

	public const TOKEN_HEADER_REGEXP = '/Bearer\s+(.*)$/i';

	public const TOKEN_CLAIM_USER = 'user';

	public const TOKEN_CLAIM_ROLES = 'roles';

	/**
	 * Defined roles
	 */

	// Anonymous
	public const ROLE_ANONYMOUS = 'guest';

	// Signed in
	public const ROLE_VISITOR = 'visitor';

	public const ROLE_USER = 'user';

	public const ROLE_MANAGER = 'manager';

	public const ROLE_ADMINISTRATOR = 'administrator';

	public const USER_ANONYMOUS = 'guest';

}
