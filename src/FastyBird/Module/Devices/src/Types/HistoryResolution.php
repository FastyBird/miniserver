<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Bucket width of a history rollup, in seconds
 */
enum HistoryResolution: int
{

	case FIVE_MINUTES = 300;

	case HOUR = 3_600;

}
