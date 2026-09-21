<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\Models;

use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\Models;
use FastyBird\Plugin\CouchDb\States;
use InvalidArgumentException;
use Orisai\ObjectMapper;
use PHPOnCouch;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;

final class StatesRepositoryTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	public function testFetchEntity(): void
	{
		$id = Uuid\Uuid::uuid4();

		$data = [
			'id' => $id->toString(),
			'datatype' => null,
		];

		$couchClient = $this->mockCouchDbWithData($id, $data);

		$repository = $this->createRepository($couchClient);

		$state = $repository->findOne($id);

		self::assertIsObject($state, States\State::class);
	}

	/**
	 * @param array<mixed> $data
	 */
	private function mockCouchDbWithData(
		Uuid\UuidInterface $id,
		array $data,
	): Connections\Connection
	{
		$data['_id'] = $data['id'];

		$couchClient = $this->createMock(PHPOnCouch\CouchClient::class);
		$couchClient
			->method('asCouchDocuments');
		$couchClient
			->expects(self::once())
			->method('find')
			->with([
				'id' => [
					'$eq' => $id->toString(),
				],
			])
			->willReturn([(object) $data]);

		$couchDbConnection = $this->createMock(Connections\Connection::class);
		$couchDbConnection
			->method('getClient')
			->willReturn($couchClient);

		return $couchDbConnection;
	}

	/**
	 * @return Models\States\StatesRepository<States\State>
	 */
	private function createRepository(
		Connections\Connection $couchClient,
	): Models\States\StatesRepository
	{
		$sourceManager = new ObjectMapper\Meta\Source\DefaultMetaSourceManager();
		$sourceManager->addSource(new ObjectMapper\Meta\Source\AttributesMetaSource());
		$injectorManager = new ObjectMapper\Processing\DefaultDependencyInjectorManager();
		$objectCreator = new ObjectMapper\Processing\ObjectCreator($injectorManager);
		$ruleManager = new ObjectMapper\Rules\DefaultRuleManager();
		$ruleManager->addRule(new ApplicationObjectMapper\UuidRule());
		$resolverFactory = new ObjectMapper\Meta\MetaResolverFactory($ruleManager, $objectCreator);
		$cache = new ObjectMapper\Meta\Cache\ArrayMetaCache();
		$metaLoader = new ObjectMapper\Meta\MetaLoader($cache, $sourceManager, $resolverFactory);

		$processor = new ObjectMapper\Processing\DefaultProcessor(
			$metaLoader,
			$ruleManager,
			$objectCreator,
		);

		$factory = new States\StateFactory($processor);

		return new Models\States\StatesRepository($couchClient, $factory);
	}

}
