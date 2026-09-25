<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\API\Messages;

use Error;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions;
use Nette;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function assert;

final class HeaderTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ObjectMapper\Exception\InvalidData
	 * @throws Error
	 */
	public function testCreateMessage(): void
	{
		$container = $this->createContainer();

		$processor = $container->getByType(ObjectMapper\Processing\Processor::class);
		assert($processor instanceof ObjectMapper\Processing\DefaultProcessor);

		$id = Uuid\Uuid::uuid4();

		$message = $processor->process(
			[
				'name' => Types\Header::ERROR_RESPONSE->value,
				'message_id' => $id->toString(),
				'version' => '1',
			],
			API\Messages\Header::class,
		);

		self::assertSame(Types\Header::ERROR_RESPONSE, $message->getName());
		self::assertSame($id->toString(), $message->getMessageId());
		self::assertSame('1', $message->getVersion());
	}

}
