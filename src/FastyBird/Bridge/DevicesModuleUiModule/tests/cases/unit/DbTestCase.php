<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit;

use Doctrine\DBAL;
use Doctrine\ORM;
use Error;
use FastyBird\Bridge\DevicesModuleUiModule\DI;
use FastyBird\Bridge\DevicesModuleUiModule\Exceptions;
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
use function getmypid;
use function in_array;
use function md5;
use function rtrim;
use function set_time_limit;
use function sprintf;
use function strlen;
use function substr;
use function time;
use function trim;

abstract class DbTestCase extends TestCase
{

	protected const VALID_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb20uZmFzdHliaXJkLmF1dGgtbW9kdWxlIiwianRpIjoiMjQ3MTBlOTYtYTZmYi00Z'
	. 'mM3LWFhMzAtNDcyNzkwNWQzMDRjIiwiaWF0IjoxNTg1NzQyNDAwLCJleHAiOjE1ODU3NDk2MDAsInVzZXIiOiI1ZTc5ZWZiZi1iZDBkLTViN2MtNDZlZi1iZmJkZWZiZmJkMzQiLCJ'
	. 'yb2xlcyI6WyJhZG1pbmlzdHJhdG9yIl19.QH_Oo_uzTXAb3pNnHvXYnnX447nfVq2_ggQ9ZxStu4s';

	protected const EXPIRED_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb20uZmFzdHliaXJkLmF1dGgtbW9kdWxlIiwianRpIjoiMjM5Nzk0NzAtYmVmNi0'
	. '0ZjE2LTlkNzUtNmFhMWZiYWVjNWRiIiwiaWF0IjoxNTc3ODgwMDAwLCJleHAiOjE1Nzc4ODcyMDAsInVzZXIiOiI1ZTc5ZWZiZi1iZDBkLTViN2MtNDZlZi1iZmJkZWZiZmJkMzQiL'
	. 'CJyb2xlcyI6WyJhZG1pbmlzdHJhdG9yIl19.2k8-_-dsPVQeYnb6OunzDp9fJmiQ2JLQo8GwtjgpBXg';

	protected const INVALID_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb20uZmFzdHliaXJkLmF1dGgtbW9kdWxlIiwianRpIjoiODkyNTcxOTQtNWUyMi0'
	. '0NWZjLThhMzEtM2JhNzI5OWM5OTExIiwiaWF0IjoxNTg1NzQyNDAwLCJleHAiOjE1ODU3NDk2MDAsInVzZXIiOiI1ZTc5ZWZiZi1iZDBkLTViN2MtNDZlZi1iZmJkZWZiZmJkMzQiL'
	. 'CJyb2xlcyI6WyJhZG1pbmlzdHJhdG9yIl19.z8hS0hUVtGkiHBeUTdKC_CMqhMIa4uXotPuJJ6Js6S4';

	protected const VALID_TOKEN_USER = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb20uZmFzdHliaXJkLmF1dGgtbW9kdWxlIiwianRpIjoiYTVkMTliNDktNjI'
	. '3Mi00ZGJkLWI3YWItNTJhY2QwMzY3MWE5IiwiaWF0IjoxNTg1NzQyNDAwLCJleHAiOjE1ODU3NDk2MDAsInVzZXIiOiJlZmJmYmRlZi1iZmJkLTY4ZWYtYmZiZC03NzBiNDBlZmJmY'
	. 'mQiLCJyb2xlcyI6WyJ1c2VyIl19.wi_KC5aDT-y6wKbA4wG29KPHUqFyEcNTI-TUvwIH5yc';

	protected Nette\DI\Container|null $container = null;

	protected bool $isDatabaseSetUp = false;

	/** @var array<string> */
	protected array $sqlFiles = [];

	/** @var array<string> */
	protected array $neonFiles = [];

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
		$config->setForceReloadContainer();
		$config->setTempDirectory(FB_TEMP_DIR);

		$config->addStaticParameters(
			['container' => ['class' => 'SystemContainer_' . getmypid() . md5((string) time())]],
		);
		$config->addStaticParameters(['appDir' => $rootDir, 'wwwDir' => $rootDir, 'vendorDir' => $vendorDir]);

		$config->addConfig(__DIR__ . '/../../common.neon');

		foreach ($this->neonFiles as $neonFile) {
			$config->addConfig($neonFile);
		}

		$config->setTimeZone('Europe/Prague');

		DI\DevicesModuleUiModuleExtension::register($config);

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

			/** @var list<ORM\Mapping\ClassMetadata<DoctrineCrud\Entities\IEntity>> $metadata */
			$metadata = $this->getEntityManager()->getMetadataFactory()->getAllMetadata();
			$schemaTool = new ORM\Tools\SchemaTool($this->getEntityManager());

			$schemas = $schemaTool->getCreateSchemaSql($metadata);

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

		$this->container = null; // Fatal error: Cannot redeclare class SystemContainer
		$this->isDatabaseSetUp = false;

		parent::tearDown();
	}

}
