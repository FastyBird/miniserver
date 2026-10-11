<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * HAP data type
 */
enum DataType: string
{

	case BOOLEAN = 'bool';

	case INT = 'int';

	case FLOAT = 'float';

	case STRING = 'string';

	case UINT8 = 'uint8';

	case UINT16 = 'uint16';

	case UINT32 = 'uint32';

	case UINT64 = 'uint64';

	case DATA = 'data';

	case TLV8 = 'tlv8';

}
