<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

/**
 * Plugins sources types
 */
enum Plugin: string implements Source
{

	case NOT_SPECIFIED = '*';

	case COUCHDB = 'com.fastybird.couchdb-plugin';

	case RABBITMQ = 'com.fastybird.rabbitmq-plugin';

	case REDISDB = 'com.fastybird.redisdb-plugin';

	case REDISDB_CACHE = 'com.fastybird.redisdb-cache-plugin';

	case WS_SERVER = 'com.fastybird.ws-server-plugin';

	case WEB_SERVER = 'com.fastybird.web-server-plugin';

	case API_KEY = 'com.fastybird.api-key-plugin';

}
