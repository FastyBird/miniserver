<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit;

use DateTimeImmutable;
use Doctrine\DBAL;
use Error;
use FastyBird\Core\Clock\FrozenClock;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Override;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid;
use RuntimeException;
use function assert;
use function is_numeric;
use function sprintf;

/**
 * What the property value history tests share: a recorder over the test database, properties and
 * states to feed it, and the rows it wrote
 */
abstract class HistoryTestCase extends DbTestCase
{

	// The container's frozen clock, 2020-04-01T12:00:00+00:00
	protected const int NOW = 1_585_742_400;

	protected const int T0 = self::NOW - 3_600;

	protected const string CHANNEL = '6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a';

	// Two dynamic channel properties that exist: humidity of tests/sql/dummy.data.sql, whose id no other property
	// table shares, and one that tests/sql/history.properties.sql adds. The compactor deletes the history of an id
	// that is in none of the property tables
	protected const string PROPERTY = '24c436f4-a2e4-4d2b-b910-1a3ff785b784';

	protected const string OTHER_PROPERTY = 'b4e2d3c5-6f7a-4b8c-9d0e-1f2a3b4c5d6e';

	// Not in any property table
	protected const string DEAD_PROPERTY = 'd0d0d0d0-5e6f-4a7b-8c9d-0e1f2a3b4c5d';

	protected Tests\Support\RecordingLogger $logger;

	protected EventLoop\Status $status;

	#[Override]
	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../sql/history.properties.sql');

		parent::setUp();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function recorder(int $minInterval = 10, bool $running = false): Models\History\Recorder
	{
		$this->logger = new Tests\Support\RecordingLogger();
		$this->status = new EventLoop\Status();
		$this->status->running = $running;

		return new Models\History\Recorder(
			$this->getDb(),
			$this->getContainer()->getByType(Helpers\Database::class),
			$this->status,
			$this->getContainer()->getByType(ClockInterface::class),
			$minInterval,
			$this->logger,
		);
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function property(
		ValuesTypes\DataType $dataType = ValuesTypes\DataType::FLOAT,
		int|float|null $step = null,
		string $id = self::PROPERTY,
	): DevicesDocuments\Channels\Properties\Dynamic
	{
		return new DevicesDocuments\Channels\Properties\Dynamic(
			Uuid\Uuid::fromString($id),
			Uuid\Uuid::fromString(self::CHANNEL),
			DevicesTypes\PropertyCategory::GENERIC,
			'property',
			null,
			$dataType,
			step: $step,
		);
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function mappedProperty(
		ValuesTypes\DataType $dataType = ValuesTypes\DataType::FLOAT,
	): DevicesDocuments\Channels\Properties\Mapped
	{
		return new DevicesDocuments\Channels\Properties\Mapped(
			Uuid\Uuid::fromString(self::OTHER_PROPERTY),
			Uuid\Uuid::fromString(self::CHANNEL),
			Uuid\Uuid::fromString(self::PROPERTY),
			DevicesTypes\PropertyCategory::GENERIC,
			'mapped',
			null,
			$dataType,
		);
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function state(
		bool|float|int|string|Payloads\Payload|null $value,
		int|null $time = null,
		bool $valid = true,
		string $id = self::PROPERTY,
	): Tests\Fixtures\Dummy\ChannelPropertyState
	{
		return new Tests\Fixtures\Dummy\ChannelPropertyState(
			Uuid\Uuid::fromString($id),
			actualValue: $value,
			valid: $valid,
			updatedAt: $time === null ? null : (new DateTimeImmutable())->setTimestamp($time),
		);
	}

	/**
	 * @return list<array{int, float|null}> the stored samples of a property, as [time, value]
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function rows(string $id = self::PROPERTY): array
	{
		$rows = [];

		foreach (
			$this->getDb()->fetchAllAssociative(
				sprintf(
					'SELECT sampled_at, value FROM %s WHERE property_id = ? ORDER BY sampled_at',
					Entities\History\Sample::TABLE,
				),
				[Uuid\Uuid::fromString($id)->getBytes()],
				[DBAL\ParameterType::BINARY],
			) as $row
		) {
			$time = $row['sampled_at'];
			$value = $row['value'];

			assert(is_numeric($time));
			assert($value === null || is_numeric($value));

			$rows[] = [(int) $time, $value === null ? null : (float) $value];
		}

		return $rows;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function countRows(): int
	{
		$count = $this->getDb()->fetchOne(sprintf('SELECT COUNT(*) FROM %s', Entities\History\Sample::TABLE));

		assert(is_numeric($count));

		return (int) $count;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function compactor(
		int $settle = 90,
		int $samplesDays = 7,
		int $fiveMinutesDays = 30,
		int $hoursDays = 365,
		int $now = self::NOW,
	): Models\History\Compactor
	{
		$this->logger = new Tests\Support\RecordingLogger();

		return new Models\History\Compactor(
			$this->getDb(),
			$this->getContainer()->getByType(Helpers\Database::class),
			new FrozenClock($now),
			$settle,
			$samplesDays,
			$fiveMinutesDays,
			$hoursDays,
			$this->logger,
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function repository(
		int $samplesDays = 7,
		int $fiveMinutesDays = 30,
		int $hoursDays = 365,
	): Models\History\Repository
	{
		return new Models\History\Repository(
			$this->getDb(),
			$this->getContainer()->getByType(Helpers\Database::class),
			$this->getContainer()->getByType(ClockInterface::class),
			$samplesDays,
			$fiveMinutesDays,
			$hoursDays,
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function insertSample(int $time, float|null $value, string $id = self::PROPERTY): void
	{
		$this->getDb()->executeStatement(
			sprintf('INSERT INTO %s (property_id, sampled_at, value) VALUES (?, ?, ?)', Entities\History\Sample::TABLE),
			[Uuid\Uuid::fromString($id)->getBytes(), $time, $value],
			[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER, $value === null ? DBAL\ParameterType::NULL : DBAL\ParameterType::STRING],
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function insertRollup(
		DevicesTypes\HistoryResolution $resolution,
		int $time,
		float|null $min,
		float|null $max,
		float|null $avg,
		float|null $last,
		int $samples = 1,
		string $id = self::PROPERTY,
	): void
	{
		$this->getDb()->executeStatement(
			sprintf(
				'INSERT INTO %s (property_id, resolution, bucket_start, value_min, value_max, value_avg, value_last, samples)'
				. ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
				Entities\History\Rollup::TABLE,
			),
			[Uuid\Uuid::fromString($id)->getBytes(), $resolution->value, $time, $min, $max, $avg, $last, $samples],
		);
	}

	/**
	 * @return list<array{int, float|null, float|null, float|null, float|null, int}> [time, min, max, avg, last, samples]
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	protected function rollups(DevicesTypes\HistoryResolution $resolution, string $id = self::PROPERTY): array
	{
		$rows = [];

		foreach (
			$this->getDb()->fetchAllAssociative(
				sprintf(
					'SELECT bucket_start, value_min, value_max, value_avg, value_last, samples FROM %s'
					. ' WHERE property_id = ? AND resolution = ? ORDER BY bucket_start',
					Entities\History\Rollup::TABLE,
				),
				[Uuid\Uuid::fromString($id)->getBytes(), $resolution->value],
				[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER],
			) as $row
		) {
			assert(is_numeric($row['bucket_start']) && is_numeric($row['samples']));

			$rows[] = [
				(int) $row['bucket_start'],
				self::nullableFloat($row['value_min']),
				self::nullableFloat($row['value_max']),
				self::nullableFloat($row['value_avg']),
				self::nullableFloat($row['value_last']),
				(int) $row['samples'],
			];
		}

		return $rows;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function countRollups(): int
	{
		$count = $this->getDb()->fetchOne(sprintf('SELECT COUNT(*) FROM %s', Entities\History\Rollup::TABLE));

		assert(is_numeric($count));

		return (int) $count;
	}

	private static function nullableFloat(mixed $value): float|null
	{
		assert($value === null || is_numeric($value));

		return $value === null ? null : (float) $value;
	}

}
