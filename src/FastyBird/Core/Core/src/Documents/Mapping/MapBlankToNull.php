<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use function preg_match;

/**
 * Valinor converter: a string holding no non-whitespace character maps to null, every other value maps as the
 * parameter's type says. It is the counterpart of orisai's NullValue(castEmptyString: true), which casts exactly
 * these strings, and stands beside it while both mappers are in the tree (#724)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class MapBlankToNull
{

	/**
	 * @template T
	 *
	 * @param callable(mixed): T $next
	 *
	 * @return T|null
	 */
	public function map(string $value, callable $next): mixed
	{
		return preg_match('/\S/', $value) === 1 ? $next($value) : null;
	}

}
