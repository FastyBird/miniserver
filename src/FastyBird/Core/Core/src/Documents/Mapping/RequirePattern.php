<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use Throwable;
use function preg_match;

/**
 * Valinor converter: a string which does not match the pattern, or is blank, is refused, every other value maps as the
 * parameter's type says. It is the counterpart of orisai's StringValue(pattern: ..., notEmpty: true), whose pattern is
 * tested with preg_match() as it is here, so that what `$` lets through (a line break at the end) is let through by both
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class RequirePattern
{

	/**
	 * @param non-empty-string $pattern
	 */
	public function __construct(private string $pattern)
	{
	}

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
		if (preg_match('/\S/', $value) !== 1 || preg_match($this->pattern, $value) !== 1) {
			throw MessageBuilder::newError('Value {source_value} does not match the pattern.')->build();
		}

		return $next($value);
	}

}
