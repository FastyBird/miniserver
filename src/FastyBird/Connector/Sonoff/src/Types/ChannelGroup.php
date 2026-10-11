<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Device parameters channels groups
 */
enum ChannelGroup: string
{

	case SWITCHES = 'switches';

	case CONFIGURE = 'configure';

	case PULSES = 'pulses';

	case RF_LIST = 'rfList';

}
