<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use CuyZ\Valinor;
use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use Nette;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;
use ValueError;

/**
 * What Mapper::map() does with an input the libraries cannot take apart: a string with a NUL byte where a date is read
 * (DateTimeImmutable::createFromFormat() throws a ValueError for it) and a key or a value which is not valid UTF-8
 * where Valinor has to print it into its own message (it throws a StringFormatterError). Both are a refusal, a
 * MappingFailed, on the orisai branch and on the Valinor branch, with or without unknown fields allowed (#823, #828)
 */
final class MapperRefusalTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string UTF8_MESSAGE = '*root*: A key or a value is not valid UTF-8.';

	/**
	 * The orisai branch: orisai reads a date with createFromFormat(), which throws a ValueError for a NUL byte, and the
	 * mapper refuses the input with what the error says
	 *
	 * @throws Throwable
	 */
	#[DataProvider('dateAndOptions')]
	public function testADateWithANulByteIsRefusedOnTheOrisaiBranch(bool $lenient, string $date): void
	{
		try {
			$this->mapper()->map(
				['id' => self::ID, 'updated_at' => $date],
				Dummy\DummyParityOrisaiDocument::class,
				new Documents\Mapping\MappingOptions(allowUnknownFields: $lenient),
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith('*root*: DateTimeImmutable::createFromFormat()', $ex->getMessage());
			self::assertStringEndsWith('must not contain any null bytes', $ex->getMessage());
			self::assertInstanceOf(ValueError::class, $ex->getPrevious());
		}
	}

	/**
	 * The Valinor branch: the date constructor of Mapper refuses it with the message of Valinor for a date that no format
	 * reads, and its code
	 *
	 * @throws Throwable
	 */
	#[DataProvider('dateAndOptions')]
	public function testADateWithANulByteIsRefusedOnTheValinorBranch(bool $lenient, string $date): void
	{
		try {
			$this->mapper()->map(
				['id' => self::ID, 'updated_at' => $date],
				Dummy\DummyParityValinorDocument::class,
				new Documents\Mapping\MappingOptions(allowUnknownFields: $lenient),
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith('updated_at: Value ', $ex->getMessage());
			self::assertStringEndsWith(
				' does not match any of the following formats: `Y-m-d\TH:i:sP`.',
				$ex->getMessage(),
			);

			$previous = $ex->getPrevious();
			self::assertInstanceOf(Valinor\Mapper\MappingError::class, $previous);

			foreach ($previous->messages()->errors() as $message) {
				self::assertSame('cannot_parse_datetime_format', $message->code());
			}
		}
	}

	/**
	 * What the date constructor says for values that are not a date at all: the text of Valinor for a value that is
	 * not a string, which its non-empty-string parameter produces, and the one for a text that no format reads
	 *
	 * @param array<mixed>|bool|float|int|string $value
	 *
	 * @throws Throwable
	 */
	#[DataProvider('notDates')]
	public function testAValueWhichIsNotADateIsRefusedWithTheMessageOfTheType(
		array|bool|float|int|string $value,
		string $message,
	): void
	{
		try {
			$this->mapper()->map(
				['id' => self::ID, 'updated_at' => $value],
				Dummy\DummyParityValinorDocument::class,
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertSame('updated_at: ' . $message, $ex->getMessage());
		}
	}

	/**
	 * A NUL byte is refused only where a date is read: in any other string it is a value, as it was
	 *
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classes')]
	public function testANulByteInAStringIsStillAValue(string $class): void
	{
		$document = $this->mapper()->map(['id' => self::ID, 'name' => "a\0b"], $class);

		self::assertSame("a\0b", $document->toArray()['name']);
	}

	/**
	 * A ValueError from the constructor of a Valinor-mapped class is a defect, and is thrown as it is: the Valinor branch
	 * catches none, as the date constructor of Mapper refuses a NUL byte with an error message
	 *
	 * @throws Throwable
	 */
	public function testAValueErrorOfAnotherCauseIsNotARefusal(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('The constructor failed with a value error');

		$this->mapper()->map(['kind' => Dummy\DummyGuardedDocument::FAIL_VALUE], Dummy\DummyGuardedDocument::class);
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classAndOptions')]
	public function testAListKeyWhichIsNotValidUtf8IsRefused(string $class, bool $lenient): void
	{
		try {
			$this->mapper()->map(
				['id' => self::ID, 'tags' => ["\xC3\x28" => 'a']],
				$class,
				new Documents\Mapping\MappingOptions(allowUnknownFields: $lenient),
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertNotSame('', $ex->getMessage());
		}
	}

	/**
	 * What Valinor refuses with its own message, it refuses as one: the text and the cause are pinned
	 *
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('valinorInputs')]
	public function testValinorRefusesWhatItCannotPrintWithoutItsOwnError(array $data, bool $lenient): void
	{
		try {
			$this->mapper()->map(
				$data,
				Dummy\DummyParityValinorDocument::class,
				new Documents\Mapping\MappingOptions(allowUnknownFields: $lenient),
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertSame(self::UTF8_MESSAGE, $ex->getMessage());
			self::assertInstanceOf(Valinor\Utility\String\StringFormatterError::class, $ex->getPrevious());
		}
	}

	/**
	 * A UUID which is not valid UTF-8 is refused, with Valinor printing it into the message of the refusal
	 *
	 * @throws Throwable
	 */
	#[DataProvider('options')]
	public function testAUuidWhichIsNotValidUtf8IsRefused(bool $lenient): void
	{
		try {
			$this->mapper()->map(
				['uuid' => "\xC3\x28", 'ratio' => 1.5, 'count' => 1, 'reading' => 1],
				Dummy\DummyTypedDocument::class,
				new Documents\Mapping\MappingOptions(allowUnknownFields: $lenient),
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertSame(self::UTF8_MESSAGE, $ex->getMessage());
		}
	}

	/**
	 * What is not valid UTF-8 and is not printed is a value like any other
	 *
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classes')]
	public function testAStringWhichIsNotValidUtf8IsStillAValue(string $class): void
	{
		$document = $this->mapper()->map(['id' => self::ID, 'name' => "\xC3\x28"], $class);

		self::assertSame("\xC3\x28", $document->toArray()['name']);
	}

	/**
	 * Valinor throws the same exception when it cannot print a message whose body does not parse, which is a defect of the
	 * constructor that wrote it and not a fault of the input: the refusal says what the exception says, and not that the
	 * input is not valid UTF-8.
	 *
	 * The exception is internal to Valinor and the catch of Mapper names it: this and testValinorRefusesWhatItCannotPrint...
	 * hold `previous` to its class, and assertInstanceOf() with a class that no longer exists is an error, so a release
	 * that renames it fails these tests, not the callers
	 *
	 * @throws Throwable
	 */
	public function testABrokenMessageIsNotBlamedOnTheInput(): void
	{
		try {
			$this->mapper()->map(
				['kind' => Dummy\DummyGuardedDocument::BROKEN_MESSAGE],
				Dummy\DummyGuardedDocument::class,
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith(
				'*root*: Message formatter error using `The kind is {broken`',
				$ex->getMessage(),
			);
			self::assertNotSame(self::UTF8_MESSAGE, $ex->getMessage());
			self::assertInstanceOf(Valinor\Utility\String\StringFormatterError::class, $ex->getPrevious());
		}
	}

	/**
	 * The input is blamed when it holds what is not valid UTF-8, wherever it is in it
	 *
	 * @throws Throwable
	 */
	public function testABrokenMessageOfAnInputWhichIsNotValidUtf8IsBlamedOnTheInput(): void
	{
		try {
			$this->mapper()->map(
				['kind' => Dummy\DummyGuardedDocument::BROKEN_MESSAGE, 'extra' => ['a' => ["\xC3\x28" => 'x']]],
				Dummy\DummyGuardedDocument::class,
				new Documents\Mapping\MappingOptions(allowUnknownFields: true),
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertSame(self::UTF8_MESSAGE, $ex->getMessage());
		}
	}

	/**
	 * @return array<string, array{class-string<Documents\Document>}>
	 */
	public static function classes(): array
	{
		return [
			'mapped by Valinor' => [Dummy\DummyParityValinorDocument::class],
			'mapped by orisai' => [Dummy\DummyParityOrisaiDocument::class],
		];
	}

	/**
	 * @return array<string, array{bool}>
	 */
	public static function options(): array
	{
		return [
			'strict' => [false],
			'lenient' => [true],
		];
	}

	/**
	 * @return array<string, array{class-string<Documents\Document>, bool}>
	 */
	public static function classAndOptions(): array
	{
		$cases = [];

		foreach (self::classes() as $name => [$class]) {
			foreach (self::options() as $option => [$lenient]) {
				$cases[$name . ', ' . $option] = [$class, $lenient];
			}
		}

		return $cases;
	}

	/**
	 * @return array<string, array{bool, string}>
	 */
	public static function dateAndOptions(): array
	{
		$cases = [];

		foreach (self::options() as $option => [$lenient]) {
			foreach (
				[
					'a NUL byte after the date' => "2026-01-01T00:00:00+00:00\0",
					'a NUL byte in the date' => "2026-01-01T00:00:00\0+00:00",
					'a NUL byte only' => "\0",
				] as $label => $date
			) {
				$cases[$option . ', ' . $label] = [$lenient, $date];
			}
		}

		return $cases;
	}

	/**
	 * @return array<string, array{array<mixed>|bool|float|int|string, string}>
	 */
	public static function notDates(): array
	{
		$types = 'does not match any of `non-empty-string`, `int`, `float`.';
		$formats = 'does not match any of the following formats: `Y-m-d\TH:i:sP`.';

		return [
			'an empty string' => ['', "Value '' " . $types],
			'true' => [true, 'Value true ' . $types],
			'false' => [false, 'Value false ' . $types],
			'a list' => [['a'], "Value array{0: 'a'} " . $types],
			'an int' => [5, 'Value 5 ' . $formats],
			'a float' => [1.5, 'Value 1.5 ' . $formats],
			'another format' => ['2026-01-01 00:00:00', "Value '2026-01-01 00:00:00' " . $formats],
		];
	}

	/**
	 * @return array<string, array{array<string, mixed>, bool}>
	 */
	public static function valinorInputs(): array
	{
		return [
			'a list key, strict' => [['id' => self::ID, 'tags' => ["\xC3\x28" => 'a']], false],
			'a list key, lenient' => [['id' => self::ID, 'tags' => ["\xC3\x28" => 'a']], true],
			'an unknown key, strict' => [['id' => self::ID, "\xC3\x28" => 'a'], false],
		];
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	private function mapper(): Documents\Mapping\Mapper
	{
		return $this->container->getByType(Documents\Mapping\Mapper::class);
	}

}
