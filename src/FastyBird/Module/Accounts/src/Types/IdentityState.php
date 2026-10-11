<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Types;

/**
 * Account identity state type
 */
enum IdentityState: string
{

	case ACTIVE = 'active';

	case BLOCKED = 'blocked';

	case DELETED = 'deleted';

	case INVALID = 'invalid';

}
