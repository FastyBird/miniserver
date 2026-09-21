<?php declare(strict_types = 1);

/**
 * Automator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           19.01.22
 */

namespace FastyBird\Core\Types\Metadata\Sources;

use FastyBird\Core\Constants as Metadata;

/**
 * Triggers automators sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Automator: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case DEVICE_MODULE = Metadata\Constants::AUTOMATOR_DEVICE_MODULE_SOURCE;

	case DATE_TIME = Metadata\Constants::AUTOMATOR_DATE_TIME_SOURCE;

}
