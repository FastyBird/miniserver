<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Orisai\ObjectMapper;
use Override;

/**
 * A document still mapped by orisai: it implements MappedObject, which is what keeps Mapper::map() on the orisai side.
 * Its count accepts a numeric string, a thing only orisai's rule does
 */
final readonly class DummyOrisaiDocument implements Documents\Document, ObjectMapper\MappedObject
{

	/**
	 * @param list<string> $tags
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\ListOf(item: new ObjectMapper\Rules\StringValue())]
		private array $tags = [],
		#[ObjectMapper\Rules\IntValue(castNumericString: true)]
		private int $count = 0,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'tags' => $this->tags,
			'count' => $this->count,
		];
	}

}
