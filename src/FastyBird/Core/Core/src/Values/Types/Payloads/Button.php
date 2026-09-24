<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Payloads;

/**
 * Button supported payload types
 */
enum Button: string implements Payload
{

	case PRESSED = 'btn_pressed';

	case RELEASED = 'btn_released';

	case CLICKED = 'btn_clicked';

	case DOUBLE_CLICKED = 'btn_double_clicked';

	case TRIPLE_CLICKED = 'btn_triple_clicked';

	case LONG_CLICKED = 'btn_long_clicked';

	case EXTRA_LONG_CLICKED = 'btn_extra_long_clicked';

}
