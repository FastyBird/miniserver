<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Override;

/**
 * The Valinor half of DummyParityBoolOrisaiDocument
 */
final readonly class DummyParityBoolValinorDocument implements Documents\Document
{

	public function __construct(
		#[Documents\Mapping\MapBoolLike]
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
