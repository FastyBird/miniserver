<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Types;

/**
 * Protocol versions
 */
enum ProtocolVersion: string
{

	case VERSION_1 = 'v1';

	case VERSION_2 = 'v2';

}
