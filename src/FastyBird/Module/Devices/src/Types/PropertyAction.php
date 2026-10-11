<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Property action
 */
enum PropertyAction: string
{

	case SET = 'set';

	case GET = 'get';

}
