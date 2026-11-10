<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use Throwable;
use function is_int;
use function is_string;
use function strtolower;

/**
 * Valinor converter: `true` and `false` in any case, and 1 and 0 as numbers or as text, are bools; every other value
 * maps as the parameter's type says. It is the counterpart of orisai's BoolValue(castBoolLike: true), whose map it
 * repeats, down to the lower case. Valinor's MapAsBool knows `true` and `false` in lower case only
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class MapBoolLike
{

	private const array BOOLS = [
		'true' => true,
		'false' => false,
		1 => true,
		0 => false,
	];

	/**
	 * @template T
	 *
	 * @param callable(mixed): T $next
	 *
	 * @return T
	 *
	 * @throws Throwable
	 */
	public function map(mixed $value, callable $next): mixed
	{
		$key = is_string($value) ? strtolower($value) : $value;

		if ((is_string($key) || is_int($key)) && isset(self::BOOLS[$key])) {
			return $next(self::BOOLS[$key]);
		}

		return $next($value);
	}

}
