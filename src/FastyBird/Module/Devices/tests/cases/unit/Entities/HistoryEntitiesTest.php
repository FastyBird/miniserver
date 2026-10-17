<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Entities;

use Doctrine\DBAL;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Throwable;
use function assert;
use function is_numeric;
use function is_string;

/**
 * The tables the property value history owns, as the mapping creates them (#624)
 *
 * The test database is built from the mapping with SchemaTool, so this reads back what the migration has to
 * match: CI's `orm:validate-schema` compares the migrated schema with this same mapping.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class HistoryEntitiesTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws Throwable
	 */
	public function testSamplesTable(): void
	{
		self::assertSame(
			[
				['property_id', 'binary(16)', 'NO'],
				['sampled_at', 'int(10) unsigned', 'NO'],
				['value', 'double', 'YES'],
			],
			$this->columns(Entities\History\Sample::TABLE),
		);
		self::assertSame(['property_id', 'sampled_at'], $this->primaryKey(Entities\History\Sample::TABLE));
		self::assertSame('Property value history', $this->comment(Entities\History\Sample::TABLE));
		self::assertSame(0, $this->foreignKeys(Entities\History\Sample::TABLE));
	}

	/**
	 * @throws Throwable
	 */
	public function testRollupsTable(): void
	{
		self::assertSame(
			[
				['property_id', 'binary(16)', 'NO'],
				['resolution', 'int(10) unsigned', 'NO'],
				['bucket_start', 'int(10) unsigned', 'NO'],
				['value_min', 'double', 'YES'],
				['value_max', 'double', 'YES'],
				['value_avg', 'double', 'YES'],
				['value_last', 'double', 'YES'],
				['samples', 'int(10) unsigned', 'NO'],
			],
			$this->columns(Entities\History\Rollup::TABLE),
		);
		self::assertSame(
			['property_id', 'resolution', 'bucket_start'],
			$this->primaryKey(Entities\History\Rollup::TABLE),
		);
		self::assertSame('Property value history rollups', $this->comment(Entities\History\Rollup::TABLE));
		self::assertSame(0, $this->foreignKeys(Entities\History\Rollup::TABLE));
	}

	/**
	 * Both entities are mapped read-only, because the history is written and read through DBAL only
	 *
	 * @throws Throwable
	 */
	public function testEntitiesAreReadOnly(): void
	{
		foreach ([Entities\History\Sample::class, Entities\History\Rollup::class] as $class) {
			$metadata = $this->getEntityManager()->getClassMetadata($class);

			self::assertTrue($metadata->isReadOnly, $class);
		}
	}

	/**
	 * @return array<string, array{Types\HistoryResolution, int}>
	 */
	public static function resolutions(): array
	{
		return [
			'five minutes' => [Types\HistoryResolution::FIVE_MINUTES, 300],
			'hour' => [Types\HistoryResolution::HOUR, 3_600],
		];
	}

	#[DataProvider('resolutions')]
	public function testResolutionIsItsBucketWidthInSeconds(Types\HistoryResolution $resolution, int $seconds): void
	{
		self::assertSame($seconds, $resolution->value);
	}

	/**
	 * @return list<array{string, string, string}> name, type and nullability of each column, in table order
	 *
	 * @throws DBAL\Exception
	 * @throws Throwable
	 */
	private function columns(string $table): array
	{
		$columns = [];

		foreach (
			$this->getDb()->fetchAllAssociative(
				'SELECT COLUMN_NAME AS name, COLUMN_TYPE AS type, IS_NULLABLE AS nullable FROM information_schema.COLUMNS'
				. ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
				[$table],
			) as $row
		) {
			assert(is_string($row['name']) && is_string($row['type']) && is_string($row['nullable']));

			$columns[] = [$row['name'], $row['type'], $row['nullable']];
		}

		return $columns;
	}

	/**
	 * @return list<string>
	 *
	 * @throws DBAL\Exception
	 * @throws Throwable
	 */
	private function primaryKey(string $table): array
	{
		$columns = [];

		foreach (
			$this->getDb()->fetchFirstColumn(
				'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
				. ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = \'PRIMARY\''
				. ' ORDER BY ORDINAL_POSITION',
				[$table],
			) as $column
		) {
			assert(is_string($column));

			$columns[] = $column;
		}

		return $columns;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Throwable
	 */
	private function comment(string $table): string
	{
		$comment = $this->getDb()->fetchOne(
			'SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
			[$table],
		);

		assert(is_string($comment));

		return $comment;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Throwable
	 */
	private function foreignKeys(string $table): int
	{
		$count = $this->getDb()->fetchOne(
			'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS'
			. ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
			[$table],
		);

		assert(is_numeric($count));

		return (int) $count;
	}

}
