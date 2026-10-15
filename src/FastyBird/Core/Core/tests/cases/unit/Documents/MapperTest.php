<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use CuyZ\Valinor;
use FastyBird\Core\Documents;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use Orisai\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use Throwable;
use function strtoupper;

/**
 * Documents\Mapping\Mapper is the one place every consumer maps a plain array to an object (#724): which library maps
 * a class, what it maps, what it refuses, which option decides it, and the text a refusal carries, which every package
 * builder puts in its own exception. A class which implements orisai's MappedObject is mapped by orisai, any other
 * class by Valinor
 */
final class MapperTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classes')]
	public function testItMapsDataToTheRequestedClass(string $class): void
	{
		$document = $this->mapper()->map(['id' => 'one', 'tags' => ['a', 'b']], $class);

		self::assertInstanceOf($class, $document);
		self::assertSame(['a', 'b'], $document->toArray()['tags']);
		self::assertSame('one', $document->toArray()['id']);
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classes')]
	public function testItRefusesUnknownFieldsByDefault(string $class): void
	{
		$this->expectException(Documents\Exceptions\MappingFailed::class);

		$this->mapper()->map(['id' => 'one', 'extra' => 'x'], $class);
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classes')]
	public function testItIgnoresUnknownFieldsWhenTheOptionAllowsThem(string $class): void
	{
		$document = $this->mapper()->map(
			['id' => 'one', 'extra' => 'x'],
			$class,
			new Documents\Mapping\MappingOptions(allowUnknownFields: true),
		);

		self::assertSame('one', $document->toArray()['id']);
		self::assertSame([], $document->toArray()['tags']);
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classes')]
	public function testItStillRefusesInvalidDataWhenUnknownFieldsAreAllowed(string $class): void
	{
		$this->expectException(Documents\Exceptions\MappingFailed::class);

		$this->mapper()->map(
			['id' => '', 'extra' => 'x'],
			$class,
			new Documents\Mapping\MappingOptions(allowUnknownFields: true),
		);
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Throwable
	 */
	#[DataProvider('classes')]
	public function testItRefusesATagListWhichIsNotAList(string $class): void
	{
		$this->expectException(Documents\Exceptions\MappingFailed::class);

		$this->mapper()->map(['id' => 'one', 'tags' => ['first' => 'exchange']], $class);
	}

	/**
	 * The dispatch: the class that implements MappedObject goes to orisai, which reads a numeric string as an int
	 * where Valinor refuses it, and the failure it keeps is orisai's
	 *
	 * @throws Throwable
	 */
	public function testAClassWhichImplementsMappedObjectIsMappedByOrisai(): void
	{
		$document = $this->mapper()->map(['id' => 'one', 'count' => '5'], Dummy\DummyOrisaiDocument::class);

		self::assertSame(['id' => 'one', 'tags' => [], 'count' => 5], $document->toArray());

		try {
			$this->mapper()->map(['id' => 'one', 'count' => 'five'], Dummy\DummyOrisaiDocument::class);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertInstanceOf(ObjectMapper\Exception\InvalidData::class, $ex->getPrevious());
		}
	}

	/**
	 * The other side of the dispatch: a class without MappedObject goes to Valinor, which refuses what orisai would
	 * have cast, and the failure it keeps is Valinor's
	 *
	 * @throws Throwable
	 */
	public function testAnyOtherClassIsMappedByValinor(): void
	{
		$data = ['uuid' => self::ID, 'ratio' => 1.5, 'count' => '5', 'reading' => 2];

		try {
			$this->mapper()->map($data, Dummy\DummyTypedDocument::class);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertInstanceOf(Valinor\Mapper\MappingError::class, $ex->getPrevious());
		}
	}

	/**
	 * A refusal by orisai carries the text orisai's own printer makes of the failure, not a summary of it
	 *
	 * @throws Throwable
	 */
	public function testARefusalByOrisaiCarriesTheTextOrisaiPrints(): void
	{
		$data = ['id' => '', 'tags' => 'not a list', 'extra' => 'x'];

		try {
			$this->container->getByType(ObjectMapper\Processing\Processor::class)->process(
				$data,
				Dummy\DummyOrisaiDocument::class,
			);

			self::fail('orisai accepted the data');
		} catch (ObjectMapper\Exception\InvalidData $expected) {
			$printer = new ObjectMapper\Printers\ErrorVisualPrinter(new ObjectMapper\Printers\TypeToStringConverter());
			$printed = $printer->printError($expected);
		}

		try {
			$this->mapper()->map($data, Dummy\DummyOrisaiDocument::class);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertNotSame('', $printed);
			self::assertSame($printed, $ex->getMessage());
		}
	}

	/**
	 * A refusal by Valinor is one line for each thing that was wrong, the path of the value first, and in the order
	 * Valinor found them. The text is pinned: builders put it in their own exceptions and logs
	 *
	 * @throws Throwable
	 */
	public function testARefusalByValinorCarriesOneLinePerError(): void
	{
		try {
			$this->mapper()->map(
				['id' => " \t", 'tags' => 'not a list', 'extra' => 'x'],
				Dummy\DummyRoutedDocument::class,
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertSame(
				"id: Value ' \t' must not be blank.\n"
				. "tags: Value 'not a list' does not match `list<string>`.\n"
				. 'extra: Unexpected key `extra`.',
				$ex->getMessage(),
			);
			self::assertInstanceOf(Valinor\Mapper\MappingError::class, $ex->getPrevious());
		}
	}

	/**
	 * A blank string is not a value for a field orisai declared notEmpty: Valinor's own non-empty-string would let a
	 * string of spaces through, so Core's RefuseBlank is what the Valinor fields carry
	 *
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('blanks')]
	public function testItRefusesABlankWhereAStringMustNotBeEmpty(array $data): void
	{
		$this->expectException(Documents\Exceptions\MappingFailed::class);

		$this->mapper()->map($data, Dummy\DummyRoutedDocument::class);
	}

	/**
	 * A blank string in a field that may be null is null, and a string which only holds spaces around text is kept as
	 * it is
	 *
	 * @throws Throwable
	 */
	public function testItMapsABlankStringToNullWhereTheFieldMayBeNull(): void
	{
		$document = $this->mapper()->map(
			['id' => self::ID, 'identifier' => 'x', 'name' => " \t ", 'comment' => ' kept '],
			Dummy\DummyDocument::class,
		);

		self::assertNull($document->toArray()['name']);
		self::assertSame(' kept ', $document->toArray()['comment']);
	}

	/**
	 * The decision for a field orisai read as AnyOf([FloatValue, IntValue]), which made every int a float: declare it
	 * `float` and the int becomes a float, as before; declare it `int|float` and Valinor keeps what it was given
	 *
	 * @throws Throwable
	 */
	public function testAnIntIsAFloatInAFloatFieldAndStaysAnIntInAFieldWhichTakesBoth(): void
	{
		$document = $this->mapper()->map(
			['uuid' => self::ID, 'ratio' => -50, 'count' => -50, 'reading' => -50],
			Dummy\DummyTypedDocument::class,
		);

		self::assertSame(-50.0, $document->toArray()['ratio']);
		self::assertSame(-50, $document->toArray()['count']);
		self::assertSame(-50, $document->toArray()['reading']);

		$document = $this->mapper()->map(
			['uuid' => self::ID, 'ratio' => 1.5, 'count' => 1, 'reading' => 2.5],
			Dummy\DummyTypedDocument::class,
		);

		self::assertSame(1.5, $document->toArray()['ratio']);
		self::assertSame(2.5, $document->toArray()['reading']);
	}

	/**
	 * Valinor casts nothing unless asked to: a number in a string is not a number, and a float is not an int
	 *
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('uncastable')]
	public function testItCastsNoScalar(array $data): void
	{
		$this->expectException(Documents\Exceptions\MappingFailed::class);

		$this->mapper()->map(
			$data + ['uuid' => self::ID, 'ratio' => 1.5, 'count' => 1, 'reading' => 1],
			Dummy\DummyTypedDocument::class,
		);
	}

	/**
	 * What orisai's UuidRule took, which is what Uuid::isValid() says: the canonical form in any case, with braces or
	 * a urn prefix around it, and an object that already is one
	 *
	 * @throws Throwable
	 */
	#[DataProvider('uuids')]
	public function testItReadsAUuidAsOrisaisRuleDoes(Uuid\UuidInterface|string $uuid): void
	{
		$document = $this->mapper()->map(
			['uuid' => $uuid, 'ratio' => 1.5, 'count' => 1, 'reading' => 1],
			Dummy\DummyTypedDocument::class,
		);

		self::assertSame(self::ID, $document->toArray()['uuid']);
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('notUuids')]
	public function testItRefusesWhatIsNotACanonicalUuid(mixed $uuid): void
	{
		try {
			$this->mapper()->map(
				['uuid' => $uuid, 'ratio' => 1.5, 'count' => 1, 'reading' => 1],
				Dummy\DummyTypedDocument::class,
			);

			self::fail('the mapper accepted the data');
		} catch (Documents\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith('uuid: ', $ex->getMessage());
		}
	}

	/**
	 * @return array<string, array{class-string<Documents\Document>}>
	 */
	public static function classes(): array
	{
		return [
			'mapped by Valinor' => [Dummy\DummyRoutedDocument::class],
			'mapped by orisai' => [Dummy\DummyOrisaiDocument::class],
		];
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function blanks(): array
	{
		return [
			'empty' => [['id' => '']],
			'spaces' => [['id' => '   ']],
			'a tab and a line break' => [['id' => "\t\n"]],
			'not a string' => [['id' => 42]],
		];
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function uncastable(): array
	{
		return [
			'a numeric string for an int' => [['count' => '5']],
			'a numeric string for a float' => [['ratio' => '1.5']],
			'a numeric string for an int or a float' => [['reading' => '1']],
			'a float for an int' => [['count' => 1.5]],
		];
	}

	/**
	 * @return array<string, array{Uuid\UuidInterface|string}>
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function uuids(): array
	{
		return [
			'canonical' => [self::ID],
			'upper case' => [strtoupper(self::ID)],
			'with braces' => ['{' . self::ID . '}'],
			'with a urn prefix' => ['urn:uuid:' . self::ID],
			'an object' => [Uuid\Uuid::fromString(self::ID)],
		];
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public static function notUuids(): array
	{
		return [
			'a word' => ['not a uuid'],
			'without dashes' => ['176984ad7cf7465d9e5371668a74a688'],
			'a dash too many' => ['176984ad-7cf7-465d-9e53-71668a74a68-8'],
			'an int' => [42],
			'null' => [null],
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
