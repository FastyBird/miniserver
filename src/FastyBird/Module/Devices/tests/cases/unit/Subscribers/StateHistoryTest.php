<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Subscribers;

use Closure;
use DateTimeImmutable;
use FastyBird\Core\EventLoop\Events as EventLoopEvents;
use FastyBird\Core\Persistence\Events as PersistenceEvents;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types as DevicesTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\EventLoop;
use Symfony\Component\EventDispatcher as ComponentEventDispatcher;
use Symfony\Contracts\EventDispatcher as ContractsEventDispatcher;
use Throwable;

/**
 * How the history subscriber feeds the recorder and drives its flush timer (#624)
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StateHistoryTest extends Tests\Cases\Unit\HistoryTestCase
{

	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	/**
	 * @return array<string, array{class-string<DevicesEvents\ConnectorPropertyStateEntityCreated>|class-string<DevicesEvents\ConnectorPropertyStateEntityUpdated>|class-string<DevicesEvents\DevicePropertyStateEntityCreated>|class-string<DevicesEvents\DevicePropertyStateEntityUpdated>|class-string<DevicesEvents\ChannelPropertyStateEntityCreated>|class-string<DevicesEvents\ChannelPropertyStateEntityUpdated>, string}>
	 */
	public static function stateEvents(): array
	{
		return [
			'connector created' => [DevicesEvents\ConnectorPropertyStateEntityCreated::class, 'connector'],
			'connector updated' => [DevicesEvents\ConnectorPropertyStateEntityUpdated::class, 'connector'],
			'device created' => [DevicesEvents\DevicePropertyStateEntityCreated::class, 'device'],
			'device updated' => [DevicesEvents\DevicePropertyStateEntityUpdated::class, 'device'],
			'channel created' => [DevicesEvents\ChannelPropertyStateEntityCreated::class, 'channel'],
			'channel updated' => [DevicesEvents\ChannelPropertyStateEntityUpdated::class, 'channel'],
		];
	}

	public function testSubscribesToTheSixStateEventsAndTheLoopLifeCycle(): void
	{
		self::assertSame(
			[
				DevicesEvents\ConnectorPropertyStateEntityCreated::class => 'stateCreated',
				DevicesEvents\ConnectorPropertyStateEntityUpdated::class => 'stateUpdated',
				DevicesEvents\DevicePropertyStateEntityCreated::class => 'stateCreated',
				DevicesEvents\DevicePropertyStateEntityUpdated::class => 'stateUpdated',
				DevicesEvents\ChannelPropertyStateEntityCreated::class => 'stateCreated',
				DevicesEvents\ChannelPropertyStateEntityUpdated::class => 'stateUpdated',
				EventLoopEvents\EventLoopStarted::class => 'loopStarted',
				EventLoopEvents\EventLoopStopping::class => 'loopStopped',
				EventLoopEvents\EventLoopStopped::class => 'loopStopped',
				PersistenceEvents\DbTransactionFinished::class => 'transactionFinished',
			],
			Subscribers\StateHistory::getSubscribedEvents(),
		);
	}

	/**
	 * @param class-string $eventClass
	 *
	 * @throws Throwable
	 */
	#[DataProvider('stateEvents')]
	public function testEveryStateEventIsRecorded(string $eventClass, string $scope): void
	{
		$recorder = $this->recorder(10, false);

		$subscriber = new Subscribers\StateHistory(
			$recorder,
			$this->createMock(EventLoop\LoopInterface::class),
			30,
		);

		$id = Uuid\Uuid::uuid4()->toString();

		$this->dispatch($subscriber, $this->event($eventClass, $scope, $id));

		self::assertSame([[self::T0, 21.5]], $this->rows($id));
	}

	/**
	 * @throws Throwable
	 */
	public function testTimerFlushesTheRecorderAndTheLoopEndingFlushesTheRest(): void
	{
		$recorder = $this->recorder(10, true);

		$timer = $this->createMock(EventLoop\TimerInterface::class);
		$callback = null;

		$loop = $this->createMock(EventLoop\LoopInterface::class);
		$loop
			->expects(self::once())
			->method('addPeriodicTimer')
			->with(30)
			->willReturnCallback(
				static function (int|float $interval, callable $listener) use ($timer, &$callback): EventLoop\TimerInterface {
					$callback = $listener;

					return $timer;
				},
			);
		$loop
			->expects(self::once())
			->method('cancelTimer')
			->with($timer);

		$subscriber = new Subscribers\StateHistory($recorder, $loop, 30);

		// A second start does not add a second timer
		$subscriber->loopStarted();
		$subscriber->loopStarted();

		self::assertInstanceOf(Closure::class, $callback);

		$this->dispatch(
			$subscriber,
			$this->event(DevicesEvents\ChannelPropertyStateEntityUpdated::class, 'channel', self::PROPERTY),
		);

		self::assertSame([], $this->rows());

		// The timer fires
		$callback();

		self::assertSame([[self::T0, 21.5]], $this->rows());

		// A change within minInterval waits for the next timer, and the loop ending writes it
		$this->dispatch(
			$subscriber,
			$this->event(
				DevicesEvents\ChannelPropertyStateEntityUpdated::class,
				'channel',
				self::PROPERTY,
				22.5,
				self::T0 + 3,
			),
		);

		self::assertSame(1, $recorder->getWaitingCount());

		$subscriber->loopStopped();

		self::assertSame([[self::T0, 21.5], [self::T0 + 3, 22.5]], $this->rows());

		// Stopping and Stopped both arrive, and the second finds nothing to cancel or write
		$subscriber->loopStopped();

		self::assertSame(2, $this->countRows());
	}

	/**
	 * A state written inside a transaction that Database::transaction() runs is held back by the recorder, and the
	 * subscriber has it written when the transaction is over
	 *
	 * @throws Throwable
	 */
	public function testStateWrittenInsideATransactionIsWrittenWhenItIsOver(): void
	{
		$recorder = $this->recorder(10, false);

		$subscriber = new Subscribers\StateHistory(
			$recorder,
			$this->createMock(EventLoop\LoopInterface::class),
			30,
		);

		$dispatcher = $this->getContainer()->getByType(ComponentEventDispatcher\EventDispatcher::class);
		$dispatcher->addSubscriber($subscriber);

		$id = Uuid\Uuid::uuid4()->toString();
		$event = $this->event(DevicesEvents\ChannelPropertyStateEntityUpdated::class, 'channel', $id);

		$this->getContainer()->getByType(Helpers\Database::class)->transaction(
			function () use ($subscriber, $event, $recorder, $id): void {
				$subscriber->stateUpdated($event);

				// Held back: nothing is written, and nothing is lost
				self::assertSame(1, $recorder->getBufferedCount());
				self::assertSame([], $this->rows($id));
			},
		);

		// Database::transaction() dispatched DbTransactionFinished, which the subscriber answered
		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame([[self::T0, 21.5]], $this->rows($id));
	}

	/**
	 * @param class-string $eventClass
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function event(
		string $eventClass,
		string $scope,
		string $id,
		float $value = 21.5,
		int $time = self::T0,
	): ContractsEventDispatcher\Event
	{
		$propertyId = Uuid\Uuid::fromString($id);
		$updatedAt = (new DateTimeImmutable())->setTimestamp($time);

		if ($scope === 'connector') {
			$property = new DevicesDocuments\Connectors\Properties\Dynamic(
				$propertyId,
				Uuid\Uuid::fromString(self::CONNECTOR),
				DevicesTypes\PropertyCategory::GENERIC,
				'property',
				null,
				ValuesTypes\DataType::FLOAT,
			);
			$state = new Tests\Fixtures\Dummy\ConnectorPropertyState(
				$propertyId,
				actualValue: $value,
				valid: true,
				updatedAt: $updatedAt,
			);

			return $eventClass === DevicesEvents\ConnectorPropertyStateEntityCreated::class
				? new DevicesEvents\ConnectorPropertyStateEntityCreated(
					$property,
					$state,
					$state,
					Sources\Module::DEVICES,
				)
				: new DevicesEvents\ConnectorPropertyStateEntityUpdated(
					$property,
					$state,
					$state,
					Sources\Module::DEVICES,
				);
		}

		if ($scope === 'device') {
			$property = new DevicesDocuments\Devices\Properties\Dynamic(
				$propertyId,
				Uuid\Uuid::fromString(self::DEVICE),
				DevicesTypes\PropertyCategory::GENERIC,
				'property',
				null,
				ValuesTypes\DataType::FLOAT,
			);
			$state = new Tests\Fixtures\Dummy\DevicePropertyState(
				$propertyId,
				actualValue: $value,
				valid: true,
				updatedAt: $updatedAt,
			);

			return $eventClass === DevicesEvents\DevicePropertyStateEntityCreated::class
				? new DevicesEvents\DevicePropertyStateEntityCreated($property, $state, $state, Sources\Module::DEVICES)
				: new DevicesEvents\DevicePropertyStateEntityUpdated(
					$property,
					$state,
					$state,
					Sources\Module::DEVICES,
				);
		}

		$property = $this->property(id: $id);
		$state = $this->state($value, $time, id: $id);

		return $eventClass === DevicesEvents\ChannelPropertyStateEntityCreated::class
			? new DevicesEvents\ChannelPropertyStateEntityCreated($property, $state, $state, Sources\Module::DEVICES)
			: new DevicesEvents\ChannelPropertyStateEntityUpdated($property, $state, $state, Sources\Module::DEVICES);
	}

	private function dispatch(Subscribers\StateHistory $subscriber, ContractsEventDispatcher\Event $event): void
	{
		$dispatcher = new ComponentEventDispatcher\EventDispatcher();
		$dispatcher->addSubscriber($subscriber);
		$dispatcher->dispatch($event);
	}

}
