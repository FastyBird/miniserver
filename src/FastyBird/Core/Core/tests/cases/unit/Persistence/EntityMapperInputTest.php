<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Persistence;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Mapping;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Persistence;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use ReflectionClass;
use ReflectionProperty;
use Throwable;
use function assert;
use function is_array;
use function is_object;
use function ksort;
use function spl_object_id;

/**
 * The CRUD boundary takes a plain array as well as an ArrayHash (#723, D3): EntityCreator,
 * EntityUpdater and EntityMapper turn an array into the ArrayHash Utils\ArrayHash::from() makes
 * of it, once, at their entry, so both inputs take one path.
 *
 * That path relies on nested `instanceof Utils\ArrayHash` checks: the values of a to-one
 * relationship, of every item of a to-many relationship, and of a relationship nested inside
 * such an item. So each test fills the whole graph of tests/fixtures/Persistence -- a plain
 * field, a JSON field with nested values, a to-one reference, a to-one relationship created from
 * nested values, and a to-many relationship to an abstract entity whose items carry a to-one
 * relationship of their own -- once from each input, and compares the two graphs: as the
 * creator or updater returns them, and as read back from the database.
 *
 * The ArrayHash tests pin the graph itself. They pass unchanged against the code before #723,
 * which is the proof that an ArrayHash caller sees no change.
 */
final class EntityMapperInputTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string AUTHOR = '0a7c1c2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b';

	private const string ARTICLE = '1b8d2d3f-4c5e-4f60-9bac-1d2e3f4a5b6c';

	private const string SUMMARY = '2c9e3e40-5d6f-4071-8cbd-2e3f4a5b6c7d';

	private const string TEXT_COMMENT = '3daf4f51-6e70-4182-9dce-3f4a5b6c7d8e';

	private const string RATING_COMMENT = '4eb05062-7f81-4293-8edf-4a5b6c7d8e9f';

	private const string ATTACHMENT = '5fc16173-8092-43a4-9fe0-5b6c7d8e9fa0';

	private const string NEW_COMMENT = '60d27284-91a3-44b5-80f1-6c7d8e9fa0b1';

	/**
	 * @throws Throwable
	 */
	public function testCreateFromAnArrayHashFillsTheWholeGraph(): void
	{
		$container = $this->databaseContainer();

		$article = $this->creator($container)->create(Utils\ArrayHash::from($this->createValues($container)));

		self::assertSame($this->createdGraph(), $this->describe($article));
	}

	/**
	 * @throws Throwable
	 */
	public function testCreateFromAnArrayFillsTheSameGraph(): void
	{
		$hashContainer = $this->databaseContainer();
		$arrayContainer = $this->databaseContainer();

		$fromHash = $this->creator($hashContainer)->create(Utils\ArrayHash::from($this->createValues($hashContainer)));
		$fromArray = $this->creator($arrayContainer)->create($this->createValues($arrayContainer));

		self::assertSame($this->createdGraph(), $this->describe($fromArray));
		self::assertSame($this->describe($fromHash), $this->describe($fromArray));
		self::assertSame(
			$this->describe($this->reload($hashContainer)),
			$this->describe($this->reload($arrayContainer)),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testUpdateFromAnArrayHashChangesTheWholeGraph(): void
	{
		$container = $this->databaseContainer();

		$article = $this->creator($container)->create(Utils\ArrayHash::from($this->createValues($container)));

		$article = $this->updater($container)->update(Utils\ArrayHash::from($this->updateValues()), $article);

		self::assertSame($this->updatedGraph(), $this->describe($article));
	}

	/**
	 * @throws Throwable
	 */
	public function testUpdateFromAnArrayChangesTheGraphTheSameWay(): void
	{
		$hashContainer = $this->databaseContainer();
		$arrayContainer = $this->databaseContainer();

		$fromHash = $this->creator($hashContainer)->create(Utils\ArrayHash::from($this->createValues($hashContainer)));
		$fromArray = $this->creator($arrayContainer)->create(
			Utils\ArrayHash::from($this->createValues($arrayContainer)),
		);

		$fromHash = $this->updater($hashContainer)->update(Utils\ArrayHash::from($this->updateValues()), $fromHash);
		$fromArray = $this->updater($arrayContainer)->update($this->updateValues(), $fromArray);

		self::assertSame($this->updatedGraph(), $this->describe($fromArray));
		self::assertSame($this->describe($fromHash), $this->describe($fromArray));
		self::assertSame(
			$this->describe($this->reload($hashContainer)),
			$this->describe($this->reload($arrayContainer)),
		);
	}

	/**
	 * EntityMapper is public too, and is the one place the nested values are read
	 *
	 * @throws Throwable
	 */
	public function testTheMapperFillsAnEntityFromAnArrayAsFromAnArrayHash(): void
	{
		$container = $this->databaseContainer();
		// Not autowired: the creator and the updater are its only consumers in the container
		$mapper = $container->getService('fbCore.persistence.entity.mapper');
		assert($mapper instanceof Mapping\EntityMapper);

		$author = $container->getByType(ORM\EntityManagerInterface::class)
			->find(Persistence\Author::class, Uuid\Uuid::fromString(self::AUTHOR));
		assert($author instanceof Persistence\Author);

		$fromHash = new Persistence\Article('Draft', $author, Uuid\Uuid::fromString(self::ARTICLE));
		$fromArray = new Persistence\Article('Draft', $author, Uuid\Uuid::fromString(self::ARTICLE));

		$mapper->fillEntity(Utils\ArrayHash::from($this->createValues($container)), $fromHash, true);
		$mapper->fillEntity($this->createValues($container), $fromArray, true);

		self::assertSame($this->describe($fromHash), $this->describe($fromArray));
		self::assertSame('Hydration', $fromArray->getTitle());
		self::assertSame('Short', $fromArray->getSummary()?->getText());
		self::assertCount(2, $fromArray->getComments());
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function createValues(Nette\DI\Container $container): array
	{
		$author = $container->getByType(ORM\EntityManagerInterface::class)
			->find(Persistence\Author::class, Uuid\Uuid::fromString(self::AUTHOR));
		assert($author instanceof Persistence\Author);

		// Shaped like a JSON:API hydrator's output: the entity class, the identifier, the
		// attributes, a resolved to-one reference and the values of the included relationships
		return [
			'entity' => Persistence\Article::class,
			'id' => Uuid\Uuid::fromString(self::ARTICLE),
			'title' => 'Hydration',
			'params' => [
				'tags' => ['mapper', 'input'],
				'layout' => ['columns' => 2],
			],
			'author' => $author,
			'summary' => [
				'id' => Uuid\Uuid::fromString(self::SUMMARY),
				'text' => 'Short',
				'words' => 1,
			],
			'comments' => [
				[
					'entity' => Persistence\TextComment::class,
					'id' => Uuid\Uuid::fromString(self::TEXT_COMMENT),
					'body' => 'First',
					'attachment' => [
						'id' => Uuid\Uuid::fromString(self::ATTACHMENT),
						'file' => 'first.png',
					],
				],
				[
					'entity' => Persistence\RatingComment::class,
					'id' => Uuid\Uuid::fromString(self::RATING_COMMENT),
					'body' => 'Second',
					'score' => 5,
				],
			],
		];
	}

	/**
	 * An update of every kind the mapper distinguishes: a field, the JSON field, the existing
	 * to-one entity, an existing to-many item found by its identifier, a new item, and an item
	 * left out
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function updateValues(): array
	{
		return [
			'title' => 'Hydration, revised',
			'params' => [
				'tags' => ['revised'],
			],
			'summary' => [
				'text' => 'Longer',
				'words' => 2,
			],
			'comments' => [
				[
					'entity' => Persistence\TextComment::class,
					'id' => Uuid\Uuid::fromString(self::TEXT_COMMENT),
					'body' => 'First, edited',
				],
				[
					'entity' => Persistence\RatingComment::class,
					'id' => Uuid\Uuid::fromString(self::NEW_COMMENT),
					'body' => 'Third',
					'score' => 3,
				],
			],
		];
	}

	/**
	 * What the mapper builds from createValues(). A nested array of a plain field, `params`
	 * here, arrives as the ArrayHash Utils\ArrayHash::from() made of it: the mapper unwraps only
	 * the outer level. That is today's behaviour, for both inputs, and is pinned as it is.
	 *
	 * @return array<string, mixed>
	 */
	private function createdGraph(): array
	{
		return [
			'(class)' => Persistence\Article::class,
			'author' => [
				'(class)' => Persistence\Author::class,
				'id' => 'uuid:' . self::AUTHOR,
				'name' => 'Ada',
			],
			'comments' => [
				[
					'(class)' => Persistence\TextComment::class,
					'article' => ['(ref)' => '$'],
					'attachment' => [
						'(class)' => Persistence\Attachment::class,
						'file' => 'first.png',
						'id' => 'uuid:' . self::ATTACHMENT,
					],
					'body' => 'First',
					'id' => 'uuid:' . self::TEXT_COMMENT,
				],
				[
					'(class)' => Persistence\RatingComment::class,
					'article' => ['(ref)' => '$'],
					'body' => 'Second',
					'id' => 'uuid:' . self::RATING_COMMENT,
					'score' => 5,
				],
			],
			'id' => 'uuid:' . self::ARTICLE,
			'params' => [
				'tags' => ['(ArrayHash)' => ['mapper', 'input']],
				'layout' => ['(ArrayHash)' => ['columns' => 2]],
			],
			'summary' => [
				'(class)' => Persistence\Summary::class,
				'id' => 'uuid:' . self::SUMMARY,
				'text' => 'Short',
				'words' => 1,
			],
			'title' => 'Hydration',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function updatedGraph(): array
	{
		return [
			'(class)' => Persistence\Article::class,
			'author' => [
				'(class)' => Persistence\Author::class,
				'id' => 'uuid:' . self::AUTHOR,
				'name' => 'Ada',
			],
			'comments' => [
				[
					'(class)' => Persistence\TextComment::class,
					'article' => ['(ref)' => '$'],
					'attachment' => [
						'(class)' => Persistence\Attachment::class,
						'file' => 'first.png',
						'id' => 'uuid:' . self::ATTACHMENT,
					],
					'body' => 'First, edited',
					'id' => 'uuid:' . self::TEXT_COMMENT,
				],
				[
					'(class)' => Persistence\RatingComment::class,
					'article' => ['(ref)' => '$'],
					'body' => 'Third',
					'id' => 'uuid:' . self::NEW_COMMENT,
					'score' => 3,
				],
			],
			'id' => 'uuid:' . self::ARTICLE,
			'params' => [
				'tags' => ['(ArrayHash)' => ['revised']],
			],
			'summary' => [
				'(class)' => Persistence\Summary::class,
				'id' => 'uuid:' . self::SUMMARY,
				'text' => 'Longer',
				'words' => 2,
			],
			'title' => 'Hydration, revised',
		];
	}

	/**
	 * A container on a database of its own, with the fixture schema and the article's author
	 *
	 * @throws Throwable
	 */
	private function databaseContainer(): Nette\DI\Container
	{
		$container = $this->createContainer(__DIR__ . '/entityMapper.neon');

		$entityManager = $container->getByType(ORM\EntityManagerInterface::class);

		(new ORM\Tools\SchemaTool($entityManager))->createSchema(
			$entityManager->getMetadataFactory()->getAllMetadata(),
		);

		$entityManager->persist(new Persistence\Author('Ada', Uuid\Uuid::fromString(self::AUTHOR)));
		$entityManager->flush();

		return $container;
	}

	/**
	 * @return Crud\Create\EntityCreator<Persistence\Article>
	 *
	 * @throws Throwable
	 */
	private function creator(Nette\DI\Container $container): Crud\Create\EntityCreator
	{
		/** @var Crud\CrudFactory<Persistence\Article> $factory */
		$factory = $container->getByType(Crud\CrudFactory::class);

		return $factory->create(Persistence\Article::class)->getEntityCreator();
	}

	/**
	 * @return Crud\Update\EntityUpdater<Persistence\Article>
	 *
	 * @throws Throwable
	 */
	private function updater(Nette\DI\Container $container): Crud\Update\EntityUpdater
	{
		/** @var Crud\CrudFactory<Persistence\Article> $factory */
		$factory = $container->getByType(Crud\CrudFactory::class);

		return $factory->create(Persistence\Article::class)->getEntityUpdater();
	}

	/**
	 * The article as the database holds it, not as the identity map does
	 *
	 * @throws Throwable
	 */
	private function reload(Nette\DI\Container $container): Persistence\Article
	{
		$entityManager = $container->getByType(ORM\EntityManagerInterface::class);
		$entityManager->clear();

		$article = $entityManager->find(Persistence\Article::class, Uuid\Uuid::fromString(self::ARTICLE));
		assert($article instanceof Persistence\Article);

		return $article;
	}

	/**
	 * The graph reachable from $value, as data: every object as its class and every property of
	 * its class hierarchy, sorted by name, a collection as the list of its items, an ArrayHash
	 * marked as one, and an object met a second time as a reference to the path it was first met
	 * at, so a back-reference cannot recurse
	 *
	 * @param array<int, string> $seen
	 */
	private function describe(mixed $value, array &$seen = [], string $path = '$'): mixed
	{
		if ($value instanceof Uuid\UuidInterface) {
			return 'uuid:' . $value->toString();
		}

		if ($value instanceof Common\Collections\Collection) {
			$value = $value->toArray();
		}

		if ($value instanceof Utils\ArrayHash) {
			return ['(ArrayHash)' => $this->describe((array) $value, $seen, $path)];
		}

		if (is_array($value)) {
			$described = [];

			foreach ($value as $key => $item) {
				$described[$key] = $this->describe($item, $seen, $path . '.' . $key);
			}

			return $described;
		}

		if (!is_object($value)) {
			return $value;
		}

		if (isset($seen[spl_object_id($value)])) {
			return ['(ref)' => $seen[spl_object_id($value)]];
		}

		$seen[spl_object_id($value)] = $path;

		/** @var array<string, ReflectionProperty> $properties */
		$properties = [];

		for ($class = new ReflectionClass($value); $class !== false; $class = $class->getParentClass()) {
			foreach ($class->getProperties() as $property) {
				if (!$property->isStatic() && $property->getDeclaringClass()->getName() === $class->getName()) {
					$properties[$property->getName()] = $property;
				}
			}
		}

		ksort($properties);

		$described = ['(class)' => $value::class];

		foreach ($properties as $name => $property) {
			$described[$name] = $property->isInitialized($value)
				? $this->describe($property->getValue($value), $seen, $path . '.' . $name)
				: '(uninitialized)';
		}

		return $described;
	}

}
