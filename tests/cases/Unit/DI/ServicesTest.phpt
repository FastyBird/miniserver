<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Unit\DI;

use FastyBird\Library\Bootstrap as LibraryBootstrap;
use FastyBird\MiniServer\Commands;
use FastyBird\MiniServer\Tests\Cases\Unit\DbTestCase;

/**
 * @testCase
 */
final class ServicesTest extends DbTestCase
{

	public function testServicesRegistration(): void
	{
		$configurator = LibraryBootstrap\Boot\Bootstrap::boot();
		$configurator->addParameters([
			'database' => [
				'driver' => 'pdo_sqlite',
			],
		]);

		$container = $configurator->createContainer();

		self::assertNotNull($container->getByType(Commands\Initialize::class));
	}

}
