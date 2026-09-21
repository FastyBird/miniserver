<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Queue\Consumers;

use Doctrine\DBAL;
use Error;
use Exception;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use function assert;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreBridgeInfoTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws DBAL\Exception
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Exception
	 */
	public function testConsumeMessage(): void
	{
		$consumer = $this->getContainer()->getByType(
			Queue\Consumers\StoreBridgeInfo::class,
		);

		$messageBuilder = $this->getContainer()->getByType(
			Helpers\MessageBuilder::class,
		);

		$message = $messageBuilder->create(
			Queue\Messages\StoreBridgeInfo::class,
			[
				'connector' => Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
				'base_topic' => 'zigbee2mqtt',
				'version' => '1.2.3',
				'commit' => '56589dc',
				'coordinator' => [
					'ieee_address' => '0xa4c138f06eafa3da',
					'type' => 'Coordinator',
				],
			],
		);

		$consumer->consume($message);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$connector = $connectorsRepository->find(
			Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
			Entities\Connectors\Connector::class,
		);
		assert($connector instanceof Entities\Connectors\Connector);

		$devicesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		);

		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$bridge = $devicesRepository->findOneBy($findDevicesQuery, Entities\Devices\Bridge::class);
		assert($bridge instanceof Entities\Devices\Bridge);

		self::assertSame('1.2.3', $bridge->getFirmwareVersion());
		self::assertSame('56589dc', $bridge->getFirmwareCommit());
		self::assertSame('0xa4c138f06eafa3da', $bridge->getIeeeAddress());
	}

}
