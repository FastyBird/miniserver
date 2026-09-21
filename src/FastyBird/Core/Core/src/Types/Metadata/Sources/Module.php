<?php declare(strict_types = 1);

/**
 * Module.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           26.04.21
 */

namespace FastyBird\Core\Types\Metadata\Sources;

use FastyBird\Core\Constants as Metadata;

/**
 * Modules sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Module: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case ACCOUNTS = Metadata\Constants::MODULE_ACCOUNTS_SOURCE;

	case DEVICES = Metadata\Constants::MODULE_DEVICES_SOURCE;

	case TRIGGERS = Metadata\Constants::MODULE_TRIGGERS_SOURCE;

	case UI = Metadata\Constants::MODULE_UI_SOURCE;

}
