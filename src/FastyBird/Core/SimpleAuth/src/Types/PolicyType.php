<?php declare(strict_types = 1);

/**
 * PolicyType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           15.07.24
 */

namespace FastyBird\Core\SimpleAuth\Types;

/**
 * Policy type types
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum PolicyType: string
{

	case POLICY = 'p';

	case ROLE = 'g';

}
