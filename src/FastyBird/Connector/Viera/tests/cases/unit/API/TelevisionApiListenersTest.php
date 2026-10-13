<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Tests\Cases\Unit\API;

use Closure;
use Error;
use FastyBird\Connector\Viera\API;
use FastyBird\Connector\Viera\Exceptions as VieraExceptions;
use FastyBird\Connector\Viera\Tests;
use FastyBird\Core\EventLoop as CoreEventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use Nette;
use Override;
use React\Socket;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use function array_fill;
use function array_map;
use function array_values;
use function assert;
use function count;
use function func_get_args;
use function is_array;

final class TelevisionApiListenersTest extends Tests\Cases\Unit\BaseTestCase
{

	private Socket\SocketServer|null $eventsServer = null;

	private Tests\Support\InertEventLoop|null $loop = null;

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\TelevisionApi::class))->getProperties(ReflectionProperty::IS_PUBLIC);

		self::assertSame([], $properties);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws VieraExceptions\TelevisionApiError
	 * @throws Error
	 */
	public function testEventNotificationNotifiesMessageListenersInOrderWithTheEvent(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		// Asking for the screen state is what makes the client subscribe to the television's events
		$client->isTurnedOn();

		$connection = $this->openEventsServer($client);

		$connection->emit('data', [$this->buildNotification('On', 'tv')]);

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(API\Messages\Response\Event::class, $event[1][0]);
			self::assertTrue($event[1][0]->getScreenState());
			self::assertSame('tv', $event[1][0]->getInputMode());
		}

		// The television is told the notification arrived
		self::assertCount(1, $connection->getWritten());
		self::assertStringStartsWith('HTTP/1.1 200 OK', $connection->getWritten()[0]);
	}

	/**
	 * `isTurnedOn()` registers a listener on the client itself, to learn the screen state from the next event. It goes
	 * through the same registration methods, after the listeners a caller registered before it.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws VieraExceptions\TelevisionApiError
	 * @throws Error
	 */
	public function testIsTurnedOnResolvesFromTheNextEventAfterTheListenersRegisteredBeforeIt(): void
	{
		$events = [];
		$resolved = [];

		$client = $this->createClient();

		$client->onMessage($this->record('caller', $events));

		$client->isTurnedOn()->then($this->record('resolved', $resolved));

		$connection = $this->openEventsServer($client);

		self::assertCount(0, array_values($resolved));

		$connection->emit('data', [$this->buildNotification('On', 'tv')]);

		self::assertCount(1, $events);
		self::assertSame([['resolved', [true]]], $resolved);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws VieraExceptions\TelevisionApiError
	 * @throws Error
	 */
	public function testSubscriptionConnectionErrorNotifiesErrorListenersInOrderWithTheError(): void
	{
		$events = [];
		$error = new RuntimeException('Connection broke');

		$client = $this->createClient();

		$client->onError($this->record('first', $events));
		$client->onError($this->record('second', $events));

		// Asking for the screen state is what makes the client subscribe to the television's events
		$client->isTurnedOn();

		$connection = $this->openEventsServer($client);

		$connection->emit('error', [$error]);

		self::assertSame([['first', [$error]], ['second', [$error]]], $events);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws VieraExceptions\TelevisionApiError
	 * @throws Error
	 */
	public function testIsTurnedOnResolvesToFalseWhenTheSubscriptionConnectionFails(): void
	{
		$events = [];
		$resolved = [];

		$client = $this->createClient();

		$client->onError($this->record('caller', $events));

		$client->isTurnedOn()->then($this->record('resolved', $resolved));

		$connection = $this->openEventsServer($client);

		$connection->emit('error', [new RuntimeException('Connection broke')]);

		self::assertCount(1, $events);
		self::assertSame([['resolved', [false]]], $resolved);
	}

	/**
	 * Asking for the screen state registers a listener for the answer, and the listener has done its work once the
	 * question is answered, by the television's event, by its connection failing or by the time running out. A client
	 * asked over and over does not keep a listener for every question it was ever asked.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws VieraExceptions\TelevisionApiError
	 * @throws Error
	 */
	public function testAskingForTheScreenStateAgainAndAgainDoesNotAccumulateListeners(): void
	{
		$events = [];
		$resolved = [];

		$client = $this->createClient();

		$client->onMessage($this->record('caller', $events));
		$client->onError($this->record('caller', $events));

		for ($i = 1; $i <= 5; $i++) {
			$client->isTurnedOn()->then($this->record('resolved', $resolved));

			// The question is waiting for its answer, and the time given for it runs out
			self::assertSame([2, 2], $this->countListeners($client));
			self::assertCount($i - 1, $resolved);

			$this->runTimers();

			self::assertCount($i, $resolved);
			self::assertSame([1, 1], $this->countListeners($client));
		}

		self::assertSame(array_fill(0, 5, ['resolved', [false]]), $resolved);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws VieraExceptions\TelevisionApiError
	 * @throws Error
	 */
	public function testQuestionsWaitingTogetherAreAllAnsweredByTheNextEventAndLeaveNoListeners(): void
	{
		$resolved = [];

		$client = $this->createClient();

		for ($i = 0; $i < 4; $i++) {
			$client->isTurnedOn()->then($this->record('resolved', $resolved));
		}

		$connection = $this->openEventsServer($client);

		self::assertSame([4, 4], $this->countListeners($client));
		self::assertSame([], $resolved);

		$connection->emit('data', [$this->buildNotification('On', 'tv')]);

		self::assertSame(array_fill(0, 4, ['resolved', [true]]), $resolved);
		self::assertSame([0, 0], $this->countListeners($client));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws VieraExceptions\TelevisionApiError
	 * @throws Error
	 */
	public function testQuestionAnsweredByTheFailingConnectionLeavesNoListeners(): void
	{
		$resolved = [];

		$client = $this->createClient();

		$client->isTurnedOn()->then($this->record('resolved', $resolved));
		$client->isTurnedOn()->then($this->record('resolved', $resolved));

		$connection = $this->openEventsServer($client);

		$connection->emit('error', [new RuntimeException('Connection broke')]);

		self::assertSame(array_fill(0, 2, ['resolved', [false]]), $resolved);
		self::assertSame([0, 0], $this->countListeners($client));
	}

	#[Override]
	protected function tearDown(): void
	{
		// The events server listens on the process-wide loop, which would run for ever when PHP shuts down
		$this->eventsServer?->close();
		$this->eventsServer = null;

		parent::tearDown();
	}

	/**
	 * The television is at a loopback address nothing listens on, so the client's request to subscribe to its
	 * events is refused at once and no packet leaves the machine
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	private function createClient(): API\TelevisionApi
	{
		// The container's loop runs at the end of the process, and a timer the client sets would keep it alive
		$this->loop = new Tests\Support\InertEventLoop();

		$this->mockContainerService(CoreEventLoop\Wrapper::class, $this->loop);

		return $this->container->getByType(API\TelevisionApiFactory::class)->create(
			'testing-television',
			'127.0.0.1',
			1,
		);
	}

	/**
	 * @return array{int, int} the message listeners and the error listeners the client holds
	 *
	 * @throws ReflectionException
	 */
	private function countListeners(API\TelevisionApi $client): array
	{
		$messages = (new ReflectionProperty($client, 'onMessageListeners'))->getValue($client);
		$errors = (new ReflectionProperty($client, 'onErrorListeners'))->getValue($client);

		assert(is_array($messages) && is_array($errors));

		return [count($messages), count($errors)];
	}

	/**
	 * Lets the time pass for every timer the client set
	 */
	private function runTimers(): void
	{
		$loop = $this->loop;

		assert($loop !== null);

		foreach ($loop->getTimers() as $timer) {
			$loop->cancelTimer($timer);

			($timer->getCallback())();
		}
	}

	/**
	 * The television opens a connection to the server the client listens on for its notifications. The test plays that
	 * connection, so nothing travels over the network.
	 *
	 * @throws ReflectionException
	 */
	private function openEventsServer(API\TelevisionApi $client): Tests\Support\FakeConnection
	{
		$server = (new ReflectionProperty($client, 'eventsServer'))->getValue($client);
		assert($server instanceof Socket\SocketServer);

		$this->eventsServer = $server;

		$connection = new Tests\Support\FakeConnection();

		$server->emit('connection', [$connection]);

		return $connection;
	}

	private function buildNotification(string $screenState, string $inputMode): string
	{
		return "NOTIFY / HTTP/1.1\r\nHost: 10.10.0.5\r\nContent-Type: text/xml\r\n\r\n"
			. '<e:propertyset><e:property><X_ScreenState>' . $screenState . '</X_ScreenState></e:property>'
			. '<e:property><X_InputMode>' . $inputMode . '</X_InputMode></e:property></e:propertyset>';
	}

	/**
	 * @param array<int, array{string, array<mixed>}> $events
	 *
	 * @return Closure(): void
	 */
	private function record(string $name, array &$events): Closure
	{
		return static function () use ($name, &$events): void {
			$events[] = [$name, func_get_args()];
		};
	}

}
