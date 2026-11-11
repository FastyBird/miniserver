<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Orisai\ObjectMapper;
use Override;

/**
 * The orisai half of a pair for a string which has to match a pattern: RequirePatternParityTest gives the same input to
 * this class and to its Valinor twin, DummyParityPatternValinorDocument, and the two must accept and refuse alike
 */
final readonly class DummyParityPatternOrisaiDocument implements Documents\Document, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(pattern: '/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/', notEmpty: true)]
		private string $email = 'a@example.com',
		#[ObjectMapper\Rules\StringValue(pattern: '/^[a-z ]*$/', notEmpty: true)]
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
