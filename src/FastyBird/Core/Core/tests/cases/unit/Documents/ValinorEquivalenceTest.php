<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Documents;

use CuyZ\Valinor;
use DateTimeInterface;
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
 * The Valinor spike (#724 PR0). Each input goes through DocumentFactory, which maps it with orisai, and through Valinor
 * configured as below; both documents must serialise to the same JSON, through the same encoder the exchange and the
 * WebSockets use, and hold the same state, compared with serialize() so that an int is not a float and an unset
 * property is not a null one. The fixtures carry orisai's attributes and the Valinor ones beside them.
 */
final class ValinorEquivalenceTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	/**
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed>|string|object $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('documents')]
	public function testValinorMapsTheSameDocument(string $class, array|string|object $data): void
	{
		$orisai = $this->container->getByType(Documents\DocumentFactory::class)->create($class, $data);
		$valinor = self::valinor()->map($orisai::class, self::input($data));

		self::assertSame(Utils\Json::encode($orisai->toArray()), Utils\Json::encode($valinor->toArray()));
		self::assertSame(serialize($orisai), serialize($valinor));
	}

	/**
	 * The known-positive control: the comparison above has to tell two documents apart when one field differs
	 *
	 * @throws Throwable
	 */
	public function testOneChangedFieldIsTold(): void
	{
		$data = self::input((string) file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.json'));

		$orisai = $this->container->getByType(Documents\DocumentFactory::class)->create(
			Dummy\DummyTwoDocument::class,
			$data,
		);
		$valinor = self::valinor()->map(Dummy\DummyTwoDocument::class, array_merge($data, ['name' => 'Another name']));

		self::assertNotSame(Utils\Json::encode($orisai->toArray()), Utils\Json::encode($valinor->toArray()));
		self::assertNotSame(serialize($orisai), serialize($valinor));
	}

	/**
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	#[DataProvider('invalid')]
	public function testValinorRefusesWhatOrisaiRefuses(string $class, array $data): void
	{
		$orisaiRefused = false;
		$valinorRefused = false;

		try {
			$this->container->getByType(Documents\DocumentFactory::class)->create($class, $data);
		} catch (CoreExceptions\InvalidArgument) {
			$orisaiRefused = true;
		}

		try {
			self::valinor()->map($class, $data);
		} catch (Valinor\Mapper\MappingError) {
			$valinorRefused = true;
		}

		self::assertTrue($orisaiRefused);
		self::assertTrue($valinorRefused);
	}

	/**
	 * @return array<string, array{class-string<Documents\Document>, array<string, mixed>|string|object}>
	 */
	public static function documents(): array
	{
		return [
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

	private static function valinor(): Valinor\Mapper\TreeMapper
	{
		return (new Valinor\MapperBuilder())
			->allowSuperfluousKeys()
			->supportDateFormats(DateTimeInterface::ATOM)
			->mapper();
	}

}
