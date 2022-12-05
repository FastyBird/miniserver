<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\fobar;

use DateTimeImmutable;
use Doctrine\DBAL;
use Doctrine\ORM;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\MiniServer\Exceptions;
use Nette;
use Nettrine\ORM as NettrineORM;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_reverse;
use function assert;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function in_array;
use function rtrim;
use function set_time_limit;
use function sprintf;
use function strlen;
use function substr;
use function trim;

abstract class DbTestCase extends TestCase
{

	private Nette\DI\Container|null $container = null;

	private bool $isDatabaseSetUp = false;

	/** @var Array<string> */
	private array $sqlFiles = [];

	/** @var Array<string> */
	private array $neonFiles = [];

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../sql/dummy.data.sql');

		parent::setUp();

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateTimeFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		$this->mockContainerService(
			DateTimeFactory\Factory::class,
			$dateTimeFactory,
		);
	}

	protected function registerDatabaseSchemaFile(string $file): void
	{
		if (!in_array($file, $this->sqlFiles, true)) {
			$this->sqlFiles[] = $file;
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	protected function mockContainerService(
		string $serviceType,
		object $serviceMock,
	): void
	{
		$container = $this->getContainer();
		$foundServiceNames = $container->findByType($serviceType);

		foreach ($foundServiceNames as $serviceName) {
			$this->replaceContainerService($serviceName, $serviceMock);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	protected function getContainer(): Nette\DI\Container
	{
		if ($this->container === null) {
			$this->container = $this->createContainer();
		}

		return $this->container;
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 */
	private function createContainer(): Nette\DI\Container
	{
		$configurator = BootstrapBoot\Bootstrap::boot();

		$configurator->addConfig(__DIR__ . '/../../common.neon');

		$this->container = $configurator->createContainer();

		$this->setupDatabase();

		assert($this->container !== null);

		return $this->container;
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	private function setupDatabase(): void
	{
		if (!$this->isDatabaseSetUp) {
			$db = $this->getDb();

			/** @var list<ORM\Mapping\ClassMetadata> $metadatas */
			$metadatas = $this->getEntityManager()->getMetadataFactory()->getAllMetadata();
			$schemaTool = new ORM\Tools\SchemaTool($this->getEntityManager());

			$schemas = $schemaTool->getCreateSchemaSql($metadatas);

			foreach ($schemas as $sql) {
				try {
					$db->executeStatement($sql);
				} catch (DBAL\Exception) {
					throw new RuntimeException('Database schema could not be created');
				}
			}

			foreach (array_reverse($this->sqlFiles) as $file) {
				$this->loadFromFile($db, $file);
			}

			$this->isDatabaseSetUp = true;
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	protected function getDb(): DBAL\Connection
	{
		return $this->getContainer()->getByType(DBAL\Connection::class);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	protected function getEntityManager(): NettrineORM\EntityManagerDecorator
	{
		return $this->getContainer()->getByType(NettrineORM\EntityManagerDecorator::class);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function loadFromFile(DBAL\Connection $db, string $file): void
	{
		@set_time_limit(0); // intentionally @

		$handle = @fopen($file, 'r'); // intentionally @

		if ($handle === false) {
			throw new Exceptions\InvalidArgument(sprintf('Cannot open file "%s".', $file));
		}

		$delimiter = ';';
		$sql = '';

		while (!feof($handle)) {
			$content = fgets($handle);

			if ($content !== false) {
				$s = rtrim($content);

				if (substr($s, 0, 10) === 'DELIMITER ') {
					$delimiter = substr($s, 10);
				} elseif (substr($s, -strlen($delimiter)) === $delimiter) {
					$sql .= substr($s, 0, -strlen($delimiter));

					try {
						$db->executeQuery($sql);
						$sql = '';
					} catch (DBAL\Exception) {
						// File could not be loaded
					}
				} else {
					$sql .= $s . "\n";
				}
			}
		}

		if (trim($sql) !== '') {
			try {
				$db->executeQuery($sql);
			} catch (DBAL\Exception) {
				// File could not be loaded
			}
		}

		fclose($handle);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	private function replaceContainerService(string $serviceName, object $service): void
	{
		$container = $this->getContainer();

		$container->removeService($serviceName);
		$container->addService($serviceName, $service);
	}

	protected function registerNeonConfigurationFile(string $file): void
	{
		if (!in_array($file, $this->neonFiles, true)) {
			$this->neonFiles[] = $file;
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 */
	protected function tearDown(): void
	{
		$this->getDb()->close();

		$this->container = null; // Fatal error: Cannot redeclare class SystemContainer
		$this->isDatabaseSetUp = false;

		parent::tearDown();
	}

}