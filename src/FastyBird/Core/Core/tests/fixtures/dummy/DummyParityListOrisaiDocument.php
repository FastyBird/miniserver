<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Orisai\ObjectMapper;
use Override;

/**
 * The orisai half of a pair for a list of texts none of which is blank: RefuseBlankItemsParityTest gives the same input to
 * this class and to its Valinor twin, DummyParityListValinorDocument, and the two must accept and refuse alike
 */
final readonly class DummyParityListOrisaiDocument implements Documents\Document, ObjectMapper\MappedObject
{

	/**
	 * @param array<int, string> $models
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $models,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return ['models' => $this->models];
	}

}
