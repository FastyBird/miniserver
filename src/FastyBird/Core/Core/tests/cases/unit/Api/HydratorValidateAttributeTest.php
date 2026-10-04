<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Api;

use Doctrine\Persistence;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyValidatingHydrator;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Throwable;

/**
 * Pins the validate<Field>Attribute() hook (#609): Hydrator calls it for each mapped attribute
 * present in the resource, an exception it throws stops hydration, and what it returns is not
 * used, so the hydrated value is still the field's own.
 */
final class HydratorValidateAttributeTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ID = '9f2c4a1e-5b0d-4c3a-8e7f-1a2b3c4d5e6f';

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAPassingValidatorLeavesTheValueToTheField(): void
	{
		$hydrator = $this->hydrator();

		$values = $hydrator->hydrate($this->document('New label'));

		self::assertSame('New label', $values->offsetGet('label'));
		self::assertSame(['New label'], $hydrator->validated);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAThrowingValidatorStopsHydration(): void
	{
		try {
			$this->hydrator()->hydrate($this->document('rejected'));

			self::fail('Hydrator::hydrate() did not call validateLabelAttribute().');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame(['pointer' => '/data/attributes/label'], $ex->getSource());
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAValidatorIsNotCalledForAnAbsentAttribute(): void
	{
		$hydrator = $this->hydrator();

		$values = $hydrator->hydrate($this->document(null));

		self::assertFalse($values->offsetExists('label'));
		self::assertSame([], $hydrator->validated);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	private function hydrator(): DummyValidatingHydrator
	{
		return new DummyValidatingHydrator(
			$this->managerRegistry(),
			$this->container->getByType(Localization\Translator::class),
		);
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
	 * @throws Throwable
	 */
	private function document(string|null $label): Encoding\Document
	{
		$attributes = ['identifier' => 'new-identifier'];

		if ($label !== null) {
			$attributes['label'] = $label;
		}

		return Encoding\Document::create(Utils\Json::encode([
			'data' => [
				'type' => 'dummy',
				'id' => self::ID,
				'attributes' => $attributes,
			],
		]));
	}

}
