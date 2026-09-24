<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Subscribers\DoctrineMigrations;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use FastyBird\Core\Persistence\Subscribers;
use PHPUnit\Framework\TestCase;
use function array_map;

/**
 * Doctrine Migrations' bookkeeping table is not ORM-mapped, so without this subscriber
 * schema-tool proposes dropping it on every run (#477). This guards that the subscriber adds a
 * matching table to the ORM-generated schema instead of leaving the comparison to find it
 * missing.
 */
final class SchemaSubscriberTest extends TestCase
{

	/**
	 * @throws TypesException
	 */
	public function testPostGenerateSchemaAddsTableMatchingTheConfiguredStorage(): void
	{
		$tableStorage = new TableMetadataStorageConfiguration();
		$tableStorage->setTableName('doctrine_migrations');
		$tableStorage->setVersionColumnName('version');
		$tableStorage->setVersionColumnLength(191);

		$subscriber = new Subscribers\SchemaSubscriber($tableStorage);

		$schema = new Schema();
		$event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

		$subscriber->postGenerateSchema($event);

		self::assertTrue($schema->hasTable('doctrine_migrations'));

		$table = $schema->getTable('doctrine_migrations');

		self::assertTrue($table->hasColumn('version'));
		self::assertTrue($table->hasColumn('executed_at'));
		self::assertTrue($table->hasColumn('execution_time'));

		$primaryKeyColumnNames = array_map(
			static fn (UnqualifiedName $name): string => $name->toString(),
			$table->getPrimaryKeyConstraint()?->getColumnNames() ?? [],
		);

		self::assertSame(['version'], $primaryKeyColumnNames);
	}

	/**
	 * @throws TypesException
	 */
	public function testPostGenerateSchemaDoesNothingWhenTableAlreadyPresent(): void
	{
		$tableStorage = new TableMetadataStorageConfiguration();
		$tableStorage->setTableName('doctrine_migrations');

		$subscriber = new Subscribers\SchemaSubscriber($tableStorage);

		$schema = new Schema();
		$schema->createTable('doctrine_migrations');

		$event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

		$subscriber->postGenerateSchema($event);

		self::assertTrue($schema->hasTable('doctrine_migrations'));
		self::assertFalse($schema->getTable('doctrine_migrations')->hasColumn('version'));
	}

}
