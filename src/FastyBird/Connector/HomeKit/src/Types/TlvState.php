<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * TLV state value types
 */
enum TlvState: int
{

	case M1 = 0x01;

	case M2 = 0x02;

	case M3 = 0x03;

	case M4 = 0x04;

	case M5 = 0x05;

	case M6 = 0x06;

}
