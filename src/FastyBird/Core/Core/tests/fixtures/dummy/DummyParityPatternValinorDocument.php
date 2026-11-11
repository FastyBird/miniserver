<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Override;

/**
 * The Valinor twin of DummyParityPatternOrisaiDocument
 */
final readonly class DummyParityPatternValinorDocument implements Documents\Document
{

	public function __construct(
		#[Documents\Mapping\RequirePattern('/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/')]
		private string $email,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return ['email' => $this->email];
	}

}
