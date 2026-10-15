<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Models;

use Clue\React\Redis;
use DateTimeImmutable;
use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\Tests;
use Nette\Utils;
use Orisai\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid;
use React\Promise;
use ReflectionProperty;
use Throwable;
use function array_key_exists;
use function React\Async\await;

/**
 * The states managers take the values to store as a plain array or as an ArrayHash (#723).
 *
 * An array is stored exactly as the ArrayHash Utils\ArrayHash::from() makes of it, which is
 * what the callers built before: the same JSON reaches Redis, the same state or error comes
 * back, and the same keys are deleted. An ArrayHash is used as it is given, so the caller's
 * object still receives the identifier the manager sets on it.
 */
final class StatesInputTest extends TestCase
{

	private const string ID = '9b6f0a3e-6a63-4c55-9c2e-2b8c7bdfe2a1';

	private const string STORED = '{"id":"9b6f0a3e-6a63-4c55-9c2e-2b8c7bdfe2a1","value":"stored","camelCased":null,'
		. '"created_at":"2020-04-01T12:00:00+00:00","updated_at":null}';

	/**
	 * @return array<string, array{array<string, mixed>}>
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public static function values(): array
	{
		return [
			'string values' => [['value' => 'keyValue', 'camelCased' => 'camel']],
			'null' => [['value' => null]],
			'unchanged value' => [['value' => 'stored']],
			'date' => [['value' => new DateTimeImmutable('2021-02-03T04:05:06+00:00')]],
			'backed enum' => [['value' => Sources\Module::DEVICES]],
			'stringable object' => [['value' => Uuid\Uuid::fromString('6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a')]],
			'integer' => [['value' => 10]],
			'nested once' => [['value' => ['a' => 1, 'b' => 'two']]],
			'nested twice' => [['value' => ['list' => [1, 2], 'map' => ['x' => true]], 'camelCased' => [[3], []]]],
			'unknown key' => [['value' => 'keyValue', 'unknown' => 'ignored']],
			'own identifier' => [['id' => '17c59dfa-2edd-438e-8c49-faa4e38e5a5e', 'value' => 'keyValue']],
			'required value missing' => [['camelCased' => 'camel']],
			'no values' => [[]],
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
		foreach (['create', 'update', 'update missing'] as $operation) {
			foreach ([false, true] as $async) {
				self::assertSame(
					$this->store($operation, $async, Utils\ArrayHash::from($values)),
					$this->store($operation, $async, $values),
					$operation . ($async ? ', async' : ''),
				);
			}
		}
	}

	/**
	 * @throws Throwable
	 */
	public function testAnArrayHashIsUsedAsItIsGiven(): void
	{
		foreach ([false, true] as $async) {
			$values = Utils\ArrayHash::from(['value' => 'keyValue']);

			$this->store('create', $async, $values);

			self::assertTrue(isset($values['id']));
			self::assertSame(self::ID, $values['id']);
		}
	}

	/**
	 * Runs one operation on a store that holds STORED (or nothing, for "update missing" and
	 * "create") and records what reaches Redis and what comes back
	 *
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return array{sets: list<string>, deletes: list<string>, result: mixed}
	 *
	 * @throws Throwable
	 */
	private function store(string $operation, bool $async, array|Utils\ArrayHash $values): array
	{
		$data = $operation === 'update' ? [self::ID => self::STORED] : [];
		$sets = [];
		$deletes = [];

		$get = static function (string $key) use (&$data): string|null {
			return array_key_exists($key, $data) ? $data[$key] : null;
		};
		$set = static function (string $key, string $content) use (&$data, &$sets): bool {
			$data[$key] = $content;
			$sets[] = $content;

			return true;
		};
		$del = static function (string $key) use (&$data, &$deletes): bool {
			unset($data[$key]);
			$deletes[] = $key;

			return true;
		};

		$id = Uuid\Uuid::fromString(self::ID);

		try {
			if ($async) {
				// The asynchronous client is final: it gets a Redis connection that answers from the
				// same store, in the replies Redis gives
				$redis = $this->createMock(Redis\RedisClient::class);
				$redis->method('__call')->willReturnCallback(
					static function (string $name, array $arguments) use ($get, $set, $del): Promise\PromiseInterface {
						$key = $arguments[0];
						self::assertIsString($key);

						if ($name === 'set') {
							$content = $arguments[1];
							self::assertIsString($content);

							$set($key, $content);

							return Promise\resolve('OK');
						}

						if ($name === 'del') {
							$del($key);

							return Promise\resolve(1);
						}

						return Promise\resolve($name === 'get' ? $get($key) : null);
					},
				);

				$client = new Clients\Async\Client(new Connections\Configuration());
				(new ReflectionProperty(Clients\Async\Client::class, 'redis'))->setValue($client, $redis);

				$manager = new Models\States\Async\StatesManager(
					$client,
					$this->createStateFactory(),
					$this->createClock(),
					Tests\Fixtures\Dummy\DummyState::class,
				);

				$result = await(
					$operation === 'create' ? $manager->create($id, $values) : $manager->update($id, $values),
				);
			} else {
				$client = $this->createMock(Clients\Client::class);
				$client->method('get')->willReturnCallback($get);
				$client->method('set')->willReturnCallback($set);
				$client->method('del')->willReturnCallback($del);

				$manager = new Models\States\StatesManager(
					$client,
					$this->createStateFactory(),
					$this->createClock(),
					Tests\Fixtures\Dummy\DummyState::class,
				);

				$result = $operation === 'create' ? $manager->create($id, $values) : $manager->update($id, $values);
			}

			$result = $result instanceof States\State ? $result->toArray() : $result;
		} catch (Throwable $ex) {
			$result = [$ex::class, $ex->getMessage()];
		}

		return ['sets' => $sets, 'deletes' => $deletes, 'result' => $result];
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

	private function createClock(): ClockInterface
	{
		$clock = $this->createMock(ClockInterface::class);
		$clock
			->method('now')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		return $clock;
	}

}
