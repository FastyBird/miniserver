<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\Models;

use DateTimeImmutable;
use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Models;
use FastyBird\Plugin\CouchDb\States;
use FastyBird\Plugin\CouchDb\Tests;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use PHPOnCouch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid;
use ReflectionProperty;
use stdClass;
use Throwable;
use TypeError;
use function array_keys;
use function assert;
use function get_object_vars;
use function is_array;

/**
 * The states manager takes the values to store as a plain array (#723).
 *
 * What an array makes the manager do is recorded in fixtures/states-input.json, taken from the
 * code before #723, which wrapped the array in the ArrayHash Utils\ArrayHash::from() makes of it:
 * the document that reaches CouchDB and the state or the error that comes back. A value that is a
 * nested list is the one difference, noted at its case. An ArrayHash is refused with a TypeError.
 */
final class StatesInputTest extends TestCase
{

	private const string ID = '9b6f0a3e-6a63-4c55-9c2e-2b8c7bdfe2a1';

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function values(): array
	{
		return [
			'string values' => [['value' => 'keyValue', 'camelCased' => 'camel']],
			'null' => [['value' => null]],
			'unchanged value' => [['value' => 'stored']],
			'date' => [['value' => new DateTimeImmutable('2021-02-03T04:05:06+00:00')]],
			'backed enum' => [['value' => Sources\Module::DEVICES]],
			'nested twice' => [['value' => ['list' => [1, 2], 'map' => ['x' => true]]]],
			'unknown key' => [['value' => 'keyValue', 'unknown' => 'ignored']],
			'required value missing' => [['camelCased' => 'camel']],
		];
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws Throwable
	 */
	#[DataProvider('values')]
	public function testAnArrayIsStoredAsRecorded(array $values): void
	{
		$recorded = self::recorded((string) $this->dataName());

		self::assertSame($recorded['create'], self::normalised($this->create($values)), 'create');
		self::assertSame($recorded['update'], self::normalised($this->update($values)), 'update');
	}

	/**
	 * The manager takes an array only, on create() and on update()
	 *
	 * @throws Throwable
	 */
	public function testAnArrayHashIsRefused(): void
	{
		$hash = Utils\ArrayHash::from(['value' => 'keyValue']);
		$id = Uuid\Uuid::fromString(self::ID);
		$manager = $this->createManager($this->createMock(PHPOnCouch\CouchClient::class));
		$data = ['id' => self::ID, 'value' => 'stored', 'camelCased' => null, 'created' => null, 'updated' => null];

		$original = $this->createStateFactory()->create(
			Tests\Fixtures\CustomState::class,
			$this->document($data),
		);

		foreach (
			[
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $manager->create($id, $hash),
				// @phpstan-ignore argument.type (the call is the test: the entry point takes an array only)
				static fn () => $manager->update($original, $hash),
			] as $call
		) {
			self::assertTrue(self::refuses($call));
		}
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Nette\IOException
	 * @throws Utils\JsonException
	 */
	private static function recorded(string $case): array
	{
		$fixture = Utils\Json::decode(
			Utils\FileSystem::read(__DIR__ . '/../../../fixtures/states-input.json'),
			forceArrays: true,
		);
		assert(is_array($fixture) && is_array($fixture[$case]));
		assert(is_array($fixture[$case]['create']) && is_array($fixture[$case]['update']));

		return ['create' => $fixture[$case]['create'], 'update' => $fixture[$case]['update']];
	}

	/**
	 * A record as JSON gives it back, which is how the fixture is written
	 *
	 * @param array<mixed> $record
	 *
	 * @return array<mixed>
	 *
	 * @throws Utils\JsonException
	 */
	private static function normalised(array $record): array
	{
		$decoded = Utils\Json::decode(Utils\Json::encode($record), forceArrays: true);
		assert(is_array($decoded));

		return $decoded;
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @return array{stored: list<string>, result: mixed}
	 */
	private function create(array $values): array
	{
		$stored = [];
		$data = [];

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient->method('storeDoc')->willReturnCallback(
			static function (stdClass $doc) use (&$stored, &$data): void {
				$stored[] = Utils\Json::encode($doc);
				$data = get_object_vars($doc);
			},
		);
		$couchClient->method('getDoc')->willReturnCallback(
			fn (): PHPOnCouch\CouchDocument => $this->document($data),
		);

		try {
			$state = $this->createManager($couchClient)->create(Uuid\Uuid::fromString(self::ID), $values);
			$result = $state->toArray();
		} catch (Throwable $ex) {
			$result = [$ex::class, $ex->getMessage()];
		}

		return ['stored' => $stored, 'result' => $result];
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @return array{stored: list<string>, result: mixed}
	 */
	private function update(array $values): array
	{
		$stored = [];
		$data = ['id' => self::ID, 'value' => 'stored', 'camelCased' => null, 'created' => null, 'updated' => null];

		$document = $this->document($data, $stored);

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient->method('getDoc')->willReturn($document);

		try {
			$original = $this->createStateFactory()->create(Tests\Fixtures\CustomState::class, $document);

			$state = $this->createManager($couchClient)->update($original, $values);
			$result = $state->toArray();
		} catch (Throwable $ex) {
			$result = [$ex::class, $ex->getMessage()];
		}

		return ['stored' => $stored, 'result' => $result];
	}

	/**
	 * A document that reads the given data and records every value set on it
	 *
	 * @param array<mixed> $data
	 * @param list<string> $stored
	 */
	private function document(array &$data, array &$stored = []): PHPOnCouch\CouchDocument
	{
		$document = $this->createMock(PHPOnCouch\CouchDocument::class);
		$document->method('id')->willReturnCallback(static fn (): mixed => $data['id'] ?? null);
		$document->method('get')->willReturnCallback(static fn (string $key): mixed => $data[$key] ?? null);
		$document->method('getKeys')->willReturnCallback(static fn (): array => array_keys($data));
		$document->method('set')->willReturnCallback(
			static function (string $key, mixed $value = null) use (&$data, &$stored): void {
				$stored[] = Utils\Json::encode([$key => $value]);
				$data[$key] = $value;
			},
		);

		return $document;
	}

	/**
	 * @return Models\States\StatesManager<Tests\Fixtures\CustomState>
	 */
	private function createManager(PHPOnCouch\CouchClient $couchClient): Models\States\StatesManager
	{
		// The connection is final: it gets the client double in place of the one it would connect
		$connection = new Connections\Connection('states');
		(new ReflectionProperty(Connections\Connection::class, 'client'))->setValue($connection, $couchClient);

		$clock = $this->createMock(ClockInterface::class);
		$clock->method('now')->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		return new Models\States\StatesManager(
			$connection,
			$this->createStateFactory(),
			$clock,
			Tests\Fixtures\CustomState::class,
		);
	}

	private function createStateFactory(): States\StateFactory
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

		return new States\StateFactory(
			new Documents\Mapping\Mapper(
				new ObjectMapper\Processing\DefaultProcessor($metaLoader, $ruleManager, $objectCreator),
			),
		);
	}

	/**
	 * Whether a call is refused with a TypeError
	 *
	 * @param callable(): mixed $call
	 */
	private static function refuses(callable $call): bool
	{
		try {
			$call();
		} catch (TypeError) {
			return true;
		}

		return false;
	}

}
