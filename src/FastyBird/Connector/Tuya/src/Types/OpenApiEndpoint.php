<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * OpenAPI endpoint types
 */
enum OpenApiEndpoint: string
{

	case CHINA = 'https://openapi.tuyacn.com';

	case AMERICA = 'https://openapi.tuyaus.com';

	case AMERICA_AZURE = 'https://openapi-ueaz.tuyaus.com';

	case EUROPE = 'https://openapi.tuyaeu.com';

	case EUROPE_MS = 'https://openapi-weaz.tuyaeu.com';

	case INDIA = 'https://openapi.tuyain.com';

}
