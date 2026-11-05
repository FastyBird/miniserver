<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Tests;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * The value of a state which holds a NUL byte is a text: converting it to a date would throw a ValueError from
 * DateTimeImmutable::createFromFormat(), where the converter has to leave the text to the types of the union
 */
final class StateValueNulByteTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string CHANNEL = '247fd6b5-8466-4323-81de-cec3e315015a';

	/**
	 * @throws Throwable
	 */
	#[DataProvider('texts')]
	public function testTheValueIsTheText(string $text): void
	{
		$document = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class)->create(
			DevicesDocuments\States\Channels\Properties\Property::class,
			[
				'id' => self::ID,
				'channel' => self::CHANNEL,
				'read' => ['actual_value' => $text, 'expected_value' => $text],
				'get' => ['actual_value' => $text, 'expected_value' => null],
				'pending' => false,
				'valid' => false,
				'created_at' => null,
				'updated_at' => null,
			],
		);

		self::assertSame($text, $document->getRead()->getActualValue());
		self::assertSame($text, $document->getRead()->getExpectedValue());
		self::assertSame($text, $document->getGet()->getActualValue());
	}

	/**
	 * A date with a NUL byte is a text where the value is one, and no date where only a date is allowed
	 *
	 * @throws Throwable
	 */
	public function testTheFlagThatIsADateRefusesIt(): void
	{
		$this->expectException(CoreExceptions\InvalidArgument::class);

		$this->getContainer()->getByType(CoreDocuments\DocumentFactory::class)->create(
			DevicesDocuments\States\Channels\Properties\Property::class,
			[
				'id' => self::ID,
				'channel' => self::CHANNEL,
				'read' => ['actual_value' => 1, 'expected_value' => 2],
				'get' => ['actual_value' => 1, 'expected_value' => 2],
				'pending' => '2026-01-01T00:00:00+00:00' . "\0",
				'valid' => false,
				'created_at' => null,
				'updated_at' => null,
			],
		);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function texts(): array
	{
		return [
			'a NUL byte at the end' => ["a\0"],
			'a NUL byte only' => ["\0"],
			'a date and a NUL byte' => ["2026-01-01T00:00:00+00:00\0"],
		];
	}

}
