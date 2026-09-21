<?php declare(strict_types = 1);

/**
 * Plugin.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Core\Types\Metadata\Sources;

use FastyBird\Core\Constants\Metadata;

/**
 * Plugins sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Plugin: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case COUCHDB = Metadata\Constants::PLUGIN_COUCHDB_SOURCE;

	case RABBITMQ = Metadata\Constants::PLUGIN_RABBITMQ_SOURCE;

	case REDISDB = Metadata\Constants::PLUGIN_REDISDB_SOURCE;

	case REDISDB_CACHE = Metadata\Constants::PLUGIN_REDISDB_CACHE_SOURCE;

	case WS_SERVER = Metadata\Constants::PLUGIN_WS_SERVER_SOURCE;

	case WEB_SERVER = Metadata\Constants::PLUGIN_WEB_SERVER_SOURCE;

	case API_KEY = Metadata\Constants::PLUGIN_API_KEY;

}
