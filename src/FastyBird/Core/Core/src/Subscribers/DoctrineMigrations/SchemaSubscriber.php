<?php declare(strict_types = 1);

namespace FastyBird\Core\Subscribers\DoctrineMigrations;

use Doctrine\Common;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Override;

/**
 * Doctrine Migrations' own bookkeeping table is not ORM-mapped entity metadata, so schema-tool
 * does not know it is supposed to exist and proposes dropping it on every run. Rather than
 * hiding the table from schema introspection -- which would also hide it from
 * TableMetadataStorage's own "does my table exist yet" check and make migrations try to create
 * it on every run -- this adds a table definition to the ORM-generated schema matching exactly
 * what TableMetadataStorage itself creates and maintains, so the comparison finds it unchanged.
 */
final readonly class SchemaSubscriber implements Common\EventSubscriber
{

	public function __construct(private TableMetadataStorageConfiguration $tableStorage)
	{
	}

	#[Override]
	public function getSubscribedEvents(): array
	{
		return [ToolEvents::postGenerateSchema];
	}

	/**
	 * @throws TypesException
	 */
	public function postGenerateSchema(GenerateSchemaEventArgs $event): void
	{
		$schema = $event->getSchema();

		if ($schema->hasTable($this->tableStorage->getTableName())) {
			return;
		}

		$table = $schema->createTable($this->tableStorage->getTableName());

		$table->addColumn(
			$this->tableStorage->getVersionColumnName(),
			'string',
			['notnull' => true, 'length' => $this->tableStorage->getVersionColumnLength()],
		);
		$table->addColumn($this->tableStorage->getExecutedAtColumnName(), 'datetime', ['notnull' => false]);
		$table->addColumn($this->tableStorage->getExecutionTimeColumnName(), 'integer', ['notnull' => false]);

		$table->addPrimaryKeyConstraint(
			PrimaryKeyConstraint::editor()
				->setColumnNames(UnqualifiedName::unquoted($this->tableStorage->getVersionColumnName()))
				->create(),
		);
	}

}
