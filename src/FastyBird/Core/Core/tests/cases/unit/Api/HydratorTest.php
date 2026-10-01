<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Api;

use Doctrine\Persistence;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Tests;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Localization;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Throwable;
use function str_contains;

/**
 * Pins the two document-level errors `Hydrators\Hydrator::hydrate()` raises before it reaches
 * any attribute. The translator is the real one, resolved from `tests/common.neon`, so a lookup
 * key that is missing from `src/Api/Translations/api.en_US.neon` makes the translator hand the
 * key itself back and the test fails -- a mocked translator would echo the key either way.
 */
final class HydratorTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testHydrateWithoutResourceThrowsATranslatedInvalidResourceError(): void
	{
		$hydrator = $this->createHydrator();

		try {
			$hydrator->hydrate(Encoding\Document::create('{"data":null}'));

			self::fail('Hydrator::hydrate() did not reject a document without a resource.');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame('Invalid resource', $ex->getMessage());
			self::assertSame('Provided resource structure is not valid', $ex->getDetail());
			self::assertSame(['pointer' => '/data'], $ex->getSource());
			self::assertFalse(str_contains($ex->getMessage(), '//api.'));
			self::assertFalse(str_contains($ex->getDetail(), '//api.'));
		}
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function invalidIdentifierDocuments(): array
	{
		return [
			'id is not a UUID' => ['{"data":{"type":"test","id":"not-a-uuid","attributes":{}}}'],
			'id is missing' => ['{"data":{"type":"test","attributes":{}}}'],
		];
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	#[DataProvider('invalidIdentifierDocuments')]
	public function testHydrateWithInvalidIdentifierThrowsATranslatedInvalidIdentifierError(string $document): void
	{
		$hydrator = $this->createHydrator();

		try {
			$hydrator->hydrate(Encoding\Document::create($document));

			self::fail('Hydrator::hydrate() did not reject a resource with an invalid identifier.');
		} catch (Exceptions\JsonApiError $ex) {
			self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $ex->getCode());
			self::assertSame('Invalid identifier', $ex->getMessage());
			self::assertSame('Provided entity identifier is not valid', $ex->getDetail());
			self::assertSame(['pointer' => '/data/id'], $ex->getSource());
			self::assertFalse(str_contains($ex->getMessage(), '//api.'));
			self::assertFalse(str_contains($ex->getDetail(), '//api.'));
		}
	}

	/**
	 * @return Hydrators\Hydrator<object>
	 *
	 * @throws Nette\DI\MissingServiceException
	 */
	private function createHydrator(): Hydrators\Hydrator
	{
		$managerRegistry = $this->createMock(Persistence\ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')->willReturn(null);

		return new class (
			$managerRegistry,
			$this->container->getByType(Localization\Translator::class),
		) extends Hydrators\Hydrator {

			public function getEntityName(): string
			{
				return stdClass::class;
			}

		};
	}

}
