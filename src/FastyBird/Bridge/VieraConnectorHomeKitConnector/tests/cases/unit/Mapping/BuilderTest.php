<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests\Cases\Unit\Mapping;

use Error;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions as VieraConnectorHomeKitConnectorExceptions;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use Nette\DI;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class BuilderTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws VieraConnectorHomeKitConnectorExceptions\InvalidArgument
	 * @throws VieraConnectorHomeKitConnectorExceptions\InvalidState
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
