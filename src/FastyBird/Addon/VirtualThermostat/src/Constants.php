<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat;

/**
 * Addon constants
 */
final class Constants
{

	public const string MANUFACTURER = 'FastyBird';

	public const string PRESET_CHANNEL_PATTERN = '/^preset(_(?P<preset>[a-zA-Z_]+))?$/';

}
