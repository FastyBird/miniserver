<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifier types
 */
enum ChannelPropertyIdentifier: string
{

	case ADDRESS = DevicesTypes\ChannelPropertyIdentifier::ADDRESS->value;

	case TYPE = 'type';

	case VALUE = 'value';

	case READING_DELAY = 'reading_delay';

}
