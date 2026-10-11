<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Property type
 */
enum PropertyType: string
{

	case VARIABLE = 'variable';

	case DYNAMIC = 'dynamic';

	case MAPPED = 'mapped';

}
