<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Override;

/**
 * The Valinor twin of DummyParityPatternOrisaiDocument. The pattern of its address is the one of the e-mail of the Accounts and
 * Triggers documents, with the `+` after the dot left out (`\.+` and the class before it both take dots, so a name of many dots
 * made the match quadratic, and the class takes the extra dots as well): it accepts what the orisai pattern accepts
 */
final readonly class DummyParityPatternValinorDocument implements Documents\Document
{

	public function __construct(
		#[Documents\Mapping\RequirePattern('/^[\w\-\.]+@[\w\-\.]+\.[\w-]{2,63}$/')]
		private string $email = 'a@example.com',
		#[Documents\Mapping\RequirePattern('/^[a-z ]*$/')]
		private string $code = 'x',
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return ['email' => $this->email, 'code' => $this->code];
	}

}
