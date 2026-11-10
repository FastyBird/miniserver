<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Orisai\ObjectMapper;
use Override;

/**
 * The orisai half of a pair for the flag that is cast from a bool-like: MapBoolLikeParityTest gives the same input to
 * this class and to its Valinor twin, DummyParityBoolValinorDocument, and the two must accept and refuse alike
 */
final readonly class DummyParityBoolOrisaiDocument implements Documents\Document, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $flag = false,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return ['flag' => $this->flag];
	}

}
