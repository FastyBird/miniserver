<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\API\Messages\Request;

use Error;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use function assert;

final class SetDeviceStatusTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Nette\IOException
	 * @throws ObjectMapper\Exception\InvalidData
	 * @throws Utils\JsonException
	 * @throws Error
	 */
	public function testCreateMessage(): void
	{
		$container = $this->createContainer();

		$processor = $container->getByType(ObjectMapper\Processing\Processor::class);
		assert($processor instanceof ObjectMapper\Processing\DefaultProcessor);

		$message = $processor->process(
			Utils\Json::decode(
				Utils\FileSystem::read(
					__DIR__ . '/../../../../../fixtures/API/request/set_device_state.json',
				),
				forceArrays: true,
			),
			API\Messages\Request\SetDeviceState::class,
		);

		self::assertSame(
			'c2e2a418-c70c-4363-aa33-8f2de5196bda',
			$message->getDirective()->getEndpoint()->getSerialNumber(),
		);
	}

}
