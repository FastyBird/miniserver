<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

use FastyBird\Core\Constants;

/**
 * Plugins sources types
 */
enum Plugin: string implements Source
{

	case NOT_SPECIFIED = Constants::NOT_SPECIFIED_SOURCE;

	case COUCHDB = Constants::PLUGIN_COUCHDB_SOURCE;

	case RABBITMQ = Constants::PLUGIN_RABBITMQ_SOURCE;

	case REDISDB = Constants::PLUGIN_REDISDB_SOURCE;

	case REDISDB_CACHE = Constants::PLUGIN_REDISDB_CACHE_SOURCE;

	case WS_SERVER = Constants::PLUGIN_WS_SERVER_SOURCE;

	case WEB_SERVER = Constants::PLUGIN_WEB_SERVER_SOURCE;

	case API_KEY = Constants::PLUGIN_API_KEY;

}
