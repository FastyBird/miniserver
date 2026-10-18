<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * The tier of the property value history a series was read from
 */
enum HistoryTier: string
{

	case SAMPLES = 'samples';

	case FIVE_MINUTES = 'five_minutes';

	case HOURS = 'hours';

}
