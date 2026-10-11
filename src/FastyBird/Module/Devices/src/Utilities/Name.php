<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Utilities;

use function array_map;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function preg_replace;
use function str_replace;
use function strtolower;
use function strtoupper;
use function ucfirst;

/**
 * Useful name helpers
 */
final class Name
{

	public static function createName(string $identifier): string|null
	{
		$transformed = preg_replace('/(?<!^)[A-Z]/', '_$0', $identifier);

		if (!is_string($transformed)) {
			return null;
		}

		$transformed = strtolower($transformed);
		$transformed = ucfirst(strtolower(str_replace('_', ' ', $transformed)));
		$transformed = explode(' ', $transformed);
		$transformed = array_map(static function (string $part): string {
			if (in_array(strtolower($part), ['ip', 'mac', 'id', 'uid'], true)) {
				return strtoupper($part);
			}

			if (strtolower($part) === 'cfg') {
				return 'configuration';
			}

			if (strtolower($part) === 'cnt') {
				return 'count';
			}

			if (strtolower($part) === 'ext') {
				return 'external';
			}

			return $part;
		}, $transformed);

		return ucfirst(implode(' ', $transformed));
	}

}
