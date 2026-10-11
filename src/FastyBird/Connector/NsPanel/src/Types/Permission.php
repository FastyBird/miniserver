<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Capability permission types
 */
enum Permission: string
{

	case READ = 'read';

	case WRITE = 'write';

	case READ_WRITE = 'readWrite';

}
