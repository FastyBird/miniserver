<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Types;

/**
 * Extension types
 */
enum ExtensionType: string
{

	case FASTYBIRD_HARDWARE = 'com.fastybird.hardware';

	case FASTYBIRD_FIRMWARE = 'com.fastybird.firmware';

}
