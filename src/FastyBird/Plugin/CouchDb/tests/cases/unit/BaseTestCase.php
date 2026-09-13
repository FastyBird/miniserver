<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Cases\Unit;

use Error;
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Plugin\CouchDb;
use Nette;
use Nette\DI;
use PHPUnit\Framework\TestCase;
use function constant;
use function defined;
use function file_exists;
use function md5;

abstract class BaseTestCase extends TestCase
{

	protected DI\Container $container;

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createContainer();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Error
	 */
	protected function createContainer(string|null $additionalConfig = null): Nette\DI\Container
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

		if ($additionalConfig !== null && file_exists($additionalConfig)) {
			$config->addConfig($additionalConfig);
		}

		$config->setTimeZone('Europe/Prague');

		CouchDb\DI\CouchDbExtension::register($config);

		return $config->createContainer();
	}

	/**
	 * @param class-string $serviceType
	 */
	protected function mockContainerService(
		string $serviceType,
		object $serviceMock,
	): void
	{
		$foundServiceNames = $this->container->findByType($serviceType);

		foreach ($foundServiceNames as $serviceName) {
			$this->replaceContainerService($serviceName, $serviceMock);
		}
	}

	private function replaceContainerService(string $serviceName, object $service): void
	{
		$this->container->removeService($serviceName);
		$this->container->addService($serviceName, $service);
	}

}
