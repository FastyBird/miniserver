<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Override;

/**
 * A Valinor-mapped document with a value which is a bool, a number, a text or null: an int is mapped to a float, as a
 * union which tried a float before an int did
 */
final readonly class DummyScalarDocument implements Documents\Document
{

	public function __construct(
		#[Documents\Mapping\MapBlankToNull]
		#[Documents\Mapping\MapIntToFloat]
		private bool|float|int|string|null $value = null,
	)
	{
	}

	public function getValue(): bool|float|int|string|null
	{
		return $this->value;
	}

	#[Override]
	public function toArray(): array
	{
		return ['value' => $this->value];
	}

}
