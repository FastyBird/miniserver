<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\Tests\Cases\Unit\Models;

use FastyBird\Bridge\RedisDbPluginTriggersModule\Models;
use FastyBird\Bridge\RedisDbPluginTriggersModule\Tests;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use Nette;
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use Throwable;
use TypeError;
use function array_key_exists;
use function assert;
use function is_array;

/**
 * The action and condition state storages take the values to store as a plain array (#723).
 *
 * What an array makes a storage do is recorded in fixtures/states-input.json, taken from the code
 * before #723, which wrapped the array in the ArrayHash Utils\ArrayHash::from() makes of it: the
 * JSON that reaches Redis and the state that comes back. An ArrayHash is refused with a TypeError.
 */
final class StatesInputTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '9b6f0a3e-6a63-4c55-9c2e-2b8c7bdfe2a1';

	private const string STORED = '{"id":"9b6f0a3e-6a63-4c55-9c2e-2b8c7bdfe2a1","validationResult":false,'
		. '"created_at":"2020-04-01T12:00:00+00:00","updated_at":null}';

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
			'triggered' => ['validationResult' => true],
			'unchanged' => ['validationResult' => false],
			'unknown key' => ['validationResult' => true, 'unknown' => 'ignored'],
			'no values' => [],
		];

		$cases = [];

		foreach ([Models\States\ActionsManager::class, Models\States\ConditionsManager::class] as $manager) {
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

		$client = $this->createMock(Clients\Client::class);
		$client->method('get')->willReturnCallback(
			fn (string $key): string|null => array_key_exists($key, $this->data) ? $this->data[$key] : null,
		);
		$client->method('set')->willReturnCallback(function (string $key, string $content): bool {
			$this->data[$key] = $content;
			$this->sets[] = $content;

			return true;
		});

		$this->mockContainerService(Clients\Client::class, $client);
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
			$service instanceof Models\States\ActionsManager || $service instanceof Models\States\ConditionsManager,
		);

		$id = Uuid\Uuid::fromString(self::ID);

		$result = $operation === 'create' ? $service->create($id, $values) : $service->update($id, $values);

		return [
			'sets' => $this->sets,
			'result' => $result instanceof RedisDbStates\State ? $result->toArray() : $result,
		];
	}

}
