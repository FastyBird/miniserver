<?php declare(strict_types = 1);

namespace FastyBird\Plugin\ApiKey\Types;

/**
 * API access key state
 */
enum KeyState: string
{

	case ACTIVE = 'active';

	case SUSPENDED = 'suspended';

	case DELETED = 'deleted';

}
