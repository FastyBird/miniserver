<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Helpers;

use function is_string;
use function preg_replace;

/**
 * MQTT payload helpers
 */
class Payload
{

	public static function cleanName(string $payload): string
	{
		$cleaned = preg_replace('/[^A-Za-z0-9.,_ -]/', '', $payload);

		return is_string($cleaned) ? $cleaned : '';
	}

	public static function cleanPayload(string $payload): string
	{
		// Remove all characters except A-Z, a-z, 0-9, dots, commas, [, ], hyphens and spaces
		// Note that the hyphen must go last not to be confused with a range (A-Z)
		// and the dot, being special, is escaped with \
		$payload = preg_replace('/[^A-Za-z0-9.:, -_°%µ³\/\"]/', '', $payload);

		if (!is_string($payload)) {
			return '';
		}

		return $payload;
	}

}
