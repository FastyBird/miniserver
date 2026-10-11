<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Illumination level payload types
 */
enum IlluminationLevel: string implements Payload
{

	case BRIGHTER = 'brighter';

	case DARKER = 'darker';

}
