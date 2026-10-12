<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Commands;

use DateTimeImmutable;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Commands;
use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Tests;
use Monolog;
use Nette;
use Nette\Localization;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use React\EventLoop;
use RuntimeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher;

/**
 * The connector command reacts to the connector termination and restart requests itself, and only
 * while it runs a connector. It used to receive them through the connector container's
 * `$onTerminate`/`$onRestart` callback arrays.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ConnectorTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '7a3dd94c-7294-46fd-8c61-1b375c313d4d';

	private EventDispatcher\EventDispatcher $dispatcher;

	private Monolog\Handler\TestHandler $logs;

	private Connectors\ContainerFactory&MockObject $serviceFactory;

	private Connectors\Connector&MockObject $service;

	private EventLoop\LoopInterface&MockObject $eventLoop;

	/** @var array<class-string> */
	private array $terminations = [];

	private Commands\Connector $command;

	/**
	 * @throws Console\Exception\LogicException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	#[Override]
	public function setUp(): void
	{
		parent::setUp();

		$this->dispatcher = new EventDispatcher\EventDispatcher();
		$this->dispatcher->addListener(
			Events\BeforeConnectorTerminate::class,
			function (Events\BeforeConnectorTerminate $event): void {
				$this->terminations[] = $event::class;
			},
		);

		$this->logs = new Monolog\Handler\TestHandler();

		// The real container resolves the connector's own service through a factory registered
		// for the connector's type, so the service the command terminates is this mock
		$this->service = $this->createMock(Connectors\Connector::class);

		$connectorFactory = $this->createMock(Connectors\ConnectorFactory::class);
		$connectorFactory
			->method('create')
			->willReturn($this->service);

		$this->serviceFactory = $this->createMock(Connectors\ContainerFactory::class);
		$this->serviceFactory
			->method('create')
			->willReturnCallback(
				static fn (Documents\Connectors\Connector $connector): Connectors\Container => new Connectors\Container(
					['dummy' => $connectorFactory],
					$connector,
				),
			);

		$this->eventLoop = $this->createMock(EventLoop\LoopInterface::class);
		$this->eventLoop
			->method('addPeriodicTimer')
			->willReturn($this->createMock(EventLoop\TimerInterface::class));
		$this->eventLoop
			->method('addTimer')
			->willReturn($this->createMock(EventLoop\TimerInterface::class));

		$clock = $this->createMock(ClockInterface::class);
		$clock
			->method('now')
			->willReturn(new DateTimeImmutable());

		$translator = $this->createMock(Localization\Translator::class);
		$translator
			->method('translate')
			->willReturn('');

		$this->command = new Commands\Connector(
			$this->serviceFactory,
			$this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class),
			$this->getContainer()->getByType(Models\Configuration\Connectors\Controls\Repository::class),
			new Devices\Logger(new Monolog\Logger('test', [$this->logs])),
			$this->getContainer()->getByType(Helpers\Database::class),
			$this->getContainer()->getByType(Consumers\Container::class),
			$this->eventLoop,
			$clock,
			$translator,
			[],
			$this->dispatcher,
		);

		$this->dispatcher->addSubscriber($this->command);
	}

	public function testSubscribedEvents(): void
	{
		self::assertSame(
			[
				Events\TerminateConnector::class => 'processTermination',
				Events\RestartConnector::class => 'processRestart',
			],
			Commands\Connector::getSubscribedEvents(),
		);
	}

	/**
	 * @throws Console\Exception\CommandNotFoundException
	 * @throws Console\Exception\LogicException
	 */
	public function testTerminationTerminatesRunningConnector(): void
	{
		$this->serviceFactory
			->expects(self::once())
			->method('create');

		$this->service
			->expects(self::once())
			->method('terminate');

		$this->eventLoop
			->expects(self::once())
			->method('run')
			->willReturnCallback(function (): void {
				$this->dispatcher->dispatch(
					new Events\TerminateConnector(Sources\Connector::HOMEKIT, 'Socket server could not be created'),
				);
			});

		self::assertSame(Console\Command\Command::SUCCESS, $this->runCommand());

		self::assertSame([Events\BeforeConnectorTerminate::class], $this->terminations);
		self::assertTrue($this->logs->hasInfoThatContains('Triggering connector termination'));
	}

	/**
	 * @throws Console\Exception\CommandNotFoundException
	 * @throws Console\Exception\LogicException
	 */
	public function testRestartTerminatesRunningConnector(): void
	{
		$this->serviceFactory
			->expects(self::once())
			->method('create');

		$this->service
			->expects(self::once())
			->method('terminate');

		$this->eventLoop
			->expects(self::once())
			->method('run')
			->willReturnCallback(function (): void {
				$this->dispatcher->dispatch(
					new Events\RestartConnector(
						Sources\Connector::HOMEKIT,
						'Accessories were changed',
						new RuntimeException('Restart'),
					),
				);
			});

		self::assertSame(Console\Command\Command::SUCCESS, $this->runCommand());

		self::assertSame([Events\BeforeConnectorTerminate::class], $this->terminations);
		self::assertTrue($this->logs->hasWarningThatContains('Triggering connector restart due to some error'));
	}

	public function testRequestsWithoutRunningConnectorAreIgnored(): void
	{
		$this->serviceFactory
			->expects(self::never())
			->method('create');

		$this->service
			->expects(self::never())
			->method('terminate');

		$this->dispatcher->dispatch(new Events\TerminateConnector(Sources\Connector::HOMEKIT, 'Terminate'));
		$this->dispatcher->dispatch(new Events\RestartConnector(Sources\Connector::HOMEKIT, 'Restart'));

		self::assertSame([], $this->terminations);
		self::assertSame([], $this->logs->getRecords());
	}

	/**
	 * @throws Console\Exception\CommandNotFoundException
	 * @throws Console\Exception\LogicException
	 */
	public function testRequestsAfterConnectorStoppedAreIgnored(): void
	{
		$this->serviceFactory
			->expects(self::once())
			->method('create');

		$this->service
			->expects(self::never())
			->method('terminate');

		$this->eventLoop
			->expects(self::once())
			->method('run');

		self::assertSame(Console\Command\Command::SUCCESS, $this->runCommand());

		$this->dispatcher->dispatch(new Events\TerminateConnector(Sources\Connector::HOMEKIT, 'Terminate'));
		$this->dispatcher->dispatch(new Events\RestartConnector(Sources\Connector::HOMEKIT, 'Restart'));

		self::assertSame([], $this->terminations);
		self::assertFalse($this->logs->hasInfoThatContains('Triggering connector'));
	}

	/**
	 * @throws Console\Exception\CommandNotFoundException
	 * @throws Console\Exception\LogicException
	 */
	private function runCommand(): int
	{
		$application = new Application();
		$application->add($this->command);

		$commandTester = new CommandTester($application->get(Commands\Connector::NAME));

		return $commandTester->execute([
			'--connector' => self::CONNECTOR_ID,
			'--quiet' => true,
			'--no-interaction' => true,
		]);
	}

}
