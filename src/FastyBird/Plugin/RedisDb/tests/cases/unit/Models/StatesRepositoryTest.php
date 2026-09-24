<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Models;

use FastyBird\Core\Persistence\Rules;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\States;
use Nette\Utils;
use Orisai\ObjectMapper;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;

final class StatesRepositoryTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function testFetchEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$data = [
			'id' => $id->toString(),
			'datatype' => null,
		];

		$redisClient = $this->mockRedisWithData($id, $data);

		$repository = $this->createRepository($redisClient);

		$state = $repository->find($id);

		self::assertIsObject($state, States\State::class);
	}

	/**
	 * @param array<mixed> $data
	 *
	 * @return Clients\Client&MockObject\MockObject
	 *
	 * @throws Utils\JsonException
	 */
	private function mockRedisWithData(
		Uuid\UuidInterface $id,
		array $data,
	): MockObject\MockObject
	{
		$data['_id'] = $data['id'];

		$redisClient = $this->createMock(Clients\Client::class);
		$redisClient
			->expects(self::once())
			->method('select')
			->with(0);
		$redisClient
			->expects(self::once())
			->method('get')
			->with($id->toString())
			->willReturn(Utils\Json::encode($data));

		return $redisClient;
	}

	/**
	 * @return Models\States\StatesRepository<States\State>
	 */
	private function createRepository(
		Clients\Client&MockObject\MockObject $redisClient,
	): Models\States\StatesRepository
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

		$processor = new ObjectMapper\Processing\DefaultProcessor(
			$metaLoader,
			$ruleManager,
			$objectCreator,
		);

		$factory = new States\StateFactory($processor);

		return new Models\States\StatesRepository($redisClient, $factory);
	}

}
