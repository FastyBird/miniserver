<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Types;

/**
 * Email visibility types
 */
enum EmailVisibility: string
{

	case PUBLIC = 'public';

	case PRIVATE = 'private';

}
