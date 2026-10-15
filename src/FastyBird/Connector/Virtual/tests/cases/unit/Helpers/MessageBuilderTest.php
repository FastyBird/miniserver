<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Cases\Unit\Helpers;

use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Tests;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Ramsey\Uuid;
use Throwable;

/**
 * Every package builder maps through Core's Documents\Mapping\Mapper and rethrows a refusal as its own Runtime exception
 * (#724 PR1): the exception type and the start of its message are what a caller sees and must not change
 */
final class MessageBuilderTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string PREFIX = 'Could not map data to message: ';

	/**
	 * @throws Throwable
	 */
	public function testItBuildsTheMessage(): void
	{
		$connector = Uuid\Uuid::uuid4();
		$device = Uuid\Uuid::uuid4();

		$message = $this->builder()->create(
			Queue\Messages\StoreDeviceConnectionState::class,
			[
				'connector' => $connector,
				'device' => $device,
				'state' => DevicesTypes\ConnectionState::CONNECTED,
				'source' => Sources\Connector::VIRTUAL,
			],
		);

		self::assertInstanceOf(Queue\Messages\StoreDeviceConnectionState::class, $message);
		self::assertTrue($message->getConnector()->equals($connector));
		self::assertTrue($message->getDevice()->equals($device));
		self::assertSame(DevicesTypes\ConnectionState::CONNECTED, $message->getState());
	}

	/**
	 * @throws Throwable
	 */
	public function testItIgnoresFieldsTheMessageDoesNotDeclare(): void
	{
		$message = $this->builder()->create(
			Queue\Messages\StoreDeviceConnectionState::class,
			[
				'connector' => Uuid\Uuid::uuid4(),
				'device' => Uuid\Uuid::uuid4(),
				'state' => DevicesTypes\ConnectionState::CONNECTED,
				'source' => Sources\Connector::VIRTUAL,
				'extra' => 'not declared',
			],
		);

		self::assertInstanceOf(Queue\Messages\StoreDeviceConnectionState::class, $message);
	}

	/**
	 * @throws Throwable
	 */
	public function testARefusedMessageIsThePackageRuntimeException(): void
	{
		try {
			$this->builder()->create(Queue\Messages\StoreDeviceConnectionState::class, ['connector' => 'not a uuid']);

			self::fail('the builder accepted the data');
		} catch (Exceptions\Runtime $ex) {
			self::assertStringStartsWith(self::PREFIX, $ex->getMessage());
			// The prefix alone would say nothing: the printed failure has to follow it
			self::assertNotSame(self::PREFIX, $ex->getMessage());
		}
	}

	/**
	 * @throws Throwable
	 */
	private function builder(): Helpers\MessageBuilder
	{
		return $this->container->getByType(Helpers\MessageBuilder::class);
	}

}
