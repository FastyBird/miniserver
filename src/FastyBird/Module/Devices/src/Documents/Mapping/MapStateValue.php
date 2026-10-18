<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use DateTimeImmutable;
use DateTimeInterface;
use FastyBird\Core\Values\Types\Payloads;
use Throwable;
use function is_string;
use function preg_match;

/**
 * Valinor converter for the value of a state: a text is the first of a date, a button, a switch and a cover payload it
 * can be read as, and a text with no non-whitespace character is null. A text of anything else is kept as text. Valinor
 * would keep every text as text, a union with a string in it takes the string first, where orisai tried the date and
 * the payloads before it. A value which is not a text is left to the types of the union
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class MapStateValue
{

	/**
	 * @template T
	 *
	 * @param callable(mixed): T $next
	 *
	 * @return T|DateTimeInterface|Payloads\Payload|null
	 *
	 * @throws Throwable
	 */
	public function map(mixed $value, callable $next): mixed
	{
		if (!is_string($value)) {
			return $next($value);
		}

		$date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);

		if ($date !== false) {
			return $date;
		}

		return Payloads\Button::tryFrom($value)
			?? Payloads\Switcher::tryFrom($value)
			?? Payloads\Cover::tryFrom($value)
			?? (preg_match('/\S/', $value) === 1 ? $next($value) : null);
	}

}
