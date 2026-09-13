<?php declare(strict_types = 1);

/**
 * TokenState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Core\SimpleAuth\Types;

/**
 * Token state types
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum TokenState: string
{

	/**
	 * Define states
	 */
	case ACTIVE = 'active';

	case BLOCKED = 'blocked';

	case DELETED = 'deleted';

	case EXPIRED = 'expired';

	case REVOKED = 'revoked';

}
