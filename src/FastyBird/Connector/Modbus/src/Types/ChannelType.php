<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * Device channel type types
 */
enum ChannelType: string
{

	case DISCRETE_INPUT = 'discrete_input';

	case COIL = 'coil';

	case INPUT_REGISTER = 'input_register';

	case HOLDING_REGISTER = 'holding_register';

}
