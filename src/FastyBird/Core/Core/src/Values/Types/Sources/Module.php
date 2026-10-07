<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

/**
 * Modules sources types
 */
enum Module: string implements Source
{

	case NOT_SPECIFIED = '*';

	case ACCOUNTS = 'com.fastybird.accounts-module';

	case DEVICES = 'com.fastybird.devices-module';

	case TRIGGERS = 'com.fastybird.triggers-module';

	case UI = 'com.fastybird.ui-module';

}
