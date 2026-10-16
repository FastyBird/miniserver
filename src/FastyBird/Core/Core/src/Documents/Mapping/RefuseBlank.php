<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use Throwable;
use function preg_match;

/**
 * Valinor converter: a string holding no non-whitespace character is refused, every other value maps as the
 * parameter's type says. It is the counterpart of orisai's StringValue(notEmpty: true), which refuses exactly these
 * strings; Valinor's non-empty-string type refuses only the empty one, so " " would pass it
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class RefuseBlank
{

	/**
	 * @template T
	 *
	 * @param callable(mixed): T $next
	 *
	 * @return T
	 *
	 * @throws Throwable
	 */
	public function map(string $value, callable $next): mixed
	{
		if (preg_match('/\S/', $value) !== 1) {
			throw MessageBuilder::newError('Value {source_value} must not be blank.')->build();
		}

		return $next($value);
	}

}
