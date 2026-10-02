<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Tests\Cases\Unit\Models;

use Exception;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Models;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Tests;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Tests\Tools\JsonAssert;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\RedisDb\Clients;
use Nette\Utils;
use Ramsey\Uuid;

final class ChannelPropertiesManagerTest extends Tests\Cases\Unit\BaseTestCase
{

	private static int $getCalled = 0;

	/**
	 * @throws Exception
	 */
	public function testCreate(): void
	{
		$id = Uuid\Uuid::uuid4();

		$redisDbClient = $this->createMock(Clients\Client::class);

		$redisDbClient
			->expects(self::once())
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('set')
			->with(
				$id->toString(),
				self::callback(static function (string $data) use ($id): bool {
					JsonAssert::assertMatch(Utils\Json::encode([
						'id' => $id->toString(),
						'actual_value' => 10,
						'expected_value' => 20,
						'pending' => false,
						'valid' => false,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => null,
					]), $data);

					return true;
				}),
			)
			->willReturn(true);

		$redisDbClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode([
				'id' => $id->toString(),
				'actual_value' => 10,
				'expected_value' => 20,
				'created_at' => '2020-04-01T12:00:00+00:00',
			]));

		$this->mockContainerService(Clients\Client::class, $redisDbClient);

		$manager = $this->container->getByType(Models\States\ChannelPropertiesManager::class);

		$property = $this->createMock(DevicesEntities\Channels\Properties\Dynamic::class);
		$property
			->expects(self::once())
			->method('getId')
			->willReturn($id);

		$state = $manager->create($property->getId(), Utils\ArrayHash::from([
			DevicesStates\Property::ACTUAL_VALUE_FIELD => 10,
			DevicesStates\Property::EXPECTED_VALUE_FIELD => 20,
		]));

		self::assertSame(10, $state->getActualValue());
		self::assertSame(20, $state->getExpectedValue());
		self::assertNotNull($state->getCreatedAt());
	}

	/**
	 * @throws Exception
	 */
	public function testUpdate(): void
	{
		$id = Uuid\Uuid::uuid4();
		self::$getCalled = 0;

		$redisDbClient = $this->createMock(Clients\Client::class);

		$redisDbClient
			->expects(self::exactly(2))
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('set')
			->with(
				$id->toString(),
				self::callback(static function (string $data) use ($id): bool {
					JsonAssert::assertMatch(Utils\Json::encode([
						'actual_value' => 10,
						'expected_value' => 40,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'id' => $id->toString(),
						'updated_at' => '2020-04-01T12:00:00+00:00',
					]), $data);

					return true;
				}),
			)
			->willReturn(true);

		$redisDbClient
			->expects(self::exactly(3))
			->method('get')
			->with($id->toString())
			->willReturnCallback(
				static function (string $id): string {
					$expected = self::$getCalled > 1 ? 40 : 20;

					self::$getCalled++;

					return Utils\Json::encode([
						'actual_value' => 10,
						'expected_value' => $expected,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'id' => $id,
					]);
				},
			);

		$this->mockContainerService(Clients\Client::class, $redisDbClient);

		$property = $this->createMock(DevicesEntities\Channels\Properties\Dynamic::class);
		$property
			->expects(self::once())
			->method('getId')
			->willReturn($id);

		$repository = $this->container->getByType(Models\States\ChannelPropertiesRepository::class);

		$state = $repository->find($property->getId());

		self::assertNotNull($state);

		$manager = $this->container->getByType(Models\States\ChannelPropertiesManager::class);

		$state = $manager->update($state->getId(), Utils\ArrayHash::from([
			DevicesStates\Property::EXPECTED_VALUE_FIELD => 40,
		]));

		self::assertIsObject($state);
		self::assertSame(10, $state->getActualValue());
		self::assertSame(40, $state->getExpectedValue());
		self::assertNotNull($state->getCreatedAt());
	}

}
