<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use Doctrine\Persistence;
use Exception;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\EventLoop\Application as ApplicationEventLoop;
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Tests;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use function is_string;

final class ModuleEntitiesTest extends TestCase
{

	public function testSubscriberEvents(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);

		$connectorPropertiesStates = $this->createMock(Models\States\ConnectorPropertiesManager::class);
		$connectorPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncConnectorPropertiesStates = $this->createMock(Models\States\Async\ConnectorPropertiesManager::class);

		$devicePropertiesStates = $this->createMock(Models\States\DevicePropertiesManager::class);
		$devicePropertiesStates
			->method('read')
			->willReturn(null);

		$asyncDevicePropertiesStates = $this->createMock(Models\States\Async\DevicePropertiesManager::class);

		$channelPropertiesStates = $this->createMock(Models\States\ChannelPropertiesManager::class);
		$channelPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncChannelPropertiesStates = $this->createMock(Models\States\Async\ChannelPropertiesManager::class);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$connectorPropertiesStates,
			$asyncConnectorPropertiesStates,
			$devicePropertiesStates,
			$asyncDevicePropertiesStates,
			$channelPropertiesStates,
			$asyncChannelPropertiesStates,
			$eventLoopStatus,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		self::assertSame([
			'postPersist',
			'postUpdate',
			'preRemove',
			'postRemove',
		], $subscriber->getSubscribedEvents());
	}

	/**
	 * @throws Exception
	 */
	public function testPublishCreatedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Sources\Module);

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue(is_string($key));
					self::assertSame(
						Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY,
						$key,
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'identifier' => 'device-name',
						'type' => 'generic',
						'owner' => null,
						'name' => 'Device custom name',
						'comment' => null,
						'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
						'parents' => [],
						'children' => [],
					], $asArray);

					return true;
				}),
			);

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$entityManager = $this->getEntityManager();

		$connectorPropertiesStates = $this->createMock(Models\States\ConnectorPropertiesManager::class);
		$connectorPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncConnectorPropertiesStates = $this->createMock(Models\States\Async\ConnectorPropertiesManager::class);

		$devicePropertiesStates = $this->createMock(Models\States\DevicePropertiesManager::class);
		$devicePropertiesStates
			->method('read')
			->willReturn(null);

		$asyncDevicePropertiesStates = $this->createMock(Models\States\Async\DevicePropertiesManager::class);

		$channelPropertiesStates = $this->createMock(Models\States\ChannelPropertiesManager::class);
		$channelPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncChannelPropertiesStates = $this->createMock(Models\States\Async\ChannelPropertiesManager::class);

		$document = $this->createMock(DevicesDocuments\Devices\Device::class);
		$document
			->method('toArray')
			->willReturn([
				'identifier' => 'device-name',
				'type' => 'generic',
				'owner' => null,
				'name' => 'Device custom name',
				'comment' => null,
				'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
				'parents' => [],
				'children' => [],
			]);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$connectorPropertiesStates,
			$asyncConnectorPropertiesStates,
			$devicePropertiesStates,
			$asyncDevicePropertiesStates,
			$channelPropertiesStates,
			$asyncChannelPropertiesStates,
			$eventLoopStatus,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$connectorEntity = new Tests\Fixtures\Dummy\DummyConnectorEntity(
			'generic-connector-name',
			Uuid\Uuid::fromString('dd6aa4bc-2611-40c3-84ef-0a438cf51e67'),
		);

		$entity = new Tests\Fixtures\Dummy\DummyDeviceEntity('device-name', $connectorEntity, 'device-name');
		$entity->setName('Device custom name');

		$eventArgs = $this->createMock(Persistence\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postPersist($eventArgs);
	}

	/**
	 * @throws Exception
	 */
	public function testPublishUpdatedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Sources\Module);

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue(is_string($key));
					self::assertSame(
						Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY,
						$key,
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'identifier' => 'device-name',
						'type' => 'generic',
						'owner' => null,
						'name' => 'Device custom name',
						'comment' => null,
						'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
						'parents' => [],
						'children' => [],
					], $asArray);

					return true;
				}),
			);

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$entityManager = $this->getEntityManager(true);

		$connectorPropertiesStates = $this->createMock(Models\States\ConnectorPropertiesManager::class);
		$connectorPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncConnectorPropertiesStates = $this->createMock(Models\States\Async\ConnectorPropertiesManager::class);

		$devicePropertiesStates = $this->createMock(Models\States\DevicePropertiesManager::class);
		$devicePropertiesStates
			->method('read')
			->willReturn(null);

		$asyncDevicePropertiesStates = $this->createMock(Models\States\Async\DevicePropertiesManager::class);

		$channelPropertiesStates = $this->createMock(Models\States\ChannelPropertiesManager::class);
		$channelPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncChannelPropertiesStates = $this->createMock(Models\States\Async\ChannelPropertiesManager::class);

		$document = $this->createMock(DevicesDocuments\Devices\Device::class);
		$document
			->method('toArray')
			->willReturn([
				'identifier' => 'device-name',
				'type' => 'generic',
				'owner' => null,
				'name' => 'Device custom name',
				'comment' => null,
				'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
				'parents' => [],
				'children' => [],
			]);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$connectorPropertiesStates,
			$asyncConnectorPropertiesStates,
			$devicePropertiesStates,
			$asyncDevicePropertiesStates,
			$channelPropertiesStates,
			$asyncChannelPropertiesStates,
			$eventLoopStatus,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$connectorEntity = new Tests\Fixtures\Dummy\DummyConnectorEntity(
			'generic-connector-name',
			Uuid\Uuid::fromString('dd6aa4bc-2611-40c3-84ef-0a438cf51e67'),
		);

		$entity = new Tests\Fixtures\Dummy\DummyDeviceEntity('device-name', $connectorEntity, 'device-name');
		$entity->setName('Device custom name');

		$eventArgs = $this->createMock(Persistence\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postUpdate($eventArgs);
	}

	/**
	 * @throws Exception
	 */
	public function testPublishDeletedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Sources\Module);

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue(is_string($key));
					self::assertSame(
						Devices\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY,
						$key,
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'identifier' => 'device-name',
						'type' => 'generic',
						'owner' => null,
						'name' => 'Device custom name',
						'comment' => null,
						'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
						'parents' => [],
						'children' => [],
					], $asArray);

					return true;
				}),
			);

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$connectorEntity = new Tests\Fixtures\Dummy\DummyConnectorEntity(
			'generic-connector-name',
			Uuid\Uuid::fromString('dd6aa4bc-2611-40c3-84ef-0a438cf51e67'),
		);

		$entity = new Tests\Fixtures\Dummy\DummyDeviceEntity('device-name', $connectorEntity, 'device-name');
		$entity->setName('Device custom name');

		$entityManager = $this->getEntityManager();

		$connectorPropertiesStates = $this->createMock(Models\States\ConnectorPropertiesManager::class);
		$connectorPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncConnectorPropertiesStates = $this->createMock(Models\States\Async\ConnectorPropertiesManager::class);

		$devicePropertiesStates = $this->createMock(Models\States\DevicePropertiesManager::class);
		$devicePropertiesStates
			->method('read')
			->willReturn(null);

		$asyncDevicePropertiesStates = $this->createMock(Models\States\Async\DevicePropertiesManager::class);

		$channelPropertiesStates = $this->createMock(Models\States\ChannelPropertiesManager::class);
		$channelPropertiesStates
			->method('read')
			->willReturn(null);

		$asyncChannelPropertiesStates = $this->createMock(Models\States\Async\ChannelPropertiesManager::class);

		$document = $this->createMock(DevicesDocuments\Devices\Device::class);
		$document
			->method('toArray')
			->willReturn([
				'identifier' => 'device-name',
				'type' => 'generic',
				'owner' => null,
				'name' => 'Device custom name',
				'comment' => null,
				'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
				'parents' => [],
				'children' => [],
			]);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$connectorPropertiesStates,
			$asyncConnectorPropertiesStates,
			$devicePropertiesStates,
			$asyncDevicePropertiesStates,
			$channelPropertiesStates,
			$asyncChannelPropertiesStates,
			$eventLoopStatus,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$eventArgs = $this->createMock(Persistence\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postRemove($eventArgs);
	}

	private function getEntityManager(bool $withUow = false): ORM\EntityManagerInterface&MockObject
	{
		// ORM 3 types EntityManagerInterface::getClassMetadata() as ClassMetadata, so PHPUnit
		// refuses the stdClass stand-in this used to pass. Nothing under test reads the
		// metadata, so an unloaded instance is enough -- it only has to satisfy the type.
		$metadata = new ORM\Mapping\ClassMetadata(Tests\Fixtures\Dummy\DummyDeviceEntity::class);
		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);
		$entityManager
			->method('getClassMetadata')
			->with([Tests\Fixtures\Dummy\DummyDeviceEntity::class])
			->willReturn($metadata);

		if ($withUow) {
			$uow = $this->createMock(ORM\UnitOfWork::class);
			$uow
				->expects(self::once())
				->method('getEntityChangeSet')
				->willReturn(['name']);
			$uow
				->method('isScheduledForDelete')
				->willReturn(false);

			$entityManager
				->expects(self::once())
				->method('getUnitOfWork')
				->willReturn($uow);
		}

		return $entityManager;
	}

}
