<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * TLV method value types
 */
enum TlvMethod: int
{

	case RESERVED = 0;

	case PAIR_SETUP = 1;

	case PAIR_VERIFY = 2;

	case ADD_PAIRING = 3;

	case REMOVE_PAIRING = 4;

	case LIST_PAIRINGS = 5;

}
