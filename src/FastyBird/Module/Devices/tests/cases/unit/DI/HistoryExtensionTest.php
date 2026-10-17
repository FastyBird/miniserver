<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DI;

use DateTimeImmutable;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types as DevicesTypes;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;

/**
 * What `fbDevicesModule.history` registers (#624)
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class HistoryExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	// The container's frozen clock, 2020-04-01T12:00:00+00:00, less an hour
	private const int T0 = 1_585_738_800;

	private const string CHANNEL = '6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a';

	private const string PROPERTY = 'a3f1c2d4-5e6f-4a7b-8c9d-0e1f2a3b4c5d';

	/**
	 * The history ships off, and off it registers nothing: no recorder to buffer, no subscriber to listen
	 * and no timer to start
	 *
	 * @throws Throwable
	 */
	public function testDisabledByDefaultAndRegistersNothing(): void
	{
		self::assertNull($this->getContainer()->getByType(Models\History\Recorder::class, false));
		self::assertNull($this->getContainer()->getByType(Subscribers\StateHistory::class, false));

		self::assertSame([], $this->getContainer()->findByType(Models\History\Recorder::class));
		self::assertSame([], $this->getContainer()->findByType(Subscribers\StateHistory::class));
	}

	/**
	 * @throws Throwable
	 */
	public function testEnabledRegistersTheRecorderAndTheSubscriber(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		self::assertNotNull($this->getContainer()->getByType(Models\History\Recorder::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\StateHistory::class, false));
	}

	/**
	 * `history.minInterval` reaches the recorder: with 7 seconds, a change 8 seconds after the last stored one is
	 * stored at once, where the default of 10 would have it wait
	 *
	 * @throws Throwable
	 */
	public function testConfiguredMinIntervalReachesTheRecorder(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$this->getContainer()->getByType(EventLoop\Status::class)->running = true;

		$recorder = $this->getContainer()->getByType(Models\History\Recorder::class);

		$property = new DevicesDocuments\Channels\Properties\Dynamic(
			Uuid\Uuid::fromString(self::PROPERTY),
			Uuid\Uuid::fromString(self::CHANNEL),
			DevicesTypes\PropertyCategory::GENERIC,
			'property',
			null,
			ValuesTypes\DataType::FLOAT,
		);

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 8));

		self::assertSame(2, $recorder->getBufferedCount());
		self::assertSame(0, $recorder->getWaitingCount());

		$recorder->record($property, $this->state(3.0, self::T0 + 10));

		self::assertSame(1, $recorder->getWaitingCount());
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function state(float $value, int $time): Tests\Fixtures\Dummy\ChannelPropertyState
	{
		return new Tests\Fixtures\Dummy\ChannelPropertyState(
			Uuid\Uuid::fromString(self::PROPERTY),
			actualValue: $value,
			valid: true,
			updatedAt: (new DateTimeImmutable())->setTimestamp($time),
		);
	}

}
