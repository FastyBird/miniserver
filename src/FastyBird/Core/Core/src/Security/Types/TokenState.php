<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Types;

/**
 * Token state types
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
