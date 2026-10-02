<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Tests\Cases\Unit\Models;

use Exception;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Models;
use FastyBird\Bridge\RedisDbPluginDevicesModule\Tests;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;

final class ChannelPropertiesRepositoryTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 * @throws Exception
	 */
	public function testFindOne(): void
	{
		$id = Uuid\Uuid::uuid4();

		$redisDbClient = $this->createMock(Clients\Client::class);

		$redisDbClient
			->expects(self::once())
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode([
				'id' => $id->toString(),
				'actual_value' => 10,
				'expected_value' => 20,
			]));

		$this->mockContainerService(Clients\Client::class, $redisDbClient);

		$repository = $this->container->getByType(Models\States\ChannelPropertiesRepository::class);

		$state = $repository->find($id);

		self::assertIsObject($state);
		self::assertSame($id->toString(), $state->getId()->toString());
		self::assertSame(10, $state->getActualValue());
		self::assertSame(20, $state->getExpectedValue());
		self::assertNull($state->getCreatedAt());
		self::assertNull($state->getUpdatedAt());
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 * @throws Exception
	 */
	public function testFindOneById(): void
	{
		$id = Uuid\Uuid::uuid4();

		$redisDbClient = $this->createMock(Clients\Client::class);

		$redisDbClient
			->expects(self::once())
			->method('select')
			->with(0);

		$redisDbClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode([
				'id' => $id->toString(),
				'actual_value' => 10,
				'expected_value' => 20,
			]));

		$this->mockContainerService(Clients\Client::class, $redisDbClient);

		$repository = $this->container->getByType(Models\States\ChannelPropertiesRepository::class);

		$property = $this->createMock(DevicesEntities\Channels\Properties\Dynamic::class);
		$property
			->expects(self::once())
			->method('getId')
			->willReturn($id);

		$state = $repository->find($property->getId());

		self::assertIsObject($state);
		self::assertSame($id->toString(), $state->getId()->toString());
		self::assertSame(10, $state->getActualValue());
		self::assertSame(20, $state->getExpectedValue());
		self::assertNull($state->getCreatedAt());
		self::assertNull($state->getUpdatedAt());
	}

}
