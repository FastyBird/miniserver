<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Override;
use Ramsey\Uuid;

/**
 * A document mapped by Valinor with every kind of field Mapper::map() has a rule for beyond strings: the UUID, the
 * float, the int, and the field which keeps whichever of the two it is given
 */
final readonly class DummyTypedDocument implements Documents\Document
{

	public function __construct(
		private Uuid\UuidInterface $uuid,
		private float $ratio,
		private int $count,
		private int|float $reading,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'uuid' => $this->uuid->toString(),
			'ratio' => $this->ratio,
			'count' => $this->count,
			'reading' => $this->reading,
		];
	}

}
