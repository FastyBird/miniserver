<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use DateTimeInterface;
use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * How the Valinor branch of Mapper::map() reads a date: the ATOM format only, as the orisai rule did, and a string that
 * does not parse, a NUL byte in it included, is an error message of the value. DateTimeImmutable::createFromFormat()
 * throws a ValueError for such a string, and no union can recover from an exception: the value of a state, a text or
 * a date, has to be the text then
 */
final class MapperDateTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string DATE = '2026-01-01T00:00:00+00:00';

	/**
	 * @throws Throwable
	 */
	public function testADateIsRead(): void
	{
		$document = $this->mapper()->map(
			['date' => self::DATE, 'maybe_date' => '2026-02-03T04:05:06+02:00', 'value' => self::DATE],
			Dummy\DummyDateDocument::class,
		);

		self::assertSame(
			['date' => self::DATE, 'maybe_date' => '2026-02-03T04:05:06+02:00', 'value' => self::DATE],
			$document->toArray(),
		);
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('refused')]
	public function testADateWhichDoesNotParseIsRefusedWithTheFormat(array $data, string $path): void
	{
		try {
			$this->mapper()->map($data, Dummy\DummyDateDocument::class);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith($path . ': Value ', $ex->getMessage());
			self::assertStringEndsWith(
				' does not match any of the following formats: `' . DateTimeInterface::ATOM . '`.',
				$ex->getMessage(),
			);
		}
	}

	/**
	 * The value is a text where it is not a date: a NUL byte is no reason to refuse it
	 *
	 * @throws Throwable
	 */
	#[DataProvider('texts')]
	public function testAUnionOfATextAndADateTakesATextWithANulByte(string $text): void
	{
		$document = $this->mapper()->map(['date' => self::DATE, 'value' => $text], Dummy\DummyDateDocument::class);

		self::assertSame($text, $document->toArray()['value']);
	}

	/**
	 * @return array<string, array{array<string, mixed>, string}>
	 */
	public static function refused(): array
	{
		return [
			'a date with a NUL byte after it' => [['date' => self::DATE . "\0"], 'date'],
			'a NUL byte only' => [['date' => "\0"], 'date'],
			'a NUL byte in the date' => [['date' => "2026-01-01T00:00:00\0+00:00"], 'date'],
			'a nullable date with a NUL byte' => [['date' => self::DATE, 'maybe_date' => self::DATE . "\0"], 'maybe_date'],
			'another format' => [['date' => '2026-01-01 00:00:00'], 'date'],
		];
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function texts(): array
	{
		return [
			'a NUL byte at the end' => ["a\0"],
			'a NUL byte only' => ["\0"],
			'a date and a NUL byte' => [self::DATE . "\0"],
		];
	}

	/**
	 * @throws Throwable
	 */
	private function mapper(): Documents\Mapping\Mapper
	{
		return $this->container->getByType(Documents\Mapping\Mapper::class);
	}

}
