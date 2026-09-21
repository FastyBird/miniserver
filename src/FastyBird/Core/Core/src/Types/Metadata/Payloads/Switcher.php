<?php declare(strict_types = 1);

/**
 * Switcher.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Core\Types\Metadata\Payloads;

/**
 * Switch supported payload types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Switcher: string implements Payload
{

	case ON = 'switch_on';

	case OFF = 'switch_off';

	case TOGGLE = 'switch_toggle';

}
