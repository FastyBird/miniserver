<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Models;

use DateTimeImmutable;
use FastyBird\Core\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\DateTimeFactory;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\Tests;
use Nette\Utils;
use Orisai\ObjectMapper;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;

final class StatesManagerTest extends TestCase
{

	/**
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $data
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $dbData
	 * @param array<Uuid\UuidInterface|array<string, mixed>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 *
	 * @dataProvider createStateValue
	 */
	public function testCreateEntity(Uuid\UuidInterface $id, array $data, array $dbData, array $expected): void
	{
		$redisClient = $this->createMock(Clients\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('set')
			->with($id->toString(), Utils\Json::encode($dbData))
			->willReturn(true);
		$redisClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode($dbData));

		$manager = $this->createManager($redisClient);

		$state = $manager->create($id, Utils\ArrayHash::from($data));

		self::assertSame(Tests\Fixtures\Dummy\DummyState::class, $state::class);
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
	 * @throws Utils\JsonException
	 *
	 * @dataProvider updateStateValue
	 */
	public function testUpdateEntity(
		Uuid\UuidInterface $id,
		array $originalData,
		array $data,
		array $dbData,
		array $expected,
	): void
	{
		$redisClient = $this->createMock(Clients\Client::class);
		$redisClient
			->expects(self::exactly(2))
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode($originalData), Utils\Json::encode($dbData));
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('set')
			->with($id->toString(), Utils\Json::encode($dbData))
			->willReturn(true);

		$manager = $this->createManager($redisClient);

		$sourceManager = new ObjectMapper\Meta\Source\DefaultMetaSourceManager();
		$sourceManager->addSource(new ObjectMapper\Meta\Source\AttributesMetaSource());
		$injectorManager = new ObjectMapper\Processing\DefaultDependencyInjectorManager();
		$objectCreator = new ObjectMapper\Processing\ObjectCreator($injectorManager);
		$ruleManager = new ObjectMapper\Rules\DefaultRuleManager();
		$ruleManager->addRule(new ApplicationObjectMapper\Rules\UuidRule());
		$resolverFactory = new ObjectMapper\Meta\MetaResolverFactory($ruleManager, $objectCreator);
		$cache = new ObjectMapper\Meta\Cache\ArrayMetaCache();
		$metaLoader = new ObjectMapper\Meta\MetaLoader($cache, $sourceManager, $resolverFactory);

		$processor = new ObjectMapper\Processing\DefaultProcessor(
			$metaLoader,
			$ruleManager,
			$objectCreator,
		);

		$factory = new States\StateFactory($processor);

		$original = $factory->create(Tests\Fixtures\Dummy\DummyState::class, Utils\Json::encode($originalData));

		$state = $manager->update($original->getId(), Utils\ArrayHash::from($data));

		self::assertIsObject($state);
		self::assertSame(Tests\Fixtures\Dummy\DummyState::class, $state::class);
		self::assertEquals($expected, $state->toArray());
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testDeleteEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$originalData = [
			'id' => $id->toString(),
			'device' => 'device_name',
			'property' => 'property_name',
		];

		$redisClient = $this->createMock(Clients\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('del')
			->with($id->toString())
			->willReturn(true);

		$manager = $this->createManager($redisClient);

		self::assertTrue($manager->delete(Uuid\Uuid::fromString($originalData['id'])));
	}

	/**
	 * @return Models\States\StatesManager<Tests\Fixtures\Dummy\DummyState>
	 */
	private function createManager(
		Clients\Client&MockObject\MockObject $redisClient,
	): Models\States\StatesManager
	{
		$sourceManager = new ObjectMapper\Meta\Source\DefaultMetaSourceManager();
		$sourceManager->addSource(new ObjectMapper\Meta\Source\AttributesMetaSource());
		$injectorManager = new ObjectMapper\Processing\DefaultDependencyInjectorManager();
		$objectCreator = new ObjectMapper\Processing\ObjectCreator($injectorManager);
		$ruleManager = new ObjectMapper\Rules\DefaultRuleManager();
		$ruleManager->addRule(new ApplicationObjectMapper\Rules\UuidRule());
		$resolverFactory = new ObjectMapper\Meta\MetaResolverFactory($ruleManager, $objectCreator);
		$cache = new ObjectMapper\Meta\Cache\ArrayMetaCache();
		$metaLoader = new ObjectMapper\Meta\MetaLoader($cache, $sourceManager, $resolverFactory);

		$processor = new ObjectMapper\Processing\DefaultProcessor(
			$metaLoader,
			$ruleManager,
			$objectCreator,
		);

		$factory = new States\StateFactory($processor);

		$systemClock = $this->createMock(DateTimeFactory\SystemClock::class);
		$systemClock
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		return new Models\States\StatesManager(
			$redisClient,
			$factory,
			$systemClock,
			Tests\Fixtures\Dummy\DummyState::class,
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
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
				],
				[
					'id' => $id->toString(),
					'value' => 'keyValue',
					'camelCased' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
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
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
				],
				[
					'id' => $id->toString(),
					'value' => null,
					'camelCased' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
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

		return [
			'one' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
				],
				[
					'value' => 'updated',
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camelCased' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2020-04-01T12:00:00+00:00',
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camelCased' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2020-04-01T12:00:00+00:00',
				],
			],
			'two' => [
				$id,
				[
					'id' => $id->toString(),
					'value' => 'value',
					'camelCased' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
				],
				[
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2020-04-01T12:00:00+00:00',
				],
				[
					'id' => $id->toString(),
					'value' => 'updated',
					'camelCased' => 'camelCasedValue',
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => '2020-04-01T12:00:00+00:00',
				],
			],
		];
	}

}
