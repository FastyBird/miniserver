<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * Communication byte order types
 */
enum ByteOrder: string
{

	case BIG = 'big';

	case BIG_SWAP = 'big_swap';

	case BIG_LOW_WORD_FIRST = 'big_lwf';

	case LITTLE = 'little';

	case LITTLE_SWAP = 'little_swap';

	case LITTLE_LOW_WORD_FIRST = 'little_lwf';

}
