<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Tests\Cases\Unit\Models;

use Clue\React\Redis;
use DateTimeImmutable;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Models;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Tests;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Connections;
use Nette;
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use React\Promise;
use ReflectionProperty;
use Throwable;
use TypeError;
use function array_key_exists;
use function assert;
use function is_array;
use function React\Async\await;

/**
 * The six property state storages take the values to store as a plain array (#723).
 *
 * What an array makes a storage do is recorded in fixtures/states-input.json, taken from the code
 * before #723, which wrapped the array in the ArrayHash Utils\ArrayHash::from() makes of it: the
 * JSON that reaches Redis and the state that comes back. An ArrayHash is refused with a TypeError.
 */
final class StatesInputTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '9b6f0a3e-6a63-4c55-9c2e-2b8c7bdfe2a1';

	private const string STORED = '{"id":"9b6f0a3e-6a63-4c55-9c2e-2b8c7bdfe2a1","actual_value":"stored",'
		. '"expected_value":null,"pending":false,"valid":true,"created_at":"2020-04-01T12:00:00+00:00",'
		. '"updated_at":null}';

	/** @var array<string, string> */
	private array $data = [];

	/** @var list<string> */
	private array $sets = [];

	/**
	 * @return array<string, array{class-string, array<string, mixed>}>
	 */
	public static function changes(): array
	{
		$changes = [
			'values' => [
				DevicesStates\Property::ACTUAL_VALUE_FIELD => 10,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => 20,
			],
			'invalid' => [
				DevicesStates\Property::ACTUAL_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => false,
			],
			'pending' => [
				DevicesStates\Property::EXPECTED_VALUE_FIELD => Payloads\Switcher::ON,
				DevicesStates\Property::PENDING_FIELD => new DateTimeImmutable('2021-02-03T04:05:06+00:00'),
			],
			'unchanged' => [
				DevicesStates\Property::ACTUAL_VALUE_FIELD => 'stored',
			],
		];

		$cases = [];

		foreach (
			[
				Models\States\ConnectorPropertiesManager::class,
				Models\States\DevicePropertiesManager::class,
				Models\States\ChannelPropertiesManager::class,
				Models\States\Async\ConnectorPropertiesManager::class,
				Models\States\Async\DevicePropertiesManager::class,
				Models\States\Async\ChannelPropertiesManager::class,
			] as $manager
		) {
			foreach ($changes as $name => $change) {
				$cases[$manager . ', ' . $name] = [$manager, $change];
			}
		}

		return $cases;
	}

	/**
	 * @param class-string $manager
	 * @param array<string, mixed> $change
	 *
	 * @throws Throwable
	 */
	#[DataProvider('changes')]
	public function testAnArrayIsStoredAsRecorded(string $manager, array $change): void
	{
		$recorded = self::recorded($manager . '|' . $this->dataName());

		foreach (['create', 'update', 'update missing'] as $operation) {
			self::assertSame(
				$recorded[$operation],
				self::normalised($this->store($manager, $operation, $change)),
				$operation,
			);
		}
	}

	/**
	 * Every storage takes an array only, on create() and on update()
	 *
	 * @param class-string $manager
	 * @param array<string, mixed> $change
	 *
	 * @throws Throwable
	 */
	#[DataProvider('changes')]
	public function testAnArrayHashIsRefused(string $manager, array $change): void
	{
		$hash = Utils\ArrayHash::from($change);

		foreach (['create', 'update'] as $operation) {
			try {
				// @phpstan-ignore argument.type (the call is the test: the storage takes an array only)
				$this->store($manager, $operation, $hash);

				self::fail('An ArrayHash was accepted');
			} catch (TypeError) {
				self::assertSame([], $this->sets);
			}
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

		return $fixture[$case];
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

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$get = fn (string $key): string|null => array_key_exists($key, $this->data) ? $this->data[$key] : null;
		$set = function (string $key, string $content): bool {
			$this->data[$key] = $content;
			$this->sets[] = $content;

			return true;
		};

		$client = $this->createMock(Clients\Client::class);
		$client->method('get')->willReturnCallback($get);
		$client->method('set')->willReturnCallback($set);

		// The asynchronous client is final: it gets a Redis connection that answers from the same
		// store, in the replies Redis gives
		$redis = $this->createMock(Redis\RedisClient::class);
		$redis->method('__call')->willReturnCallback(
			static function (string $name, array $arguments) use ($get, $set): Promise\PromiseInterface {
				$key = $arguments[0];
				self::assertIsString($key);

				if ($name === 'set') {
					$content = $arguments[1];
					self::assertIsString($content);

					$set($key, $content);

					return Promise\resolve('OK');
				}

				return Promise\resolve($name === 'get' ? $get($key) : null);
			},
		);

		$asyncClient = new Clients\Async\Client(new Connections\Configuration());
		(new ReflectionProperty(Clients\Async\Client::class, 'redis'))->setValue($asyncClient, $redis);

		$this->mockContainerService(Clients\Client::class, $client);
		$this->mockContainerService(Clients\Async\Client::class, $asyncClient);
	}

	/**
	 * @param class-string $manager
	 * @param array<string, mixed> $values
	 *
	 * @return array{sets: list<string>, result: mixed}
	 *
	 * @throws Throwable
	 */
	private function store(string $manager, string $operation, array $values): array
	{
		$this->data = $operation === 'update' ? [self::ID => self::STORED] : [];
		$this->sets = [];

		$service = $this->container->getByType($manager);
		self::assertTrue(
			$service instanceof Models\States\ConnectorPropertiesManager
			|| $service instanceof Models\States\DevicePropertiesManager
			|| $service instanceof Models\States\ChannelPropertiesManager
			|| $service instanceof Models\States\Async\ConnectorPropertiesManager
			|| $service instanceof Models\States\Async\DevicePropertiesManager
			|| $service instanceof Models\States\Async\ChannelPropertiesManager,
		);

		$id = Uuid\Uuid::fromString(self::ID);

		$result = $operation === 'create' ? $service->create($id, $values) : $service->update($id, $values);

		if ($result instanceof Promise\PromiseInterface) {
			$result = await($result);
		}

		return [
			'sets' => $this->sets,
			'result' => $result instanceof DevicesStates\Property ? $result->toArray() : $result,
		];
	}

}
