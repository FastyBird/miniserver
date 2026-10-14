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
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use React\Promise;
use ReflectionProperty;
use Throwable;
use function array_key_exists;
use function React\Async\await;

/**
 * The six property state storages take the values to store as a plain array or as an ArrayHash
 * (#723). An array reaches Redis exactly as the ArrayHash Utils\ArrayHash::from() makes of it,
 * which is what the callers built before, and the same state comes back.
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
	public function testAnArrayIsStoredAsTheArrayHashItStandsFor(string $manager, array $change): void
	{
		foreach (['create', 'update', 'update missing'] as $operation) {
			self::assertSame(
				$this->store($manager, $operation, Utils\ArrayHash::from($change)),
				$this->store($manager, $operation, $change),
				$operation,
			);
		}
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
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return array{sets: list<string>, result: mixed}
	 *
	 * @throws Throwable
	 */
	private function store(string $manager, string $operation, array|Utils\ArrayHash $values): array
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
