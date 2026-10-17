<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use Ramsey\Uuid;
use Throwable;
use function is_string;
use function preg_match;

/**
 * Valinor converter for a value transformer, which is the UUID of another property or the text of an equation: a UUID
 * is read as one, any other text with a non-whitespace character is kept as text and a blank one is null. Valinor
 * keeps a UUID written in a text as the text a union of a string and a UUID, where orisai read the UUID first
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class MapValueTransformer
{

	/**
	 * @throws Throwable
	 */
	public function map(mixed $value): Uuid\UuidInterface|string|null
	{
		if ($value === null || $value instanceof Uuid\UuidInterface) {
			return $value;
		}

		if (!is_string($value)) {
			throw MessageBuilder::newError('Value {source_value} is not a UUID, a text or null.')->build();
		}

		if (Uuid\Uuid::isValid($value)) {
			return Uuid\Uuid::fromString($value);
		}

		return preg_match('/\S/', $value) === 1 ? $value : null;
	}

}
