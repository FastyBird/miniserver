<?php declare(strict_types = 1);

namespace FastyBird\Core\Application\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Application\Documents;
use FastyBird\Core\Application\Exceptions;
use FastyBird\Core\Application\Tests;
use Monolog;
use Nette;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Symfony\Bridge\Monolog as SymfonyMonolog;

final class ApplicationExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testCompilersServices(): void
	{
		$container = $this->createContainer();

		self::assertNotNull($container->getByType(Monolog\Handler\RotatingFileHandler::class, false));
		self::assertNull($container->getByType(SymfonyMonolog\Handler\ConsoleHandler::class, false));

		self::assertNotNull($this->container->getByType(Documents\DocumentFactory::class, false));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 */
	#[DoesNotPerformAssertions]
	public function testServicesRegistration(): void
	{
		$this->createContainer();
	}

}
