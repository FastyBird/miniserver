<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Types;

/**
 * Trigger condition operator type
 */
enum ConditionOperator: string
{

	case EQUAL = 'eq';

	case ABOVE = 'above';

	case BELOW = 'below';

}
