<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DI;

use Doctrine;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Crud\Create;
use FastyBird\Core\Persistence\Crud\Delete;
use FastyBird\Core\Persistence\Crud\Update;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Persistence\Helpers\StringFunctions;
use FastyBird\Core\Persistence\Mapping;
use FastyBird\Core\Persistence\Subscribers;
use FastyBird\Core\Persistence\Utilities;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Nettrine\Migrations;
use Override;
use function assert;
use function class_exists;

/**
 * Persistence: entity CRUD, timestamping, the entity discriminator, the schema subscriber and
 * the Doctrine helpers
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.persistence and owns the schema of
 * fbCore > persistence, so its services are fbCore.persistence.subscribers.entityDiscriminator,
 * fbCore.persistence.helpers.database, fbCore.persistence.utilities.doctrineDateProvider,
 * fbCore.persistence.entity.*, fbCore.persistence.crud, fbCore.persistence.timestampable.* and
 * fbCore.persistence.migrations.subscriber. The composite reads fbCore > persistence >
 * timestampable itself, for the root Configuration.
 *
 * Its Doctrine subscribers sit on both sides of Security's in definition order, which nettrine's
 * EventPass turns into listener order: the entity discriminator before them, the timestampable
 * and schema subscribers after them. So the composite calls loadConfiguration() for the first
 * group and loadTimestampable() for the second (census section 5.3).
 */
final class PersistenceExtension extends DI\CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'timestampable' => Schema\Expect::structure([
				'lazyAssociation' => Schema\Expect::bool(false),
				'autoMapField' => Schema\Expect::bool(true),
				'dbFieldType' => Schema\Expect::string('datetime_immutable'),
			]),
		]);
	}

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition(
				$this->prefix('subscribers.entityDiscriminator'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Subscribers\EntityDiscriminator::class);
		}

		/**
		 * Helpers
		 */

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition($this->prefix('helpers.database'), new DI\Definitions\ServiceDefinition())
				->setType(Helpers\Database::class);
		}

		$builder->addDefinition(
			$this->prefix('utilities.doctrineDateProvider'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Utilities\DateTimeProvider::class);

		/**
		 * Entity CRUD
		 */

		$builder->addDefinition($this->prefix('entity.mapper'))
			->setType(Mapping\EntityMapper::class)
			->setAutowired(false);

		$builder->addFactoryDefinition($this->prefix('entity.creator'))
			->setImplement(Create\EntityCreatorFactory::class)
			->setAutowired(false)
			->getResultDefinition()
			->setType(Create\EntityCreator::class);

		$builder->addFactoryDefinition($this->prefix('entity.updater'))
			->setImplement(Update\EntityUpdaterFactory::class)
			->setAutowired(false)
			->getResultDefinition()
			->setFactory(Update\EntityUpdater::class);

		$builder->addFactoryDefinition($this->prefix('entity.deleter'))
			->setImplement(Delete\EntityDeleterFactory::class)
			->setAutowired(false)
			->getResultDefinition()
			->setFactory(Delete\EntityDeleter::class);

		$builder->addFactoryDefinition($this->prefix('crud'))
			->setImplement(Crud\CrudFactory::class)
			->getResultDefinition()
			->setType(Crud\EntityCrud::class)
			->setArguments([
				new PhpGenerator\Literal('$entityName'),
				'@' . $this->prefix('entity.mapper'),
				'@' . $this->prefix('entity.creator'),
				'@' . $this->prefix('entity.updater'),
				'@' . $this->prefix('entity.deleter'),
			]);
	}

	/**
	 * The second half of loadConfiguration(), which the composite calls after Security's
	 * definitions and the root Configuration: the timestampable driver and subscriber, and the
	 * schema subscriber
	 */
	public function loadTimestampable(): void
	{
		$builder = $this->getContainerBuilder();

		/**
		 * Timestampable
		 */

		$builder->addDefinition($this->prefix('timestampable.driver'))
			->setType(Mapping\Driver\Timestampable::class);

		$builder->addDefinition($this->prefix('timestampable.subscriber'))
			->setType(Subscribers\TimestampableSubscriber::class);

		/**
		 * Migrations
		 *
		 * Registered only where nettrineMigrations itself is -- isolated per-package unit tests
		 * boot only Core's own internal config, not the application's config/common.neon that
		 * declares that extension, so its Doctrine\Migrations\Metadata\Storage\
		 * TableMetadataStorageConfiguration service the subscriber is autowired against would
		 * otherwise never exist for them to compile against.
		 *
		 * This keys on the extension being registered, not on findByType() against that service:
		 * MigrationsExtension declares it with setFactory() and no setType(), so at this point in
		 * loadConfiguration() the definition carries no resolvable type yet and findByType() always
		 * returns [], which left the subscriber never registered in production (#515).
		 */

		if ($this->compiler->getExtensions(Migrations\DI\MigrationsExtension::class) !== []) {
			$builder->addDefinition($this->prefix('migrations.subscriber'))
				->setType(Subscribers\SchemaSubscriber::class);
		}
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	#[Override]
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * Entity CRUD services, removed when Doctrine ORM is absent
		 *
		 * loadConfiguration() unconditionally registers entity.{mapper,creator,updater,deleter}
		 * and crud: EntityMapper/EntityCreator/EntityUpdater all
		 * take a non-nullable Doctrine\Persistence\ManagerRegistry constructor argument, which
		 * only exists in containers that also register nettrineOrm/nettrineDbal. Several
		 * fbCore-using containers (RabbitMq, RedisDb, RedisDbCache among them) don't, so those
		 * five otherwise-unconditional definitions failed to compile at all. Whether
		 * ManagerRegistry ends up registered can depend on another extension's own
		 * loadConfiguration() -- order-dependent within that phase -- so this can only be
		 * decided reliably here, in beforeCompile(), after every extension's loadConfiguration()
		 * has already run.
		 */

		if ($builder->getByType(Doctrine\Persistence\ManagerRegistry::class) === null) {
			foreach ([
				'entity.mapper',
				'entity.creator',
				'entity.updater',
				'entity.deleter',
				'crud',
			] as $crudServiceName) {
				$builder->removeDefinition($this->prefix($crudServiceName));
			}
		}

		/**
		 * Custom DATE_FORMAT string function
		 */

		// throw:true here unconditionally required Doctrine ORM's EntityManagerInterface in
		// *every* container using fbCore -- including the several packages (CouchDb, RabbitMq,
		// RedisDb, RedisDbCache among them) whose test containers never wire nettrineOrm at all.
		// The Sentry handler lookup in LoggingExtension uses the same "look, act only if found"
		// pattern this now matches; Doctrine's DATE_FORMAT function only needs registering when an
		// EntityManager actually exists to register it on.
		$entityManagerServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class);

		if ($entityManagerServiceName !== null) {
			$entityManagerService = $builder->getDefinition($entityManagerServiceName);

			if ($entityManagerService instanceof DI\Definitions\ServiceDefinition) {
				$entityManagerService->addSetup('?->getConfiguration()->addCustomStringFunction(?, ?)', [
					'@self',
					'DATE_FORMAT',
					StringFunctions\DateFormat::class,
				]);
			}
		}

		/**
		 * Timestampable -- EventManager subscriber wiring
		 */

		// EntityDiscriminator used to be attached here by hand. nettrine/orm 0.10's EventPass
		// finds every service typed Doctrine\Common\EventSubscriber and registers it on its
		// ContainerEventManager itself, so doing it here too would subscribe it twice.

		// KNOWN DEFECT, kept verbatim on purpose (#564, D2 of the Epic #459 census): for the same
		// reason, this second subscription makes TimestampableSubscriber run twice on
		// loadClassMetadata and onFlush. KnownDefectDoubleDoctrineSubscriptionTest pins it.
		// Same fix as the DATE_FORMAT block above and for the same reason: throw:true made this
		// unconditional for every fbCore container, including the several packages that never
		// wire Doctrine ORM at all.
		$emServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class);

		if ($emServiceName !== null) {
			$emService = $builder->getDefinition($emServiceName);
			assert($emService instanceof DI\Definitions\ServiceDefinition);
			$emService->addSetup('?->getEventManager()->addEventSubscriber(?)', [
				'@self',
				$builder->getDefinition($this->prefix('timestampable.subscriber')),
			]);
		}
	}

}
