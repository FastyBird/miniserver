<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit;

use Doctrine\DBAL;
use Doctrine\ORM;
use Error;
use FastyBird\Connector\Shelly\DI;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use IPub\DoctrineCrud;
use Nette;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_reverse;
use function assert;
use function constant;
use function defined;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function in_array;
use function md5;
use function rtrim;
use function set_time_limit;
use function sprintf;
use function strlen;
use function substr;
use function trim;
use const PHP_EOL;

abstract class DbTestCase extends TestCase
{

	private Nette\DI\Container|null $container = null;

	private bool $isDatabaseSetUp = false;

	/** @var array<string> */
	private array $sqlFiles = [];

	/** @var array<string> */
	private array $neonFiles = [];

	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../sql/dummy.data.sql');

		parent::setUp();
	}

	protected function registerDatabaseSchemaFile(string $file): void
	{
		if (!in_array($file, $this->sqlFiles, true)) {
			$this->sqlFiles[] = $file;
		}
	}

	/**
	 * @param class-string $serviceType
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function getContainer(): Nette\DI\Container
	{
		if ($this->container === null) {
			$this->container = $this->createContainer();
		}

		return $this->container;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function createContainer(): Nette\DI\Container
	{
		$rootDir = __DIR__ . '/../..';
		$vendorDir = defined('FB_VENDOR_DIR') ? constant('FB_VENDOR_DIR') : $rootDir . '/../vendor';

		$config = ApplicationBoot\Bootstrap::boot();
		// Per package, because several caches under the temp directory use a fixed filename
		// and would otherwise collide now that the directory is shared. The translation
		// catalogue is the clearest case: Symfony names it from a hash of the fallback
		// locales alone, which every package configures identically, and with debugMode off
		// the ConfigCache has no resource checkers and treats any existing file as fresh.
		$config->setTempDirectory(FB_TEMP_DIR . '/' . md5($rootDir));

		$config->addStaticParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir, 'vendorDir' => $vendorDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		foreach ($this->neonFiles as $neonFile) {
			$config->addConfig($neonFile);
		}

		$config->setTimeZone('Europe/Prague');

		DI\ShellyExtension::register($config);

		$this->container = $config->createContainer();

		$this->setupDatabase();

		assert($this->container instanceof Nette\DI\Container);

		return $this->container;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function setupDatabase(): void
	{
		if (!$this->isDatabaseSetUp) {
			$db = $this->getDb();

			/** @var list<ORM\Mapping\ClassMetadata<DoctrineCrud\Entities\IEntity>> $metadatas */
			$metadatas = $this->getEntityManager()->getMetadataFactory()->getAllMetadata();
			$schemaTool = new ORM\Tools\SchemaTool($this->getEntityManager());

			$schemas = $schemaTool->getCreateSchemaSql($metadatas);

			foreach ($schemas as $sql) {
				try {
					$db->executeStatement($sql);
				} catch (DBAL\Exception $ex) {
					// Carry the driver's message and the failing DDL. Without them this reads
					// as an environment problem when it is almost always a mapping problem.
					throw new RuntimeException(
						sprintf(
							'Database schema could not be created: %s%sFailing statement: %s',
							$ex->getMessage(),
							PHP_EOL,
							$sql,
						),
						0,
						$ex,
					);
				}
			}

			foreach (array_reverse($this->sqlFiles) as $file) {
				$this->loadFromFile($db, $file);
			}

			$this->isDatabaseSetUp = true;
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function getDb(): DBAL\Connection
	{
		return $this->getContainer()->getByType(DBAL\Connection::class);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function getEntityManager(): ORM\EntityManagerInterface
	{
		return $this->getContainer()->getByType(ORM\EntityManagerInterface::class);
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

		try {
			while (!feof($handle)) {
				$content = fgets($handle);

				if ($content !== false) {
					$s = rtrim($content);

					if (substr($s, 0, 10) === 'DELIMITER ') {
						$delimiter = substr($s, 10);
					} elseif (substr($s, -strlen($delimiter)) === $delimiter) {
						$sql .= substr($s, 0, -strlen($delimiter));

						$this->executeFixtureStatement($db, $file, $sql);

						$sql = '';
					} else {
						$sql .= $s . "\n";
					}
				}
			}

			if (trim($sql) !== '') {
				$this->executeFixtureStatement($db, $file, $sql);
			}
		} finally {
			fclose($handle);
		}
	}

	/**
	 * A fixture that cannot load is a broken test, not a warning. This used to be a try with
	 * an empty catch, and the statement buffer was reset inside that try -- so a failing
	 * statement both vanished and took the rest of the file with it, the next line being
	 * concatenated onto the broken SQL.
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function executeFixtureStatement(DBAL\Connection $db, string $file, string $sql): void
	{
		try {
			$db->executeStatement($sql);
		} catch (DBAL\Exception $ex) {
			$statement = trim($sql);

			// Show both ends. A fixture INSERT names every column before it reaches a single
			// value, so a plain head-truncation would report the column list and never the
			// row that actually failed.
			$excerpt = strlen($statement) > 520
				? substr($statement, 0, 260) . ' [...] ' . substr($statement, -260)
				: $statement;

			throw new Exceptions\InvalidArgument(
				sprintf(
					'Fixture "%s" could not be loaded: %s%sFailing statement: %s',
					$file,
					$ex->getMessage(),
					PHP_EOL,
					$excerpt,
				),
				0,
				$ex,
			);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Error
	 */
	protected function tearDown(): void
	{
		$this->getDb()->close();

		$this->container = null; // Release the container; the next test builds its own instance
		$this->isDatabaseSetUp = false;

		parent::tearDown();
	}

}
