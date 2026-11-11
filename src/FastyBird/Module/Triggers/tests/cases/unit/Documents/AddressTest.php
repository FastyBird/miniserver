<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use Orisai\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function str_repeat;

/**
 * The pattern of an e-mail address is written out on the notification once, and on the documents of the Accounts module
 * twice: a top level domain is 2 to 63 letters on every copy
 */
final class AddressTest extends TestCase
{

	/**
	 * @throws CoreDocuments\Exceptions\MappingFailed
	 */
	#[DataProvider('tops')]
	public function testATopLevelDomainIsTwoToSixtyThreeLetters(string $top, bool $accepted): void
	{
		$data = [
			'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
			'trigger' => 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1',
			'type' => 'email',
			'enabled' => true,
			'email' => 'a@example.' . $top,
		];
		$mapper = new CoreDocuments\Mapping\Mapper($this->createMock(ObjectMapper\Processing\Processor::class));

		try {
			$document = $mapper->map($data, TriggersDocuments\Notifications\Email::class);

			self::assertTrue($accepted, $top);
			self::assertSame($data['email'], $document->getEmail());
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			self::assertFalse($accepted, $top);
			self::assertStringStartsWith('email: Value ', $ex->getMessage());
		}
	}

	/**
	 * @return array<string, array{string, bool}>
	 */
	public static function tops(): array
	{
		return [
			'2 letters' => ['cz', true],
			'1 letter' => ['c', false],
			'63 letters' => [str_repeat('x', 63), true],
			'64 letters' => [str_repeat('x', 64), false],
		];
	}

}
