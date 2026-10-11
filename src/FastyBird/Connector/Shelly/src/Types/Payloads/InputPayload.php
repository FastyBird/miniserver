<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Input component payload values
 */
enum InputPayload: string implements Payload
{

	case PRESS = 'btn_down';

	case RELEASE = 'btn_up';

	case SINGLE_PUSH = 'single_push';

	case DOUBLE_PUSH = 'double_push';

	case TRIPLE_PUSH = 'triple_push';

	case LONG_PUSH = 'long_push';

}
