<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use DateTimeInterface;
use FastyBird\Core\Documents;
use Orisai\ObjectMapper;
use Override;

/**
 * The orisai half of a pair: MapperParityTest gives the same input to this class and to its Valinor twin,
 * DummyParityValinorDocument, and the two must accept and refuse alike and hand back the same values
 */
final readonly class DummyParityOrisaiDocument implements Documents\Document, ObjectMapper\MappedObject
{

	/**
	 * @param list<string> $tags
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\ListOf(item: new ObjectMapper\Rules\StringValue())]
		private array $tags = [],
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		private DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'tags' => $this->tags,
			'name' => $this->name,
			'updated_at' => $this->updatedAt?->format(DateTimeInterface::ATOM),
		];
	}

}
