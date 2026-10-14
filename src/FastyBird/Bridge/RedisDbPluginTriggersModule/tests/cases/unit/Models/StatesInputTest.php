<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\Tests\Cases\Unit\Models;

use FastyBird\Bridge\RedisDbPluginTriggersModule\Models;
use FastyBird\Bridge\RedisDbPluginTriggersModule\Tests;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid;
use Throwable;
use function array_key_exists;

/**
 * The action and condition state storages take the values to store as a plain array or as an
 * ArrayHash (#723). An array reaches Redis exactly as the ArrayHash Utils\ArrayHash::from() makes
 * of it, which is what the callers built before, and the same state comes back.
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
