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
use function array_keys;
use function get_object_vars;

/**
 * The states manager takes the values to store as a plain array or as an ArrayHash (#723). An
 * array is stored exactly as the ArrayHash Utils\ArrayHash::from() makes of it, which is what the
 * callers built before: the same document reaches CouchDB and the same state or error comes back.
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
	public function testAnArrayIsStoredAsTheArrayHashItStandsFor(array $values): void
	{
		self::assertSame($this->create(Utils\ArrayHash::from($values)), $this->create($values), 'create');
		self::assertSame($this->update(Utils\ArrayHash::from($values)), $this->update($values), 'update');
	}

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return array{stored: list<string>, result: mixed}
	 */
	private function create(array|Utils\ArrayHash $values): array
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
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return array{stored: list<string>, result: mixed}
	 */
	private function update(array|Utils\ArrayHash $values): array
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

}
