<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Helpers;

use function iconv;
use function mb_convert_case;
use function preg_replace;
use function str_replace;
use function strtolower;
use function strval;
use const MB_CASE_TITLE;

/**
 * Useful name helpers
 */
final class Name
{

	public static function sanitizeEnumName(string $name): string
	{
		return str_replace(
			' ',
			'',
			strval(iconv(
				'utf-8',
				'ascii//TRANSLIT',
				strtolower(
					strval(preg_replace(
						'/(?<!^)[A-Z]/',
						'_$0',
						mb_convert_case($name, MB_CASE_TITLE, 'UTF-8'),
					)),
				),
			)),
		);
	}

}
