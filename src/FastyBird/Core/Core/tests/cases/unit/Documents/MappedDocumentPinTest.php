<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;
use function array_merge;
use function assert;
use function file_get_contents;
use function is_array;
use function is_string;
use function serialize;

/**
 * What the Core fixture documents map to, pinned. Each input goes through DocumentFactory, which hands it to the mapper,
 * and the result must be what orisai made of the same input when the fixtures were still mapped by it (#724 PR2): the
 * JSON the exchange and the WebSockets encode, with Nette's Json::encode(), and serialize() of the document, so that an
 * int is not a float and an unset property is not a null one. tests/fixtures/Documents/pinned.json holds both strings
 * per input, taken from the orisai mapping at 09000bcaa
 */
final class MappedDocumentPinTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	/**
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed>|string|object $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('documents')]
	public function testTheDocumentIsWhatOrisaiMade(string $name, string $class, array|string|object $data): void
	{
		$document = $this->container->getByType(Documents\DocumentFactory::class)->create($class, $data);
		$pinned = self::pinned($name);

		self::assertSame($pinned['json'], Utils\Json::encode($document->toArray()));
		self::assertSame($pinned['serialized'], serialize($document));
	}

	/**
	 * The known-positive control: the comparison above has to tell two documents apart when one field differs
	 *
	 * @throws Throwable
	 */
	public function testOneChangedFieldIsTold(): void
	{
		$data = self::input((string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.json'));

		$document = $this->container->getByType(Documents\DocumentFactory::class)->create(
			Dummy\DummyTwoDocument::class,
			array_merge($data, ['name' => 'Another name']),
		);
		$pinned = self::pinned('two, as JSON');

		self::assertNotSame($pinned['json'], Utils\Json::encode($document->toArray()));
		self::assertNotSame($pinned['serialized'], serialize($document));
	}

	/**
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('invalid')]
	public function testTheDocumentIsRefusedWhatOrisaiRefused(string $class, array $data): void
	{
		$this->expectException(CoreExceptions\InvalidArgument::class);

		$this->container->getByType(Documents\DocumentFactory::class)->create($class, $data);
	}

	/**
	 * @return array<string, array{string, class-string<Documents\Document>, array<string, mixed>|string|object}>
	 */
	public static function documents(): array
	{
		$cases = [
			'one, as JSON, through the root' => [
				Dummy\DummyDocument::class,
				(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.one.json'),
			],
			'two, as JSON' => [
				Dummy\DummyTwoDocument::class,
				(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.json'),
			],
			'every field' => [
				Dummy\DummyDocument::class,
				[
					'id' => self::ID,
					'type' => Dummy\DummyTwoDocument::TYPE,
					'identifier' => 'every-field',
					'name' => 'Name',
					'comment' => 'Comment',
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2021-11-30T23:59:59+02:00',
				],
			],
			'only the required fields' => [
				Dummy\DummyOneDocument::class,
				['id' => self::ID, 'type' => Dummy\DummyOneDocument::TYPE, 'identifier' => 'required'],
			],
			'blank strings' => [
				Dummy\DummyOneDocument::class,
				[
					'id' => self::ID,
					'type' => Dummy\DummyOneDocument::TYPE,
					'identifier' => 'blank',
					'name' => '',
					'comment' => " \t ",
				],
			],
			'unknown fields' => [
				Dummy\DummyOneDocument::class,
				[
					'id' => self::ID,
					'type' => Dummy\DummyOneDocument::TYPE,
					'identifier' => 'unknown',
					'source' => 'com.fastybird.core',
					'nested' => ['created_at' => 'not a date'],
				],
			],
			'an object' => [
				Dummy\DummyTwoDocument::class,
				Utils\ArrayHash::from([
					'id' => self::ID,
					'type' => Dummy\DummyTwoDocument::TYPE,
					'identifier' => 'object',
					'created_at' => '2020-04-01T12:00:00+00:00',
				]),
			],
			'routed, with tags' => [
				Dummy\DummyRoutedDocument::class,
				['id' => self::ID, 'tags' => ['exchange', 'routing']],
			],
			'routed, without tags' => [
				Dummy\DummyRoutedDocument::class,
				['id' => self::ID],
			],
		];

		foreach ($cases as $name => $case) {
			$cases[$name] = [$name, ...$case];
		}

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<Documents\Document>, array<string, mixed>}>
	 */
	public static function invalid(): array
	{
		return [
			'no identifier' => [
				Dummy\DummyOneDocument::class,
				['id' => self::ID, 'type' => Dummy\DummyOneDocument::TYPE],
			],
			'an identifier which is not a string' => [
				Dummy\DummyOneDocument::class,
				['id' => self::ID, 'type' => Dummy\DummyOneDocument::TYPE, 'identifier' => 42],
			],
			'a blank identifier' => [
				Dummy\DummyOneDocument::class,
				['id' => self::ID, 'type' => Dummy\DummyOneDocument::TYPE, 'identifier' => " \t "],
			],
			'an empty identifier' => [
				Dummy\DummyOneDocument::class,
				['id' => self::ID, 'type' => Dummy\DummyOneDocument::TYPE, 'identifier' => ''],
			],
			'a blank id' => [
				Dummy\DummyOneDocument::class,
				['id' => '  ', 'type' => Dummy\DummyOneDocument::TYPE, 'identifier' => 'x'],
			],
			'an id which is not a string' => [
				Dummy\DummyOneDocument::class,
				['id' => 42, 'type' => Dummy\DummyOneDocument::TYPE, 'identifier' => 'x'],
			],
			'a name which is not a string' => [
				Dummy\DummyOneDocument::class,
				['id' => self::ID, 'type' => Dummy\DummyOneDocument::TYPE, 'identifier' => 'x', 'name' => 42],
			],
			'a date in another format' => [
				Dummy\DummyOneDocument::class,
				[
					'id' => self::ID,
					'type' => Dummy\DummyOneDocument::TYPE,
					'identifier' => 'x',
					'created_at' => '2020-04-01 12:00:00',
				],
			],
			'a date with fractions of a second' => [
				Dummy\DummyOneDocument::class,
				[
					'id' => self::ID,
					'type' => Dummy\DummyOneDocument::TYPE,
					'identifier' => 'x',
					'created_at' => '2020-04-01T12:00:00.123+00:00',
				],
			],
			'a timestamp' => [
				Dummy\DummyOneDocument::class,
				[
					'id' => self::ID,
					'type' => Dummy\DummyOneDocument::TYPE,
					'identifier' => 'x',
					'created_at' => 1_585_742_400,
				],
			],
			'tags which are not a list' => [
				Dummy\DummyRoutedDocument::class,
				['id' => self::ID, 'tags' => ['first' => 'exchange']],
			],
		];
	}

	/**
	 * What DocumentFactory::create() hands the mapper: a string decoded, an object serialised to JSON and back
	 *
	 * @param array<string, mixed>|string|object $data
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Utils\JsonException
	 */
	private static function input(array|string|object $data): array
	{
		if (is_array($data)) {
			return $data;
		}

		$data = Utils\Json::decode(is_string($data) ? $data : Utils\Json::encode($data), forceArrays: true);
		assert(is_array($data));

		/** @var array<string, mixed> $data */
		return $data;
	}

	/**
	 * @return array{json: string, serialized: string}
	 *
	 * @throws Utils\JsonException
	 */
	private static function pinned(string $name): array
	{
		$pinned = Utils\Json::decode(
			(string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/pinned.json'),
			forceArrays: true,
		);
		assert(is_array($pinned) && isset($pinned[$name]) && is_array($pinned[$name]));
		assert(is_string($pinned[$name]['json']) && is_string($pinned[$name]['serialized']));

		return ['json' => $pinned[$name]['json'], 'serialized' => $pinned[$name]['serialized']];
	}

}
