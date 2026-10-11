<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Helpers;

use function count;
use function implode;
use function shuffle;

/**
 * Drivers helper
 */
final class Drivers
{

	public static function generateMacAddress(): string
	{
		$allowedValues = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];

		$mac = [];

		while (count($mac) < 7) {
			shuffle($allowedValues);

			$mac[] = $allowedValues[0] . $allowedValues[1];
		}

		return implode(':', $mac);
	}

}
