<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use Doctrine\Persistence;
use Exception;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\EventLoop\Application as ApplicationEventLoop;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Exchange\Publisher\Async;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Subscribers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function is_string;

final class ModuleEntitiesTest extends TestCase
{

	public function testSubscriberEvents(): void
	{
		$publisher = $this->createMock(Publisher\MessagePublisher::class);

		$asyncPublisher = $this->createMock(Async\MessagePublisher::class);

		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$eventLoopStatus,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		self::assertSame([
			ORM\Events::prePersist,
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
			ORM\Events::postRemove,
		], $subscriber->getSubscribedEvents());
	}

	/**
	 * @throws Exception
	 */
	public function testPublishCreatedEntity(): void
	{
		$publisher = $this->createMock(Publisher\MessagePublisher::class);
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
						Triggers\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_CREATED_ROUTING_KEY,
						$key,
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'name' => 'Trigger name',
						'comment' => null,
						'enabled' => true,
						'owner' => null,
						'type' => 'manual',
						'is_triggered' => false,
					], $asArray);

					return true;
				}),
			);

		$asyncPublisher = $this->createMock(Async\MessagePublisher::class);

		$entityManager = $this->getEntityManager();

		$document = $this->createMock(TriggersDocuments\Triggers\Manual::class);
		$document
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$eventLoopStatus,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$entity = new Entities\Triggers\Manual('Trigger name');

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
		$publisher = $this->createMock(Publisher\MessagePublisher::class);
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
						Triggers\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_UPDATED_ROUTING_KEY,
						$key,
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'name' => 'Trigger name',
						'comment' => null,
						'enabled' => true,
						'owner' => null,
						'type' => 'manual',
						'is_triggered' => false,
					], $asArray);

					return true;
				}),
			);

		$asyncPublisher = $this->createMock(Async\MessagePublisher::class);

		$entityManager = $this->getEntityManager(true);

		$document = $this->createMock(TriggersDocuments\Triggers\Manual::class);
		$document
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$eventLoopStatus,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$entity = new Entities\Triggers\Manual('Trigger name');

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
		$publisher = $this->createMock(Publisher\MessagePublisher::class);
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
						Triggers\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_DELETED_ROUTING_KEY,
						$key,
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'name' => 'Trigger name',
						'comment' => null,
						'enabled' => true,
						'owner' => null,
						'type' => 'manual',
						'is_triggered' => false,
					], $asArray);

					return true;
				}),
			);

		$asyncPublisher = $this->createMock(Async\MessagePublisher::class);

		$entity = new Entities\Triggers\Manual('Trigger name');

		$entityManager = $this->getEntityManager();

		$document = $this->createMock(TriggersDocuments\Triggers\Manual::class);
		$document
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$documentFactory = $this->createMock(CoreDocuments\RoutingDocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$eventLoopStatus = $this->createMock(ApplicationEventLoop\Status::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
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
		$metadata = new ORM\Mapping\ClassMetadata(Entities\Triggers\Trigger::class);
		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);
		$entityManager
			->method('getClassMetadata')
			->with([Entities\Triggers\Trigger::class])
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
