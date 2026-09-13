<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests\Cases\Unit\Mapping;

use Error;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use Nette\DI;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class BuilderTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 */
	public function testBuild(): void
	{
		$builder = $this->getContainer()->getByType(Mapping\Builder::class);

		$mapping = $builder->getServicesMapping();

		self::assertNotEmpty($mapping->getServices());

		foreach ($mapping->getServices() as $serviceMapping) {
			self::assertNotEmpty($serviceMapping->getCharacteristics());
		}
	}

}
