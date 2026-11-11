<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use Throwable;
use function is_string;
use function preg_match;

/**
 * Valinor converter: a list in which a text holds no non-whitespace character is refused, every other value maps as the
 * parameter's type says. It is the counterpart of orisai's ArrayOf(StringValue(notEmpty: true)), which refuses exactly these
 * texts as items (RefuseBlank, for a value that is a text, refuses them the same way); Valinor's non-empty-string type
 * refuses only the empty one. What an item is, besides a blank text, is left to the type of the list
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class RefuseBlankItems
{

	/**
	 * @template T
	 *
	 * @param array<mixed> $value
	 * @param callable(mixed): T $next
	 *
	 * @return T
	 *
	 * @throws Throwable
	 */
	public function map(array $value, callable $next): mixed
	{
		foreach ($value as $key => $item) {
			if (is_string($item) && preg_match('/\S/', $item) !== 1) {
				throw MessageBuilder::newError(
					'Value {source_value} must not hold a blank text, the item {key} is one.',
				)
					->withParameter('key', (string) $key)
					->build();
			}
		}

		return $next($value);
	}

}
