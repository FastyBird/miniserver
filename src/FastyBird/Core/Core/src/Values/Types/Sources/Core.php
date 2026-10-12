<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

/**
 * Core sources types
 */
enum Core: string implements Source
{

	case NOT_SPECIFIED = '*';

	case CORE = 'com.fastybird.core';

}
