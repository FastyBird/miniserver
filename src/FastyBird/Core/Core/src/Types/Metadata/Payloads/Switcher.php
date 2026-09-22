<?php declare(strict_types = 1);

namespace FastyBird\Core\Types\Metadata\Payloads;

/**
 * Switch supported payload types
 */
enum Switcher: string implements Payload
{

	case ON = 'switch_on';

	case OFF = 'switch_off';

	case TOGGLE = 'switch_toggle';

}
