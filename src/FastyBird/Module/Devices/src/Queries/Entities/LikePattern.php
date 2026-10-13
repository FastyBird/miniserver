<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Queries\Entities;

use function strtr;

/**
 * Values put into a LIKE pattern, matched as they are
 *
 * @internal
 */
final class LikePattern
{

	/**
	 * The character the patterns are written with to take the next one as it is. Not the backslash, which the
	 * database also reads as an escape inside the query's string literal
	 */
	public const string ESCAPE = '!';

	/**
	 * The value with every `%` and `_` in it, and the escape character itself, matching only itself
	 */
	public static function literal(string $value): string
	{
		return strtr($value, [
			self::ESCAPE => self::ESCAPE . self::ESCAPE,
			'%' => self::ESCAPE . '%',
			'_' => self::ESCAPE . '_',
		]);
	}

}
