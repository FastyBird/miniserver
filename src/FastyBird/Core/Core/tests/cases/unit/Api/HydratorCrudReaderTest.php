<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Api;

use Doctrine\Persistence;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyCrudEntity;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyCrudHydrator;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Localization;
use Nette\Utils;
use ReflectionProperty;
use Throwable;
use function assert;

/**
 * Pins what the #[Crud] attribute does to a JSON:API write once Hydrator has a CrudReader (#552):
 * on update, a field that is not writable and a field that is not annotated are both left out of
 * the hydrated values, while a writable one passes; on create, a required field is carried even
 * though it is not writable, and an explicit null for it is rejected. The reader comes from the
 * container, the way every module's hydrator gets it, so these fail if the service is not
 * registered. The last test pins the behaviour without a reader, for contrast.
 */
final class HydratorCrudReaderTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '9f2c4a1e-5b0d-4c3a-8e7f-1a2b3c4d5e6f';

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testTheContainerRegistersTheCrudReaderAndAutowiresItIntoAHydrator(): void
	{
		self::assertTrue($this->container->hasService('fbCore.api.helpers.crudReader'));
		self::assertInstanceOf(
			Helpers\CrudReader::class,
			$this->container->getService('fbCore.api.helpers.crudReader'),
		);

		self::assertInstanceOf(Helpers\CrudReader::class, $this->readerOf($this->autowiredHydrator()));
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testUpdateLeavesOutNonWritableAndUnannotatedFields(): void
	{
		$values = $this->autowiredHydrator()->hydrate($this->document(), new DummyCrudEntity());

		self::assertTrue($values->offsetExists('label'));
		self::assertSame('New label', $values->offsetGet('label'));
		self::assertFalse($values->offsetExists('identifier'));
		self::assertFalse($values->offsetExists('secret'));
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testCreateCarriesARequiredFieldThatIsNotWritable(): void
	{
		$values = $this->autowiredHydrator()->hydrate($this->document());

		self::assertSame('new-identifier', $values->offsetGet('identifier'));
		self::assertSame('New label', $values->offsetGet('label'));
		self::assertFalse($values->offsetExists('secret'));
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testCreateRejectsANullRequiredAttribute(): void
	{
		try {
			$this->autowiredHydrator()->hydrate($this->document(['identifier' => null]));

			self::fail('Hydrator::hydrate() accepted a null required attribute on create.');
		} catch (Exceptions\JsonApiMultipleError $ex) {
			$errors = $ex->getErrors();

			self::assertCount(1, $errors);
			self::assertSame((string) StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $errors[0]->getStatus());
			self::assertSame('Missing required attribute', $errors[0]->getTitle());
			self::assertSame(['pointer' => '/data/attributes/identifier'], $errors[0]->getSource());
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testWithoutAReaderAnUpdateCarriesEveryListedField(): void
	{
		$hydrator = new DummyCrudHydrator(
			$this->managerRegistry(),
			$this->container->getByType(Localization\Translator::class),
		);

		self::assertNull($this->readerOf($hydrator));

		$values = $hydrator->hydrate($this->document(), new DummyCrudEntity());

		self::assertSame('new-identifier', $values->offsetGet('identifier'));
		self::assertSame('New label', $values->offsetGet('label'));
		self::assertSame('New secret', $values->offsetGet('secret'));
	}

	private function autowiredHydrator(): DummyCrudHydrator
	{
		return $this->container->createInstance(
			DummyCrudHydrator::class,
			['managerRegistry' => $this->managerRegistry()],
		);
	}

	private function readerOf(DummyCrudHydrator $hydrator): Helpers\CrudReader|null
	{
		$reader = (new ReflectionProperty(Hydrators\Hydrator::class, 'crudReader'))->getValue($hydrator);
		assert($reader instanceof Helpers\CrudReader || $reader === null);

		return $reader;
	}

	/**
	 * Hydrator::mapEntity() only asks Doctrine for the field and association names, on top of the
	 * class's own reflected properties; DummyCrudEntity is not mapped, so both lists are empty
	 */
	private function managerRegistry(): Persistence\ManagerRegistry
	{
		$classMetadata = self::createStub(Persistence\Mapping\ClassMetadata::class);
		$classMetadata->method('getFieldNames')->willReturn([]);
		$classMetadata->method('getAssociationNames')->willReturn([]);

		$manager = self::createStub(Persistence\ObjectManager::class);
		$manager->method('getClassMetadata')->willReturn($classMetadata);

		$managerRegistry = self::createStub(Persistence\ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')->willReturn($manager);

		return $managerRegistry;
	}

	/**
	 * @param array<string, string|null> $override
	 *
	 * @throws Throwable
	 */
	private function document(array $override = []): Encoding\Document
	{
		return Encoding\Document::create(Utils\Json::encode([
			'data' => [
				'type' => 'dummy',
				'id' => self::ID,
				'attributes' => $override + [
					'identifier' => 'new-identifier',
					'label' => 'New label',
					'secret' => 'New secret',
				],
			],
		]));
	}

}
