<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\fobar\DI;

use FastyBird\Library\Bootstrap as LibraryBootstrap;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\MiniServer\Commands;
use FastyBird\MiniServer\Tests\Cases\fobar\DbTestCase;
use Nette;

final class ServicesTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testServicesRegistration(): void
	{
		$configurator = LibraryBootstrap\Boot\Bootstrap::boot();
		$configurator->addParameters([
			'database' => [
				'driver' => 'pdo_sqlite',
			],
		]);

		$container = $configurator->createContainer();

		self::assertNotNull($container->getByType(Commands\Initialize::class, false));
	}

}
