<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit\States;

use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use FastyBird\Plugin\CouchDb\Tests;
use InvalidArgumentException;
use Orisai\ObjectMapper;
use PHPOnCouch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use Throwable;
use function array_keys;

final class FactoryTest extends TestCase
{

	/**
	 * @param class-string<Tests\Fixtures\CustomState> $class
	 * @param array<string, array<string|array<string, mixed>>> $data
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	#[DataProvider('createStateValidDocumentData')]
	public function testCreateEntity(string $class, array $data): void
	{
		$document = $this->createMock(PHPOnCouch\CouchDocument::class);
		$document
			->method('getKeys')
			->willReturn(array_keys($data));
		$document
			->method('get')
			->willReturnCallback(static fn ($key) => $data[$key]);
		$document
			->method('id')
			->willReturn($data['id']);

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

		$entity = $factory->create($class, $document);

		self::assertTrue($entity instanceof $class);

		$formatted = $entity->toArray();

		foreach ($data as $key => $value) {
			self::assertSame($value, $formatted[$key]);
		}
	}

	/**
	 * @param class-string<Tests\Fixtures\CustomState> $class
	 * @param array<string, array<string|array<string, mixed>>> $data
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	#[DataProvider('createStateInvalidDocumentData')]
	public function testCreateEntityFail(string $class, array $data, string $exception): void
	{
		$document = $this->createMock(PHPOnCouch\CouchDocument::class);
		$document
			->method('getKeys')
			->willReturn(array_keys($data));
		$document
			->method('get')
			->willReturnCallback(static fn ($key) => $data[$key]);

		$this->expectException($exception);

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

		$factory->create($class, $document);
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

}
