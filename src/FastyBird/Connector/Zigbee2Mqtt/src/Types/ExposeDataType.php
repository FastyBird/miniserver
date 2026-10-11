<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Expose data type types
 */
enum ExposeDataType: string
{

	case SINGLE = 'single';

	case COMPOSITE = 'composite';

}
