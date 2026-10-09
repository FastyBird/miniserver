<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Common;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FastyBird\Core\Documents\Mapping;
use FastyBird\Core\Documents\Mapping\Driver;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Exceptions as HttpExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Phone\Entities as PhoneEntities;
use FastyBird\Core\Phone\Exceptions as PhoneExceptions;
use FastyBird\Core\Security\Entities as SecurityEntities;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyDocument;
use FastyBird\Core\WebSockets\Server;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use React\Http\Message\ServerRequest;
use ReflectionException;

/**
 * Read-back of the accessor pairs E5.12 (#644) turns into property hooks or asymmetric
 * visibility, for the classes no other test reads them back on (census T6, T12-25): each value is
 * assigned through today's setter or constructor and read through today's getter. #644 rewrites
 * the call syntax mechanically and must keep every value. The rows #644 makes `private(set)` or
 * `protected(set)` are read back only through what their own class writes (#671, #672).
 */
final class AccessorReadBackTest extends TestCase
{

	public function testEventLoopStatus(): void
	{
		$status = new EventLoop\Status();

		self::assertFalse($status->isRunning());

		$status->setStatus(true);

		self::assertTrue($status->isRunning());

		$status->setStatus(false);

		self::assertFalse($status->isRunning());
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function testHttpExceptionTitleAndDescription(): void
	{
		$request = new ServerRequest('GET', 'http://localhost/api/v1');
		$exception = new HttpExceptions\Http($request, 'e5 message', 418);

		self::assertSame('', $exception->getTitle());
		self::assertSame('', $exception->getDescription());
		self::assertSame($request, $exception->getRequest());
		self::assertSame('e5 message', $exception->getMessage());
		self::assertSame(418, $exception->getCode());
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function testHttpSpecializedExceptionsKeepTheirTitleAndDescription(): void
	{
		$request = new ServerRequest('GET', 'http://localhost/api/v1');

		$notFound = new HttpExceptions\HttpNotFound($request);

		self::assertSame('404 Not Found', $notFound->getTitle());
		self::assertSame(
			'The requested resource could not be found. Please verify the URI and try again.',
			$notFound->getDescription(),
		);

		$notAllowed = new HttpExceptions\HttpMethodNotAllowed($request);

		self::assertSame('405 Method Not Allowed', $notAllowed->getTitle());
		self::assertSame(
			'The request method is not supported for the requested resource.',
			$notAllowed->getDescription(),
		);
	}

	/**
	 * @throws PhoneExceptions\NoValidCountry
	 * @throws PhoneExceptions\NoValidPhone
	 */
	public function testPhoneReadsBackWhatFromNumberParsed(): void
	{
		$italian = PhoneEntities\Phone::fromNumber('+39 06 1234 5678');

		self::assertTrue($italian->getItalianLeadingZero());
		self::assertSame(['Europe/Rome'], $italian->getTimeZones());
		self::assertTrue($italian->isInTimeZone('Europe/Rome'));
		self::assertNull($italian->getExtension());

		$withExtension = PhoneEntities\Phone::fromNumber('+420 777 123 456 ext. 12');

		self::assertSame('12', $withExtension->getExtension());
		self::assertFalse($withExtension->getItalianLeadingZero());
		self::assertSame(['Europe/Prague'], $withExtension->getTimeZones());

		$leadingZeros = PhoneEntities\Phone::fromNumber('+992 00 500 8965');

		self::assertSame(2, $leadingZeros->getNumberOfLeadingZeros());
	}

	public function testWebSocketsServerConfiguration(): void
	{
		$defaults = new Server\Configuration();

		self::assertSame(8_080, $defaults->getPort());
		self::assertSame('0.0.0.0', $defaults->getAddress());
		self::assertFalse($defaults->isSslEnabled());
		self::assertSame([], $defaults->getSslConfiguration());

		$configuration = new Server\Configuration(8_888, '127.0.0.1');

		self::assertSame(8_888, $configuration->getPort());
		self::assertSame('127.0.0.1', $configuration->getAddress());
	}

	/**
	 * @throws ReflectionException
	 */
	public function testDocumentsClassMetadata(): void
	{
		$metadata = new Mapping\ClassMetadata(DummyDocument::class);

		self::assertNull($metadata->getOwningEntity());
		self::assertFalse($metadata->isMappedSuperclass());
		self::assertSame(Mapping\ClassMetadata::INHERITANCE_TYPE_NONE, $metadata->getInheritanceType());
		self::assertTrue($metadata->isInheritanceTypeNone());

		$metadata->setOwningEntity(self::class);
		$metadata->setIsMappedSuperclass(true);
		$metadata->setInheritanceType(Mapping\ClassMetadata::INHERITANCE_TYPE_JOINED_TABLE);

		self::assertSame(self::class, $metadata->getOwningEntity());
		self::assertTrue($metadata->isMappedSuperclass());
		self::assertSame(Mapping\ClassMetadata::INHERITANCE_TYPE_JOINED_TABLE, $metadata->getInheritanceType());
		self::assertFalse($metadata->isInheritanceTypeNone());
	}

	public function testAttributeDriverFileExtension(): void
	{
		$driver = new Driver\AttributeDriver();

		self::assertSame('.php', $driver->getFileExtension());
	}

	public function testMappingDriverChainDefaultDriver(): void
	{
		$chain = new Driver\MappingDriverChain();

		self::assertNull($chain->getDefaultDriver());
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 */
	public function testCrudManagerFlush(): void
	{
		$entityManager = $this->createMock(EntityManagerInterface::class);
		$entityManager->method('getRepository')
			->willReturn($this->createMock(EntityRepository::class));

		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')
			->willReturn($entityManager);

		$manager = new class (SecurityEntities\Policies\Policy::class, $managerRegistry) extends Crud\CrudManager {

		};

		self::assertTrue($manager->getFlush());
	}

}
