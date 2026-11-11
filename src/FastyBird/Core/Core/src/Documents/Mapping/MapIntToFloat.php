<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use function is_int;

/**
 * Valinor converter: an int becomes a float before the union it is mapped to is decided, every other value maps as the
 * parameter's type says. It is the counterpart of an orisai union which tried FloatValue before IntValue, where Valinor
 * keeps an int an int. The parameter keeps `int` in its type for code that passes an int to the constructor
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class MapIntToFloat
{

	/**
	 * @template T
	 *
	 * @param callable(mixed): T $next
	 *
	 * @return T
	 */
	public function map(mixed $value, callable $next): mixed
	{
		return $next(is_int($value) ? (float) $value : $value);
	}

}
