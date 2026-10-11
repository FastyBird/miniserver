<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Device generations
 */
enum DeviceGeneration: string
{

	case UNKNOWN = 'unknown';

	case GENERATION_1 = 'gen1';

	case GENERATION_2 = 'gen2';

	case GENERATION_3 = 'gen3';

}
