<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\States;

use FastyBird\Core\Documents;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use FastyBird\Plugin\RedisDb\Tests\Fixtures\Dummy\DummyState;
use Nette\Utils;
use Orisai\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use stdClass;
use Throwable;

final class FactoryTest extends TestCase
{

	/**
	 * @param class-string<DummyState> $class
	 * @param array<string, array<string|array<string, mixed>>> $data
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	#[DataProvider('createStateValidDocumentData')]
	public function testCreateEntity(string $class, array $data): void
	{
		$raw = new stdClass();

		foreach ($data as $key => $value) {
			$raw->$key = $value;
		}

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

		$factory = new States\StateFactory(new Documents\Mapping\Mapper($processor));

		$entity = $factory->create($class, Utils\Json::encode($raw));

		self::assertTrue($entity instanceof $class);

		$formatted = $entity->toArray();

		foreach ($data as $key => $value) {
			self::assertSame($value, $formatted[$key]);
		}
	}

	/**
	 * @param class-string<DummyState> $class
	 * @param array<string, array<string|array<string, mixed>>> $data
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	#[DataProvider('createStateInvalidDocumentData')]
	public function testCreateEntityFail(string $class, array $data, string $exception): void
	{
		$raw = new stdClass();

		foreach ($data as $key => $value) {
			$raw->$key = $value;
		}

		$this->expectException($exception);

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

		$factory = new States\StateFactory(new Documents\Mapping\Mapper($processor));

		$factory->create($class, Utils\Json::encode($raw));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function testCreateEntityIgnoresUnknownFields(): void
	{
		$id = Uuid\Uuid::uuid4()->toString();

		$entity = $this->createFactory()->create(
			States\State::class,
			Utils\Json::encode(['id' => $id, 'extra' => 'x']),
		);

		self::assertSame($id, $entity->toArray()['id']);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function testCreateEntityFailMessage(): void
	{
		try {
			$this->createFactory()->create(States\State::class, Utils\Json::encode(['id' => 'invalid-string']));

			self::fail('the factory accepted the data');
		} catch (Exceptions\InvalidArgument $ex) {
			self::assertStringStartsWith('Could not map data to state: ', $ex->getMessage());
			self::assertNotSame('Could not map data to state: ', $ex->getMessage());
		}
	}

	/**
	 * @return array<string, array<string|array<string, mixed>>>
	 */
	public static function createStateValidDocumentData(): array
	{
		return [
			'one' => [
				States\State::class,
				[
					'id' => Uuid\Uuid::uuid4()->toString(),
				],
			],
			'two' => [
				States\State::class,
				[
					'id' => Uuid\Uuid::uuid4()->toString(),
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|array<string, mixed>>>
	 */
	public static function createStateInvalidDocumentData(): array
	{
		return [
			'one' => [
				States\State::class,
				[],
				Exceptions\InvalidArgument::class,
			],
			'two' => [
				States\State::class,
				[
					'id' => 'invalid-string',
				],
				Exceptions\InvalidArgument::class,
			],
		];
	}

	private function createFactory(): States\StateFactory
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

}
