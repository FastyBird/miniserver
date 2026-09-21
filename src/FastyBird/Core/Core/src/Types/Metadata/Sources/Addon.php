<?php declare(strict_types = 1);

/**
 * Addon.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.02.24
 */

namespace FastyBird\Core\Types\Metadata\Sources;

use FastyBird\Core\Constants\Metadata;

/**
 * Bridges sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Addon: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case VIRTUAL_THERMOSTAT = Metadata\Constants::ADDON_VIRTUAL_THERMOSTAT_SOURCE;

}
