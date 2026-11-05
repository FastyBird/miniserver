<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Models\History;

use DateTimeImmutable;
use Doctrine\DBAL;
use FastyBird\Bridge\DevicesModuleUiModule\Documents as DevicesModuleUiModuleDocuments;
use FastyBird\Bridge\DevicesModuleUiModule\Models as DevicesModuleUiModuleModels;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use FastyBird\Module\Ui\Types as UiTypes;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function sprintf;

/**
 * What the bridge reads of the history of a data source (#624)
 *
 * The fixtures bind data sources to a channel, a device and a connector property, a mapped property, a variable
 * property and a property that does not exist. The history is the Devices module's, written into its tables here.
 * The container's clock is frozen at NOW.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class PropertyReaderTest extends Tests\Cases\Unit\DbTestCase
{

	private const int NOW = 1_585_742_400;

	// A channel data source: the temperature property, a float in °C
	private const string CHANNEL_DATA_SOURCE = '764937a7-8565-472e-8e12-fe97cd55a377';

	// Bound to the mapped channel property that has the temperature as its parent, and has °F itself
	private const string MAPPED_DATA_SOURCE = 'c1d2e3f4-0516-4718-a9b0-c1d2e3f40516';

	private const string VARIABLE_DATA_SOURCE = 'd1e2f304-1526-4738-b9c0-d1e2f3041526';

	private const string CONNECTOR_DATA_SOURCE = 'f1a2b3c4-d5e6-4758-698a-9b0c1d2e3f40';

	// A device data source: the rssi property, whose id is the temperature's, an integer with no unit
	private const string DEVICE_DATA_SOURCE = 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e';

	private const string GENERIC_DATA_SOURCE = '0a1b2c3d-4e5f-4a6b-8c7d-9e0f1a2b3c4d';

	// The temperature, the connector's `mode`, and the parent of the mapped property
	private const string TEMPERATURE = '28bc0d38-2f7c-4a71-aa74-27b102f8df4c';

	private const string MODE = 'a4c2e6f8-0b1d-4f3a-9c5e-7a9b1d3f5a70';

	#[Override]
	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../../sql/history.sources.sql');
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../../sql/connector.dynamic.property.sql');

		parent::setUp();
	}

	/**
	 * @throws Throwable
	 */
	public function testChannelPropertyHistory(): void
	{
		$this->sample(self::TEMPERATURE, self::NOW - 1_000, 20.0);
		$this->sample(self::TEMPERATURE, self::NOW - 500, 22.0);

		$series = $this->read(self::CHANNEL_DATA_SOURCE, 1_000, 10);

		self::assertNotNull($series);
		self::assertSame(self::CHANNEL_DATA_SOURCE, $series->id);
		self::assertSame(UiTypes\HistoryTier::SAMPLES, $series->tier);
		self::assertSame('°C', $series->unit);
		self::assertSame('float', $series->dataType);
		self::assertSame(100, $series->resolution);
		self::assertSame(self::NOW - 1_000, $series->from->getTimestamp());
		self::assertSame(self::NOW, $series->to->getTimestamp());
		self::assertCount(10, $series->time);
		self::assertSame(self::NOW - 1_000, $series->time[0]);
		self::assertSame([20.0, 20.0, 20.0, 20.0, 20.0, 22.0, 22.0, 22.0, 22.0, 22.0], $series->avg);
		self::assertSame($series->avg, $series->last);
	}

	/**
	 * The data source says which table its property is in: the device property that has the id of the
	 * temperature is another property, an integer with no unit
	 *
	 * @throws Throwable
	 */
	public function testDevicePropertyHistory(): void
	{
		$series = $this->read(self::DEVICE_DATA_SOURCE, 1_000, 10);

		self::assertNotNull($series);
		self::assertSame(self::DEVICE_DATA_SOURCE, $series->id);
		self::assertNull($series->unit);
		self::assertSame('int', $series->dataType);
	}

	/**
	 * @throws Throwable
	 */
	public function testConnectorPropertyHistory(): void
	{
		$this->sample(self::MODE, self::NOW - 100, 1.0);

		$series = $this->read(self::CONNECTOR_DATA_SOURCE, 1_000, 10);

		self::assertNotNull($series);
		self::assertSame('string', $series->dataType);
		// Nothing is known before the first sample, so the series starts at the point it falls in
		self::assertSame([self::NOW - 100], $series->time);
		self::assertSame([1.0], $series->avg);
	}

	/**
	 * A mapped property has no history of its own: its parent's is the one that is recorded, and what is shown
	 * is the parent's values, unconverted, with the parent's unit and data type
	 *
	 * @throws Throwable
	 */
	public function testMappedPropertyShowsTheHistoryOfItsParent(): void
	{
		$this->sample(self::TEMPERATURE, self::NOW - 1_000, 20.0);

		$series = $this->read(self::MAPPED_DATA_SOURCE, 1_000, 10);

		self::assertNotNull($series);
		self::assertSame(self::MAPPED_DATA_SOURCE, $series->id);
		self::assertSame('°C', $series->unit);
		self::assertSame(20.0, $series->avg[0]);
	}

	/**
	 * @throws Throwable
	 */
	public function testNoHistoryOfAVariableOfAMissingPropertyOrOfAGenericDataSource(): void
	{
		self::assertNull($this->read(self::VARIABLE_DATA_SOURCE, 1_000, 10));
		self::assertNull($this->read(self::GENERIC_DATA_SOURCE, 1_000, 10));
	}

	/**
	 * A data source whose property is gone has no history. The tables do not let a data source outlive its
	 * property, so the document is made by hand
	 *
	 * @throws Throwable
	 */
	public function testNoHistoryOfAPropertyThatIsGone(): void
	{
		$reader = $this->getContainer()->getByType(DevicesModuleUiModuleModels\History\PropertyReader::class);

		foreach (
			[
				DevicesModuleUiModuleDocuments\Widgets\DataSources\ChannelProperty::class,
				DevicesModuleUiModuleDocuments\Widgets\DataSources\DeviceProperty::class,
				DevicesModuleUiModuleDocuments\Widgets\DataSources\ConnectorProperty::class,
			] as $class
		) {
			$dataSource = new $class(Uuid\Uuid::uuid4(), Uuid\Uuid::uuid4(), Uuid\Uuid::uuid4(), Uuid\Uuid::uuid4());

			self::assertNull(
				$reader->read($dataSource, $this->at(self::NOW - 1_000), $this->at(self::NOW), 10),
				$class,
			);
		}
	}

	/**
	 * @throws Throwable
	 */
	public function testEmptyHistoryIsAnEmptySeries(): void
	{
		$series = $this->read(self::CHANNEL_DATA_SOURCE, 1_000, 10);

		self::assertNotNull($series);
		self::assertSame([], $series->time);
		self::assertSame([], $series->avg);
		self::assertSame('°C', $series->unit);
	}

	/**
	 * What the Devices repository refuses is a failure to read, in the Ui module's words
	 *
	 * @throws Throwable
	 */
	public function testPeriodTheRepositoryRefusesIsAFailure(): void
	{
		$this->expectException(UiExceptions\InvalidState::class);

		$reader = $this->getContainer()->getByType(DevicesModuleUiModuleModels\History\PropertyReader::class);

		$reader->read($this->dataSource(self::CHANNEL_DATA_SOURCE), $this->at(self::NOW), $this->at(self::NOW), 10);
	}

	/**
	 * Registered as the module's reader
	 *
	 * @throws Throwable
	 */
	public function testIsTheReaderOfTheUiModule(): void
	{
		self::assertInstanceOf(
			DevicesModuleUiModuleModels\History\PropertyReader::class,
			$this->getContainer()->getByType(UiModels\History\Reader::class),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function read(string $dataSource, int $span, int $points): UiModels\History\Series|null
	{
		return $this->getContainer()->getByType(DevicesModuleUiModuleModels\History\PropertyReader::class)->read(
			$this->dataSource($dataSource),
			$this->at(self::NOW - $span),
			$this->at(self::NOW),
			$points,
		);
	}

	/**
	 * @throws Throwable
	 */
	private function dataSource(string $id): UiDocuments\Widgets\DataSources\DataSource
	{
		$dataSource = $this->getContainer()->getByType(
			UiModels\Configuration\Widgets\DataSources\Repository::class,
		)->find(Uuid\Uuid::fromString($id));

		self::assertNotNull($dataSource);

		return $dataSource;
	}

	/**
	 * @throws Throwable
	 */
	private function sample(string $property, int $time, float $value): void
	{
		$this->getDb()->executeStatement(
			sprintf(
				'INSERT INTO %s (property_id, sampled_at, value) VALUES (?, ?, ?)',
				DevicesEntities\History\Sample::TABLE,
			),
			[Uuid\Uuid::fromString($property)->getBytes(), $time, $value],
			[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER, DBAL\ParameterType::STRING],
		);
	}

	private function at(int $time): DateTimeImmutable
	{
		return (new DateTimeImmutable())->setTimestamp($time);
	}

}
