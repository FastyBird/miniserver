<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Addon\VirtualThermostat;

/**
 * Addon constants
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const string MANUFACTURER = 'FastyBird';

	public const string PRESET_CHANNEL_PATTERN = '/^preset(_(?P<preset>[a-zA-Z_]+))?$/';

}
