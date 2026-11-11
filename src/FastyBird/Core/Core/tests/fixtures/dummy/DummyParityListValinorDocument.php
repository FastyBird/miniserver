<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Override;

/**
 * The Valinor twin of DummyParityListOrisaiDocument
 */
final readonly class DummyParityListValinorDocument implements Documents\Document
{

	/**
	 * @param array<int<0, max>, non-empty-string> $models
	 */
	public function __construct(
		#[Documents\Mapping\RefuseBlankItems]
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
