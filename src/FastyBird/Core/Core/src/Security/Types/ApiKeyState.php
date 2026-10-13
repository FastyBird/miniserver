<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Types;

/**
 * API key state types. Only an active key authenticates.
 */
enum ApiKeyState: string
{

	case ACTIVE = 'active';

	case SUSPENDED = 'suspended';

	case DELETED = 'deleted';

}
