<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Bootstrap\Boot;
use FastyBird\MiniServer\Application;
use FastyBird\MiniServer\Commands;
use FastyBird\MiniServer\Models;
use FastyBird\MiniServer\Subscribers;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ServicesTest extends BaseTestCase
{

	public function testServicesRegistration(): void
	{
		$configurator = Boot\Bootstrap::boot();
		$configurator->addParameters([
			'database' => [
				'driver' => 'pdo_sqlite',
			],
		]);

		$container = $configurator->createContainer();

		Assert::notNull($container->getByType(Application\Application::class));

		Assert::notNull($container->getByType(Commands\InitializeCommand::class));

		Assert::notNull($container->getByType(Models\PropertiesManager::class));
		Assert::notNull($container->getByType(Models\PropertyRepository::class));

		Assert::notNull($container->getByType(Subscribers\EntitiesSubscriber::class));
	}

}

$test_case = new ServicesTest();
$test_case->run();
