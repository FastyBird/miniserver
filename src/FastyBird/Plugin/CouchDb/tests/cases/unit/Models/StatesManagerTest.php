<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\Models;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use FastyBird\Core\Clock;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\Models;
use FastyBird\Plugin\CouchDb\States;
use FastyBird\Plugin\CouchDb\Tests;
use InvalidArgumentException;
use Nette\Utils;
use Orisai\ObjectMapper;
use PHPOnCouch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use stdClass;
use function array_keys;

final class StatesManagerTest extends TestCase
{

	/**
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $data
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $dbData
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $expected
	 *
	 * @throws Exceptions\InvalidState
	 */
	#[DataProvider('createStateValue')]
	public function testCreateEntity(Uuid\UuidInterface $id, array $data, array $dbData, array $expected): void
	{
		$id = Uuid\Uuid::uuid4();

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->method('storeDoc')
			->with(self::callback(static function (stdClass $create) use ($dbData): bool {
				foreach ($dbData as $key => $value) {
					if ($key !== 'id') {
						self::assertEquals($value, $create->$key);
					}
				}

				return true;
			}));
		$couchClient
			->method('asCouchDocuments');
		$couchClient
			->method('getDoc')
			->willReturnCallback(function () use ($dbData, $id): PHPOnCouch\CouchDocument {
				$dbData['id'] = $id->toString();

				$document = $this->createMock(PHPOnCouch\CouchDocument::class);

				$document
					->method('id')
					->willReturn($dbData['id']);
				$document
					->method('get')
					->willReturnCallback(static fn ($key) => $dbData[$key]);
				$document
					->method('getKeys')
					->willReturn(array_keys($dbData));

				return $document;
			});

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		$manager = $this->createManager($couchDbConnection);

		$state = $manager->create($id, Utils\ArrayHash::from($data));

		$expected['id'] = $id->toString();

		self::assertSame(Tests\Fixtures\CustomState::class, $state::class);
		self::assertEquals($expected, $state->toArray());
	}

	/**
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $originalData
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $data
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $dbData
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	#[DataProvider('updateStateValue')]
	public function testUpdateEntity(
		Uuid\UuidInterface $id,
		array $originalData,
		array $data,
		array $dbData,
		array $expected,
	): void
	{
		$document = $this->createMock(PHPOnCouch\CouchDocument::class);

		$document
			->method('setAutocommit');
		$document
			->method('get')
			->willReturnCallback(static function ($key) use (&$originalData) {
				return $originalData[$key];
			});
		$document
			->method('set')
			->willReturnCallback(
				static function (string $key, string|null $value = null) use ($data, &$originalData): void {
					if ($data[$key] instanceof BackedEnum) {
						self::assertEquals((string) $data[$key], $value);

					} else {
						self::assertEquals($data[$key], $value);
					}

					$originalData[$key] = $value;
				},
			);
		$document
			->method('record');
		$document
			->method('getKeys')
			->willReturn(array_keys($originalData));
		$document
			->method('id')
			->willReturn($originalData['id']);

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->method('asCouchDocuments');
		$couchClient
			->expects(self::once())
			->method('getDoc')
			->with($originalData['id'])
			->willReturn($document);

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		$manager = $this->createManager($couchDbConnection);

		$sourceManager = new ObjectMapper\Meta\Source\DefaultMetaSourceManager();
		$sourceManager->addSource(new ObjectMapper\Meta\Source\AttributesMetaSource());
		$injectorManager = new ObjectMapper\Processing\DefaultDependencyInjectorManager();
		$objectCreator = new ObjectMapper\Processing\ObjectCreator($injectorManager);
		$ruleManager = new ObjectMapper\Rules\DefaultRuleManager();
		$ruleManager->addRule(new Rules\UuidRule());
		$resolverFactory = new ObjectMapper\Meta\MetaResolverFactory($ruleManager, $objectCreator);
		$cache = new ObjectMapper\Meta\Cache\ArrayMetaCache();
		$metaLoader = new ObjectMapper\Meta\MetaLoader($cache, $sourceManager, $resolverFactory);

		$processor = new ObjectMapper\Processing\DefaultProcessor(
			$metaLoader,
			$ruleManager,
			$objectCreator,
		);

		$factory = new States\StateFactory($processor);

		$original = $factory->create(Tests\Fixtures\CustomState::class, $document);

		$state = $manager->update($original, Utils\ArrayHash::from($data));

		self::assertSame(Tests\Fixtures\CustomState::class, $state::class);
		self::assertEquals($expected, $state->toArray());
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	public function testDeleteEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$originalData = [
			'id' => $id->toString(),
			'device' => 'device_name',
			'property' => 'property_name',
		];

		$document = $this->createMock(PHPOnCouch\CouchDocument::class);

		$document
			->method('get')
			->willReturnCallback(static function ($key) use (&$originalData) {
				return $originalData[$key];
			});
		$document
			->method('getKeys')
			->willReturn(array_keys($originalData));
		$document
			->method('id')
			->willReturn($originalData['id']);

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->expects(self::once())
			->method('asCouchDocuments');
		$couchClient
			->expects(self::once())
			->method('getDoc')
			->with($originalData['id'])
			->willReturn($document);
		$couchClient
			->expects(self::once())
			->method('deleteDoc')
			->with($document);

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		$manager = $this->createManager($couchDbConnection);

		$sourceManager = new ObjectMapper\Meta\Source\DefaultMetaSourceManager();
		$sourceManager->addSource(new ObjectMapper\Meta\Source\AttributesMetaSource());
		$injectorManager = new ObjectMapper\Processing\DefaultDependencyInjectorManager();
		$objectCreator = new ObjectMapper\Processing\ObjectCreator($injectorManager);
		$ruleManager = new ObjectMapper\Rules\DefaultRuleManager();
		$ruleManager->addRule(new Rules\UuidRule());
		$resolverFactory = new ObjectMapper\Meta\MetaResolverFactory($ruleManager, $objectCreator);
		$cache = new ObjectMapper\Meta\Cache\ArrayMetaCache();
		$metaLoader = new ObjectMapper\Meta\MetaLoader($cache, $sourceManager, $resolverFactory);

		$processor = new ObjectMapper\Processing\DefaultProcessor(
			$metaLoader,
			$ruleManager,
			$objectCreator,
		);

		$factory = new States\StateFactory($processor);

		$original = $factory->create(Tests\Fixtures\CustomState::class, $document);

		self::assertTrue($manager->delete($original));
	}

	/**
	 * @return Models\States\StatesManager<Tests\Fixtures\CustomState>
	 */
	private function createManager(
		Connections\Connection $couchClient,
	): Models\States\StatesManager
	{
		$sourceManager = new ObjectMapper\Meta\Source\DefaultMetaSourceManager();
		$sourceManager->addSource(new ObjectMapper\Meta\Source\AttributesMetaSource());
		$injectorManager = new ObjectMapper\Processing\DefaultDependencyInjectorManager();
		$objectCreator = new ObjectMapper\Processing\ObjectCreator($injectorManager);
		$ruleManager = new ObjectMapper\Rules\DefaultRuleManager();
		$ruleManager->addRule(new Rules\UuidRule());
		$resolverFactory = new ObjectMapper\Meta\MetaResolverFactory($ruleManager, $objectCreator);
		$cache = new ObjectMapper\Meta\Cache\ArrayMetaCache();
		$metaLoader = new ObjectMapper\Meta\MetaLoader($cache, $sourceManager, $resolverFactory);

		$processor = new ObjectMapper\Processing\DefaultProcessor(
			$metaLoader,
			$ruleManager,
			$objectCreator,
		);

		$factory = new States\StateFactory($processor);

		$systemClock = $this->createMock(Clock\SystemClock::class);

		return new Models\States\StatesManager(
			$couchClient,
			$factory,
			$systemClock,
			Tests\Fixtures\CustomState::class,
		);
	}

	/**
	 * @return array<string, array<Uuid\UuidInterface|array<string, mixed>>>
	 */
	public static function createStateValue(): array
	{
		$id = Uuid\Uuid::uuid4();

		return [
			'one' => [
				$id,
				[
					'value' => 'keyValue',
				],
				[
					'id' => $id->toString(),
					'value' => 'keyValue',
					'camelCased' => null,
					'created' => null,
				],
				[
					'id' => $id->toString(),
					'value' => 'keyValue',
					'camelCased' => null,
					'created' => null,
					'updated' => null,
				],
			],
			'two' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => null,
				],
				[
					'id' => $id->toString(),
					'value' => null,
					'camelCased' => null,
					'created' => null,
				],
				[
					'id' => $id->toString(),
					'value' => null,
					'camelCased' => null,
					'created' => null,
					'updated' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array<Uuid\UuidInterface|array<string, mixed>>>
	 */
	public static function updateStateValue(): array
	{
		$id = Uuid\Uuid::uuid4();
		$now = new DateTimeImmutable();

		return [
			'one' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created' => $now->format(DateTimeInterface::ATOM),
					'updated' => null,
				],
				[
					'updated' => $now->format(DateTimeInterface::ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created' => $now->format(DateTimeInterface::ATOM),
					'updated' => $now->format(DateTimeInterface::ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created' => $now->format(DateTimeInterface::ATOM),
					'updated' => $now->format(DateTimeInterface::ATOM),
				],
			],
			'two' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created' => $now->format(DateTimeInterface::ATOM),
					'updated' => null,
				],
				[
					'updated' => $now->format(DateTimeInterface::ATOM),
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
					'created' => $now->format(DateTimeInterface::ATOM),
					'updated' => $now->format(DateTimeInterface::ATOM),
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
					'created' => $now->format(DateTimeInterface::ATOM),
					'updated' => $now->format(DateTimeInterface::ATOM),
				],
			],
		];
	}

}
